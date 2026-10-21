<?php

namespace App\Traits;

use App\Services\RozpisNotificationService;

/**
 * Whenever a rozpis-shaping row (Assignment, PositionSlot) is saved or deleted, schedule the
 * debounced "rozpis changed" mail - see RozpisNotificationService::scheduleChangeNotification(),
 * which no-ops unless that week's rozpis is already published.
 *
 * A save that only touched sort_order is skipped: dragging rows into a new order doesn't change
 * what anyone is actually scheduled to do.
 */
trait NotifiesRozpisChange
{
    public static function bootNotifiesRozpisChange(): void
    {
        static::saved(function (self $model): void {
            if (array_diff(array_keys($model->getChanges()), ['sort_order', 'updated_at']) === []) {
                return;
            }

            static::scheduleRozpisChangeNotification($model);
        });

        static::deleted(function (self $model): void {
            static::scheduleRozpisChangeNotification($model);
        });
    }

    private static function scheduleRozpisChangeNotification(self $model): void
    {
        if (! $model->date || ! $model->team_id) {
            return;
        }

        app(RozpisNotificationService::class)->scheduleChangeNotification($model->team_id, $model->date);
    }
}
