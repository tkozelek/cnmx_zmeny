<?php

namespace App\Support;

/**
 * What an audit record may say about a model's attributes - shared by the log file (Loggable) and
 * the platform audit trail, so the privacy page's promises hold for both.
 */
final class AuditRedactor
{
    /**
     * `remember_token` is the remember-me cookie value itself, so a logged one is a usable credential.
     *
     * @var list<string>
     */
    public const array NEVER_LOGGED = [
        'password',
        'remember_token',
        'pending_email_token',
        'app_authentication_secret',
        'app_authentication_recovery_codes',
    ];

    /**
     * Logged as changed, never with the value: logs outlive the deleted account.
     *
     * @var list<string>
     */
    public const array VALUE_REDACTED = ['name', 'lastname', 'email', 'pending_email', 'reason', 'note'];

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function redact(array $attributes): array
    {
        foreach (self::NEVER_LOGGED as $secret) {
            unset($attributes[$secret]);
        }

        foreach (self::VALUE_REDACTED as $personal) {
            if (array_key_exists($personal, $attributes)) {
                $attributes[$personal] = '[skryté]';
            }
        }

        return $attributes;
    }
}
