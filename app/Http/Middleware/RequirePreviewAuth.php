<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A shared HTTP basic-auth gate in front of a non-production copy (beta holds real staff data).
 * Off unless PREVIEW_AUTH_USER and PREVIEW_AUTH_PASSWORD are both set, so production never has it.
 */
class RequirePreviewAuth
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = (string) config('app.preview_auth.user');
        $password = (string) config('app.preview_auth.password');

        if ($user === '' || $password === '') {
            return $next($request);
        }

        if (hash_equals($user, (string) $request->getUser()) && hash_equals($password, (string) $request->getPassword())) {
            return $next($request);
        }

        return response('Prístup len s heslom.', 401, ['WWW-Authenticate' => 'Basic realm="Cinemax Zmeny beta", charset="UTF-8"']);
    }
}
