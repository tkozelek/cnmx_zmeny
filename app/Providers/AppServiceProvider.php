<?php

namespace App\Providers;

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SetCurrentTeam;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
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

    /**
     * Livewire's update endpoint is its own route, so it only re-runs the middleware that has
     * been declared persistent — auth is on that list by default, our two are not.
     *
     * Without this, the first page load is scoped to a team but every subsequent Livewire
     * action runs with no team in the registrar: `BelongsToTeam` stops filtering and
     * `app(Team::class)` builds an empty model. That fails open — a component would read and
     * write across every cinema — so it has to be registered explicitly.
     */
    /**
     * Record which cinema an activity belongs to, and which day of the plan it touched.
     *
     * Copied onto the activity rather than read back off the subject, because the edits most worth
     * auditing are the deletions — by the time anyone reads the history, that row is gone. The
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
            ], fn (mixed $value): bool => $value !== null));
        });
    }

    private function persistTenantMiddlewareThroughLivewire(): void
    {
        Livewire::addPersistentMiddleware([
            EnsureUserIsActive::class,
            SetCurrentTeam::class,
        ]);
    }
}
