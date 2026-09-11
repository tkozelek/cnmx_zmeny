<?php

namespace App\Models;

use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Cache;

/**
 * One cinema. The tenant boundary for everything except User.
 */
class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Every membership, approved or not. Use `approvedUsers()` for people who may log in.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('approved_at')
            ->withTimestamps();
    }

    public function approvedUsers(): BelongsToMany
    {
        return $this->users()->wherePivotNotNull('approved_at');
    }

    public function pendingUsers(): BelongsToMany
    {
        return $this->users()->wherePivotNull('approved_at');
    }

    /**
     * Always present - created together with the team, so unsaved defaults never leak.
     */
    public function settings(): HasOne
    {
        return $this->hasOne(TeamSetting::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function absences(): HasMany
    {
        return $this->hasMany(Absence::class);
    }

    public function weekLocks(): HasMany
    {
        return $this->hasMany(WeekLock::class);
    }

    /**
     * Cached across requests (see CACHING.md, key `team:{id}:settings`) - read on essentially
     * every request (week rendering, absence create/delete checks) and changed only via the
     * team settings page. `TeamSetting`'s own `saved` hook busts this automatically.
     */
    public function cachedSettings(): ?TeamSetting
    {
        return Cache::remember("team:{$this->id}:settings", now()->addMinutes(10), fn () => $this->settings);
    }

    /** 0 = Monday … 6 = Sunday. */
    public function weekStartDay(): int
    {
        return $this->cachedSettings()?->week_start_day ?? TeamSetting::DEFAULT_WEEK_START_DAY;
    }

    /** How many weeks past the current one the calendar may navigate. */
    public function weekLookahead(): int
    {
        return $this->cachedSettings()?->week_lookahead ?? TeamSetting::DEFAULT_WEEK_LOOKAHEAD;
    }

    /** Days before the first absent day that a submission is still accepted. */
    public function absenceDeadlineDays(): int
    {
        return $this->cachedSettings()?->absence_deadline_days ?? TeamSetting::DEFAULT_ABSENCE_DEADLINE_DAYS;
    }

    /** Number of days after an inactive absence ends where the owner may still delete it. 0 = no limit. */
    public function staleAbsenceDeletionDays(): int
    {
        return $this->cachedSettings()?->stale_absence_deletion_days ?? TeamSetting::DEFAULT_STALE_ABSENCE_DELETION_DAYS;
    }

    /**
     * What one worked day is worth per weekday, Monday-indexed. Feeds FairnessService.
     *
     * @return list<float>
     */
    public function fairnessDayWeights(): array
    {
        $weights = $this->cachedSettings()?->fairness_day_weights;

        // A stored array of the wrong length would silently misprice part of the week, so a
        // partial row falls back to the default rather than being padded.
        return is_array($weights) && count($weights) === 7
            ? array_map('floatval', array_values($weights))
            : TeamSetting::DEFAULT_FAIRNESS_DAY_WEIGHTS;
    }

    /** How many weeks of history the fairness ranking counts. */
    public function fairnessWindowWeeks(): int
    {
        return $this->cachedSettings()?->fairness_window_weeks ?? TeamSetting::DEFAULT_FAIRNESS_WINDOW_WEEKS;
    }

    /**
     * Everybody in this cinema who holds $role and can actually use it: approved membership and
     * an active account.
     *
     * "Can actually use it" is the point - a cinema whose only hlavný manažér is blocked or
     * unapproved has nobody who can administer it, which is the state isLastHeadManager() exists
     * to prevent. Counting the role alone would call that team covered.
     *
     * Brackets the registrar the way User::hasPermissionInTeam() does, so the answer is about
     * *this* team rather than whichever one the request happens to be acting in.
     *
     * @return EloquentCollection<int, User>
     */
    public function activeHoldersOf(Role $role): EloquentCollection
    {
        $originalTeamId = getPermissionsTeamId();
        setPermissionsTeamId($this->getKey());

        try {
            return User::role($role->value)
                ->where('users.is_active', true)
                ->whereHas('teams', fn (Builder $query) => $query
                    ->where('teams.id', $this->getKey())
                    ->whereNotNull('team_user.approved_at'))
                ->get();
        } finally {
            setPermissionsTeamId($originalTeamId);
        }
    }
}
