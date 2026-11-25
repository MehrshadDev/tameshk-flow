<?php

namespace App\Workflow\Support;

final class BotMessageHelper
{
    public static function truncate(string $text, int $maxLength): string
    {
        if (mb_strlen($text, 'UTF-8') <= $maxLength) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, max(1, $maxLength - 3), 'UTF-8')) . '...';
    }

    public static function split(string $text, int $maxLength = 3500, bool $preserveFenceLang = false): array
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $text);

        if (mb_strlen($normalized, 'UTF-8') <= $maxLength) {
            return [$normalized];
        }

        $chunks = [];
        $remaining = $normalized;
        $minCut = (int) floor($maxLength * 0.5);

        while (mb_strlen($remaining, 'UTF-8') > $maxLength) {
            $slice = mb_substr($remaining, 0, $maxLength, 'UTF-8');
            $cut = $maxLength;

            $doubleNewline = mb_strrpos($slice, "\n\n", 0, 'UTF-8');
            if ($doubleNewline !== false && $doubleNewline >= $minCut) {
                $cut = $doubleNewline;
            } else {
                $singleNewline = mb_strrpos($slice, "\n", 0, 'UTF-8');
                if ($singleNewline !== false && $singleNewline >= $minCut) {
                    $cut = $singleNewline;
                } else {
                    $space = mb_strrpos($slice, ' ', 0, 'UTF-8');
                    if ($space !== false && $space >= $minCut) {
                        $cut = $space;
                    }
                }
            }

            $chunk = rtrim(mb_substr($remaining, 0, $cut, 'UTF-8'));
            $remaining = ltrim(mb_substr($remaining, $cut, null, 'UTF-8'), "\n");

            $fenceCount = preg_match_all('/^```/m', $chunk);
            if ($fenceCount !== false && $fenceCount % 2 === 1) {
                $lang = '';
                if ($preserveFenceLang && preg_match_all('/^```([a-zA-Z0-9_+-]*)/m', $chunk, $fenceMatches)) {
                    $lang = trim((string) end($fenceMatches[1]));
                }
                $chunk .= "\n```";
                $remaining = "```$lang\n" . $remaining;
            }

            if ($chunk !== '') {
                $chunks[] = $chunk;
            }
        }

        if ($remaining !== '') {
            $chunks[] = $remaining;
        }

        return $chunks !== [] ? $chunks : [$normalized];
    }

    public static function buildInlineKeyboard(string $raw): ?array
    {
        $raw = trim($raw);

        if ($raw === '') {
            return null;
        }

        if (str_starts_with($raw, '[') || str_starts_with($raw, '{')) {
            $decoded = json_decode($raw, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                if (isset($decoded['inline_keyboard']) && is_array($decoded['inline_keyboard'])) {
                    return ['inline_keyboard' => $decoded['inline_keyboard']];
                }

                if (isset($decoded[0]['text'])) {
                    return ['inline_keyboard' => [$decoded]];
                }

                return ['inline_keyboard' => $decoded];
            }
        }

        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $keyboard = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $parts = preg_split('/[,،]+/u', $line) ?: [];
            $row = [];

            foreach ($parts as $part) {
                $part = trim($part);

                if ($part === '') {
                    continue;
                }

                if (str_contains($part, '|')) {
                    [$btnLabel, $btnData] = array_map('trim', explode('|', $part, 2));
                } else {
                    $btnLabel = $part;
                    $btnData = $part;
                }

                if ($btnLabel === '') {
                    continue;
                }

                if ($btnData === '') {
                    $btnData = $btnLabel;
                }

                if (preg_match('#^https?://#i', $btnData)) {
                    $row[] = [
                        'text' => $btnLabel,
                        'url'  => $btnData,
                    ];
                } else {
                    $row[] = [
                        'text'          => $btnLabel,
                        'callback_data' => mb_strcut($btnData, 0, 64, 'UTF-8'),
                    ];
                }
            }

            if ($row !== []) {
                $keyboard[] = $row;
            }
        }

        return $keyboard !== [] ? ['inline_keyboard' => $keyboard] : null;
    }

    public static function detectMimeType(string $filePath, string $headerContentType): string
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $byExt = match ($ext) {
            'oga', 'ogg', 'opus' => 'audio/ogg',
            'mp3'                => 'audio/mp3',
            'm4a'                => 'audio/m4a',
            'wav'                => 'audio/wav',
            'aac'                => 'audio/aac',
            'jpg', 'jpeg'        => 'image/jpeg',
            'png'                => 'image/png',
            'webp'               => 'image/webp',
            'gif'                => 'image/gif',
            'mp4'                => 'video/mp4',
            'mov'                => 'video/quicktime',
            'webm'               => 'video/webm',
            'pdf'                => 'application/pdf',
            default              => null,
        };

        if ($byExt !== null) {
            return $byExt;
        }

        $cleanHeader = trim(explode(';', $headerContentType)[0] ?? '');

        if ($cleanHeader !== '' && $cleanHeader !== 'application/octet-stream') {
            return $cleanHeader;
        }

        return 'audio/ogg';
    }
}
