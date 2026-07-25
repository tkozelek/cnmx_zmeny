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
            unset($attributes['updated_at'], $attributes['password'], $attributes['remember_token']);

            Log::warning('Používateľ "'.($user ?? 'Neznámy').'" vymazal model "'.get_class($model).'" (ID: '.$model->getKey().').', [
                'user_id' => $user?->id,
                'model' => get_class($model),
                'target_id' => $model->getKey(),
                'attributes' => $attributes,
            ]);
        });
    }
}
