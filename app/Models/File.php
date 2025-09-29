<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class File extends Model
{
    use HasFactory;

    protected $table = 'file_storage';

    protected $fillable = [
        'filename', 'size', 'mime_type', 'id_user', 'is_shown',
    ];

    protected $attributes = [
        'is_shown' => 0,
    ];

    public function scopeForWeek(Builder $query, Week $week)
    {
        return $query->where('id_week', $week->id);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        // ak je admin vratime cele query
        if ($user->hasRole(config('constants.roles.admin'))) {
            return $query;
        }

        // ak nie pridame iba visible subory
        return $query->where('is_shown', 1);
    }

    public function getCreatedAtAttribute($date)
    {
        return Carbon::parse($date)->format('d.m.Y H:i:s');
    }

    public function getUpdatedAtAttribute($date)
    {
        return Carbon::parse($date)->format('d.m.Y H:i:s');
    }
}
