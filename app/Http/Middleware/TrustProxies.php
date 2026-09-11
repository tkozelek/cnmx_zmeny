<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * Was null - trusting nothing - which is wrong behind a reverse proxy: `X-Forwarded-Proto`
     * is then ignored, `$request->isSecure()` answers false on an HTTPS site, and the absolute
     * URLs in verification and reset e-mails are generated as http://.
     *
     * Env-driven rather than hardcoded to `*`, because trusting every client's X-Forwarded-*
     * headers is only safe when the app is genuinely unreachable except through the proxy. Set
     * TRUSTED_PROXIES to the proxy's address, or to `*` when the platform terminates TLS for you
     * and nothing else can reach the origin (Laravel Cloud, Cloudflare-only ingress).
     *
     * @var array<int, string>|string|null
     */
    protected $proxies = null;

    public function __construct()
    {
        $proxies = env('TRUSTED_PROXIES');

        $this->proxies = $proxies === null || $proxies === ''
            ? null
            : ($proxies === '*' ? '*' : array_map('trim', explode(',', (string) $proxies)));
    }

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
