<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SuperAdminRoster;
use DomainException;
use Illuminate\Console\Command;

class SuperAdmin extends Command
{
    protected $signature = 'app:super-admin {email : E-mail of an existing, verified account} {--revoke : Take the role away instead}';

    protected $description = 'Grant or revoke the platform super admin role';

    public function handle(SuperAdminRoster $roster): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->components->error('Účet s týmto e-mailom neexistuje.');

            return self::FAILURE;
        }

        try {
            $this->option('revoke') ? $roster->revoke($user) : $roster->grant($user);
        } catch (DomainException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info($this->option('revoke')
            ? "{$user->email} už nie je super admin."
            : "{$user->email} je super admin. Pri prvom prihlásení na /platform si nastaví overovanie aplikáciou.");

        return self::SUCCESS;
    }
}
