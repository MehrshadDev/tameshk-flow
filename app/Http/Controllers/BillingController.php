<?php

namespace App\Http\Controllers;

use App\Models\DiscountCode;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Subscriptions\Exceptions\PaymentException;
use App\Subscriptions\Gateways\ZibalGateway;
use App\Subscriptions\SubscriptionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class BillingController extends Controller
{
    public function __construct(
        private readonly SubscriptionManager $subscriptions,
        private readonly ZibalGateway $gateway,
    ) {}

    public function index(): View
    {
        abort_unless($this->subscriptions->isSaasEnabled(), 404);

        return view('pages.billing.index');
    }

    public function checkout(Request $request): RedirectResponse
    {
        abort_unless($this->subscriptions->isSaasEnabled(), 404);

        $user = $request->user();
        $rateKey = 'billing-checkout:' . $user->id;

        if (RateLimiter::tooManyAttempts($rateKey, 8)) {
            return redirect()
                ->route('app.billing.index')
                ->with('payment_result', [
                    'status' => 'error',
                    'message' => 'تعداد درخواست‌های پرداخت شما بیش از حد مجاز است. لطفاً یک دقیقه دیگر تلاش کنید.',
                ]);
        }

        RateLimiter::hit($rateKey, 60);

        $validated = $request->validate([
            'plan' => ['required', 'string', 'max:50'],
            'billing_cycle' => ['required', 'string', Rule::in(['monthly', 'yearly'])],
            'discount_code' => ['nullable', 'string', 'max:40'],
        ]);

        $plan = Plan::query()
            ->where('slug', $validated['plan'])
            ->where('is_active', true)
            ->first();

        if (! $plan || $plan->isFree()) {
            return redirect()
                ->route('app.billing.index')
                ->with('payment_result', [
                    'status' => 'error',
                    'message' => 'پلن انتخاب‌شده برای خرید معتبر نیست.',
                ]);
        }

        $billingCycle = $validated['billing_cycle'];
        $check = $this->subscriptions->canPurchase($user, $plan, $billingCycle);

        if (! $check['allowed']) {
            return redirect()
                ->route('app.billing.index')
                ->with('payment_result', [
                    'status' => 'error',
                    'message' => $check['reason'],
                ]);
        }

        $rawDiscountCode = trim((string) ($validated['discount_code'] ?? ''));
        $discount = null;

        if ($rawDiscountCode !== '') {
            $discountCheck = $this->subscriptions->validateDiscountCode(
                user: $user,
                targetPlan: $plan,
                billingCycle: $billingCycle,
                rawCode: $rawDiscountCode,
            );

            if (! $discountCheck['valid']) {
                return redirect()
                    ->route('app.billing.index')
                    ->with('payment_result', [
                        'status' => 'error',
                        'message' => $discountCheck['message'],
                    ]);
            }

            $discount = $discountCheck['discount'];
            $quote = $discountCheck['quote'];
        } else {
            $quote = $this->subscriptions->calculateQuote($user, $plan, $billingCycle);
        }

        $amount = (int) $quote['payable_amount'];
        $discountAmount = (int) $quote['discount_amount'];

        if ($amount < 0 || ($amount === 0 && $discount === null)) {
            return redirect()
                ->route('app.billing.index')
                ->with('payment_result', [
                    'status' => 'error',
                    'message' => 'مبلغ پلن انتخاب‌شده نامعتبر است.',
                ]);
        }

        Payment::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->delete();

        if ($amount === 0 && $discount !== null) {
            return $this->completeFreeDiscountCheckout($request, $plan, $billingCycle, $rawDiscountCode);
        }

        $payment = Payment::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'discount_code_id' => $discount?->id,
            'billing_cycle' => $billingCycle,
            'amount' => $amount,
            'discount_amount' => $discountAmount,
            'gateway' => 'zibal',
            'status' => 'pending',
            'ip_address' => $request->ip(),
        ]);

        $cycleLabel = $billingCycle === 'yearly' ? 'سالانه' : 'ماهانه';
        $description = "اشتراک $plan->name ($cycleLabel) - " . config('app.name');

        try {
            $result = $this->gateway->request(
                amountInTomans: $amount,
                description: $description,
                callbackUrl: route('billing.callback'),
                metadata: [
                    'order_id' => $payment->public_id,
                ]
            );

            $payment->update([
                'authority' => $result['authority'],
                'raw_response' => $result['raw'],
            ]);

            return redirect()->away($result['redirect_url']);
        } catch (PaymentException $e) {
            $payment->delete();

            return redirect()
                ->route('app.billing.index')
                ->with('payment_result', [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ]);
        } catch (Throwable $e) {
            report($e);
            $payment->delete();

            return redirect()
                ->route('app.billing.index')
                ->with('payment_result', [
                    'status' => 'error',
                    'message' => 'خطایی در اتصال به درگاه پرداخت رخ داد. لطفاً دوباره تلاش کنید.',
                ]);
        }
    }

    public function callback(Request $request): RedirectResponse
    {
        abort_unless($this->subscriptions->isSaasEnabled(), 404);

        $trackId = trim((string) $request->query('trackId', ''));
        $success = trim((string) $request->query('success', ''));
        $status = (int) $request->query('status', 0);
        $orderId = trim((string) $request->query('orderId', ''));

        if ($trackId === '' || strlen($trackId) > 32 || ! ctype_digit($trackId)) {
            return $this->redirectAfterCallback('error', 'شناسه پرداخت بازگشتی از درگاه معتبر نیست.');
        }

        try {
            return DB::transaction(function () use ($trackId, $success, $status, $orderId, $request) {
                $payment = Payment::query()
                    ->with(['user', 'plan'])
                    ->where('authority', $trackId)
                    ->lockForUpdate()
                    ->first();

                if (! $payment || ! $payment->user || ! $payment->plan) {
                    return $this->redirectAfterCallback('error', 'تراکنش مورد نظر در سیستم یافت نشد.');
                }

                if ($orderId !== '' && ! hash_equals((string) $payment->public_id, $orderId)) {
                    return $this->redirectAfterCallback('error', 'اطلاعات سفارش بازگشتی از درگاه معتبر نیست.');
                }

                if ($request->user() && $payment->user_id !== $request->user()->id) {
                    abort(403);
                }

                if ($payment->isPaid()) {
                    return $this->redirectAfterCallback(
                        'success',
                        "این پرداخت قبلاً تایید شده و اشتراک «{$payment->plan->name}» شما فعال است.",
                        $payment->ref_id
                    );
                }

                if (! $payment->isPending()) {
                    return $this->redirectAfterCallback('error', 'این تراکنش قبلاً منقضی یا لغو شده است.');
                }

                if ($success !== '1') {
                    $payment->delete();

                    return $this->redirectAfterCallback('error', $this->gateway->translateCallbackStatus($status));
                }

                try {
                    $verified = $this->gateway->verify($payment->amount, $trackId, (string) $payment->public_id);
                } catch (PaymentException $e) {
                    $payment->update(['status' => 'failed']);

                    return $this->redirectAfterCallback('error', $e->getMessage());
                }

                $refId = $verified['ref_id'];

                if ($refId !== '' && Payment::query()->where('ref_id', $refId)->where('status', 'paid')->whereKeyNot($payment->id)->exists()) {
                    $payment->update(['status' => 'failed']);

                    return $this->redirectAfterCallback('error', 'شماره پیگیری این تراکنش تکراری است.');
                }

                if ($payment->discount_code_id) {
                    $lockedDiscount = DiscountCode::query()
                        ->whereKey($payment->discount_code_id)
                        ->lockForUpdate()
                        ->first();

                    $lockedDiscount?->increment('used_count');
                }

                $subscription = $this->subscriptions->activateSubscription(
                    $payment->user,
                    $payment->plan,
                    $payment->billing_cycle
                );

                $payment->update([
                    'subscription_id' => $subscription->id,
                    'status' => 'paid',
                    'ref_id' => $refId !== '' ? $refId : null,
                    'card_pan' => $verified['card_pan'] ?: null,
                    'card_hash' => $verified['card_hash'] ?: null,
                    'paid_at' => now(),
                    'raw_response' => $verified['raw'],
                ]);

                if (auth()->check()) {
                    return $this->redirectWithCelebration($payment->plan, $subscription, $payment->billing_cycle, $refId);
                }

                return $this->redirectAfterCallback(
                    'success',
                    "پرداخت با موفقیت انجام شد و اشتراک «{$payment->plan->name}» برای حساب شما فعال گردید!",
                    $refId
                );
            });
        } catch (Throwable $e) {
            report($e);

            return $this->redirectAfterCallback('error', 'خطایی در بررسی نهایی تراکنش رخ داد. لطفاً با پشتیبانی تماس بگیرید.');
        }
    }

    private function completeFreeDiscountCheckout(
        Request $request,
        Plan $plan,
        string $billingCycle,
        string $rawDiscountCode
    ): RedirectResponse {
        try {
            return DB::transaction(function () use ($request, $plan, $billingCycle, $rawDiscountCode) {
                $user = User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();

                $discountCheck = $this->subscriptions->validateDiscountCode(
                    user: $user,
                    targetPlan: $plan,
                    billingCycle: $billingCycle,
                    rawCode: $rawDiscountCode,
                    lockForUpdate: true,
                );

                if (! $discountCheck['valid'] || ! $discountCheck['discount']) {
                    return redirect()
                        ->route('app.billing.index')
                        ->with('payment_result', [
                            'status' => 'error',
                            'message' => $discountCheck['message'] ?? 'کد تخفیف معتبر نیست.',
                        ]);
                }

                $lockedDiscount = $discountCheck['discount'];
                $quote = $discountCheck['quote'];

                if ((int) $quote['payable_amount'] !== 0) {
                    return redirect()
                        ->route('app.billing.index')
                        ->with('payment_result', [
                            'status' => 'error',
                            'message' => 'مبلغ سفارش تغییر کرده است. لطفاً مجدداً تلاش کنید.',
                        ]);
                }

                $lockedDiscount->increment('used_count');

                $subscription = $this->subscriptions->activateSubscription($user, $plan, $billingCycle);
                $refId = 'GIFT-' . strtoupper(Str::random(8));

                Payment::create([
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'subscription_id' => $subscription->id,
                    'discount_code_id' => $lockedDiscount->id,
                    'billing_cycle' => $billingCycle,
                    'amount' => 0,
                    'discount_amount' => (int) $quote['discount_amount'],
                    'gateway' => 'discount',
                    'ref_id' => $refId,
                    'status' => 'paid',
                    'paid_at' => now(),
                    'ip_address' => $request->ip(),
                ]);

                return $this->redirectWithCelebration($plan, $subscription, $billingCycle, $refId);
            });
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('app.billing.index')
                ->with('payment_result', [
                    'status' => 'error',
                    'message' => 'خطایی در فعال‌سازی اشتراک رخ داد. لطفاً دوباره تلاش کنید.',
                ]);
        }
    }

    private function redirectWithCelebration(
        Plan $plan,
        Subscription $subscription,
        string $billingCycle,
        ?string $refId
    ): RedirectResponse {
        return redirect()
            ->route('app.workflows.index')
            ->with('subscription_celebration', [
                'plan_name' => $plan->name,
                'plan_slug' => $plan->slug,
                'billing_cycle' => $billingCycle === 'yearly' ? 'سالانه' : 'ماهانه',
                'max_runs' => $plan->max_monthly_runs !== null
                    ? number_format($plan->max_monthly_runs)
                    : 'نامحدود',
                'max_active' => $plan->max_active_workflows !== null
                    ? $plan->max_active_workflows . ' ورک‌فلو'
                    : 'نامحدود',
                'remaining_days' => $subscription->remainingDays(),
                'ref_id' => $refId,
            ]);
    }

    private function redirectAfterCallback(string $status, string $message, ?string $refId = null): RedirectResponse
    {
        $targetRoute = auth()->check() ? 'app.billing.index' : 'login';

        return redirect()
            ->route($targetRoute)
            ->with('payment_result', [
                'status' => $status,
                'message' => $message,
                'ref_id' => $refId,
            ]);
    }
}
