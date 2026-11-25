<?php

namespace App\Logging;

use App\Support\SecretRedactor;
use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;

final class RedactingFormatter implements FormatterInterface
{
    public function __construct(
        private readonly FormatterInterface $inner,
    ) {}

    public function format(LogRecord $record): mixed
    {
        $formatted = $this->inner->format($record);

        return is_string($formatted)
            ? SecretRedactor::redact($formatted)
            : SecretRedactor::redactValue($formatted);
    }

    public function formatBatch(array $records): mixed
    {
        $formatted = $this->inner->formatBatch($records);

        return is_string($formatted)
            ? SecretRedactor::redact($formatted)
            : SecretRedactor::redactValue($formatted);
    }
}
