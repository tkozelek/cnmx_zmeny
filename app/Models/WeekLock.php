<?php

namespace App\Models;

use App\Traits\BelongsToTeam;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A row here means that week is frozen for that team. Unlocking deletes the row.
 *
 * This is all that survives of the legacy `weeks` table — weeks themselves are computed
 * from team_settings.week_start_day, so there is nothing to pre-generate.
 */
class WeekLock extends Model
{
    use BelongsToTeam;

    /** The table has only created_at. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'team_id',
        'week_start',
        'locked_by',
        'rozpis_published_at',
        'published_by',
    ];

    /**
     * `date:Y-m-d`, not `date`: a plain `date` cast writes "2026-07-23 00:00:00" to the
     * column, so any lookup comparing against a "Y-m-d" string misses. MySQL hides it by
     * truncating a DATE column; SQLite keeps the time and the comparison fails.
     */
    protected function casts(): array
    {
        return [
            'week_start' => 'date:Y-m-d',
            'rozpis_published_at' => 'datetime',
        ];
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function isRozpisPublished(): bool
    {
        return $this->rozpis_published_at !== null;
    }

    /**
     * The single lock check, used by both policies and the admin UI.
     *
     * The team is passed explicitly, so the tenant global scope is dropped — otherwise
     * asking about another team (console, seeder, tests) would silently answer "no".
     */
    public static function locked(int $teamId, CarbonInterface|string $weekStart): bool
    {
        return static::query()
            ->forTeamWeek($teamId, $weekStart)
            ->exists();
    }

    /**
     * The lock row itself, for the callers that need more than "is it locked" — publishing hangs
     * off it. Null when the week was never locked, which is also the answer to "is the rozpis
     * published": there is no rozpis before there is a lock.
     */
    public static function forWeek(int $teamId, CarbonInterface|string $weekStart): ?self
    {
        return static::query()
            ->forTeamWeek($teamId, $weekStart)
            ->first();
    }

    /** Same team-agnostic lookup both statics need — see locked() for why the scope is dropped. */
    public function scopeForTeamWeek(Builder $query, int $teamId, CarbonInterface|string $weekStart): Builder
    {
        return $query->withoutGlobalScope('team')
            ->where('team_id', $teamId)
            ->where('week_start', $weekStart instanceof CarbonInterface ? $weekStart->toDateString() : $weekStart);
    }
}
