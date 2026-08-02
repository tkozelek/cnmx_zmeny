<?php

namespace App\Traits;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Queryable edit history for the rozpis: who changed what, when.
 *
 * Which cinema the change belongs to and which day of the plan it touched are stamped onto the
 * activity by AppServiceProvider, not here - v5 has no per-model hook into `properties`, and the
 * stamp has to happen for every logged model anyway. They are copied *into* the activity rather
 * than joined back through the subject because an activity must outlive its subject: "kto zmazal
 * pozíciu vo štvrtok" is exactly what a history panel is for, and by then the row is gone.
 *
 * Supersedes {@see Loggable} for these models - that one writes prose into the log file, which
 * nothing can filter by week.
 */
trait LogsRozpisActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            // Timestamps and no-op saves would bury the real edits.
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
