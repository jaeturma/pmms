<?php

namespace App\Http\Controllers;

use App\Enums\ScoringSessionStatus;
use App\Enums\SportPortalSlug;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\ScoringSession;
use App\Models\Sport;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public sports directory (`/sports`) — a browsable catalog of every
 * routed sport (`App\Enums\SportPortalSlug`'s full 28), each linking to
 * its own permanent mini portal (`/{sportSlug}`). Deliberately separate
 * from `PortalController::sports()`, which stays the older, meet-scoped
 * "sports actually contested in meet {meet}" directory
 * (`/meets/{meet}/sports`) — the two serve different purposes (browse the
 * whole catalog vs. see what's in one specific meet) per explicit owner
 * decision, not a replacement.
 *
 * Resolves to whichever meet is currently "active" the same way
 * `PortalTeamsController`/`PortalController::sportPortal()` do, purely to
 * decorate each card with real current-meet context (category count, live
 * status) when available — every sport still appears even with no active
 * meet or no current-meet inclusion, since its own mini portal already
 * renders honest empty states in that case.
 */
class PortalSportsController extends Controller
{
    public function index(): Response
    {
        $meet = Meet::query()->published()->active()->first();

        return Inertia::render('portal/sports-directory', [
            'meet' => $meet === null ? null : $this->meetSummary($meet),
            'sports' => $this->sportCards($meet),
        ]);
    }

    /**
     * One card per catalog sport that has a real, routed mini portal —
     * every `SportPortalSlug` case, not just the ones with current-meet
     * data, so the directory is a stable browse-the-catalog page rather
     * than shrinking/growing with each meet's configured sport list.
     *
     * @return array<int, array<string, mixed>>
     */
    private function sportCards(?Meet $meet): array
    {
        $sports = Sport::query()->orderBy('name')->get();
        $meetSportsBySportId = $meet === null ? collect() : $this->meetSportsForMeet($meet);
        $liveSportIds = $meet === null ? collect() : $this->liveSportIds($meet);

        return $sports
            ->map(function (Sport $sport) use ($meetSportsBySportId, $liveSportIds): ?array {
                $slug = SportPortalSlug::fromSportName($sport->name);

                if ($slug === null) {
                    return null;
                }

                $meetSport = $meetSportsBySportId->get($sport->id);

                return [
                    'id' => $sport->id,
                    'slug' => $slug->value,
                    'name' => $sport->name,
                    'short_description' => $sport->short_description,
                    'photo_url' => $sport->photoUrl(),
                    'classification' => $sport->classification,
                    'icon_key' => $sport->icon_key,
                    'is_paragames' => $sport->classification === 'paragames',
                    'category_count' => $this->categoryCount($sport, $meetSport),
                    'is_live' => $liveSportIds->contains($sport->id),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Catalog-wide categories (`SportCategory.meet_sport_id` null) plus
     * this specific meet's own scoped categories, deduplicated — a sport
     * with no current-meet inclusion still shows its catalog-wide count
     * rather than 0, since that count is real and not meet-dependent.
     */
    private function categoryCount(Sport $sport, ?MeetSport $meetSport): int
    {
        $catalogWide = $sport->categories()->whereNull('meet_sport_id')->count();
        $meetScoped = $meetSport === null ? 0 : $meetSport->categories()->count();

        return $catalogWide + $meetScoped;
    }

    /**
     * @return Collection<int, MeetSport>
     */
    private function meetSportsForMeet(Meet $meet): Collection
    {
        return MeetSport::query()
            ->where('meet_id', $meet->id)
            ->get()
            ->keyBy('sport_id');
    }

    /**
     * Sport ids with at least one currently-running scoring session in
     * this meet — a lightweight existence check, not the full featured-
     * match payload `PortalController::sportPortalLiveNow()` builds for a
     * single sport's own mini portal.
     *
     * @return Collection<int, int>
     */
    private function liveSportIds(Meet $meet): Collection
    {
        return ScoringSession::query()
            ->where('status', '!=', ScoringSessionStatus::Ended->value)
            ->whereHas('match', fn ($query) => $query
                ->where('meet_id', $meet->id))
            ->with('match.event:id,sport_id')
            ->get()
            ->map(fn (ScoringSession $session): ?int => $session->match?->event?->sport_id)
            ->reject(fn (?int $sportId): bool => $sportId === null)
            ->unique()
            ->values();
    }

    /**
     * Same public-safe meet header shape as `PortalController::
     * meetSummary()`/`PortalTeamsController::meetSummary()`, duplicated
     * rather than shared to keep this controller decoupled.
     *
     * @return array<string, mixed>
     */
    private function meetSummary(Meet $meet): array
    {
        return [
            'id' => $meet->id,
            'name' => $meet->name,
            'school_year' => $meet->school_year,
            'starts_at' => $meet->starts_at->format('M j, Y'),
            'starts_at_iso' => $meet->starts_at->toIso8601String(),
            'ends_at' => $meet->ends_at->format('M j, Y'),
            'venue' => $meet->venue,
            'status_label' => $meet->status->label(),
        ];
    }
}
