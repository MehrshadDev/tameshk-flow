<?php

namespace App\Credentials\Types;

use App\Credentials\Contracts\CredentialTypeContract;
use App\Credentials\DTOs\CredentialDefinition;
use App\Credentials\Exceptions\InvalidCredentialException;

class WooCommerceCredential implements CredentialTypeContract
{
    public static function key(): string
    {
        return 'woocommerce';
    }

    public static function definition(): CredentialDefinition
    {
        return new CredentialDefinition(
            key: 'woocommerce',
            name: 'WooCommerce',
            description: 'اتصال به فروشگاه ووکامرس (وردپرس) از طریق کلیدهای REST API (نسخه v3)',
            icon: 'brand:woocommerce',
            schema: [
                'url' => [
                    'type'        => 'text',
                    'label'       => 'آدرس وب‌سایت فروشگاه (Store URL)',
                    'required'    => true,
                    'placeholder' => 'https://example.com',
                    'help'        => 'آدرس اصلی سایت وردپرسی خود را وارد کنید (مثلاً https://shop.ir).',
                ],
                'consumer_key' => [
                    'type'        => 'text',
                    'label'       => 'کلید مصرف‌کننده (Consumer Key)',
                    'required'    => true,
                    'placeholder' => 'ck_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
                    'help'        => 'از پنل وردپرس ← ووکامرس ← پیکربندی ← پیشرفته ← REST API یک کلید با دسترسی «خواندن/نوشتن» بسازید.',
                ],
                'consumer_secret' => [
                    'type'        => 'password',
                    'label'       => 'رمز مصرف‌کننده (Consumer Secret)',
                    'required'    => true,
                    'placeholder' => 'cs_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
                ],
                'auth_mode' => [
                    'type'    => 'select',
                    'label'   => 'روش احراز هویت (Authentication Mode)',
                    'default' => 'auto',
                    'options' => [
                        ['value' => 'auto', 'label' => 'هوشمند (سازگار با تمام هاست‌ها - پیشنهادی)'],
                        ['value' => 'header', 'label' => 'فقط هدر HTTP Basic Auth'],
                        ['value' => 'query', 'label' => 'ارسال کلید در پارامترهای آدرس (Query String)'],
                    ],
                    'help'    => 'حالت هوشمند مشکل حذف هدر Authorization در برخی هاست‌های اشتراکی را به‌طور خودکار حل می‌کند.',
                ],
            ],
        );
    }

    public function validate(array $data): void
    {
        $url = trim((string) ($data['url'] ?? ''));

        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            throw InvalidCredentialException::because('آدرس وب‌سایت فروشگاه (Store URL) معتبر نیست.');
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (! in_array($scheme, ['http', 'https'], true)) {
            throw InvalidCredentialException::because('آدرس فروشگاه باید با https:// یا http:// شروع شود.');
        }

        $consumerKey = trim((string) ($data['consumer_key'] ?? ''));
        if ($consumerKey === '' || strlen($consumerKey) < 10) {
            throw InvalidCredentialException::because('کلید مصرف‌کننده (Consumer Key) معتبر الزامی است.');
        }

        $consumerSecret = trim((string) ($data['consumer_secret'] ?? ''));
        if ($consumerSecret === '' || strlen($consumerSecret) < 10) {
            throw InvalidCredentialException::because('رمز مصرف‌کننده (Consumer Secret) معتبر الزامی است.');
        }
    }
}
