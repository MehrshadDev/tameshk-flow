<?php

use App\Models\Plan;
use App\Subscriptions\SubscriptionManager;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $billingCycle = 'monthly';

    #[Locked]
    public ?string $checkoutPlanSlug = null;

    #[Locked]
    public string $checkoutCycle = 'monthly';

    public string $discountInput = '';

    #[Locked]
    public ?string $appliedDiscountCode = null;

    public ?string $discountMessage = null;

    public bool $discountSuccess = false;

    public function mount(SubscriptionManager $subscriptions): void
    {
        $summary = $subscriptions->usageSummary(auth()->user());
        $sub = $summary['subscription'];
        $plan = $summary['plan'];

        if (
            ($sub && $sub->billing_cycle === 'yearly')
            || ($plan->slug === 'business' && ! $summary['can_renew_current'])
        ) {
            $this->billingCycle = 'yearly';
        }
    }

    public function setBillingCycle(string $cycle): void
    {
        if (! in_array($cycle, ['monthly', 'yearly'], true)) {
            return;
        }

        if ($cycle === 'monthly' && ! $this->canSelectMonthly) {
            return;
        }

        $this->billingCycle = $cycle;
    }

    public function openCheckout(string $planSlug, string $cycle, SubscriptionManager $subscriptions): void
    {
        if (! in_array($cycle, ['monthly', 'yearly'], true)) {
            return;
        }

        $plan = Plan::query()
            ->where('slug', $planSlug)
            ->where('is_active', true)
            ->first();

        if (! $plan || $plan->isFree()) {
            $this->dispatch('notify', message: 'پلن انتخاب‌شده معتبر نیست.', type: 'error');

            return;
        }

        $check = $subscriptions->canPurchase(auth()->user(), $plan, $cycle);

        if (! $check['allowed']) {
            $this->dispatch('notify', message: $check['reason'], type: 'error');

            return;
        }

        $this->checkoutPlanSlug = $plan->slug;
        $this->checkoutCycle = $cycle;
        $this->discountInput = '';
        $this->appliedDiscountCode = null;
        $this->discountMessage = null;
        $this->discountSuccess = false;

        $this->dispatch('open-modal', name: 'billing-checkout-modal');
    }

    public function setCheckoutCycle(string $cycle, SubscriptionManager $subscriptions): void
    {
        if (! in_array($cycle, ['monthly', 'yearly'], true) || ! $this->checkoutPlan) {
            return;
        }

        $check = $subscriptions->canPurchase(auth()->user(), $this->checkoutPlan, $cycle);

        if (! $check['allowed']) {
            $this->dispatch('notify', message: $check['reason'], type: 'error');

            return;
        }

        $this->checkoutCycle = $cycle;

        if ($this->appliedDiscountCode !== null) {
            $this->discountInput = $this->appliedDiscountCode;
            $this->applyDiscountCode($subscriptions);
        }
    }

    public function applyDiscountCode(SubscriptionManager $subscriptions): void
    {
        $plan = $this->checkoutPlan;

        if (! $plan) {
            return;
        }

        $user = auth()->user();
        $rateKey = 'discount-check:' . $user->id;

        if (RateLimiter::tooManyAttempts($rateKey, 15)) {
            $this->discountSuccess = false;
            $this->appliedDiscountCode = null;
            $this->discountMessage = 'تعداد تلاش‌های بررسی کد تخفیف بیش از حد مجاز است. کمی صبر کنید.';

            return;
        }

        RateLimiter::hit($rateKey, 60);

        $this->discountInput = strtoupper(trim($this->discountInput));

        if ($this->discountInput === '') {
            $this->discountSuccess = false;
            $this->appliedDiscountCode = null;
            $this->discountMessage = 'لطفاً ابتدا کد تخفیف را وارد کنید.';

            return;
        }

        $result = $subscriptions->validateDiscountCode(
            user: $user,
            targetPlan: $plan,
            billingCycle: $this->checkoutCycle,
            rawCode: $this->discountInput,
        );

        if (! $result['valid'] || ! $result['discount']) {
            $this->discountSuccess = false;
            $this->appliedDiscountCode = null;
            $this->discountMessage = $result['message'];

            return;
        }

        $this->appliedDiscountCode = $result['discount']->code;
        $this->discountSuccess = true;
        $this->discountMessage = $result['discount']->title
            ? "کد تخفیف «{$result['discount']->title}» اعمال شد."
            : 'کد تخفیف با موفقیت اعمال شد.';
    }

    public function removeDiscountCode(): void
    {
        $this->discountInput = '';
        $this->appliedDiscountCode = null;
        $this->discountMessage = null;
        $this->discountSuccess = false;
    }

    #[Computed]
    public function checkoutPlan(): ?Plan
    {
        if (! $this->checkoutPlanSlug) {
            return null;
        }

        return Plan::query()
            ->where('slug', $this->checkoutPlanSlug)
            ->where('is_active', true)
            ->first();
    }

    #[Computed]
    public function checkoutQuote(): ?array
    {
        $plan = $this->checkoutPlan;

        if (! $plan) {
            return null;
        }

        $subscriptions = app(SubscriptionManager::class);
        $user = auth()->user();

        if ($this->appliedDiscountCode !== null) {
            $validation = $subscriptions->validateDiscountCode(
                user: $user,
                targetPlan: $plan,
                billingCycle: $this->checkoutCycle,
                rawCode: $this->appliedDiscountCode,
            );

            if ($validation['valid']) {
                return $validation['quote'];
            }
        }

        return $subscriptions->calculateQuote($user, $plan, $this->checkoutCycle);
    }

    #[Computed]
    public function canSwitchCheckoutCycle(): bool
    {
        $plan = $this->checkoutPlan;

        if (! $plan) {
            return false;
        }

        $subscriptions = app(SubscriptionManager::class);
        $user = auth()->user();

        return $subscriptions->canPurchase($user, $plan, 'monthly')['allowed']
            && $subscriptions->canPurchase($user, $plan, 'yearly')['allowed'];
    }

    #[Computed]
    public function summary(): array
    {
        return app(SubscriptionManager::class)->usageSummary(auth()->user());
    }

    #[Computed]
    public function canSelectMonthly(): bool
    {
        $summary = $this->summary;
        $sub = $summary['subscription'];

        if (! $sub) {
            return true;
        }

        if ($summary['plan']->slug === 'business' && ! $sub->isWithinRenewalWindow()) {
            return false;
        }

        if ($sub->billing_cycle === 'yearly') {
            return $sub->isWithinRenewalWindow();
        }

        return true;
    }

    #[Computed]
    public function visiblePlans()
    {
        return app(SubscriptionManager::class)->visiblePlansFor(auth()->user());
    }

    #[Computed]
    public function payments()
    {
        return auth()->user()
            ->payments()
            ->where('status', 'paid')
            ->with(['plan:id,name,slug', 'discountCode:id,code,title'])
            ->latest('id')
            ->paginate(8);
    }
};
?>

