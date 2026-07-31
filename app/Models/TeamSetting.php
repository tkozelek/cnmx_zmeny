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

    /**
     * Monday-indexed day weights for FairnessService, where 1.0 is an ordinary weekday.
     *
     * Above 1.0 = hard to fill, so working it earns more credit. Below 1.0 = sought after, so it
     * earns less. Friday is the problem day nobody volunteers for; the weekend is popular, which
     * is why it sits *below* baseline rather than above it — taking the shifts everybody wants
     * moves you up the queue for the next Friday.
     *
     * @var list<float>
     */
    public const array DEFAULT_FAIRNESS_DAY_WEIGHTS = [1, 1, 1, 1, 1.6, 0.8, 0.8];

    public const int DEFAULT_FAIRNESS_WINDOW_WEEKS = 12;

    protected $fillable = [
        'team_id',
        'week_start_day',
        'week_lookahead',
        'absence_deadline_days',
        'stale_absence_deletion_days',
        'fairness_day_weights',
        'fairness_window_weeks',
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
            'fairness_day_weights' => 'array',
            'fairness_window_weeks' => 'integer',
        ];
    }
}
