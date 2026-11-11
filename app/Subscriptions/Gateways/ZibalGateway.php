<?php

namespace App\Subscriptions\Gateways;

use App\Subscriptions\Exceptions\PaymentException;
use Illuminate\Support\Facades\Http;
use Throwable;

final class ZibalGateway
{
    private const BASE_URL = 'https://gateway.zibal.ir';

    private bool $sandbox;
    private string $merchantId;
    private string $currency;

    public function __construct()
    {
        $this->sandbox = (bool) config('saas.zibal.sandbox', true);
        $this->merchantId = trim((string) config('saas.zibal.merchant_id', ''));
        $this->currency = strtoupper(trim((string) config('saas.zibal.currency', 'IRT')));

        if (! in_array($this->currency, ['IRT', 'IRR'], true)) {
            $this->currency = 'IRT';
        }
    }

    public function request(
        int $amountInTomans,
        string $description,
        string $callbackUrl,
        array $metadata = []
    ): array {
        $payload = [
            'merchant' => $this->effectiveMerchant(),
            'amount' => $this->normalizeAmount($amountInTomans),
            'callbackUrl' => $callbackUrl,
            'description' => $description,
        ];

        if (! empty($metadata['order_id'])) {
            $payload['orderId'] = (string) $metadata['order_id'];
        }

        if (! empty($metadata['mobile'])) {
            $payload['mobile'] = (string) $metadata['mobile'];
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(20)
                ->post(self::BASE_URL . '/v1/request', $payload);
        } catch (Throwable $e) {
            $detail = config('app.debug') ? ' (' . $e->getMessage() . ')' : '';

            throw PaymentException::because('امکان برقراری ارتباط با درگاه پرداخت زیبال وجود ندارد. لطفاً اتصال اینترنت را بررسی کنید.' . $detail);
        }

        $json = (array) $response->json();
        $result = (int) data_get($json, 'result', 0);
        $trackId = trim((string) data_get($json, 'trackId', ''));

        if ($response->failed() || $result !== 100 || $trackId === '' || ! ctype_digit($trackId)) {
            throw PaymentException::because($this->translateResultError($result));
        }

        return [
            'authority' => $trackId,
            'redirect_url' => self::BASE_URL . '/start/' . $trackId,
            'raw' => $json,
        ];
    }

    public function verify(int $amountInTomans, string $authority, string $expectedOrderId): array
    {
        $trackId = trim($authority);

        $payload = [
            'merchant' => $this->effectiveMerchant(),
            'trackId' => (int) $trackId,
        ];

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(25)
                ->post(self::BASE_URL . '/v1/verify', $payload);
        } catch (Throwable $e) {
            $detail = config('app.debug') ? ' (' . $e->getMessage() . ')' : '';

            throw PaymentException::because('خطا در استعلام وضعیت پرداخت از زیبال. در صورت کسر وجه، مبلغ ظرف ۷۲ ساعت به حساب شما بازمی‌گردد.' . $detail);
        }

        $json = (array) $response->json();
        $result = (int) data_get($json, 'result', 0);
        $status = (int) data_get($json, 'status', 0);

        if ($response->failed() || ! in_array($result, [100, 201], true)) {
            throw PaymentException::because($this->translateResultError($result, $status));
        }

        if ($result === 100 && $status !== 1) {
            throw PaymentException::because($this->translateCallbackStatus($status));
        }

        $expectedRials = $this->normalizeAmount($amountInTomans);
        $verifiedAmount = (int) data_get($json, 'amount', 0);

        if ($verifiedAmount > 0 && $verifiedAmount !== $expectedRials) {
            throw PaymentException::because('مبلغ پرداخت‌شده با مبلغ فاکتور مطابقت ندارد.');
        }

        $returnedOrderId = trim((string) data_get($json, 'orderId', ''));

        if ($expectedOrderId !== '' && $returnedOrderId !== '' && ! hash_equals($expectedOrderId, $returnedOrderId)) {
            throw PaymentException::because('شناسه سفارش بازگشتی از درگاه با سفارش ثبت‌شده همخوانی ندارد.');
        }

        $refNumber = trim((string) data_get($json, 'refNumber', ''));
        $cardPan = trim((string) data_get($json, 'cardNumber', ''));

        return [
            'code' => $result,
            'already_verified' => $result === 201,
            'ref_id' => $refNumber !== '' ? $refNumber : $trackId,
            'card_pan' => $cardPan,
            'card_hash' => $cardPan !== '' ? hash('sha256', $cardPan) : '',
            'raw' => $json,
        ];
    }

    public function translateCallbackStatus(int $status): string
    {
        return match ($status) {
            -1 => 'تراکنش در انتظار پرداخت است.',
            -2 => 'خطای داخلی در درگاه پرداخت رخ داده است.',
            3 => 'فرآیند پرداخت توسط شما لغو شد.',
            4 => 'شماره کارت واردشده معتبر نیست.',
            5 => 'موجودی حساب برای انجام تراکنش کافی نیست.',
            6 => 'رمز واردشده اشتباه است.',
            7 => 'تعداد درخواست‌ها بیش از حد مجاز است.',
            8 => 'تعداد پرداخت اینترنتی روزانه بیش از حد مجاز است.',
            9 => 'مبلغ پرداخت اینترنتی روزانه بیش از حد مجاز است.',
            10 => 'صادرکننده کارت نامعتبر است.',
            11 => 'خطای سوییچ بانکی رخ داده است.',
            12 => 'کارت در دسترس نیست.',
            default => 'فرآیند پرداخت ناموفق بود یا توسط کاربر لغو شد.',
        };
    }

    private function effectiveMerchant(): string
    {
        if ($this->sandbox) {
            return 'zibal';
        }

        if ($this->merchantId === '' || strtolower($this->merchantId) === 'zibal' || strlen($this->merchantId) < 10) {
            throw PaymentException::because('کد درگاه (Merchant) زیبال به درستی تنظیم نشده است.');
        }

        return $this->merchantId;
    }

    private function normalizeAmount(int $amountInTomans): int
    {
        return $this->currency === 'IRT'
            ? $amountInTomans * 10
            : $amountInTomans;
    }

    private function translateResultError(int $result, int $status = 0): string
    {
        if ($result === 202 && $status !== 0) {
            return $this->translateCallbackStatus($status);
        }

        return match ($result) {
            102 => 'کد درگاه (Merchant) در زیبال یافت نشد.',
            103 => 'درگاه پرداخت زیبال غیرفعال است.',
            104 => 'کد درگاه (Merchant) نامعتبر است.',
            105 => 'مبلغ تراکنش باید بیشتر از ۱٬۰۰۰ ریال باشد.',
            106 => 'آدرس بازگشت (Callback URL) معتبر نیست.',
            113 => 'مبلغ تراکنش بیشتر از سقف مجاز است.',
            201 => 'این تراکنش قبلاً تایید شده است.',
            202 => 'سفارش پرداخت نشده یا ناموفق بوده است.',
            203 => 'شناسه پیگیری (trackId) درگاه نامعتبر است.',
            default => 'خطا در عملیات درگاه پرداخت زیبال (کد: ' . $result . ').',
        };
    }
}
