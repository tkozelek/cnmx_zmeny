<?php

namespace App\Models;

use App\Traits\BelongsToTeam;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Hours actually worked. This is what payroll reads - `Assignment` is only the plan.
 */
class Shift extends Model
{
    use BelongsToTeam;

    protected $fillable = [
        'team_id',
        'user_id',
        'assignment_id',
        'position_id',
        'starts_at',
        'ends_at',
        'break_minutes',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'break_minutes' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    /**
     * Paid minutes. Every hours calculation must go through this.
     *
     * The DATETIME pair is the whole point of the column choice: a 21:00 -> 01:30 cinema
     * shift stored as a TIME pair gives end < start, so the legacy code computed negative
     * durations. Here `ends_at` is simply on the next day.
     */
    public function workedMinutes(): int
    {
        return (int) $this->starts_at->diffInMinutes($this->ends_at) - $this->break_minutes;
    }

    /** The day the shift is booked to - the day it started, not the day it ended. */
    public function payrollDate(): string
    {
        return $this->starts_at->toDateString();
    }

    public function scopeInMonth(Builder $query, int $year, int $month): Builder
    {
        return $query->whereYear('starts_at', $year)->whereMonth('starts_at', $month);
    }
}
