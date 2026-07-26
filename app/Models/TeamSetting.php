<?php

namespace App\Models;

use App\Traits\BelongsToTeam;
use Illuminate\Database\Eloquent\Model;

/**
 * Per-cinema configuration. Exactly one row per team.
 */
class TeamSetting extends Model
{
    use BelongsToTeam;

    /** Thursday — the legacy hardcoded start of the work week. */
    public const DEFAULT_WEEK_START_DAY = 3;

    public const DEFAULT_WEEK_LOOKAHEAD = 5;

    public const DEFAULT_ABSENCE_DEADLINE_DAYS = 2;

    public const DEFAULT_STALE_ABSENCE_DELETION_DAYS = 30;

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
