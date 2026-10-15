<?php

namespace App\Providers;

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SetCurrentTeam;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Pulse\Facades\Pulse;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->persistTenantMiddlewareThroughLivewire();

        Builder::macro('search', function ($field, $string) {
            return $string ? $this->where($field, 'like', '%'.$string.'%') : $this;
        });

        // A session ended by a password change elsewhere goes to the login page, like any other
        // expired login - without this, AuthenticateSession answers with a bare 401.
        AuthenticateSession::redirectUsing(fn (Request $request): ?string => $request->expectsJson() ? null : route('login'));

        // Pulse spans every cinema (names, e-mails, errors), so it is for the developer only - not
        // for any cinema's manager.
        Gate::define('viewPulse', fn (User $user): bool => in_array(mb_strtolower($user->email), config('pulse.admins'), true));

        Pulse::user(fn ($user) => [
            'name' => $user,
            'extra' => $user->email,
            'avatar' => null,
        ]);
    }

    /**
     * Livewire's update endpoint is its own route, so it only re-runs the middleware that has
     * been declared persistent - auth is on that list by default, our two are not.
     *
     * Without this, the first page load is scoped to a team but every subsequent Livewire
     * action runs with no team in the registrar: `BelongsToTeam` stops filtering and
     * `app(Team::class)` builds an empty model. That fails open - a component would read and
     * write across every cinema - so it has to be registered explicitly.
     */
    private function persistTenantMiddlewareThroughLivewire(): void
    {
        Livewire::addPersistentMiddleware([
            EnsureUserIsActive::class,
            SetCurrentTeam::class,
        ]);
    }
}
