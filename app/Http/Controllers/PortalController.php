<?php

namespace App\Http\Controllers;

use App\Enums\AgeDivision;
use App\Enums\MeetStatus;
use App\Enums\ResultStatus;
use App\Enums\ScoringSessionStatus;
use App\Models\Announcement;
use App\Models\Delegation;
use App\Models\District;
use App\Models\EventMatch;
use App\Models\EventResult;
use App\Models\EventSchedule;
use App\Models\Meet;
use App\Models\ResultPlacement;
use App\Models\ScoringSession;
use App\Models\Sport;
use App\Services\MedalTallyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public portal pages: guest routes, no authentication. Every query goes
 * through Meet::published() and every page builds its own minimal,
 * public-safe prop set — never reuse internal page props.
 */
class PortalController extends Controller
{
    /**
     * Portal home: the single meet the system admin has set active, with
     * the municipalities competing in it. Only one meet is ever active
     * (Meet::scopeActive(), enforced by MeetController::activate()) —
     * this is deliberately not a list of meets.
     */
    public function home(MedalTallyService $tally): Response
    {
        $meet = Meet::query()->published()->active()->first();

        // Computed once and shared by `currentLeaders()`/`closingSummary()`
        // below (WP-08.5-09) — both need the same district-points ranking;
        // calling `MedalTallyService::standings()` a second time in the
        // same request would just repeat its query and grouping for no
        // reason.
        $districtStandings = $meet === null ? null : collect($tally->standings($meet->id)['districts'])
            ->sortByDesc('points')
            ->values();

        return Inertia::render('public/home', [
            'meet' => $meet === null ? null : $this->meetSummary($meet),
            'municipalities' => $meet === null ? [] : $this->competingMunicipalities($meet),
            'announcements' => $this->publishedAnnouncements($meet?->id),
            'liveMatches' => $meet === null ? [] : $this->liveMatches($meet),
            'currentLeaders' => $districtStandings === null ? [] : $this->currentLeaders($districtStandings),
            'upcomingEvents' => $meet === null ? [] : $this->upcomingEvents($meet),
            'latestResult' => $meet === null ? null : $this->latestResult($meet),
            'closingSummary' => $meet === null ? null : $this->closingSummary($meet, $districtStandings),
        ]);
    }

