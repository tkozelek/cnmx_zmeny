<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAllowed
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect('/prihlasenie')->with(['error' => 'Musis sa prihlasit.']);
        }

        if ($request->user()->hasRole(config('constants.roles.admin')) || $request->user()->hasRole(config('constants.roles.brigadnik'))) {
            return $next($request);
        }

        if ($request->user()->hasRole(config('constants.roles.zablokovany'))) {
            $this->logout($request);

            return redirect('/prihlasenie')->with(['error' => 'Účet je zablokovaný.']);
        }
        if ($request->user()->hasRole(config('constants.roles.neovereny'))) {
            $this->logout($request);

            return redirect('/prihlasenie')->with(['error' => 'Ešte si nebol/a overený. Viac info v sekcií pomoc.']);
        }

        abort(403, 'Vstup zakazany. Musis byt potvrdeny.');
    }

    private function logout(Request $request)
    {
        auth()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
