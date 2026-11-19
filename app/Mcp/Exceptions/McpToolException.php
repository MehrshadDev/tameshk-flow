<?php

namespace App\Mcp\Exceptions;

use RuntimeException;

final class McpToolException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly array $details = [],
    ) {
        parent::__construct($message);
    }

    public static function because(string $message, array $details = []): self
    {
        return new self($message, $details);
    }
}
