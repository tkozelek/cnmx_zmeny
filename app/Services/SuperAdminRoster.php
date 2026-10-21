<?php

namespace App\Services;

use App\Models\User;
use DomainException;

class SuperAdminRoster
{
    public function grant(User $user): void
    {
        if (! $user->is_active || ! $user->hasVerifiedEmail()) {
            throw new DomainException('Super admin musí mať aktívny účet s overeným e-mailom.');
        }

        $user->forceFill(['is_super_admin' => true])->save();
    }

    public function revoke(User $user, ?User $actor = null): void
    {
        if ($actor?->is($user)) {
            throw new DomainException('Sám sebe super admina odobrať nemôžeš.');
        }

        if (User::where('is_super_admin', true)->whereKeyNot($user->getKey())->doesntExist()) {
            throw new DomainException('Toto je posledný super admin platformy.');
        }

        $user->forceFill([
            'is_super_admin' => false,
            'app_authentication_secret' => null,
            'app_authentication_recovery_codes' => null,
        ])->save();
    }

    /** For a lost authenticator: they set up a new one at their next /platform login. */
    public function resetMultiFactor(User $user): void
    {
        $user->forceFill([
            'app_authentication_secret' => null,
            'app_authentication_recovery_codes' => null,
        ])->save();
    }
}
