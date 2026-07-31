<?php

namespace App\Models;

use App\Traits\BelongsToTeam;
use Carbon\CarbonInterface;
use Database\Factories\AssignmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One person planned to work one position on one date.
 *
 * Replaces the whole legacy weeks -> days -> user_days chain: there is no Day row to
 * create first, signup keys straight off `date`.
 */
class Assignment extends Model
{
    use BelongsToTeam;

    /** @use HasFactory<AssignmentFactory> */
    use HasFactory;

    protected $fillable = [
        'team_id',
        'user_id',
        'position_id',
        'position_slot_id',
        'date',
        'start_time',
        'end_time',
        'note',
        'created_by',
    ];

    /**
     * `start_time` / `end_time` are deliberately not cast: they are TIME columns, and a
     * datetime cast would invent a date around them. Views format the raw H:i:s.
     */
    protected function casts(): array
    {
        return [
            // See WeekLock: a plain `date` cast would store a time component too, breaking
            // both the (user, date, position) unique key and date-range lookups.
            'date' => 'date:Y-m-d',
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

    /**
     * Which row of the day's plan this person stands in — null until a manager places them.
     *
     * Distinct from `position()` on purpose: a day can offer the same position several times
     * (bufet 1, bufet 2), so the slot says *which* bufet, while `position_id` says what the work
     * was and survives the slot being deleted.
     */
    public function positionSlot(): BelongsTo
    {
        return $this->belongsTo(PositionSlot::class);
    }

    /** The admin who assigned this person. Null when they signed themselves up. */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeBetweenDates(Builder $query, CarbonInterface $from, CarbonInterface $to): Builder
    {
        return $query->whereBetween('date', [$from->toDateString(), $to->toDateString()]);
    }

    public function isSelfSignup(): bool
    {
        return $this->created_by === null;
    }
}
