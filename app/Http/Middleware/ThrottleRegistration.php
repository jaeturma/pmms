<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * WP-06-03 finding: unlike every other Fortify auth action (login,
 * two-factor, passkeys), Fortify registers `POST /register` with no rate
 * limiter at all — unlimited automated account creation, and since
 * registration emails a verification link to whatever address is
 * submitted, mail-bombing of arbitrary addresses. Fortify has no
 * `limiters.registration` config hook to attach a named limiter
 * declaratively (the route is registered internally by the package, not
 * from this app's own route files), so it's enforced here directly against
 * the resolved route name instead, matching login's 5-per-minute bar.
 */
class ThrottleRegistration
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->routeIs('register.store')) {
            return $next($request);
        }

        $key = 'register:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            abort(429);
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }
}
