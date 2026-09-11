<?php

namespace App\Models;

use App\Notifications\AddUserResetPassword;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailAddress;
use App\Traits\Loggable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Traits\HasRoles;

/**
 * A person. Deliberately *not* team-scoped: one account can belong to several cinemas and
 * hold a different role in each, which is why `BelongsToTeam` is not used here and why
 * `email` is globally unique. Membership lives in `team_user`.
 *
 * The legacy `id_role` column is gone. Its two non-permission values became attributes:
 * blocked -> `is_active = false`, unverified -> `team_user.approved_at IS NULL`.
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use CanResetPassword, HasFactory, HasRoles, Loggable, Notifiable;

    protected $fillable = [
        'name',
        'lastname',
        'email',
        'password',
        'current_team_id',
        'is_active',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /** Every membership, approved or not. */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)
            ->withPivot('approved_at')
            ->withTimestamps();
    }

    /** The team this user is currently acting in. */
    public function currentTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'current_team_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function absences(): HasMany
    {
        return $this->hasMany(Absence::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    /** One rate row per team, and a user acts in one team at a time. */
    public function rate(): HasOne
    {
        return $this->hasOne(Rate::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    /**
     * Cached across requests (see CACHING.md, key `user:{id}:approved-teams`) - re-queried
     * independently by middleware, every permission check, the team switcher (rendered twice,
     * desktop + mobile nav), and once per row when rendering action columns, and membership
     * approval changes rarely. Also memoized on the instance so one request never hits the
     * cache store twice.
     */
    private ?Collection $approvedTeamsCache = null;

    /** @return Collection<int, Team> */
    public function approvedTeams(): Collection
    {
        if ($this->approvedTeamsCache !== null) {
            return $this->approvedTeamsCache;
        }

        $cacheKey = "user:{$this->id}:approved-teams";

        return $this->approvedTeamsCache = Cache::remember($cacheKey, now()->addMinutes(10), function () {
            return $this->teams()->wherePivotNotNull('approved_at')->get();
        });
    }

    /** Call after any change to this user's team_user.approved_at (accept/deny in the pending queue). */
    public function forgetApprovedTeamsCache(): void
    {
        Cache::forget("user:{$this->id}:approved-teams");
        $this->approvedTeamsCache = null;
    }

    public function isApprovedIn(Team $team): bool
    {
        return $this->approvedTeams()->contains(fn (Team $t) => $t->getKey() === $team->getKey());
    }

    /**
     * Point the user at another of their teams. Refuses teams they are not approved in,
     * so a forged team id on the switch route cannot cross the tenant boundary.
     */
    public function switchTeam(Team $team): bool
    {
        if (! $this->isApprovedIn($team)) {
            return false;
        }

        $this->forceFill(['current_team_id' => $team->getKey()])->save();

        return true;
    }

    /**
     * Check whether the user has a specific permission in a given cinema team (or current active team).
     */
    public function hasPermissionInTeam(string $permission, ?Team $team = null): bool
    {
        $team = $team ?? $this->currentTeam;

        if (! $team || ! $this->isApprovedIn($team)) {
            return false;
        }

        $originalTeamId = getPermissionsTeamId();
        setPermissionsTeamId($team->id);

        try {
            return $this->hasPermissionTo($permission);
        } catch (PermissionDoesNotExist) {
            return false;
        } finally {
            setPermissionsTeamId($originalTeamId);
        }
    }

    /** Views print users directly: "Kozelek T." */
    public function __toString(): string
    {
        return $this->lastname.' '.mb_substr($this->name, 0, 1).'.';
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailAddress);
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
