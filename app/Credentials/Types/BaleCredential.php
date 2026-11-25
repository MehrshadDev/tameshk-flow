<?php

namespace App\Credentials\Types;

use App\Credentials\Contracts\CredentialTypeContract;
use App\Credentials\DTOs\CredentialDefinition;
use App\Credentials\Exceptions\InvalidCredentialException;

class BaleCredential implements CredentialTypeContract
{
    public static function key(): string
    {
        return 'bale';
    }

    public static function definition(): CredentialDefinition
    {
        return new CredentialDefinition(
            key: 'bale',
            name: 'بازوی بله',
            description: 'توکن اختصاصی بازوی بله دریافتی از BotFather@ در پیام‌رسان بله',
            icon: 'brand:bale',
            schema: [
                'bot_token' => [
                    'type'        => 'password',
                    'label'       => 'توکن بازو (Bot Token)',
                    'required'    => true,
                    'placeholder' => '123456789:abcdIuZmK5qNEm2A1BhUaAg7MPJv1O9KCcBQB2ro',
                    'help'        => 'توکن بازوی خود را از طریق گفت‌وگو با بازوی پدر (BotFather@) در بله دریافت کنید.',
                ],
            ],
        );
    }

    public function validate(array $data): void
    {
        if (empty($data['bot_token']) || ! is_string($data['bot_token'])) {
            throw InvalidCredentialException::because('توکن بازوی بله الزامی است.');
        }

        $token = trim($data['bot_token']);

        if (! preg_match('/^\d+:[\w-]{20,}$/', $token)) {
            throw InvalidCredentialException::because('فرمت توکن بازوی بله واردشده نامعتبر است.');
        }
    }
}