    /**
     * Public meet page: the schedule per day grouped by venue, plus a
     * venue guide. Unpublished meets 404.
     */
    public function meet(Request $request, int $meet): Response
    {
        $meet = Meet::query()->published()->findOrFail($meet);

        $slots = EventSchedule::query()
            ->where('meet_id', $meet->id)
            ->with(['venue:id,name,address', 'event.sport:id,name'])
            ->orderBy('scheduled_date')
            ->orderBy('starts_at')
            ->get();

        $days = $slots
            ->map(fn (EventSchedule $slot): string => $slot->scheduled_date->toDateString())
            ->unique()
            ->values();

        $requested = $request->string('date')->toString();

        $selectedDay = match (true) {
            $days->contains($requested) => $requested,
            $days->contains(today()->toDateString()) => today()->toDateString(),
            default => $days->first(),
        };

        return Inertia::render('public/meet', [
            'meet' => $this->meetSummary($meet),
            'announcements' => $this->publishedAnnouncements($meet->id),
            'hasAthletics' => $slots->contains(fn (EventSchedule $slot): bool => $slot->event->sport->name === 'Athletics'),
            'days' => $days
                ->map(fn (string $day): array => [
                    'value' => $day,
                    'label' => Carbon::parse($day)->format('D, M j'),
                ])
                ->all(),
            'selectedDay' => $selectedDay,
            'venuesForDay' => $slots
                ->filter(fn (EventSchedule $slot): bool => $slot->scheduled_date->toDateString() === $selectedDay)
                ->groupBy(fn (EventSchedule $slot): string => $slot->venue->name)
                ->sortKeys()
                ->map(fn ($group, string $venue): array => [
                    'venue' => $venue,
                    'slots' => $group
                        ->map(fn (EventSchedule $slot): array => [
                            'id' => $slot->id,
                            'starts_at' => substr($slot->starts_at, 0, 5),
                            'ends_at' => substr($slot->ends_at, 0, 5),
                            'event' => sprintf(
                                '%s — %s (%s, %s)',
                                $slot->event->sport->name,
                                $slot->event->name,
                                $slot->event->gender->label(),
                                $slot->event->age_division->label(),
                            ),
                            'note' => $slot->note,
                        ])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
            'venueGuide' => $slots
                ->map(fn (EventSchedule $slot): array => [
                    'name' => $slot->venue->name,
                    'address' => $slot->venue->address,
                ])
                ->unique('name')
                ->sortBy('name')
                ->values()
                ->all(),
            'liveMatches' => $this->liveMatches($meet),
        ]);
    }

    /**
     * Public results: validated standings only — encoded results are
     * structurally excluded by the status filter, so a corrected
     * (reopened) result disappears automatically. Unpublished meets 404.
     */
    public function results(Request $request, int $meet): Response
    {
        $meet = Meet::query()->published()->findOrFail($meet);

        $sportId = $request->integer('sport_id');

        $results = EventResult::query()
            ->where('meet_id', $meet->id)
            ->where('status', ResultStatus::Validated->value)
            ->when($sportId > 0, fn ($query) => $query->whereHas(
                'event',
                fn ($event) => $event->where('sport_id', $sportId),
            ))
            ->with([
                'event.sport:id,name',
                'placements.entry.athlete:id,first_name,last_name,school_id',
                'placements.entry.athlete.school:id,name,district_id',
                'placements.entry.athlete.school.district:id,name',
            ])
            ->orderByDesc('validated_at')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('public/results', [
            'meet' => $this->meetSummary($meet),
            'results' => $results
                ->map(fn (EventResult $result): array => [
                    'id' => $result->id,
                    'event' => sprintf(
                        '%s — %s (%s, %s)',
                        $result->event->sport->name,
                        $result->event->name,
                        $result->event->gender->label(),
                        $result->event->age_division->label(),
                    ),
                    'official_as_of' => $result->validated_at?->format('M j, Y g:i A'),
                    'placements' => $result->placements
                        ->sortBy([['rank', 'asc']])
                        ->map(fn (ResultPlacement $placement): array => [
                            'rank' => $placement->rank,
                            'athlete' => $placement->entry->athlete->fullName(),
                            'school' => $placement->entry->athlete->school->name,
                            'delegation' => $placement->entry->athlete->school->district->name,
                            'mark' => $placement->mark,
                            'is_tie' => $placement->is_tie,
                        ])
                        ->values()
                        ->all(),
                ])
                ->values(),
            'filters' => ['sport_id' => $sportId > 0 ? $sportId : null],
            'sportOptions' => $this->validatedSportOptions($meet),
        ]);
    }

    /**
     * Public medal tally: standings derived from validated results only,
     * via the same service the internal tally uses. Unpublished meets 404.
     */
    public function tally(Request $request, int $meet, MedalTallyService $tally): Response
    {
        $meet = Meet::query()->published()->findOrFail($meet);

        $sportId = $request->integer('sport_id');
        $ageDivision = AgeDivision::tryFrom((string) $request->query('age_division', ''))?->value;

        $standings = $tally->standings(
            $meet->id,
            $sportId > 0 ? $sportId : null,
            $ageDivision,
        );

        $districts = collect($standings['districts']);

        return Inertia::render('public/tally', [
            'meet' => $this->meetSummary($meet),
            'schools' => $standings['schools'],
            'districts' => $standings['districts'],
            'totals' => [
                'gold' => (int) $districts->sum('gold'),
                'silver' => (int) $districts->sum('silver'),
                'bronze' => (int) $districts->sum('bronze'),
                'total' => (int) $districts->sum('total'),
            ],
            'topByPoints' => $districts
                ->sortByDesc('points')
                ->take(5)
                ->values()
                ->all(),
            'bySport' => $tally->medalsBySport($meet->id, $sportId > 0 ? $sportId : null, $ageDivision),
            'recentMedals' => $tally->recentMedals($meet->id, $sportId > 0 ? $sportId : null, $ageDivision),
            'filters' => [
                'sport_id' => $sportId > 0 ? $sportId : null,
                'age_division' => $ageDivision,
            ],
            'sportOptions' => $this->validatedSportOptions($meet),
            'ageDivisionOptions' => array_map(
                fn (AgeDivision $division): array => ['id' => $division->value, 'label' => $division->label()],
                AgeDivision::cases(),
            ),
            'generatedAt' => now()->toDayDateTimeString(),
        ]);
    }

    /**
     * Public Athletics event listing (WP-08-10 flagged, WP-08-11):
     * deliberately a real-data-only "shell," not a live scoreboard.
     * `App\Enums\ScoreboardType` has no Athletics case and no scoring
     * event anywhere attributes a time or mark to an individual athlete
     * mid-race — Athletics results are only ever recorded after the fact,
     * through Phase 3's normal encode->validate flow, the same as every
     * other individual event. This page shows the real schedule for a
     * selected day plus, once validated, the real top-3 placements — no
     * live clock, no per-athlete live position, no field-event live
     * board, no meet-records register, all of which the approved
     * reference shows but none of which exist as real data. Unpublished
     * meets 404.
     */
    public function athletics(Request $request, int $meet, MedalTallyService $tally): Response
    {
        $meet = Meet::query()->published()->findOrFail($meet);

        $athleticsSportId = Sport::query()->where('name', 'Athletics')->value('id');

        $slots = EventSchedule::query()
            ->where('meet_id', $meet->id)
            ->when(
                $athleticsSportId !== null,
                fn ($query) => $query->whereHas('event', fn ($event) => $event->where('sport_id', $athleticsSportId)),
                fn ($query) => $query->whereRaw('1 = 0'),
            )
            ->with(['venue:id,name', 'event:id,name,gender,age_division'])
            ->orderBy('scheduled_date')
            ->orderBy('starts_at')
            ->get();

        $days = $slots
            ->map(fn (EventSchedule $slot): string => $slot->scheduled_date->toDateString())
            ->unique()
            ->values();

        $requested = $request->string('date')->toString();

        $selectedDay = match (true) {
            $days->contains($requested) => $requested,
            $days->contains(today()->toDateString()) => today()->toDateString(),
            default => $days->first(),
        };

        $validatedResults = EventResult::query()
            ->where('meet_id', $meet->id)
            ->where('status', ResultStatus::Validated->value)
            ->when(
                $athleticsSportId !== null,
                fn ($query) => $query->whereHas('event', fn ($event) => $event->where('sport_id', $athleticsSportId)),
                fn ($query) => $query->whereRaw('1 = 0'),
            )
            ->with([
                'placements' => fn ($placements) => $placements->orderBy('rank')->limit(3),
                'placements.entry.athlete:id,first_name,last_name,school_id',
                'placements.entry.athlete.school:id,name',
            ])
            ->get()
            ->keyBy('event_id');

        $now = now();
        $today = today()->toDateString();

        $totals = collect($tally->standings($meet->id, $athleticsSportId)['districts'])
            ->reduce(fn (array $carry, array $row): array => [
                'gold' => $carry['gold'] + $row['gold'],
                'silver' => $carry['silver'] + $row['silver'],
                'bronze' => $carry['bronze'] + $row['bronze'],
                'total' => $carry['total'] + $row['total'],
            ], ['gold' => 0, 'silver' => 0, 'bronze' => 0, 'total' => 0]);

        return Inertia::render('public/athletics', [
            'meet' => $this->meetSummary($meet),
            'days' => $days
                ->map(fn (string $day): array => [
                    'value' => $day,
                    'label' => Carbon::parse($day)->format('D, M j'),
                ])
                ->all(),
            'selectedDay' => $selectedDay,
            'medalTotals' => $totals,
            'slots' => $slots
                ->filter(fn (EventSchedule $slot): bool => $slot->scheduled_date->toDateString() === $selectedDay)
                ->map(function (EventSchedule $slot) use ($validatedResults, $now, $today): array {
                    $result = $validatedResults->get($slot->event_id);

                    $status = match (true) {
                        $result !== null => 'completed',
                        $slot->scheduled_date->toDateString() === $today
                            && $now->format('H:i:s') >= $slot->starts_at
                            && $now->format('H:i:s') <= $slot->ends_at => 'ongoing',
                        default => 'upcoming',
                    };

                    return [
                        'id' => $slot->id,
                        'starts_at' => substr($slot->starts_at, 0, 5),
                        'ends_at' => substr($slot->ends_at, 0, 5),
                        'event' => sprintf(
                            '%s (%s, %s)',
                            $slot->event->name,
                            $slot->event->gender->label(),
                            $slot->event->age_division->label(),
                        ),
                        'venue' => $slot->venue->name,
                        'status' => $status,
                        'top_placements' => $result === null ? [] : $result->placements
                            ->map(fn (ResultPlacement $placement): array => [
                                'rank' => $placement->rank,
                                'athlete' => $placement->entry->athlete->fullName(),
                                'school' => $placement->entry->athlete->school->name,
                                'mark' => $placement->mark,
                            ])
                            ->values()
                            ->all(),
                        'official_as_of' => $result?->validated_at?->format('M j, Y g:i A'),
                    ];
                })
                ->values()
                ->all(),
        ]);
    }

    /**
     * Public live scoreboard for one match (Phase 7, WP-07-08): read-only,
     * always provisional — never the official result (that's
     * /meets/{meet}/results, validated only). A live scoreboard is visible
     * whenever its meet is published, the same publication decision that
     * already covers the schedule/results/tally — no separate opt-in. No
     * live session is not an error, just an empty state. Unpublished
     * meets, or a match that doesn't belong to this meet, both 404.
     */
    public function scoreboard(int $meet, int $match): Response
    {
        $meet = Meet::query()->published()->findOrFail($meet);

        $match = EventMatch::query()
            ->where('meet_id', $meet->id)
            ->with(['event.sport:id,name', 'schedule.venue:id,name'])
            ->findOrFail($match);

        $session = $match->scoringSessions()->latest('id')->first();

        return Inertia::render('public/scoreboard', [
            'meet' => $this->meetSummary($meet),
            'match' => [
                'id' => $match->id,
                'event' => sprintf('%s — %s', $match->event->sport->name, $match->event->name),
                'sport' => $match->event->sport->name,
                'category' => sprintf('%s %s', $match->event->gender->label(), $match->event->age_division->label()),
                'round_label' => $match->round_label,
                'venue' => $match->schedule?->venue?->name,
                'scheduled_date' => $match->schedule?->scheduled_date?->format('M j, Y'),
                // ISO 8601, for the public scoreboard's opening countdown
                // (WP-08.5-08) — the one real scheduled instant this
                // match has, combining the slot's date and start time.
                // `null` when the match has no schedule slot yet.
                'scheduled_start_at' => $match->schedule === null ? null : Carbon::parse(
                    $match->schedule->scheduled_date->toDateString().' '.$match->schedule->starts_at,
                )->toIso8601String(),
            ],
            'session' => $session === null ? null : $session->toLivePayload(),
            // No `participants`/photo prop here, deliberately — athlete
            // photos are never public (docs/public-portal.md's privacy
            // baseline), unlike the internal operator console. The public
            // boxing scoreboard falls back to the same generated logo
            // badge basketball/softball already use.
        ]);
    }

    /**
     * Polling contract for the public scoreboard — the guest equivalent of
     * the internal `scoring.show` endpoint, scoped to published meets. No
     * Reverb channel for guests this WP; polling alone is the whole
     * mechanism, same baseline every internal live-scoring page already
     * guarantees works standalone.
     */
    public function scoreboardPoll(int $meet, int $match): JsonResponse
    {
        $meet = Meet::query()->published()->findOrFail($meet);

        $match = EventMatch::query()->where('meet_id', $meet->id)->findOrFail($match);

        $session = $match->scoringSessions()->latest('id')->first();

        return response()->json([
            'session' => $session === null ? null : $session->toLivePayload(),
        ]);
    }

    /**
     * Published announcements, newest first. The portal home shows the
     * latest few across all meets; a meet page shows its own only.
     *
     * @return array<int, array<string, mixed>>
     */
    private function publishedAnnouncements(?int $meetId = null): array
    {
        return Announcement::query()
            ->published()
            ->when($meetId !== null, fn ($query) => $query->where('meet_id', $meetId))
            ->with('meet:id,name')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (Announcement $announcement): array => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'body' => $announcement->body,
                'meet' => $announcement->meet?->name,
                'published_at' => $announcement->published_at?->format('M j, Y g:i A'),
            ])
            ->all();
    }

