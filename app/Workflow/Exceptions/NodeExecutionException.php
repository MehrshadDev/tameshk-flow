<?php

namespace App\Workflow\Exceptions;

use App\Support\SecretRedactor;
use RuntimeException;
use Throwable;

class NodeExecutionException extends RuntimeException
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(SecretRedactor::redact($message), $code, $previous);
    }

    public static function because(string $message, ?Throwable $previous = null, array $secrets = []): self
    {
        return new self(SecretRedactor::redact($message, $secrets), 0, $previous);
    }
}
