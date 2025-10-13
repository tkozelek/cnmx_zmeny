<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Pulse\Facades\Pulse;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application Services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application Services.
     */
    public function boot(): void
    {
        Builder::macro('search', function ($field, $string) {
            return $string ? $this->where($field, 'like', '%'.$string.'%') : $this;
        });

        Gate::define('viewPulse', function (User $user) {
            return $user->id == 1;
        });

        Pulse::user(fn ($user) => [
            'name' => $user,
            'extra' => $user->email,
            'avatar' => null,
        ]);
    }
}
