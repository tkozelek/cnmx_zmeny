<?php

namespace App\Logging;

/**
 * A short keyed hash for log lines: the same e-mail or IP address always gives the same value,
 * so repeated attempts can be correlated, but the address itself never reaches the log.
 */
final class Fingerprint
{
    public static function of(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return substr(hash_hmac('sha256', mb_strtolower(trim($value)), (string) config('app.key')), 0, 12);
    }
}
