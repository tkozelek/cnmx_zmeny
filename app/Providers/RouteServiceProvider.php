<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Per form, not one bucket per IP: Laravel's `throttle:6,1` keys guests on the IP alone,
        // so a few bad logins also locked the same IP out of registration and password reset.
        RateLimiter::for('guest-form', fn (Request $request) => [
            Limit::perMinute(10)->by($request->route()?->getName().'|ip:'.$request->ip()),
            Limit::perMinute(5)->by($request->route()?->getName().'|email:'.mb_strtolower((string) $request->input('email'))),
        ]);

        // Forms that send an e-mail to the typed address: capped per recipient, so nobody can
        // flood someone's inbox by rotating IPs.
        RateLimiter::for('mail-recipient', fn (Request $request) => [
            Limit::perMinute(6)->by($request->route()?->getName().'|ip:'.$request->ip()),
            Limit::perHour(3)->by($request->route()?->getName().'|email:'.mb_strtolower((string) $request->input('email'))),
        ]);

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
