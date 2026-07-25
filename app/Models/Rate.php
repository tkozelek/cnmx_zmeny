<?php

namespace App\Models;

use App\Traits\BelongsToTeam;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One user's hourly pay rates at one cinema. Read together with `Shift` for payroll.
 */
class Rate extends Model
{
    use BelongsToTeam;

    protected $fillable = [
        'team_id',
        'user_id',
        'weekday',
        'saturday',
        'sunday',
        'break_deduction',
    ];

    protected function casts(): array
    {
        return [
            'weekday' => 'decimal:2',
            'saturday' => 'decimal:2',
            'sunday' => 'decimal:2',
            'break_deduction' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
