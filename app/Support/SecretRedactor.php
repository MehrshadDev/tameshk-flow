<?php

namespace App\Support;

final class SecretRedactor
{
    private const REPLACEMENT = '[REDACTED]';

    private const PATTERNS = [
        '#(\/(?:business\/)?(?:file\/)?bot)\d+:[^\s\/?"\'&<>]+#i' => '$1' . self::REPLACEMENT,
        '#\b\d{6,15}:[A-Za-z0-9_-]{20,}\b#' => self::REPLACEMENT,
        '#([?&](?:consumer_key|consumer_secret|api_key|apikey|key|token|access_token|refresh_token|secret|secret_token|password|passwd|auth|signature|client_secret)=)[^&\s"\'<>]+#i' => '$1' . self::REPLACEMENT,
        '#(https?:\/\/)([^\/\s:@]+):([^\/\s@]+)@#i' => '$1***:' . self::REPLACEMENT . '@',
        '#(\b(?:Bearer|Basic)\s+)[A-Za-z0-9._~+\/=-]{8,}#i' => '$1' . self::REPLACEMENT,
        '#\bsk-(?:ant-|or-v1-|proj-)?[A-Za-z0-9_-]{16,}\b#' => self::REPLACEMENT,
        '#\bgsk_[A-Za-z0-9_-]{16,}\b#' => self::REPLACEMENT,
        '#\bAIza[0-9A-Za-z_-]{25,}\b#' => self::REPLACEMENT,
        '#\b(?:ck|cs)_[a-fA-F0-9]{20,}\b#' => self::REPLACEMENT,
        '#\bGOCSPX-[A-Za-z0-9_-]{16,}\b#' => self::REPLACEMENT,
        '#\btmk(?:s|c|_at_|_rt_)[A-Za-z0-9]{16,}\b#' => self::REPLACEMENT,
    ];

    public static function redact(string $text, array $knownSecrets = []): string
    {
        if ($text === '') {
            return '';
        }

        $result = $text;

        foreach ($knownSecrets as $secret) {
            if (! is_string($secret)) {
                continue;
            }

            $clean = trim($secret);

            if (strlen($clean) >= 6) {
                $result = str_replace([$clean, urlencode($clean), rawurlencode($clean)], self::REPLACEMENT, $result);
            }
        }

        foreach (self::PATTERNS as $pattern => $replacement) {
            $replaced = preg_replace($pattern, $replacement, $result);

            if ($replaced !== null) {
                $result = $replaced;
            }
        }

        return $result;
    }

    public static function redactValue(mixed $value, array $knownSecrets = []): mixed
    {
        if (is_string($value)) {
            return self::redact($value, $knownSecrets);
        }

        if (is_array($value)) {
            return self::redactArray($value, $knownSecrets);
        }

        return $value;
    }

    public static function redactArray(array $data, array $knownSecrets = []): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            $sanitized[$key] = self::redactValue($value, $knownSecrets);
        }

        return $sanitized;
    }
}
