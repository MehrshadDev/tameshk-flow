<?php

namespace App\Workflow\Support;

final class TelegramApi
{
    public static function url(string $botToken, string $method): string
    {
        $baseUrl = rtrim((string) config('services.telegram.base_url', 'https://api.telegram.org'), '/');

        return "$baseUrl/bot$botToken/$method";
    }

    public static function fileUrl(string $botToken, string $filePath): string
    {
        $baseUrl = rtrim((string) config('services.telegram.base_url', 'https://api.telegram.org'), '/');
        $cleanPath = ltrim($filePath, '/');

        return "$baseUrl/file/bot$botToken/$cleanPath";
    }
}
