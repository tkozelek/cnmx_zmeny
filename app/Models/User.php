<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\AddUserResetPassword;
use App\Notifications\ResetPasswordNotification;
use App\Traits\Loggable;
use Carbon\Carbon;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Password;

class User extends Authenticatable
{
    use CanResetPassword, HasFactory, Loggable, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $attributes = [
        'id_role' => 1,
    ];

    protected $fillable = [
        'name',
        'lastname',
        'username',
        'email',
        'password',
        'id_role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'id_role',
        'email',
        'id',
        'created_at',
        'updated_at',
        'last_login_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
    ];

    public function days()
    {
        return $this->belongsToMany(Day::class, 'user_days', 'id_user', 'id_day');
    }

    public function holidays()
    {
        return $this->hasMany(Holiday::class, 'id_user');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'id_role');
    }

    public function files()
    {
        return $this->hasMany(File::class, 'id_user');
    }

    public function hasRole($i): bool
    {
        return $this->id_role == $i;
    }

    public function isAdmin(): bool
    {
        return $this->id_role == config('constants.roles.admin');
    }

    public function getCreatedAtAttribute($date)
    {
        return Carbon::parse($date)->format('d.m.Y H:i:s');
    }

    public function getUpdatedAtAttribute($date)
    {
        return Carbon::parse($date)->format('d.m.Y H:i:s');
    }

    public function getDate($date)
    {
        return Carbon::parse($date)->format('d.m.Y H:i:s');
    }

    public function __toString()
    {
        return $this->lastname.' '.mb_substr($this->name, 0, 1).'.';
    }

    public function sendPasswordResetNotification($token): void
    {
        if (Password::getDefaultDriver() === 'add_user') {
            $this->notify(new AddUserResetPassword($token));
        } else {
            $this->notify(new ResetPasswordNotification($token));
        }
    }
}
