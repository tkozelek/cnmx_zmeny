<?php

namespace App\Logging;

use App\Support\AuditRedactor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Log\Logger;
use Monolog\LogRecord;
use Throwable;

/**
 * Log channel tap: strips secrets and personal data from every record before it is written, so
 * the privacy page's "no names, e-mails or absence reasons in the logs" holds for exceptions too.
 */
final class ScrubPersonalData
{
    private const array SECRET_KEYS = [
        ...AuditRedactor::NEVER_LOGGED,
        'password_confirmation',
        'current_password',
        'token',
        '_token',
        'signature',
        'secret',
        'authorization',
        'cookie',
    ];

    /** @var array<string, string> */
    private const array PATTERNS = [
        '/[^\s@<>()\[\]"\'`,;:\/]+@[^\s@<>()\[\]"\'`,;:\/]+\.[a-z]{2,}/iu' => '[e-mail]',
        '#(/(?:zabudnute-heslo|zmena-emailu/\d+|overenie-emailu/\d+))/[^\s/?"\'&]+#' => '$1/[token]',
        '/\b(signature|token)=[^&\s"\']+/i' => '$1=[skryté]',
        '/\b(?:\d{1,3}\.){3}\d{1,3}\b/' => '[ip]',
    ];

    public function __invoke(Logger $logger): void
    {
        $logger->pushProcessor($this->scrub(...));
    }

    public function scrub(LogRecord $record): LogRecord
    {
        $exception = $record->context['exception'] ?? null;

        // The exception handler logs `$e->getMessage()` as the message; for a QueryException that
        // message carries the bound values (names, e-mails), so it is rebuilt without them.
        $message = $exception instanceof Throwable && $record->message === $exception->getMessage()
            ? $this->messageOf($exception)
            : $record->message;

        return $record->with(
            message: $this->scrubString($message),
            context: $this->scrubArray($record->context),
            extra: $this->scrubArray($record->extra),
        );
    }

    /**
     * @param  array<array-key, mixed>  $values
     * @return array<array-key, mixed>
     */
    private function scrubArray(array $values): array
    {
        foreach ($values as $key => $value) {
            if (is_string($key) && in_array(mb_strtolower($key), self::SECRET_KEYS, true)) {
                unset($values[$key]);

                continue;
            }

            if (is_string($key) && $value !== null && in_array($key, AuditRedactor::VALUE_REDACTED, true)) {
                $values[$key] = '[skryté]';

                continue;
            }

            $values[$key] = match (true) {
                $value instanceof Throwable => $this->describe($value),
                // A model would be serialised with every attribute, e-mail and name included.
                $value instanceof Model => $value::class.'#'.$value->getKey(),
                is_array($value) => $this->scrubArray($value),
                is_string($value) => $this->scrubString($value),
                default => $value,
            };
        }

        return $values;
    }

    private function scrubString(string $value): string
    {
        return (string) preg_replace(array_keys(self::PATTERNS), array_values(self::PATTERNS), $value);
    }

    private function messageOf(Throwable $e): string
    {
        return $e instanceof QueryException
            ? ($e->getPrevious() ?? $e)->getMessage().' (SQL: '.$e->getSql().')'
            : $e->getMessage();
    }

    private function describe(Throwable $e): string
    {
        $description = sprintf(
            "[object] (%s(code: %s): %s at %s:%d)\n[stacktrace]\n%s",
            $e::class,
            $e->getCode(),
            $this->messageOf($e),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString(),
        );

        if ($e->getPrevious() !== null && ! $e instanceof QueryException) {
            $description .= "\n[previous exception] ".$this->describe($e->getPrevious());
        }

        return $this->scrubString($description);
    }
}
