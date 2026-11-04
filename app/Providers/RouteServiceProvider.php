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
        // A crafted `email[]=` would otherwise hit "Array to string conversion" here - a 500 before validation.
        $email = fn (Request $request): string => is_string($value = $request->input('email')) ? mb_strtolower($value) : '';

        // Signed-in writes: generous for real use, a ceiling for a script or a stuck double-submit loop.
        // Livewire replays this with the page's own method (GET), so its calls - sign-ups,
        // approvals, but also table searches and rozpis drags - get a separate, roomier ceiling.
        RateLimiter::for('member-writes', fn (Request $request) => match (true) {
            $request->hasHeader('X-Livewire') && $request->isMethodSafe() => Limit::perMinute(240)->by('livewire:'.($request->user()?->id ?: $request->ip())),
            $request->isMethodSafe() => Limit::none(),
            default => Limit::perMinute(60)->by('member:'.($request->user()?->id ?: $request->ip())),
        });

        // Each invite sends a mail to an address the manager typed in.
        RateLimiter::for('invites', fn (Request $request) => Limit::perHour(30)->by('invites:'.$request->user()?->id));

        // Per form, not one bucket per IP: Laravel's `throttle:6,1` keys guests on the IP alone,
        // so a few bad logins also locked the same IP out of registration and password reset.
        // The tight e-mail bucket is per IP too: keyed on the address alone, anyone who knew a
        // colleague's e-mail could keep them locked out. The IP ceiling leaves room for a whole
        // cinema logging in over one shared Wi-Fi at the start of a shift. The account-wide
        // ceiling stops password guessing from rotating IPs - generous enough that someone
        // hammering a colleague's address costs them a minute's wait, not a lockout.
        RateLimiter::for('guest-form', fn (Request $request) => [
            Limit::perMinute(30)->by($request->route()?->getName().'|ip:'.$request->ip()),
            Limit::perMinute(5)->by($request->route()?->getName().'|ip:'.$request->ip().'|email:'.$email($request)),
            Limit::perMinute(20)->by($request->route()?->getName().'|email:'.$email($request)),
        ]);

        // Forms that send an e-mail to the typed address: capped per recipient, so nobody can
        // flood someone's inbox by rotating IPs.
        RateLimiter::for('mail-recipient', fn (Request $request) => [
            Limit::perMinute(6)->by($request->route()?->getName().'|ip:'.$request->ip()),
            Limit::perHour(3)->by($request->route()?->getName().'|email:'.$email($request)),
        ]);

        $this->routes(function () {
            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
