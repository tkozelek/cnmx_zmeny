<?php

namespace App\Models;

use App\Traits\BelongsToTeam;
use Database\Factories\PositionGroupFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A heading the cinema files its positions under: Bufet, Uvádzač, Manažment.
 *
 * Purely an ordering and presentation device - a group grants nothing and forbids nothing. It
 * decides which rows sit next to each other on the printed rozpis, and under which heading they
 * appear on screen.
 */
class PositionGroup extends Model
{
    use BelongsToTeam;

    /** @use HasFactory<PositionGroupFactory> */
    use HasFactory;

    protected $fillable = [
        'team_id',
        'name',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    /** The order the cinema arranged them in, which is the order the rozpis prints. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
