<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceCanonicalHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower((string) $request->getHost());
        $publicHosts = ['weofferwellness.co.uk', 'www.weofferwellness.co.uk'];

        if (! in_array($host, $publicHosts, true)) {
            return $next($request);
        }

        $forwardedProtoHeader = (string) $request->header('X-Forwarded-Proto', '');
        $forwardedProto = strtolower(trim(explode(',', $forwardedProtoHeader)[0] ?? ''));
        $isHttps = $request->isSecure() || $forwardedProto === 'https';

        if ($host === 'www.weofferwellness.co.uk' && $isHttps) {
            return $next($request);
        }

        return redirect()->away(
            'https://www.weofferwellness.co.uk'.$request->getRequestUri(),
            301
        );
    }
}
