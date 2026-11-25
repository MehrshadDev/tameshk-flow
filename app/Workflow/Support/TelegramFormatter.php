<?php

namespace App\Workflow\Support;

final class TelegramFormatter
{
    public const MAX_MESSAGE_LENGTH = 3500;
    public const MAX_CAPTION_LENGTH = 1024;
    public const MAX_EDIT_LENGTH = 4096;

    public static function prepare(string $text, string $parseMode): array
    {
        $mode = trim($parseMode);

        if ($text === '' || $mode === '' || $mode === 'None') {
            return [
                'text' => $text,
                'parse_mode' => null,
            ];
        }

        if (strcasecmp($mode, 'Markdown') === 0 || strcasecmp($mode, 'MarkdownV2') === 0) {
            return [
                'text' => self::markdownToHtml($text),
                'parse_mode' => 'HTML',
            ];
        }

        if (strcasecmp($mode, 'HTML') === 0) {
            return [
                'text' => $text,
                'parse_mode' => 'HTML',
            ];
        }

        return [
            'text' => $text,
            'parse_mode' => $mode,
        ];
    }

    public static function isParseEntityError(string $message): bool
    {
        $lower = strtolower($message);

        return str_contains($lower, "can't parse entities")
            || str_contains($lower, 'cant parse entities')
            || str_contains($lower, 'parse entities');
    }

    public static function truncate(string $text, int $maxLength): string
    {
        return BotMessageHelper::truncate($text, $maxLength);
    }

    public static function split(string $text, int $maxLength = self::MAX_MESSAGE_LENGTH): array
    {
        return BotMessageHelper::split($text, $maxLength, preserveFenceLang: true);
    }

    public static function markdownToHtml(string $text): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $text);
        $uid = bin2hex(random_bytes(4));
        $placeholders = [];

        $store = function (string $html) use (&$placeholders, $uid): string {
            $token = 'TGPH' . $uid . 'N' . count($placeholders) . 'E';
            $placeholders[$token] = $html;

            return $token;
        };

        $normalized = preg_replace_callback(
            '/```([a-zA-Z0-9_+-]*)[ \t]*\n([\s\S]*?)```/',
            function (array $m) use ($store): string {
                $lang = preg_replace('/[^a-zA-Z0-9_+-]/', '', trim($m[1])) ?? '';
                $code = htmlspecialchars(rtrim($m[2], "\n"), ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');

                if ($lang !== '') {
                    return $store("<pre><code class=\"language-$lang\">$code</code></pre>");
                }

                return $store("<pre>$code</pre>");
            },
            $normalized
        ) ?? $normalized;

        $normalized = preg_replace_callback(
            '/```([^\n`]+)```/',
            function (array $m) use ($store): string {
                $code = htmlspecialchars($m[1], ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');

                return $store("<pre>$code</pre>");
            },
            $normalized
        ) ?? $normalized;

        $normalized = preg_replace_callback(
            '/`([^`\n]+)`/',
            function (array $m) use ($store): string {
                $code = htmlspecialchars($m[1], ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');

                return $store("<code>$code</code>");
            },
            $normalized
        ) ?? $normalized;

        $normalized = htmlspecialchars($normalized, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $normalized = preg_replace_callback(
            '/\[([^\]\n]+)\]\((https?:\/\/[^\s\)]+|tg:\/\/[^\s\)]+|mailto:[^\s\)]+)\)/i',
            function (array $m) use ($store): string {
                $label = $m[1];
                $href = str_replace('"', '&quot;', $m[2]);

                return $store("<a href=\"$href\">$label</a>");
            },
            $normalized
        ) ?? $normalized;

        $normalized = preg_replace_callback(
            '/(?:^&gt;[ \t]?.*(?:\n|$))+/m',
            function (array $m): string {
                $lines = explode("\n", rtrim($m[0], "\n"));
                $cleaned = array_map(
                    fn (string $line) => preg_replace('/^&gt;[ \t]?/', '', $line) ?? $line,
                    $lines
                );

                return '<blockquote>' . implode("\n", $cleaned) . "</blockquote>\n";
            },
            $normalized
        ) ?? $normalized;

        $normalized = preg_replace_callback(
            '/^[ \t]*#{1,6}[ \t]+(.+?)[ \t]*#*$/mu',
            function (array $m): string {
                $inner = preg_replace('/\*\*(.+?)\*\*/su', '$1', $m[1]) ?? $m[1];

                return '<b>' . trim($inner) . '</b>';
            },
            $normalized
        ) ?? $normalized;

        $normalized = preg_replace('/^([ \t]*)[*\-][ \t]+/m', '$1• ', $normalized) ?? $normalized;

        $normalized = preg_replace('/\*\*\*(?=\S)(.+?)(?<=\S)\*\*\*/su', '<b><i>$1</i></b>', $normalized) ?? $normalized;
        $normalized = preg_replace('/\*\*(?=\S)(.+?)(?<=\S)\*\*/su', '<b>$1</b>', $normalized) ?? $normalized;
        $normalized = preg_replace('/(?<![\p{L}\p{N}_])__(?=\S)(.+?)(?<=\S)__(?![\p{L}\p{N}_])/su', '<b>$1</b>', $normalized) ?? $normalized;
        $normalized = preg_replace('/~~(?=\S)(.+?)(?<=\S)~~/su', '<s>$1</s>', $normalized) ?? $normalized;
        $normalized = preg_replace('/(?<!\*)\*(?=\S)([^\*\n]+?)(?<=\S)\*(?!\*)/u', '<i>$1</i>', $normalized) ?? $normalized;
        $normalized = preg_replace('/(?<![\p{L}\p{N}_])_(?=\S)([^_\n]+?)(?<=\S)_(?![\p{L}\p{N}_])/u', '<i>$1</i>', $normalized) ?? $normalized;

        return strtr($normalized, $placeholders);
    }
}
