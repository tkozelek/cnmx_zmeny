<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Models\roles;
use App\Policies\rolesPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
        roles::class => rolesPolicy::class,
    ];

    /**
     * Register any authentication / authorization Services.
     */
    public function boot(): void
    {
        //
    }
}
