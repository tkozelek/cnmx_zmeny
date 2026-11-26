<?php

namespace App\Providers;

use App\Enums\TeamFeature;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SetCurrentTeam;
use App\Models\Team;
use App\Models\User;
use App\Providers\Filament\PlatformPanelProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\QueryBuilder\QueryBuilderServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;
use Laravel\Pennant\Middleware\EnsureFeaturesAreActive;
use Laravel\Pulse\Facades\Pulse;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Filament's packages are excluded from package discovery (composer.json `dont-discover`) and
     * registered here, together with the /platform panel.
     *
     * @var list<class-string<ServiceProvider>>
     */
    private const array FILAMENT_PROVIDERS = [
        ActionsServiceProvider::class,
        FilamentServiceProvider::class,
        FormsServiceProvider::class,
        InfolistsServiceProvider::class,
        NotificationsServiceProvider::class,
        QueryBuilderServiceProvider::class,
        SchemasServiceProvider::class,
        SupportServiceProvider::class,
        TablesServiceProvider::class,
        WidgetsServiceProvider::class,
        PlatformPanelProvider::class,
    ];

    public function register(): void
    {
        if ($this->requestNeedsFilament()) {
            foreach (self::FILAMENT_PROVIDERS as $provider) {
                $this->app->register($provider);
            }
        }
    }

    /**
     * Filament registers ~200 classes, which every request used to load although only the
     * /platform panel needs them. On the NFS-backed hosting a request whose files are not in the
     * cache reads each one from the network, so loading Filament everywhere made those requests
     * noticeably slower.
     *
     * Only skipped once routes are cached: without the cache Filament's providers are what
     * registers the panel's routes, and the member layout links to the panel by route name.
     * Console runs (artisan, queue worker, tests) always load it.
     */
    private function requestNeedsFilament(): bool
    {
        if ($this->app->runningInConsole() || ! $this->app->routesAreCached()) {
            return true;
        }

        $request = $this->app->make('request');

        if ($request->is('platform', 'platform/*', 'filament/*')) {
            return true;
        }

        // A Livewire action posts to the shared update endpoint; each component's snapshot
        // records the page it was rendered on.
        if (! $request->isMethod('POST') || ! $request->is('livewire*/update')) {
            return false;
        }

        foreach ((array) $request->json('components', []) as $component) {
            $snapshot = json_decode((string) ($component['snapshot'] ?? ''), true);
            $path = (string) ($snapshot['memo']['path'] ?? '');

            if ($path === 'platform' || str_starts_with($path, 'platform/')) {
                return true;
            }
        }

        return false;
    }

    public function boot(): void
    {
        $this->persistTenantMiddlewareThroughLivewire();
        $this->stampActivityWithTeamAndDay();
        $this->scopeFeaturesToTheCurrentTeam();

        // Behind the hosting's load balancer the request itself may arrive as plain http; mail links,
        // redirects and signed URLs must still be https on the real domain.
        if ($this->app->isProduction()) {
            URL::forceRootUrl(config('app.url'));
            URL::forceScheme('https');
        }

        Builder::macro('search', function ($field, $string) {
            return $string ? $this->where($field, 'like', '%'.$string.'%') : $this;
        });

        // A session ended by a password change elsewhere goes to the login page, like any other
        // expired login - without this, AuthenticateSession answers with a bare 401.
        AuthenticateSession::redirectUsing(fn (Request $request): ?string => $request->expectsJson() ? null : route('login'));

        // Pulse spans every cinema (names, e-mails, errors), so it is for the developer only - not
        // for any cinema's manager.
        Gate::define('accessPlatform', fn (User $user): bool => $user->actsAsSuperAdmin());

        // /pulse sits outside the `tenant` group, so EnsureUserIsActive never runs there - check it here.
        Gate::define('viewPulse', fn (User $user): bool => $user->actsAsSuperAdmin()
            || ($user->is_active && $user->hasVerifiedEmail() && in_array(mb_strtolower($user->email), config('pulse.admins'), true)));

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
     *
     * ThrottleRequests carries the page's `throttle:member-writes` over too, so sign-ups and
     * approvals made through components count against the same write ceiling as forms.
     */
    private function persistTenantMiddlewareThroughLivewire(): void
    {
        Livewire::addPersistentMiddleware([
            EnsureUserIsActive::class,
            SetCurrentTeam::class,
            EnsureFeaturesAreActive::class,
            ThrottleRequests::class,
        ]);
    }

    /** No team bound (guests, console, the panel) means null scope: every module reads as off. */
    private function scopeFeaturesToTheCurrentTeam(): void
    {
        foreach (TeamFeature::cases() as $feature) {
            Feature::define($feature, fn (?Team $team): bool => false);
        }

        Feature::resolveScopeUsing(fn (): ?Team => app()->bound(Team::class) ? app(Team::class) : null);

        EnsureFeaturesAreActive::whenInactive(fn () => abort(404));
    }

    /**
     * Record which cinema an activity belongs to, and which day of the plan it touched.
     *
     * Copied onto the activity rather than read back off the subject, because the edits most worth
     * auditing are the deletions - by the time anyone reads the history, that row is gone. The
     * subject is still in memory here, which is the whole reason this hangs off `creating`.
     *
     * Registered once for every logged model rather than per model: activitylog v5 has no
     * per-model hook into `properties`, and team scoping is an application-wide concern anyway.
     */
    private function stampActivityWithTeamAndDay(): void
    {
        Activity::creating(function (Activity $activity): void {
            $subject = $activity->subject;

            if (! $subject instanceof Model) {
                return;
            }

            $activity->properties = collect($activity->properties ?? [])->merge(array_filter([
                'team_id' => $subject->team_id ?? null,
                // Y-m-d, so filtering a week is one whereBetween. Null on a model with no day.
                'date' => $subject->date?->toDateString(),
                // Who the row is about. Not derivable from the diff: logOnlyDirty strips every
                // column that did not change, so moving somebody between slots records the slot
                // and not the person - and a deleted row has no subject left to ask afterwards.
                'user_id' => $subject->user_id ?? null,
            ], fn (mixed $value): bool => $value !== null));
        });
    }
}
