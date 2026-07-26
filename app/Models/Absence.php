<?php

namespace App\Models;

use App\Enums\AbsenceStatus;
use App\Traits\BelongsToTeam;
use Carbon\CarbonInterface;
use Database\Factories\AbsenceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * When an employee cannot work. Replaces the legacy `user_holidays`.
 *
 * Single day  -> date_from == date_to, day_of_week null
 * Multi-day   -> a range,              day_of_week null
 * Recurring   -> a range + day_of_week ("every Tuesday until X")
 */
class Absence extends Model
{
    use BelongsToTeam;

    /** @use HasFactory<AbsenceFactory> */
    use HasFactory;

    /**
     * Sentinel for an open-ended absence. `date_to` is NOT NULL so the overlap query
     * stays a plain sargable range scan — "OR date_to IS NULL" cannot use the index.
     */
    public const FOREVER = '9999-12-31';

    protected $fillable = [
        'team_id',
        'user_id',
        'date_from',
        'date_to',
        'day_of_week',
        'reason',
        'status',
    ];

    protected function casts(): array
    {
        return [
            // See WeekLock: a plain `date` cast would store a time component too, which
            // breaks the overlap comparisons against Y-m-d strings.
            'date_from' => 'date:Y-m-d',
            'date_to' => 'date:Y-m-d',
            'day_of_week' => 'integer',
            'status' => AbsenceStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Absences touching the given range.
     */
    public function scopeOverlapping(Builder $query, CarbonInterface $from, CarbonInterface $to): Builder
    {
        return $query->where('status', '!=', AbsenceStatus::Cancelled->value)
            ->where('date_from', '<=', $to->toDateString())
            ->where('date_to', '>=', $from->toDateString());
    }

    /** Not over yet. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', AbsenceStatus::Active->value)
            ->where('date_to', '>=', now()->toDateString());
    }

    /** Already finished — either it ran out or it was ended early / cancelled. */
    public function scopePast(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('date_to', '<', now()->toDateString())
                ->orWhere('status', AbsenceStatus::Cancelled->value);
        });
    }

    /** Not cancelled, and its date range hasn't run out yet. */
    public function isActive(): bool
    {
        if ($this->status === AbsenceStatus::Cancelled) {
            return false;
        }

        return $this->date_to->gte(now()->startOfDay());
    }

    public function isRecurring(): bool
    {
        return $this->day_of_week !== null;
    }

    public function isOpenEnded(): bool
    {
        return $this->date_to->toDateString() === self::FOREVER;
    }

    /** Whether this absence actually covers the given date. */
    public function covers(CarbonInterface $date): bool
    {
        if ($date->lt($this->date_from) || $date->gt($this->date_to)) {
            return false;
        }

        // day_of_week is 0=Mon..6=Sun; Carbon's ISO day is 1=Mon..7=Sun.
        return ! $this->isRecurring() || $this->day_of_week === $date->dayOfWeekIso - 1;
    }
}
