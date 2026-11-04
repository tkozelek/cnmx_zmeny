<?php

namespace App\Http\Middleware;

use App\Logging\Fingerprint;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tags every log line of a request (and the jobs it queues) with one request id, so an error
 * can be traced back through everything that happened around it. The id is also returned in
 * the `X-Request-Id` header.
 */
class AddRequestLogContext
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = (string) Str::uuid();

        Context::add([
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => '/'.ltrim($request->path(), '/'),
            'ip_hash' => Fingerprint::of($request->ip()),
        ]);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
