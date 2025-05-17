<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Week extends Model
{
    protected $attributes = [
        'locked' => 0,
    ];

    protected $dates = ['date_from', 'date_to'];

    protected $casts = [
        'date_from' => 'date:Y-m-d',
        'date_to' => 'date:Y-m-d',
    ];

    public $timestamps = false;

    protected $dateFormat = 'Y-m-d';

    public function days()
    {
        return $this->hasMany(Day::class, 'id_week');
    }
}
