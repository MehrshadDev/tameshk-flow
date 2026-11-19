<?php

namespace App\Mcp\Exceptions;

use RuntimeException;

final class McpOAuthException extends RuntimeException
{
    public function __construct(
        public readonly string $oauthError,
        public readonly string $errorDescription,
        public readonly int $statusCode = 400,
    ) {
        parent::__construct($errorDescription);
    }

    public static function make(string $error, string $description, int $status = 400): self
    {
        return new self($error, $description, $status);
    }
}
