<?php

namespace App\Workflow\Support;

final class BaleApi
{
    private const BUSINESS_METHODS = [
        'getMe',
        'sendMessage',
        'forwardMessage',
        'sendPhoto',
        'sendVideo',
        'sendAudio',
        'sendDocument',
    ];

    public static function url(string $botToken, string $method, bool $business = false): string
    {
        $baseUrl = rtrim((string) config('services.bale.base_url', 'https://tapi.bale.ai'), '/');

        if ($business && in_array($method, self::BUSINESS_METHODS, true)) {
            return "$baseUrl/business/bot$botToken/$method";
        }

        return "$baseUrl/bot$botToken/$method";
    }

    public static function fileUrl(string $botToken, string $filePath): string
    {
        $baseUrl = rtrim((string) config('services.bale.base_url', 'https://tapi.bale.ai'), '/');
        $cleanPath = ltrim($filePath, '/');

        return "$baseUrl/file/bot$botToken/$cleanPath";
    }
}
