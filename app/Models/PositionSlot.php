<?php

namespace App\Models;

use App\Traits\BelongsToTeam;
use App\Traits\LogsRozpisActivity;
use Carbon\CarbonInterface;
use Database\Factories\PositionSlotFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * One position offered on one date - the drop target of the rozpis builder.
 *
 * A day may offer the same position repeatedly: three bufet rows on a Friday are three slots of
 * one Bufet position, not three positions. They are told apart by id and numbered for display by
 * their order within the day (see `label()`).
 *
 * A slot says nothing about who works it. Who works it lives on `Assignment.position_slot_id`;
 * placing someone also copies this slot's position and times onto their row. That keeps the
 * layout of a day (which is what the copy actions duplicate) separate from the people filling it.
 */
class PositionSlot extends Model
{
    use BelongsToTeam;

    /** @use HasFactory<PositionSlotFactory> */
    use HasFactory;

    use LogsRozpisActivity;

    protected $fillable = [
        'team_id',
        'position_id',
        'date',
        'sort_order',
        'start_time',
        'end_time',
    ];

    /**
     * `start_time` / `end_time` stay uncast for the same reason as `Assignment`: they are
     * TIME columns and a datetime cast would invent a date around them.
     */
    protected function casts(): array
    {
        return [
            // See Assignment/WeekLock: a plain `date` cast stores a time component too, which
            // breaks both the (team, date, position) unique key and date-range lookups.
            'date' => 'date:Y-m-d',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Times reach this model in two shapes: `H:i` from the time picker, `H:i:s` from a copied
     * slot or a factory. Normalised on write so the column never holds both - MySQL would coerce
     * silently, but SQLite stores whatever it is given, and an assignment copying the value out
     * would inherit the difference.
     */
    protected function startTime(): Attribute
    {
        return Attribute::set(fn (?string $time): ?string => self::normaliseTime($time));
    }

    protected function endTime(): Attribute
    {
        return Attribute::set(fn (?string $time): ?string => self::normaliseTime($time));
    }

    private static function normaliseTime(?string $time): ?string
    {
        if (blank($time)) {
            return null;
        }

        return preg_match('/^\d{1,2}:\d{2}$/', $time) === 1 ? $time.':00' : $time;
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /** The one person placed here, if any. Enforced one-per-slot by a unique index. */
    /**
     * A shift-leader row ("vedúci zmeny").
     *
     * These are never filled from the volunteer pool - a manager puts a leader on them by hand
     * (RozpisDay::placeLeader), and the AI never sees them at all. Asked in enough places that
     * the predicate belongs here rather than being re-spelled at each one.
     */
    public function isManagerSlot(): bool
    {
        return (bool) $this->position->is_manager;
    }

    public function occupant(): HasOne
    {
        return $this->hasOne(Assignment::class);
    }

    /**
     * "Bufet" alone, "Bufet 2" when the day offers more than one.
     *
     * $ordinal is the slot's 1-based position among that day's slots for the same position, which
     * only the caller holding the whole day can work out - passing it in beats a query per row.
     */
    public function label(?int $ordinal = null, bool $repeated = false): string
    {
        return $repeated
            ? $this->position->label().' '.$ordinal
            : $this->position->label();
    }

    public function scopeBetweenDates(Builder $query, CarbonInterface $from, CarbonInterface $to): Builder
    {
        return $query->whereBetween('date', [$from->toDateString(), $to->toDateString()]);
    }
}
