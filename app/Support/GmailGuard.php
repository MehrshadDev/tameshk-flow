<?php

namespace App\Support;

final class GmailGuard
{
    public static function normalize(string $email): string
    {
        $email = strtolower(trim($email));

        if (! str_contains($email, '@')) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);

        if ($domain === 'gmail.com' || $domain === 'googlemail.com') {
            $local = explode('+', $local)[0];
            $local = str_replace('.', '', $local);
            $domain = 'gmail.com';
        }

        return $local . '@' . $domain;
    }

    public static function validate(string $email, bool $allowAdminBypass = false): ?string
    {
        $trimmed = strtolower(trim($email));

        if ($trimmed === '' || mb_strlen($trimmed) > 255 || ! filter_var($trimmed, FILTER_VALIDATE_EMAIL)) {
            return 'فرمت ایمیل وارد شده صحیح نیست.';
        }

        if ($allowAdminBypass && self::isBypassedAdminEmail($trimmed)) {
            return null;
        }

        $parts = explode('@', $trimmed);

        if (count($parts) !== 2) {
            return 'فرمت ایمیل وارد شده صحیح نیست.';
        }

        [$local, $domain] = $parts;

        if ($domain !== 'gmail.com') {
            return 'در حال حاضر فقط ثبت‌نام و ورود با حساب جیمیل (@gmail.com) امکان‌پذیر است.';
        }

        if (str_contains($local, '+')) {
            return 'استفاده از علامت + در آدرس جیمیل مجاز نیست.';
        }

        if (
            ! preg_match('/^[a-z0-9.]+$/', $local)
            || str_starts_with($local, '.')
            || str_ends_with($local, '.')
            || str_contains($local, '..')
        ) {
            return 'نام کاربری جیمیل معتبر نیست.';
        }

        $canonicalLocal = str_replace('.', '', $local);

        if (strlen($canonicalLocal) < 3 || strlen($canonicalLocal) > 64) {
            return 'طول نام کاربری جیمیل معتبر نیست.';
        }

        return null;
    }

    private static function isBypassedAdminEmail(string $email): bool
    {
        $allowed = array_merge(
            (array) config('app.admin_emails', []),
            (array) config('tickets.admin_emails', []),
            (array) config('terminal.allowed_emails', []),
        );

        return in_array($email, $allowed, true);
    }
}
