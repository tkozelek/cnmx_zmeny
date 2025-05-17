<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, int $role): Response
    {
        if (! auth()->check()) {
            return redirect('/prihlasenie')->with(['message' => 'Musis sa prihlasit.']);
        }

        if ($request->user()->hasRole($role)) {
            return $next($request);
        }

        return abort(403, 'Vstup zakazany');
    }
}
