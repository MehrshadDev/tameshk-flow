<?php

namespace App\Backup;

use RuntimeException;

final class GitBackupCipher
{
    private const CIPHER = 'aes-256-gcm';

    private string $masterKey;

    public function __construct(?string $rawKey = null)
    {
        $configured = trim($rawKey ?? (string) config('git_backup.encryption_key', ''));

        if ($configured === '') {
            $configured = trim((string) config('app.key', ''));
        }

        if (str_starts_with($configured, 'base64:')) {
            $decoded = base64_decode(substr($configured, 7), true);
            if ($decoded !== false && strlen($decoded) >= 32) {
                $this->masterKey = substr($decoded, 0, 32);

                return;
            }
        }

        if ($configured === '') {
            throw new RuntimeException('Backup encryption key is not configured.');
        }

        $this->masterKey = hash('sha256', $configured, true);
    }

    public function encryptData(string $table, string|int $recordId, array $data): string
    {
        $canonical = $this->canonicalize($data);
        $plaintext = json_encode($canonical, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($plaintext === false) {
            throw new RuntimeException("Failed to encode backup record [$table:$recordId].");
        }

        $aad = "$table:$recordId";
        $encKey = hash_hmac('sha256', "enc:$aad", $this->masterKey, true);
        $iv = substr(hash_hmac('sha256', "iv:$aad:$plaintext", $this->masterKey, true), 0, 12);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $encKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $aad,
            16
        );

        if ($ciphertext === false) {
            throw new RuntimeException("Failed to encrypt backup record [$table:$recordId].");
        }

        return base64_encode($iv . $tag . $ciphertext);
    }

    public function decryptData(string $table, string|int $recordId, string $encodedPayload): array
    {
        $raw = base64_decode(trim($encodedPayload), true);

        if ($raw === false || strlen($raw) <= 28) {
            throw new RuntimeException("Corrupted encrypted payload for [$table:$recordId].");
        }

        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $ciphertext = substr($raw, 28);

        $aad = "$table:$recordId";
        $encKey = hash_hmac('sha256', "enc:$aad", $this->masterKey, true);

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $encKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $aad
        );

        if ($plaintext === false) {
            throw new RuntimeException("Decryption authentication failed for [$table:$recordId]. Check BACKUP_ENCRYPTION_KEY.");
        }

        $decoded = json_decode($plaintext, true);

        if (! is_array($decoded)) {
            throw new RuntimeException("Invalid JSON structure in decrypted record [$table:$recordId].");
        }

        return $decoded;
    }

    private function canonicalize(array $data): array
    {
        ksort($data);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->canonicalize($value);
            }
        }

        return $data;
    }
}
