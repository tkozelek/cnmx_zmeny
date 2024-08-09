<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bug extends Model
{
    use HasFactory;

    protected $attributes = [
        'id_file' => null
    ];

    protected $dates = ['date_from', 'date_to'];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date'
    ];

    protected $fillable = [
        'subject', 'where', 'description', 'id_user', 'id_file'
    ];

    public function user() {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function file()
    {
        return $this->hasOne(File::class, 'id_file');
    }
}