    /**
     * Sports that have validated results in this meet — the public
     * filter options for the results and tally pages.
     *
     * @return array<int, array{id: int, label: string}>
     */
    private function validatedSportOptions(Meet $meet): array
    {
        return EventResult::query()
            ->where('meet_id', $meet->id)
            ->where('status', ResultStatus::Validated->value)
            ->with('event.sport:id,name')
            ->get()
            ->map(fn (EventResult $result): array => [
                'id' => $result->event->sport->id,
                'label' => $result->event->sport->name,
            ])
            ->unique('id')
            ->sortBy('label')
            ->values()
            ->all();
    }

    /**
     * The public-safe meet header shared by the portal's meet pages.
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
            'ends_at' => $meet->ends_at->format('M j, Y'),
            'venue' => $meet->venue,
            'status_label' => $meet->status->label(),
        ];
    }

    /**
     * The municipalities competing in this meet — one row per registered
     * delegation's municipality, deduplicated (a delegation is rooted at
     * either a district/municipality or, in a City division, a school —
     * both resolve to a municipality here). This is the landing page's
     * "competing entry" list, each shown with a placeholder logo.
     *
     * @return array<int, array{id: int, name: string, nickname: string|null}>
     */
    private function competingMunicipalities(Meet $meet): array
    {
        return Delegation::query()
            ->where('meet_id', $meet->id)
            ->with(['district', 'school.district'])
            ->get()
            ->map(fn (Delegation $delegation): ?District => $delegation->district ?? $delegation->school?->district)
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values()
            ->map(fn (District $municipality): array => [
                'id' => $municipality->id,
                'name' => $municipality->name,
                'nickname' => $municipality->nickname,
            ])
            ->all();
    }

