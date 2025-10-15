<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

trait Loggable
{
    public static function bootLoggable(): void
    {
        static::updated(function ($model) {
            $user = Auth::user();
            $changes = $model->getChanges();
            $original = $model->getOriginal();

            if ($model->timestamps) {
                unset($changes['updated_at'], $original['updated_at']);
            }

            if ($model->last_login_at) {
                unset($changes['last_login_at']);
            }

            if (! empty($changes)) {
                Log::info('Používateľ "'.($user ?? 'Neznámy').'" upravil model "'.get_class($model).'" (ID: '.$model->id.').', [
                    'user_id' => $user?->id,
                    'model' => get_class($model),
                    'target_id' => $model->id,
                    'name' => $model instanceof \App\Models\User ? $model->__toString() : '',
                    'changes' => $changes,
                    'original' => array_intersect_key($original, $changes),
                ]);
            }
        });

        static::deleted(function ($model) {
            $user = Auth::user();

            $attributes = $model->getAttributes();
            unset($attributes['updated_at'], $attributes['password'], $attributes['remember_token']);

            Log::warning('Používateľ "'.($user ?? 'Neznámy').'" vymazal model "'.get_class($model).'" (ID: '.$model->id.').', [
                'user_id' => $user?->id,
                'model' => get_class($model),
                'target_id' => $model->id,
                'attributes' => $attributes,
            ]);
        });

        static::creating(function ($model) {
            $user = Auth::user();

            if ($user && ($user->id != $model->id || $user->id != $model->id_user)) {
                return;
            }

            $attributes = $model->getAttributes();
            unset($attributes['updated_at'], $attributes['password'], $attributes['remember_token']);

            Log::warning('Používateľ "'.($user ?? 'Neznámy').'" vytvoril model "'.get_class($model).'" (ID: '.$model->id.').', [
                'user_id' => $user?->id,
                'model' => get_class($model),
                'target_id' => $model->id,
                'attributes' => $attributes,
            ]);
        });
    }
}
