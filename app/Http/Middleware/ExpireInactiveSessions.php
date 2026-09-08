<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Request-based authenticated inactivity expiry.
 *
 * **Off by default.** Does nothing until a System Administrator enables
 * `system_settings.authenticated_inactivity_expiry_enabled` — so shipping
 * this middleware changes no session behaviour on its own.
 *
 * Once enabled: a genuine authenticated request stamps `last_activity_at`
 * in the session. If the NEXT genuine authenticated request arrives more
 * than `Setting::inactivityTimeoutMinutes()` later, the session is torn
 * down and the user must sign in again.
 *
 * There is no heartbeat — nothing here keeps a session alive on its own,
 * which is the whole point (server-load control). Public scoreboard /
 * tally polling is explicitly NOT genuine activity: those routes are
 * skipped entirely, so a background poll can neither extend nor collapse
 * an authenticated session.
 *
 * "remember me" is handled by `Auth::logout()`, which cycles the
 * remember token and forgets the recaller cookie — so an expired session
 * cannot be silently re-authenticated on the next request.
 */
class ExpireInactiveSessions
{
    private const ACTIVITY_KEY = 'last_activity_at';

    /**
     * Routes whose traffic must not count as user activity — the public
     * poll endpoints and the (manual-refresh) public medal tally. A
     * logged-in user idly leaving one of these open in a tab is exactly
     * the "no genuine activity" case.
     */
    private const PASSIVE_ROUTES = [
        'public.scoreboard.poll',
        'public.sport-portal.poll',
        'public.tally',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('web')->guest() || $request->routeIs(self::PASSIVE_ROUTES)) {
            return $next($request);
        }

        $controls = Setting::loadControls();

        // Off by default — until a System Administrator switches it on,
        // this middleware is a no-op and does not even stamp the marker.
        if (! $controls['inactivity_expiry_enabled']) {
            return $next($request);
        }

        $timeoutSeconds = $controls['inactivity_timeout_minutes'] * 60;
        $lastActivity = $request->session()->get(self::ACTIVITY_KEY);
        $now = time();

        if ($lastActivity !== null && ($now - (int) $lastActivity) > $timeoutSeconds) {
            return $this->expire($request);
        }

        $request->session()->put(self::ACTIVITY_KEY, $now);

        return $next($request);
    }

    private function expire(Request $request): Response
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $message = __('Your session expired due to inactivity. Please sign in again.');

        if ($request->header('X-Inertia')) {
            return Inertia::location(route('login'));
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 401);
        }

        return redirect()->guest(route('login'))->with('status', $message);
    }
}
