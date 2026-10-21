<?php

namespace App\Models;

use App\Enums\TeamFeature;
use App\Notifications\AddUserResetPassword;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailAddress;
use App\Services\SuperAdminSession;
use App\Traits\Loggable;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
 *
 * `is_super_admin` is deliberately not fillable, and neither is `pending_email` - only
 * EmailChangeService moves an address through its confirmation steps.
 */
class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery, HasName, MustVerifyEmail
{
    use CanResetPassword, HasFactory, HasRoles, InteractsWithAppAuthentication, InteractsWithAppAuthenticationRecovery, Loggable, Notifiable;

    /** Roles live on `web`; without this Spatie would look them up under `platform` inside the panel. */
    protected string $guard_name = 'web';

    protected $fillable = [
        'name',
        'lastname',
        'email',
        'password',
        'current_team_id',
        'is_active',
        'email_verified_at',
        'last_login_at',
        'compact_week',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'pending_email_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_super_admin' => 'boolean',
            'email_verified_at' => 'datetime',
            'pending_email_approved_at' => 'datetime',
            'last_login_at' => 'datetime',
            'compact_week' => 'boolean',
        ];
    }

    /** Every membership, approved or not. */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)
            ->withPivot('approved_at')
            ->withTimestamps();
    }

    /** Whether the account is shared - a cinema may then change only its own membership, not the account. */
    public function belongsToSeveralTeams(): bool
    {
        return $this->teams()->count() > 1;
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
     * Belongs to this cinema at all - pending, approved or denied.
     *
     * Weaker than isApprovedIn() on purpose: it is the tenant boundary for acting *on* a user
     * (UserPolicy), where the pending queue is exactly the case that must stay reachable. Not
     * cached, because it gates writes and a stale answer here is a cross-tenant edit.
     */
    public function isMemberOf(Team $team): bool
    {
        // The users table asks this per row with `teams` already eager-loaded - no query per row then.
        return $this->relationLoaded('teams')
            ? $this->teams->contains($team)
            : $this->teams()->whereKey($team->getKey())->exists();
    }

    public function actsAsSuperAdmin(): bool
    {
        return app(SuperAdminSession::class)->isActive($this);
    }

    /**
     * Kept apart from approvedTeams(), which lists a cinema's people - a super admin is not one.
     *
     * @return Collection<int, Team>
     */
    public function accessibleTeams(): Collection
    {
        return $this->actsAsSuperAdmin()
            ? Team::query()->orderBy('name')->get()
            : $this->approvedTeams();
    }

    public function canEnter(Team $team): bool
    {
        return $this->actsAsSuperAdmin() || $this->isApprovedIn($team);
    }

    /**
     * Point the user at another of their teams. Refuses teams they may not enter, so a forged
     * team id on the switch route cannot cross the tenant boundary.
     */
    public function switchTeam(Team $team): bool
    {
        if (! $this->canEnter($team)) {
            return false;
        }

        $this->forceFill(['current_team_id' => $team->getKey()])->save();
        $this->setRelation('currentTeam', $team);

        return true;
    }

    /**
     * Check whether the user has a specific permission in a given cinema team (or current active team).
     *
     * Super admin bypass lives here, not in Gate::before, so policy rules (last head manager,
     * ownership, locks) still apply to them.
     */
    public function hasPermissionInTeam(string $permission, ?Team $team = null): bool
    {
        $team = $team ?? $this->currentTeam;

        if (! $team) {
            return false;
        }

        if ($this->actsAsSuperAdmin()) {
            return true;
        }

        if (! $this->isApprovedIn($team)) {
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

    /**
     * May turn this cinema's signups into a shift plan - the rozpis builder, its slots, its
     * exports and its AI draft all hang off this one permission, and off the cinema having the
     * rozpis module switched on at all.
     */
    public function canBuildRozpis(): bool
    {
        $team = app(Team::class);

        return $team->hasFeature(TeamFeature::Rozpis)
            && $this->hasPermissionInTeam('assignment.assign-position', $team);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'platform'
            && $this->is_super_admin
            && $this->is_active
            && $this->hasVerifiedEmail();
    }

    public function getFilamentName(): string
    {
        return trim($this->name.' '.$this->lastname);
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