    /**
     * Top 3 municipalities by points (WP-08.5-03's "current leaders"
     * entry) — the same weighting `tally()`'s "Top by points" widget
     * already uses, derived from validated results only. Empty before any
     * result is validated; never the official rank, same caveat as the
     * tally page's own widget. Takes the already-sorted-by-points district
     * standings from `home()` (WP-08.5-09) rather than querying
     * `MedalTallyService` itself — `closingSummary()` below needs the
     * exact same ranking, so `home()` computes it once and shares it.
     *
     * @param  Collection<int, array<string, mixed>>  $districtStandings
     * @return array<int, array{district: string, points: int}>
     */
    private function currentLeaders(Collection $districtStandings): array
    {
        return $districtStandings
            ->take(3)
            ->map(fn (array $row): array => [
                'district' => $row['district'],
                'points' => $row['points'],
            ])
            ->values()
            ->all();
    }

    /**
     * The meet's champion and final medal totals (WP-08.5-08's "closing
     * summary") — `null` unless the meet has actually been marked
     * `Completed` (the real terminal state, `App\Enums\MeetStatus`), so
     * this never shows on an ongoing meet. Shares `home()`'s already-
     * computed district standings with `currentLeaders()` above (WP-
     * 08.5-09) rather than each independently recomputing the same
     * `MedalTallyService::standings()` call within the same request.
     *
     * @param  Collection<int, array<string, mixed>>  $districtStandings
     * @return array{champion: string, gold: int, silver: int, bronze: int, total: int}|null
     */
    private function closingSummary(Meet $meet, Collection $districtStandings): ?array
    {
        if ($meet->status !== MeetStatus::Completed) {
            return null;
        }

        $champion = $districtStandings->first();

        if ($champion === null) {
            return null;
        }

        return [
            'champion' => $champion['district'],
            'gold' => $champion['gold'],
            'silver' => $champion['silver'],
            'bronze' => $champion['bronze'],
            'total' => $champion['total'],
        ];
    }

