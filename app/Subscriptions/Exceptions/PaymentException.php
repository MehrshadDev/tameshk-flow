<?php

namespace App\Subscriptions\Exceptions;

use RuntimeException;

class PaymentException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly bool $isTransient = false,
    ) {
        parent::__construct($message);
    }

    public static function because(string $message): self
    {
        return new self($message, false);
    }

    public static function transient(string $message): self
    {
        return new self($message, true);
    }
}
