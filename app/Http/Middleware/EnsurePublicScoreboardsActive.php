<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * The public half of the "Suspend All Live Scoreboards" emergency lever
 * (System Administrator only, set via `system_settings`). When suspended
 * this short-circuits BEFORE the controller runs, so a public
 * live-scoreboard request never performs its expensive per-second
 * scoreboard build:
 *
 * - a `*.poll` route returns a tiny `{ suspended: true }` JSON body — the
 *   frontend polls see this, stop their interval and show the notice;
 * - a scoreboard PAGE route renders the lightweight
 *   `portal/scoreboard-suspended` screen.
 *
 * It is mounted ONLY on the public portal's scoreboard/live routes.
 * Authenticated ICT scoring (`scoring.*`, a wholly separate route tree
 * under `auth`) is never reached by this and keeps working normally.
 *
 * There is deliberately no auto-retry to detect resumption — a viewer
 * refreshes the page when they want to check again.
 */
class EnsurePublicScoreboardsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Setting::loadControls()['suspended']) {
            return $next($request);
        }

        if ($request->routeIs('public.scoreboard.poll')) {
            return response()->json(['suspended' => true, 'session' => null])
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
        }

        if ($request->routeIs('public.sport-portal.poll')) {
            return response()->json(['suspended' => true, 'liveNow' => null, 'otherLiveCount' => 0])
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
        }

        return Inertia::render('portal/scoreboard-suspended', [
            'message' => __('Live scoreboards are temporarily suspended. Please check again later.'),
        ])->toResponse($request);
    }
}
