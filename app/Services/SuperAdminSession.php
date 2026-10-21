<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\Auth\Factory as AuthFactory;

/**
 * The flag alone grants nothing: the session must also be signed in to /platform (login + TOTP).
 */
class SuperAdminSession
{
    public const string GUARD = 'platform';

    public function __construct(private readonly AuthFactory $auth) {}

    public function isActive(User $user): bool
    {
        return $user->is_super_admin
            && $user->is_active
            && filled($user->getAppAuthenticationSecret())
            && $this->auth->guard(self::GUARD)->id() === $user->getKey();
    }

    public function enter(User $user, Team $team): void
    {
        $user->forceFill(['current_team_id' => $team->getKey()])->save();

        $this->auth->guard('web')->login($user);
    }
}
