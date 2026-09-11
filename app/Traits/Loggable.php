<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Writes an audit line to the log when a model is changed or deleted.
 *
 * ponytail: the log file is the audit trail. Swap for spatie/laravel-activitylog only when
 * someone actually needs to query history from the UI.
 */
trait Loggable
{
    /**
     * Never written to the log, on any event.
     *
     * `remember_token` is the worst of these: Laravel stores it unhashed and it *is* the
     * remember-me cookie value, so a token in a log file is a usable credential. The password
     * hash is little better. The delete handler always stripped these; the update handler did
     * not, which meant every password reset and every rehash-on-login wrote both.
     *
     * @var list<string>
     */
    private const NEVER_LOGGED = ['password', 'remember_token'];

    public static function bootLoggable(): void
    {
        static::updated(function (Model $model): void {
            $user = Auth::user();
            $changes = $model->getChanges();
            $original = $model->getOriginal();

            if ($model->timestamps) {
                unset($changes['updated_at'], $original['updated_at']);
            }

            // A login is not an edit worth logging.
            unset($changes['last_login_at']);

            foreach (self::NEVER_LOGGED as $secret) {
                unset($changes[$secret], $original[$secret]);
            }

            if (empty($changes)) {
                return;
            }

            Log::info('Používateľ "'.($user ?? 'Neznámy').'" upravil model "'.get_class($model).'" (ID: '.$model->getKey().').', [
                'user_id' => $user?->id,
                'model' => get_class($model),
                'target_id' => $model->getKey(),
                'changes' => $changes,
                'original' => array_intersect_key($original, $changes),
            ]);
        });

        static::deleted(function (Model $model): void {
            $user = Auth::user();

            $attributes = $model->getAttributes();
            unset($attributes['updated_at']);

            foreach (self::NEVER_LOGGED as $secret) {
                unset($attributes[$secret]);
            }

            Log::warning('Používateľ "'.($user ?? 'Neznámy').'" vymazal model "'.get_class($model).'" (ID: '.$model->getKey().').', [
                'user_id' => $user?->id,
                'model' => get_class($model),
                'target_id' => $model->getKey(),
                'attributes' => $attributes,
            ]);
        });
    }
}
