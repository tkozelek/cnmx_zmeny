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
 * One person signed up to work on one date.
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
        'date',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            // See WeekLock: a plain `date` cast would store a time component too, breaking
            // both the (team, user, date) unique key and date-range lookups.
            'date' => 'date:Y-m-d',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
