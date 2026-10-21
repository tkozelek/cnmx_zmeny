<?php

namespace App\Providers;

use App\Enums\TeamFeature;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SetCurrentTeam;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;
use Laravel\Pennant\Middleware\EnsureFeaturesAreActive;
use Laravel\Pulse\Facades\Pulse;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->persistTenantMiddlewareThroughLivewire();
        $this->stampActivityWithTeamAndDay();
        $this->scopeFeaturesToTheCurrentTeam();

        Builder::macro('search', function ($field, $string) {
            return $string ? $this->where($field, 'like', '%'.$string.'%') : $this;
        });

        // A session ended by a password change elsewhere goes to the login page, like any other
        // expired login - without this, AuthenticateSession answers with a bare 401.
        AuthenticateSession::redirectUsing(fn (Request $request): ?string => $request->expectsJson() ? null : route('login'));

        // Pulse spans every cinema (names, e-mails, errors), so it is for the developer only - not
        // for any cinema's manager.
        Gate::define('accessPlatform', fn (User $user): bool => $user->actsAsSuperAdmin());

        Gate::define('viewPulse', fn (User $user): bool => $user->actsAsSuperAdmin() || in_array(mb_strtolower($user->email), config('pulse.admins'), true));

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
            EnsureFeaturesAreActive::class,
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
