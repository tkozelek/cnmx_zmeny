<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The gate that used to be `EnsureUserIsAllowed`, minus the roles.
 *
 * The legacy "blocked" and "unverified" roles are not roles any more: blocked is
 * `users.is_active = false`, and unverified is having no `team_user` row with
 * `approved_at` set. Both mean the same thing here — log them back out with the reason.
 */
class EnsureUserIsActive
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user->is_active) {
            return $this->reject($request, 'Účet je zablokovaný.');
        }

        if (! $user->teams()->wherePivotNotNull('approved_at')->exists()) {
            return $this->reject($request, 'Ešte si nebol/a overený. Počkaj kým ťa administrátor overí.');
        }

        return $next($request);
    }

    private function reject(Request $request, string $error): Response
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with(['error' => $error]);
    }
}
