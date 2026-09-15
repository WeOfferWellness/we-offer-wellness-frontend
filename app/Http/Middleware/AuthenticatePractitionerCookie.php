<?php

namespace App\Http\Middleware;

use App\Services\CrossDomainPractitionerAuthService;
use Closure;
use Illuminate\Http\Request;

class AuthenticatePractitionerCookie
{
    public function handle(Request $request, Closure $next): mixed
    {
        app(CrossDomainPractitionerAuthService::class)->authenticate($request);

        return $next($request);
    }
}
