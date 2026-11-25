<?php

namespace App\Logging;

use App\Support\SecretRedactor;
use Illuminate\Log\Logger;
use Monolog\Handler\FormattableHandlerInterface;
use Monolog\LogRecord;

final class RedactSecretsTap
{
    public function __invoke(Logger $logger): void
    {
        $monolog = $logger->getLogger();

        if (method_exists($monolog, 'pushProcessor')) {
            $monolog->pushProcessor(function (LogRecord $record): LogRecord {
                return $record->with(
                    message: SecretRedactor::redact($record->message),
                    context: SecretRedactor::redactArray($record->context),
                    extra: SecretRedactor::redactArray($record->extra),
                );
            });
        }

        if (method_exists($monolog, 'getHandlers')) {
            foreach ($monolog->getHandlers() as $handler) {
                if ($handler instanceof FormattableHandlerInterface) {
                    $handler->setFormatter(new RedactingFormatter($handler->getFormatter()));
                }
            }
        }
    }
}
