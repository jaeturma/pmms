<?php

namespace App\Http\Middleware;

use App\Enums\MeetStatus;
use App\Enums\ScoringSessionStatus;
use App\Models\Division;
use App\Models\Meet;
use App\Models\ScoringSession;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $division = Division::current();
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user === null ? null : [
                    ...$user->toArray(),
                    'role_label' => $user->role->label(),
                ],
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'division' => [
                'type' => $division->type->value,
                'name' => $division->name,
                'areaLabel' => $division->areaLabel(),
            ],
            // Shell-level chrome (WP-08-03): the sidebar's persistent meet-context
            // card needs this on every authenticated page, not just the dashboard
            // — guarded to authenticated requests only so guest/public portal
            // page loads (no sidebar) never pay for the extra query.
            'currentMeet' => $user === null ? null : $this->currentMeet(),
            // Shell-level chrome (WP-08-07): the public portal header's nav
            // needs a meet to link Schedule/Results/Medal Tally into, and a
            // live-match count for its "Live now" indicator, on every public
            // page — guarded to guest requests only so authenticated page
            // loads never pay for the extra query.
            'publicNav' => $user === null ? $this->publicNav() : null,
        ];
    }

    /**
     * @return array{name: string, status_label: string, starts_at: string, ends_at: string, venue: string|null}|null
     */
    private function currentMeet(): ?array
    {
        $meet = Meet::query()
            ->where('status', '!=', MeetStatus::Completed->value)
            ->orderByDesc('starts_at')
            ->first();

        if ($meet === null) {
            return null;
        }

        return [
            'name' => $meet->name,
            'status_label' => $meet->status->label(),
            'starts_at' => $meet->starts_at->toDateString(),
            'ends_at' => $meet->ends_at->toDateString(),
            'venue' => $meet->venue,
        ];
    }

    /**
     * The one active meet (the same meet the landing page features), or —
     * when no meet is currently active — the most recently started
     * published meet, so guest navigation still has somewhere to point.
     * Scoped through `Meet::published()` since this feeds guest-facing
     * navigation, unlike the authenticated sidebar card.
     *
     * @return array{meetId: int, meetName: string, liveCount: int}|null
     */
    private function publicNav(): ?array
    {
        $meet = Meet::query()->published()->active()->first()
            ?? Meet::query()->published()->orderByDesc('starts_at')->first();

        if ($meet === null) {
            return null;
        }

        return [
            'meetId' => $meet->id,
            'meetName' => $meet->name,
            'liveCount' => ScoringSession::query()
                ->where('status', '!=', ScoringSessionStatus::Ended->value)
                ->whereHas('match', fn ($query) => $query->where('meet_id', $meet->id))
                ->count(),
        ];
    }
}
