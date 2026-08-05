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
     * @param  'optional'|null  $mode  Public routes still render the authenticated navigation,
     *                                 whose entries are gated on team-scoped permissions. They
     *                                 pass `optional` to get the team established when there is
     *                                 one, while staying reachable for guests and for a user who
     *                                 belongs to no cinema yet.
     */
    public function handle(Request $request, Closure $next, ?string $mode = null): Response
    {
        $optional = $mode === 'optional';

        $user = $request->user();

        if (! $user) {
            abort_if(! $optional, 403, 'Nepatríš do žiadneho kina.');

            return $next($request);
        }

        $team = $user->currentTeam;

        // A stale or unset current_team_id falls back to any approved membership, and the
        // choice is persisted so it survives the next request.
        if (! $team || ! $user->isApprovedIn($team)) {
            $team = $user->approvedTeams()->first();

            if ($team) {
                $user->forceFill(['current_team_id' => $team->getKey()])->save();
            }
        }

        if (! $team instanceof Team) {
            abort_if(! $optional, 403, 'Nepatríš do žiadneho kina.');

            return $next($request);
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($team->getKey());

        // Loaded once here so views and services do not each re-query the settings row.
        $team->loadMissing('settings');

        // Bound in the container so anything downstream - controllers, form requests,
        // services - can just type-hint Team and get the tenant, with no extra plumbing.
        app()->instance(Team::class, $team);
        View::share('currentTeam', $team);

        return $next($request);
    }
}
