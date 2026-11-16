<?php

namespace App\Credentials\Types;

use App\Credentials\Contracts\CredentialTypeContract;
use App\Credentials\DTOs\CredentialDefinition;
use App\Credentials\Exceptions\InvalidCredentialException;

class MySqlCredential implements CredentialTypeContract
{
    public static function key(): string
    {
        return 'mysql';
    }

    public static function definition(): CredentialDefinition
    {
        return new CredentialDefinition(
            key: 'mysql',
            name: 'MySQL',
            description: 'اتصال امن به پایگاه داده MySQL یا MariaDB',
            icon: 'brand:mysql',
            schema: [
                'host' => [
                    'type'        => 'text',
                    'label'       => 'آدرس سرور (Host)',
                    'required'    => true,
                    'placeholder' => 'db.example.com',
                ],
                'port' => [
                    'type'        => 'number',
                    'label'       => 'پورت (Port)',
                    'required'    => true,
                    'default'     => 3306,
                    'min'         => 1,
                    'max'         => 65535,
                    'integer'     => true,
                    'placeholder' => '3306',
                ],
                'database' => [
                    'type'        => 'text',
                    'label'       => 'نام پایگاه داده (Database)',
                    'required'    => true,
                    'placeholder' => 'my_database',
                ],
                'username' => [
                    'type'        => 'text',
                    'label'       => 'نام کاربری (User)',
                    'required'    => true,
                    'placeholder' => 'db_user',
                ],
                'password' => [
                    'type'        => 'password',
                    'label'       => 'رمز عبور (Password)',
                    'required'    => true,
                ],
                'ssl' => [
                    'type'    => 'select',
                    'label'   => 'اتصال امن (SSL / TLS)',
                    'default' => '0',
                    'options' => [
                        ['value' => '0', 'label' => 'غیرفعال (استاندارد)'],
                        ['value' => '1', 'label' => 'فعال (Require SSL)'],
                    ],
                ],
            ],
        );
    }

    public function validate(array $data): void
    {
        $host = trim((string) ($data['host'] ?? ''));

        if ($host === '' || mb_strlen($host) > 255 || str_contains($host, '/') || str_contains($host, ':')) {
            throw InvalidCredentialException::because('آدرس سرور (Host) معتبر نیست. آدرس را بدون http یا پورت وارد کنید.');
        }

        $port = $data['port'] ?? 3306;
        if (! is_numeric($port) || (int) $port < 1 || (int) $port > 65535) {
            throw InvalidCredentialException::because('پورت پایگاه داده باید عددی بین ۱ تا ۶۵۵۳۵ باشد.');
        }

        $database = trim((string) ($data['database'] ?? ''));
        if ($database === '' || mb_strlen($database) > 128 || ! preg_match('/^[a-zA-Z0-9_\-]+$/', $database)) {
            throw InvalidCredentialException::because('نام پایگاه داده (Database) معتبر نیست.');
        }

        $username = trim((string) ($data['username'] ?? ''));
        if ($username === '' || mb_strlen($username) > 128) {
            throw InvalidCredentialException::because('نام کاربری پایگاه داده الزامی است.');
        }

        if (! isset($data['password']) || ! is_string($data['password']) || $data['password'] === '') {
            throw InvalidCredentialException::because('رمز عبور پایگاه داده الزامی است.');
        }
    }
}
