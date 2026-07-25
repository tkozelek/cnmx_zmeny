<?php

namespace App\Http\Middleware;

use App\Models\Team;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Establishes which team the request acts in.
 *
 * The answer is stored in Spatie's registrar rather than in a second, parallel notion of
 * "current team": Spatie needs it anyway to resolve per-team roles, and `BelongsToTeam`
 * reads it straight back out. One source of truth.
 */
class SetCurrentTeam
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $team = $user->currentTeam;

        // A stale or unset current_team_id falls back to any approved membership, and the
        // choice is persisted so it survives the next request.
        if (! $team || ! $user->isApprovedIn($team)) {
            $team = $user->teams()->wherePivotNotNull('approved_at')->first();

            if ($team) {
                $user->forceFill(['current_team_id' => $team->getKey()])->save();
            }
        }

        abort_if(! $team instanceof Team, 403, 'Nepatríš do žiadneho kina.');

        app(PermissionRegistrar::class)->setPermissionsTeamId($team->getKey());

        // Loaded once here so views and services do not each re-query the settings row.
        $team->loadMissing('settings');

        // Bound in the container so anything downstream — controllers, form requests,
        // services — can just type-hint Team and get the tenant, with no extra plumbing.
        app()->instance(Team::class, $team);
        View::share('currentTeam', $team);

        return $next($request);
    }
}
