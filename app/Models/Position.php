<?php

namespace App\Models;

use App\Traits\BelongsToTeam;
use Database\Factories\PositionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A named job at one cinema (Uvádzač, Bufet, Pokladňa …). Replaces the legacy
 * free-text `user_days.popis`.
 */
class Position extends Model
{
    use BelongsToTeam;

    /** @use HasFactory<PositionFactory> */
    use HasFactory;

    protected $fillable = [
        'team_id',
        'position_group_id',
        'name',
        'code',
        'color',
        'is_manager',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_manager' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    /** Which heading this position prints under. Null for an ungrouped position, which sorts last. */
    public function group(): BelongsTo
    {
        return $this->belongsTo(PositionGroup::class, 'position_group_id');
    }

    /**
     * Where this position sorts among its peers: its group's order first, then its own.
     *
     * Ungrouped positions go after every group rather than jumbling in among them — a cinema
     * that only files some of its positions still gets a tidy sheet.
     */
    public function groupOrder(): int
    {
        return $this->group?->sort_order ?? PHP_INT_MAX;
    }

    public function groupName(): ?string
    {
        return $this->group?->name;
    }

    /**
     * Positions offered for new signups, in the order the cinema wants them shown.
     *
     * Grouped first, so the "add position" dropdown reads in the same order as the printed sheet.
     * The group is eager-loaded because every caller goes on to ask for its name or order.
     */
    public function scopeSelectable(Builder $query): Builder
    {
        return $query->with('group')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function label(): string
    {
        return $this->code ?: $this->name;
    }
}
