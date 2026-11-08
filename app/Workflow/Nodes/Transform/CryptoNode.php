<?php

namespace App\Workflow\Nodes\Transform;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\HandleType;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;
use Illuminate\Support\Str;

class CryptoNode implements NodeContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'transform.crypto',
            name: 'Crypto & Random',
            category: NodeCategory::Transform,
            description: 'تولید عدد تصادفی در بازه دلخواه، کد تایید (OTP)، UUID، توکن امن، هش (SHA256/MD5)، امضای HMAC و تبدیل Base64.',
            icon: 'lock',
            inputs: [HandleType::Main->value],
            outputs: [HandleType::Main->value],
            configSchema: [
                'operation' => [
                    'type'     => 'select',
                    'label'    => 'عملیات',
                    'options'  => [
                        ['value' => 'random_int', 'label' => 'تولید عدد تصادفی در بازه (Random Number in Range)'],
                        ['value' => 'otp', 'label' => 'تولید کد عددی تصادفی (OTP Code)'],
                        ['value' => 'uuid', 'label' => 'تولید شناسه یکتا یا رشته تصادفی (UUID / Token)'],
                        ['value' => 'hash', 'label' => 'تولید هش (MD5 / SHA-256 / SHA-512)'],
                        ['value' => 'hmac', 'label' => 'امضای امنیتی با کلید (HMAC)'],
                        ['value' => 'base64_encode', 'label' => 'کدگذاری Base64 (Encode)'],
                        ['value' => 'base64_decode', 'label' => 'رمزگشایی Base64 (Decode)'],
                    ],
                    'default'  => 'otp',
                    'required' => true,
                ],
                'min_value' => [
                    'type'       => 'number',
                    'label'      => 'حداقل مقدار (Min)',
                    'default'    => 1,
                    'min'        => -1000000000,
                    'max'        => 1000000000,
                    'integer'    => true,
                    'required'   => true,
                    'depends_on' => ['operation' => 'random_int'],
                ],
                'max_value' => [
                    'type'       => 'number',
                    'label'      => 'حداکثر مقدار (Max)',
                    'default'    => 100,
                    'min'        => -1000000000,
                    'max'        => 1000000000,
                    'integer'    => true,
                    'required'   => true,
                    'depends_on' => ['operation' => 'random_int'],
                ],
                'otp_digits' => [
                    'type'       => 'number',
                    'label'      => 'تعداد ارقام کد (۱ تا ۱۰ رقم)',
                    'default'    => 6,
                    'min'        => 1,
                    'max'        => 10,
                    'integer'    => true,
                    'required'   => true,
                    'depends_on' => ['operation' => 'otp'],
                ],
                'token_type' => [
                    'type'       => 'select',
                    'label'      => 'نوع شناسه یا توکن',
                    'options'    => [
                        ['value' => 'uuid', 'label' => 'UUID v4 استاندارد'],
                        ['value' => 'ulid', 'label' => 'ULID قابل مرتب‌سازی زمانی'],
                        ['value' => 'hex', 'label' => 'توکن هگزادسیمال (Hex)'],
                        ['value' => 'alnum', 'label' => 'رشته تصادفی حروف و اعداد (Alphanumeric)'],
                        ['value' => 'hex32', 'label' => 'توکن هگزادسیمال ۳۲ کاراکتری'],
                        ['value' => 'alnum32', 'label' => 'رشته تصادفی ۳۲ کاراکتری'],
                    ],
                    'default'    => 'uuid',
                    'depends_on' => ['operation' => 'uuid'],
                ],
                'token_length' => [
                    'type'       => 'number',
                    'label'      => 'طول رشته (تعداد کاراکتر)',
                    'default'    => 16,
                    'min'        => 1,
                    'max'        => 256,
                    'integer'    => true,
                    'required'   => true,
                    'depends_on' => ['operation' => 'uuid', 'token_type' => ['hex', 'alnum']],
                ],
                'value' => [
                    'type'        => 'textarea',
                    'label'       => 'متن ورودی',
                    'placeholder' => 'مثلاً {{ input.text }}',
                    'required'    => true,
                    'depends_on'  => ['operation' => ['hash', 'hmac', 'base64_encode', 'base64_decode']],
                ],
                'algorithm' => [
                    'type'       => 'select',
                    'label'      => 'الگوریتم هش',
                    'options'    => [
                        ['value' => 'sha256', 'label' => 'SHA-256'],
                        ['value' => 'sha512', 'label' => 'SHA-512'],
                        ['value' => 'md5', 'label' => 'MD5'],
                        ['value' => 'sha1', 'label' => 'SHA-1'],
                    ],
                    'default'    => 'sha256',
                    'depends_on' => ['operation' => ['hash', 'hmac']],
                ],
                'secret_key' => [
                    'type'        => 'password',
                    'label'       => 'کلید مخفی (Secret Key)',
                    'required'    => true,
                    'depends_on'  => ['operation' => 'hmac'],
                ],
            ],
            presets: [
                [
                    'key'         => 'random_int',
                    'name'        => 'Random Number',
                    'description' => 'تولید عدد صحیح تصادفی در هر بازه دلخواه (مثلاً ۱ تا ۶ یا ۱ تا ۱۰۰).',
                    'icon'        => 'dice-3',
                    'config'      => ['operation' => 'random_int', 'min_value' => 1, 'max_value' => 100],
                ],
                [
                    'key'         => 'otp',
                    'name'        => 'Generate OTP Code',
                    'description' => 'تولید کد عددی تصادفی با تعداد ارقام مشخص جهت ارسال پیامک یا ایمیل تایید.',
                    'icon'        => 'dice-3',
                    'config'      => ['operation' => 'otp', 'otp_digits' => 6],
                ],
                [
                    'key'         => 'uuid',
                    'name'        => 'UUID & Random Token',
                    'description' => 'تولید شناسه یکتا (UUID / ULID) یا توکن و رشته تصادفی امن.',
                    'icon'        => 'lock',
                    'config'      => ['operation' => 'uuid', 'token_type' => 'uuid'],
                ],
                [
                    'key'         => 'hash_hmac',
                    'name'        => 'Hash & HMAC',
                    'description' => 'تولید هش (SHA-256 / MD5) یا امضای دیجیتال با کلید مخفی.',
                    'icon'        => 'lock',
                    'config'      => ['operation' => 'hash', 'algorithm' => 'sha256'],
                ],
                [
                    'key'         => 'base64',
                    'name'        => 'Base64 Encode / Decode',
                    'description' => 'تبدیل متن به Base64 و رمزگشایی رشته‌های Base64.',
                    'icon'        => 'lock',
                    'config'      => ['operation' => 'base64_encode'],
                ],
            ],
            color: 'amber',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        $operation = (string) ($context->config['operation'] ?? 'otp');

        return match ($operation) {
            'random_int'    => $this->handleRandomInt($context),
            'otp'           => $this->handleOtp($context),
            'uuid'          => $this->handleUuid($context),
            'hash'          => $this->handleHash($context),
            'hmac'          => $this->handleHmac($context),
            'base64_encode' => $this->handleBase64Encode($context),
            'base64_decode' => $this->handleBase64Decode($context),
        };
    }

    private function handleRandomInt(NodeContext $context): NodeResult
    {
        $rawMin = $context->config['min_value'] ?? 1;
        $rawMax = $context->config['max_value'] ?? 100;

        if (! is_numeric($rawMin) || ! is_numeric($rawMax)) {
            throw NodeExecutionException::because('مقادیر حداقل (Min) و حداکثر (Max) باید عدد معتبر باشند.');
        }

        $min = (int) $rawMin;
        $max = (int) $rawMax;

        if ($min > $max) {
            [$min, $max] = [$max, $min];
        }

        $number = random_int($min, $max);

        return NodeResult::of([
            'number' => $number,
            'value'  => $number,
            'text'   => (string) $number,
            'min'    => $min,
            'max'    => $max,
        ]);
    }

    private function handleOtp(NodeContext $context): NodeResult
    {
        $digits = max(1, min(10, (int) ($context->config['otp_digits'] ?? 6)));
        $min = $digits === 1 ? 0 : (int) pow(10, $digits - 1);
        $max = (int) pow(10, $digits) - 1;
        $code = (string) random_int($min, $max);

        return NodeResult::of([
            'code'   => $code,
            'number' => (int) $code,
            'value'  => $code,
            'text'   => $code,
            'digits' => $digits,
        ]);
    }

    private function handleUuid(NodeContext $context): NodeResult
    {
        $type = (string) ($context->config['token_type'] ?? 'uuid');
        $length = max(1, min(256, (int) ($context->config['token_length'] ?? 16)));

        $token = match ($type) {
            'ulid'    => (string) Str::ulid(),
            'hex'     => substr(bin2hex(random_bytes(max(1, (int) ceil($length / 2)))), 0, $length),
            'alnum'   => Str::random($length),
            'hex32'   => bin2hex(random_bytes(16)),
            'alnum32' => Str::random(32),
            default   => (string) Str::uuid(),
        };

        return NodeResult::of([
            'value'  => $token,
            'token'  => $token,
            'text'   => $token,
            'type'   => $type,
            'length' => strlen($token),
        ]);
    }

    private function handleHash(NodeContext $context): NodeResult
    {
        $value = (string) ($context->config['value'] ?? '');
        $algo = strtolower((string) ($context->config['algorithm'] ?? 'sha256'));

        if (! in_array($algo, ['sha256', 'sha512', 'md5', 'sha1'], true)) {
            $algo = 'sha256';
        }

        $hashed = hash($algo, $value);

        return NodeResult::of([
            'value'     => $hashed,
            'hash'      => $hashed,
            'text'      => $hashed,
            'algorithm' => $algo,
        ]);
    }

    private function handleHmac(NodeContext $context): NodeResult
    {
        $value = (string) ($context->config['value'] ?? '');
        $secret = (string) ($context->config['secret_key'] ?? '');
        $algo = strtolower((string) ($context->config['algorithm'] ?? 'sha256'));

        if ($secret === '') {
            throw NodeExecutionException::because('کلید مخفی (Secret Key) برای تولید HMAC الزامی است.');
        }

        if (! in_array($algo, ['sha256', 'sha512', 'md5', 'sha1'], true)) {
            $algo = 'sha256';
        }

        $signature = hash_hmac($algo, $value, $secret);

        return NodeResult::of([
            'value'     => $signature,
            'signature' => $signature,
            'text'      => $signature,
            'algorithm' => $algo,
        ]);
    }

    private function handleBase64Encode(NodeContext $context): NodeResult
    {
        $value = (string) ($context->config['value'] ?? '');
        $encoded = base64_encode($value);

        return NodeResult::of([
            'value' => $encoded,
            'text'  => $encoded,
        ]);
    }

    private function handleBase64Decode(NodeContext $context): NodeResult
    {
        $value = trim((string) ($context->config['value'] ?? ''));
        $decoded = base64_decode($value, true);

        if ($decoded === false) {
            throw NodeExecutionException::because('رشته واردشده یک Base64 معتبر نیست.');
        }

        return NodeResult::of([
            'value' => $decoded,
            'text'  => $decoded,
        ]);
    }
}
