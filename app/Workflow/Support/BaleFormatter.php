<?php

namespace App\Workflow\Support;

final class BaleFormatter
{
    public const MAX_MESSAGE_LENGTH = 3500;
    public const MAX_CAPTION_LENGTH = 1024;
    public const MAX_EDIT_LENGTH = 4096;

    public static function prepare(string $text, string $formatMode = 'auto'): string
    {
        $mode = strtolower(trim($formatMode));

        if ($text === '' || $mode === 'none' || $mode === 'raw') {
            return $text;
        }

        return self::markdownToBale($text);
    }

    public static function truncate(string $text, int $maxLength): string
    {
        return BotMessageHelper::truncate($text, $maxLength);
    }

    public static function split(string $text, int $maxLength = self::MAX_MESSAGE_LENGTH): array
    {
        return BotMessageHelper::split($text, $maxLength, preserveFenceLang: false);
    }

    public static function markdownToBale(string $text): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $text);
        $uid = bin2hex(random_bytes(4));
        $placeholders = [];

        $store = function (string $value) use (&$placeholders, $uid): string {
            $token = 'BLPH' . $uid . 'N' . count($placeholders) . 'E';
            $placeholders[$token] = $value;

            return $token;
        };

        $normalized = preg_replace_callback(
            '/```[\s\S]*?```/',
            fn (array $m) => $store($m[0]),
            $normalized
        ) ?? $normalized;

        $normalized = preg_replace_callback(
            '/`([^`\n]+)`/',
            fn (array $m) => $store($m[1]),
            $normalized
        ) ?? $normalized;

        $normalized = preg_replace_callback(
            '/\[([^\]\n]+)\]\((https?:\/\/[^\s\)]+)\)/i',
            fn (array $m) => $store('[' . $m[1] . '](' . $m[2] . ')'),
            $normalized
        ) ?? $normalized;

        $normalized = preg_replace_callback(
            '/^[ \t]*#{1,6}[ \t]+(.+?)[ \t]*#*$/mu',
            function (array $m): string {
                $inner = preg_replace('/\*{1,3}(.+?)\*{1,3}/su', '$1', $m[1]) ?? $m[1];

                return '*' . trim($inner) . '*';
            },
            $normalized
        ) ?? $normalized;

        $normalized = preg_replace('/^([ \t]*)[*\-][ \t]+(?!\[[ xX]\])/m', '$1• ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\*\*\*(?=\S)(.+?)(?<=\S)\*\*\*/su', '*_$1_*', $normalized) ?? $normalized;
        $normalized = preg_replace('/\*\*(?=\S)(.+?)(?<=\S)\*\*/su', '*$1*', $normalized) ?? $normalized;
        $normalized = preg_replace('/(?<![\p{L}\p{N}_])__(?=\S)(.+?)(?<=\S)__(?![\p{L}\p{N}_])/su', '_$1_', $normalized) ?? $normalized;

        return strtr($normalized, $placeholders);
    }
}
