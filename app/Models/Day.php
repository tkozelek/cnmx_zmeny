<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Day extends Model
{

    protected $dateFormat = 'Y-m-d';

    public $timestamps = false;

    protected $casts = [
        'date' => 'date',
    ];

    protected $dates = ['date'];

    public function users() {
        return $this->belongsToMany(User::class, 'user_days', 'id_day', 'id_user')->withPivot("popis");
    }

    public function week() {
        return $this->belongsTo(Week::class, "id_week");
    }
}
