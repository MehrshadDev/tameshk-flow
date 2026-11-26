<?php

namespace App\Credentials\Types;

use App\Credentials\Contracts\CredentialTypeContract;
use App\Credentials\DTOs\CredentialDefinition;
use App\Credentials\Exceptions\InvalidCredentialException;

class FtpCredential implements CredentialTypeContract
{
    public static function key(): string
    {
        return 'ftp';
    }

    public static function definition(): CredentialDefinition
    {
        return new CredentialDefinition(
            key: 'ftp',
            name: 'FTP / FTPS',
            description: 'اتصال امن به سرور فایل از طریق پروتکل FTP یا FTPS (TLS/SSL)',
            icon: 'server',
            schema: [
                'protocol' => [
                    'type'     => 'select',
                    'label'    => 'پروتکل اتصال (Protocol)',
                    'required' => true,
                    'default'  => 'ftps_explicit',
                    'options'  => [
                        ['value' => 'ftps_explicit', 'label' => 'FTPS (Explicit TLS)'],
                        ['value' => 'ftps_implicit', 'label' => 'FTPS (Implicit TLS)'],
                        ['value' => 'ftp', 'label' => 'FTP'],
                    ],
                ],
                'host' => [
                    'type'        => 'text',
                    'label'       => 'آدرس سرور (Host)',
                    'required'    => true,
                    'placeholder' => 'ftp.example.com',
                    'help'        => 'آدرس دامنه یا IP سرور FTP را بدون پیشوند ftp:// و بدون پورت وارد کنید.',
                ],
                'port' => [
                    'type'        => 'number',
                    'label'       => 'پورت (Port)',
                    'required'    => true,
                    'default'     => 21,
                    'min'         => 1,
                    'max'         => 65535,
                    'integer'     => true,
                    'placeholder' => '21',
                ],
                'username' => [
                    'type'        => 'text',
                    'label'       => 'نام کاربری (Username)',
                    'required'    => true,
                    'placeholder' => 'ftp_user@example.com',
                ],
                'password' => [
                    'type'     => 'password',
                    'label'    => 'رمز عبور (Password)',
                    'required' => true,
                ],
                'verify_ssl' => [
                    'type'    => 'select',
                    'label'   => 'اعتبارسنجی گواهی SSL (Verify SSL)',
                    'default' => '1',
                    'options' => [
                        ['value' => '1', 'label' => 'فعال'],
                        ['value' => '0', 'label' => 'غیرفعال'],
                    ],
                ],
            ],
        );
    }

    public function validate(array $data): void
    {
        $protocol = trim((string) ($data['protocol'] ?? 'ftps_explicit'));
        if (! in_array($protocol, ['ftp', 'ftps_explicit', 'ftps_implicit'], true)) {
            throw InvalidCredentialException::because('پروتکل انتخاب‌شده برای FTP معتبر نیست.');
        }

        $host = trim((string) ($data['host'] ?? ''));
        if (
            $host === ''
            || mb_strlen($host) > 255
            || preg_match('/[\s\/\\\\\x00-\x1F\x7F]/', $host)
            || (str_contains($host, ':') && ! filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))
        ) {
            throw InvalidCredentialException::because('آدرس سرور (Host) معتبر نیست. آدرس را بدون ftp:// یا پورت وارد کنید.');
        }

        $port = $data['port'] ?? 21;
        if (! is_numeric($port) || (int) $port < 1 || (int) $port > 65535) {
            throw InvalidCredentialException::because('پورت سرور باید عددی بین ۱ تا ۶۵۵۳۵ باشد.');
        }

        $username = trim((string) ($data['username'] ?? ''));
        if ($username === '' || mb_strlen($username) > 255 || preg_match('/[\r\n\0]/', $username)) {
            throw InvalidCredentialException::because('نام کاربری FTP معتبر نیست.');
        }

        $password = $data['password'] ?? null;
        if (! is_string($password) || $password === '' || preg_match('/[\r\n\0]/', $password)) {
            throw InvalidCredentialException::because('رمز عبور FTP الزامی و باید معتبر باشد.');
        }
    }
}
