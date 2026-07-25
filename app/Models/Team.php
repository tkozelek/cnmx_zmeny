<?php

namespace App\Models;

use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
     * Always present — created together with the team, so unsaved defaults never leak.
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

    /** 0 = Monday … 6 = Sunday. */
    public function weekStartDay(): int
    {
        return $this->settings?->week_start_day ?? TeamSetting::DEFAULT_WEEK_START_DAY;
    }

    /** How many weeks past the current one the calendar may navigate. */
    public function weekLookahead(): int
    {
        return $this->settings?->week_lookahead ?? TeamSetting::DEFAULT_WEEK_LOOKAHEAD;
    }

    /** Hours before the first absent day that a submission is still accepted. */
    public function absenceDeadlineHours(): int
    {
        return $this->settings?->absence_deadline_hours ?? TeamSetting::DEFAULT_ABSENCE_DEADLINE_HOURS;
    }
}
