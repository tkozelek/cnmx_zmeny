<?php

namespace App\Models;

use App\Traits\BelongsToTeam;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Per-cinema configuration. Exactly one row per team.
 */
class TeamSetting extends Model
{
    use BelongsToTeam;

    /**
     * Bust Team::cachedSettings() on every save — whatever wrote the change (the settings
     * page, a seeder, a test), not just the one call site that remembers to invalidate.
     */
    protected static function booted(): void
    {
        static::saved(fn (self $settings) => Cache::forget("team:{$settings->team_id}:settings"));
    }

    /** Thursday — the legacy hardcoded start of the work week. */
    public const int DEFAULT_WEEK_START_DAY = 3;

    public const int DEFAULT_WEEK_LOOKAHEAD = 5;

    public const int DEFAULT_ABSENCE_DEADLINE_DAYS = 2;

    public const int DEFAULT_STALE_ABSENCE_DELETION_DAYS = 7;

    protected $fillable = [
        'team_id',
        'week_start_day',
        'week_lookahead',
        'absence_deadline_days',
        'stale_absence_deletion_days',
        'timezone',
        'locale',
    ];

    protected function casts(): array
    {
        return [
            'week_start_day' => 'integer',
            'week_lookahead' => 'integer',
            'absence_deadline_days' => 'integer',
            'stale_absence_deletion_days' => 'integer',
        ];
    }
}