    /**
     * The next few schedule slots that haven't finished yet (WP-08.5-03's
     * "upcoming events" entry) — today's remaining slots plus future days,
     * earliest first. A short preview list; the full schedule lives on
     * the meet page.
     *
     * @return array<int, array<string, mixed>>
     */
    private function upcomingEvents(Meet $meet): array
    {
        $now = now();

        return EventSchedule::query()
            ->where('meet_id', $meet->id)
            ->where(function ($query) use ($now) {
                $query->where('scheduled_date', '>', $now->toDateString())
                    ->orWhere(function ($query) use ($now) {
                        $query->where('scheduled_date', $now->toDateString())
                            ->where('ends_at', '>=', $now->format('H:i:s'));
                    });
            })
            ->with(['venue:id,name', 'event.sport:id,name'])
            ->orderBy('scheduled_date')
            ->orderBy('starts_at')
            ->limit(5)
            ->get()
            ->map(fn (EventSchedule $slot): array => [
                'id' => $slot->id,
                'date' => $slot->scheduled_date->format('M j'),
                'starts_at' => substr($slot->starts_at, 0, 5),
                'event' => sprintf('%s — %s', $slot->event->sport->name, $slot->event->name),
                'venue' => $slot->venue->name,
            ])
            ->values()
            ->all();
    }