<div class="mx-auto max-w-6xl space-y-6 p-4 sm:p-6">
    @php
        $paymentResult = session('payment_result');
        $summary = $this->summary;
        $effectiveCycle = (! $this->canSelectMonthly) ? 'yearly' : $billingCycle;
    @endphp

    @if (is_array($paymentResult) && ! empty($paymentResult['message']))
        <div x-data="{ show: true }"
             x-show="show"
             x-transition.opacity
            @class([
                'flex items-center justify-between gap-3 rounded-2xl border p-4 shadow-2xs',
                'border-emerald-500/30 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' => ($paymentResult['status'] ?? '') === 'success',
                'border-primary/30 bg-primary/10 text-primary' => ($paymentResult['status'] ?? '') !== 'success',
            ])>
            <div class="flex min-w-0 flex-1 items-center gap-3">
                <div @class([
                    'flex size-10 shrink-0 items-center justify-center rounded-xl',
                    'bg-emerald-500/20 text-emerald-500' => ($paymentResult['status'] ?? '') === 'success',
                    'bg-primary/20 text-primary' => ($paymentResult['status'] ?? '') !== 'success',
                ])>
                    <x-ui.icon :name="($paymentResult['status'] ?? '') === 'success' ? 'check' : 'alert-triangle'" class="size-5"/>
                </div>

                <div class="min-w-0 flex-1 space-y-1 text-xs">
                    <p class="font-bold leading-6">{{ $paymentResult['message'] }}</p>
                    @if (! empty($paymentResult['ref_id']))
                        <p class="font-mono text-[11px] opacity-90">
                            کد رهگیری:
                            <span class="font-bold select-all" dir="ltr">{{ $paymentResult['ref_id'] }}</span>
                        </p>
                    @endif
                </div>
            </div>

            <button type="button"
                    @click="show = false"
                    aria-label="بستن"
                    class="flex size-7 shrink-0 items-center justify-center rounded-lg opacity-70 transition hover:opacity-100 cursor-pointer">
                <x-ui.icon name="x" class="size-4"/>
            </button>
        </div>
    @endif

    <x-billing.overview :summary="$summary" />

    <x-billing.plans
        :plans="$this->visiblePlans"
        :current-plan="$summary['plan']"
        :active-subscription="$summary['subscription']"
        :can-renew-current="$summary['can_renew_current']"
        :can-select-monthly="$this->canSelectMonthly"
        :billing-cycle="$effectiveCycle"
    />

    <x-billing.payments :payments="$this->payments" />

    <x-billing.checkout-modal
        :plan="$this->checkoutPlan"
        :cycle="$checkoutCycle"
        :quote="$this->checkoutQuote"
        :can-switch-cycle="$this->canSwitchCheckoutCycle"
        :applied-code="$appliedDiscountCode"
        :discount-message="$discountMessage"
        :discount-success="$discountSuccess"
    />
</div>
