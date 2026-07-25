<?php

namespace App\Models;

use App\Traits\BelongsToTeam;
use Database\Factories\PositionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    /** Positions offered for new signups, in the order the cinema wants them shown. */
    public function scopeSelectable(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    public function label(): string
    {
        return $this->code ?: $this->name;
    }
}