    /**
     * The most recently validated result (WP-08.5-03's "latest official
     * result" entry), top 3 placements only — a preview; the full list
     * lives on the results page. `null` before any result is validated.
     *
     * @return array<string, mixed>|null
     */
    private function latestResult(Meet $meet): ?array
    {
        $result = EventResult::query()
            ->where('meet_id', $meet->id)
            ->where('status', ResultStatus::Validated->value)
            ->with([
                'event.sport:id,name',
                'placements' => fn ($placements) => $placements->orderBy('rank')->limit(3),
                'placements.entry.athlete:id,first_name,last_name,school_id',
                'placements.entry.athlete.school:id,name,district_id',
                'placements.entry.athlete.school.district:id,name',
            ])
            ->orderByDesc('validated_at')
            ->orderByDesc('id')
            ->first();

        if ($result === null) {
            return null;
        }

        return [
            'event' => sprintf('%s — %s', $result->event->sport->name, $result->event->name),
            'official_as_of' => $result->validated_at?->format('M j, Y g:i A'),
            'placements' => $result->placements
                ->sortBy('rank')
                ->map(fn (ResultPlacement $placement): array => [
                    'rank' => $placement->rank,
                    'athlete' => $placement->entry->athlete->fullName(),
                    'school' => $placement->entry->athlete->school->name,
                    'delegation' => $placement->entry->athlete->school->district->name,
                    'mark' => $placement->mark,
                    'is_tie' => $placement->is_tie,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * Matches in this meet with a currently active (non-ended) live
     * scoring session — the "watch live now" entry points on the public
     * meet page. Only one non-ended session can exist per match, so this
     * is naturally one row per live match, not per session.
     *
     * @return array<int, array<string, mixed>>
     */
    private function liveMatches(Meet $meet): array
    {
        return ScoringSession::query()
            ->where('status', '!=', ScoringSessionStatus::Ended->value)
            ->whereHas('match', fn ($query) => $query->where('meet_id', $meet->id))
            ->with('match.event.sport:id,name')
            ->get()
            ->map(fn (ScoringSession $session): array => [
                'match_id' => $session->match_id,
                'event' => sprintf('%s — %s', $session->match->event->sport->name, $session->match->event->name),
                'round_label' => $session->match->round_label,
                'side_a_label' => $session->side_a_label,
                'side_b_label' => $session->side_b_label,
                'score_a' => $session->score_a,
                'score_b' => $session->score_b,
                'status_label' => $session->status->label(),
            ])
            ->values()
            ->all();
    }
}
