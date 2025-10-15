<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    public $timestamps = false;

    protected $table = 'shifts';

    protected $fillable = [
        'date',
        'user_id',
        'start',
        'end',
        'break',
    ];

    protected $attributes = [
        'break' => 0,
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'start' => 'datetime:H:i',
        'end' => 'datetime:H:i',
        'break' => 'integer',
    ];
}
