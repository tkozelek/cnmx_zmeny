<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rate extends Model
{
    protected $fillable = [
        'user_id',
        'weekday',
        'saturday',
        'sunday',
        'break' => 0
    ];

    protected $hidden = [
        'user_id',
        'created_at',
        'updated_at',
        'id'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
