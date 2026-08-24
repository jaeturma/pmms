<?php

namespace App\Http\Controllers;

use App\Enums\AgeDivision;
use App\Enums\MatchStatus;
use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetStatus;
use App\Enums\ResultStatus;
use App\Enums\ScoringSessionStatus;
use App\Enums\SportPortalSlug;
use App\Models\Announcement;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\District;
use App\Models\EventMatch;
use App\Models\EventResult;
use App\Models\EventSchedule;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\ResultPlacement;
use App\Models\School;
use App\Models\ScoringSession;
use App\Models\Setting;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\Venue;
use App\Services\MedalTallyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
        $settings = Setting::current();

        // Computed once and shared by `currentLeaders()`/`closingSummary()`
        // below (WP-08.5-09) — both need the same district-points ranking;
        // calling `MedalTallyService::standings()` a second time in the
        // same request would just repeat its query and grouping for no
        // reason.
        $districtStandings = $meet === null ? null : collect($tally->standings($meet->id)['districts'])
            ->sortByDesc('points')
            ->values();

        return Inertia::render('portal/home', [
            'meet' => $meet === null ? null : $this->meetSummary($meet),
            'municipalities' => $meet === null ? [] : $this->competingMunicipalities($meet),
            'announcements' => $this->publishedAnnouncements($meet?->id),
            'facebookLive' => $settings->facebook_live_enabled && filled($settings->facebook_live_url)
                ? [
                    'url' => $settings->facebook_live_url,
                    'embed_url' => 'https://www.facebook.com/plugins/video.php?'.http_build_query([
                        'href' => $settings->facebook_live_url,
                        'show_text' => 'false',
                        'autoplay' => 'false',
                    ]),
                ]
                : null,
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
            ->with([
                'venue:id,name,address,public_notes',
                'competitionArea:id,name',
                'event.sport:id,name',
            ])
            ->orderBy('scheduled_date')
            ->orderBy('starts_at')
            ->get();

        $days = $slots
            ->map(fn (EventSchedule $slot): string => $slot->scheduled_date->toDateString())
            ->unique()
            ->values();

        $requested = $request->string('date')->toString();

        // A slot can exist outside the meet's own starts_at/ends_at window
        // (e.g. a live-scoring demo dated to "today" regardless of when the
        // real meet runs) — still a real, reachable day via `?date=` or the
        // day-tab list itself, but never the *default* landing day. Default
        // only to "today" when today genuinely falls within the meet's own
        // dates; otherwise prefer the meet's own first real day over
        // whichever day happens to sort first among every slot.
        $officialDays = $days->filter(fn (string $day): bool => $day >= $meet->starts_at->toDateString()
            && $day <= $meet->ends_at->toDateString());

        $selectedDay = match (true) {
            $days->contains($requested) => $requested,
            $officialDays->contains(today()->toDateString()) => today()->toDateString(),
            default => $officialDays->first() ?? $days->first(),
        };

        return Inertia::render('portal/schedule', [
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
            'sportOptions' => $slots
                ->map(fn (EventSchedule $slot): array => [
                    'value' => (string) $slot->event->sport->id,
                    'label' => $slot->event->sport->name,
                ])
                ->unique('value')
                ->sortBy('label')
                ->values()
                ->all(),
            'venuesForDay' => $slots
                ->filter(fn (EventSchedule $slot): bool => $slot->scheduled_date->toDateString() === $selectedDay)
                ->groupBy(fn (EventSchedule $slot): string => $slot->venue->name)
                ->sortKeys()
                ->map(fn ($group, string $venue): array => [
                    'venue' => $venue,
                    'slots' => $group
                        ->map(fn (EventSchedule $slot): array => [
                            'id' => $slot->id,
                            'sport_id' => $slot->event->sport->id,
                            'sport' => $slot->event->sport->name,
                            'starts_at' => substr($slot->starts_at, 0, 5),
                            'ends_at' => substr($slot->ends_at, 0, 5),
                            'event' => sprintf(
                                '%s — %s (%s, %s)',
                                $slot->event->sport->name,
                                $slot->event->name,
                                $slot->event->gender->label(),
                                $slot->event->age_division->label(),
                            ),
                            'competition_area' => $slot->competitionArea?->name,
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
                    'public_notes' => $slot->venue->public_notes,
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
            ->where('status', ResultStatus::Official->value)
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

        return Inertia::render('portal/results', [
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
                    'age_division' => $result->event->age_division->value,
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

        return Inertia::render('portal/tally', [
            'meet' => $this->meetSummary($meet),
            'schools' => $standings['schools'],
            'districts' => $this->attachDistrictLogos($standings['districts']),
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
            'topMedalists' => $tally->topMedalists($meet->id, $sportId > 0 ? $sportId : null, $ageDivision),
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
            'medalTallyOfficial' => Setting::current()->medalTallyIsOfficial(),
        ]);
    }

    /**
     * Standalone rankings page (WP-11-02): the exact same
     * MedalTallyService::standings() data tally()'s own "Overall
     * ranking" table already renders, given its own destination and a
     * full, untruncated presentation — no sport/age filter, no new
     * computation. Phase 10 originally folded Rankings into Medal
     * Tally; the owner asked for a separate route in Phase 11 instead.
     * Unpublished meets 404.
     */
    public function rankings(int $meet, MedalTallyService $tally): Response
    {
        $meet = Meet::query()->published()->findOrFail($meet);

        return Inertia::render('portal/standings', [
            'meet' => $this->meetSummary($meet),
            'districts' => $this->attachDistrictLogos($tally->standings($meet->id)['districts']),
            'generatedAt' => now()->toDayDateTimeString(),
        ]);
    }

    /**
     * Attaches each row's real municipality crest, team logo, and public
     * Teams-profile slug via its carried-through `district_id`
     * (`MedalTallyService::standings()`'s district rows) — one batched
     * lookup rather than N name-matched queries. Rows without a
     * `district_id` (there always is one here, but this stays honest if
     * that ever changes) simply get nulls, and `MunicipalityCrest` falls
     * back to its initials badge exactly as it already does.
     *
     * @param  array<int, array<string, mixed>>  $districts
     * @return array<int, array<string, mixed>>
     */
    private function attachDistrictLogos(array $districts): array
    {
        $ids = collect($districts)->pluck('district_id')->filter()->unique()->values();

        $districtsById = District::query()->whereIn('id', $ids)->get()->keyBy('id');

        return array_map(
            function (array $row) use ($districtsById): array {
                /** @var District|null $district */
                $district = $districtsById->get($row['district_id'] ?? null);

                return [
                    ...$row,
                    'logo_url' => $district?->logoUrl(),
                    'team_logo_url' => $district?->teamLogoUrl(),
                    'slug' => $district !== null ? Str::slug($district->name) : null,
                ];
            },
            $districts,
        );
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
            ->where('status', ResultStatus::Official->value)
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

        return Inertia::render('portal/athletics', [
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

        return Inertia::render('portal/scoreboard', [
            'meet' => $this->meetSummary($meet),
            'match' => [
                'id' => $match->id,
                'event' => sprintf('%s — %s', $match->event->sport->name, $match->event->name),
                'sport' => $match->event->sport->name,
                'category' => sprintf('%s %s', $match->event->gender->label(), $match->event->age_division->label()),
                'round_label' => $match->round_label,
                'status' => $match->status->value,
                'status_label' => $match->status->label(),
                'venue' => $match->schedule?->venue?->name,
                'scheduled_date' => $match->schedule?->scheduled_date?->format('M j, Y'),
                // Same HH:mm passthrough format the rest of the portal
                // uses for schedule times (`sportPortalGameRow()`,
                // `athletics()`, etc.) — the basketball event-strip's
                // date/time row reuses it rather than introducing a
                // different (e.g. 12-hour) format for just this page.
                'starts_at' => $match->schedule === null ? null : substr($match->schedule->starts_at, 0, 5),
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
            //
            // A match's official result is a validated `EventResult`,
            // which belongs to its `Event` — not the `EventMatch` itself
            // (a multi-round bracket event has many matches feeding one
            // eventual result, with no schema-level way to attribute the
            // result to one specific match). Only surfaced as a fallback
            // when this match never had a `ScoringSession` at all — a
            // match that *did* use live scoring always shows that
            // session's own final score/play-by-play instead, even once
            // ended, since that's this specific match's own record.
            'officialResult' => $session === null ? $this->matchOfficialResult($match) : null,
        ]);
    }

    /**
     * The validated official result for this match's event, in the same
     * shape `latestResult()`/`results()` already use — the fallback
     * `scoreboard()` shows for a match that concluded without ever using
     * live scoring. `null` before any result for this event is
     * validated (e.g. still pending encode/review).
     *
     * @return array<string, mixed>|null
     */
    private function matchOfficialResult(EventMatch $match): ?array
    {
        $result = EventResult::query()
            ->where('event_id', $match->event_id)
            ->where('status', ResultStatus::Official->value)
            ->with([
                'event.sport:id,name',
                'placements' => fn ($placements) => $placements->orderBy('rank'),
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
     * Sports contested in this meet (WP-10-07), each linking into
     * `results`/`tally` pre-filtered by `sport_id` (both already accept
     * it) — a real integration, not a static dead-end list. `Sport` has
     * no description/image field, so the card grid shows only name and
     * how many events this meet has in it. Unpublished meets 404.
     */
    public function sports(int $meet): Response
    {
        $meet = Meet::query()->published()->findOrFail($meet);

        return Inertia::render('portal/sports', [
            'meet' => $this->meetSummary($meet),
            'sports' => $this->contestedSports($meet),
        ]);
    }

    /**
     * Static gallery page (WP-11-03): sport-identity tiles, not
     * photographs — PMMS has no photo/media model or upload pipeline
     * anywhere, and fabricating stock/placeholder "event photos" would
     * misrepresent real DepEd content, so this reuses the exact same
     * real, already-contested-sports data `sports()` shows, just at a
     * gallery-style card presentation (`docs/phases/phase-11-public-
     * portal-completion/DESIGN-NOTES.md`). Same two destinations
     * (`results`/`tally` pre-filtered by `sport_id`) as the Sports
     * page — this is a different visual presentation of the same real
     * integration, not a new data source. Unpublished meets 404.
     */
    public function gallery(int $meet): Response
    {
        $meet = Meet::query()->published()->findOrFail($meet);

        return Inertia::render('portal/gallery', [
            'meet' => $this->meetSummary($meet),
            'sports' => $this->contestedSports($meet),
        ]);
    }

    /**
     * The meet's actually-contested sports, one row per sport with its
     * real event count — shared by `sports()` (WP-10-07) and `gallery()`
     * (WP-11-03) so both pages read from one query instead of each
     * re-deriving the same grouping.
     *
     * @return array<int, array{id: int, name: string, event_count: int}>
     */
    private function contestedSports(Meet $meet): array
    {
        return $meet->events()
            ->with('sport:id,name,photo_upload_id')
            ->get()
            ->groupBy('sport_id')
            ->map(fn (Collection $events): array => [
                'id' => $events->first()->sport->id,
                'name' => $events->first()->sport->name,
                'event_count' => $events->count(),
                'photo_url' => $events->first()->sport->photoUrl(),
            ])
            ->sortBy('name')
            ->values()
            ->all();
    }

    /**
     * Full, paginated list of this meet's published announcements (WP-
     * 10-07) — the home page's own `publishedAnnouncements()` stays
     * capped at a 5-item preview; this is the "see all" destination it
     * links out to. Unpublished meets 404.
     */
    public function news(int $meet): Response
    {
        $meet = Meet::query()->published()->findOrFail($meet);

        return Inertia::render('portal/news', [
            'meet' => $this->meetSummary($meet),
            'announcements' => Announcement::query()
                ->published()
                ->where('meet_id', $meet->id)
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Announcement $announcement): array => [
                    'id' => $announcement->id,
                    'title' => $announcement->title,
                    'body' => $announcement->body,
                    'meet' => $meet->name,
                    'published_at' => $announcement->published_at?->format('M j, Y g:i A'),
                ]),
        ]);
    }

    /**
     * Meet/venue info and quick links only (WP-10-07) — the phase's own
     * resolved decision: no office-contact section, since PMMS stores no
     * division-office address/phone/email anywhere and nothing should be
     * invented. Reuses `meetSummary()` exactly; no new query beyond the
     * `Meet::published()` lookup every public route already does.
     * Unpublished meets 404.
     */
    public function contact(int $meet): Response
    {
        $meet = Meet::query()->published()->findOrFail($meet);

        return Inertia::render('portal/contact', [
            'meet' => $this->meetSummary($meet),
        ]);
    }

    /**
     * About page (WP-11-04): the Division running the meet and the
     * meet's own real participation counts — no office/history/mission
     * copy invented anywhere. `Division::current()`'s name/type/
     * areaLabel is already a global shared Inertia prop
     * (`HandleInertiaRequests::share()`), so this page reads it the
     * same way `tally.tsx` already does rather than passing it again as
     * a page-specific prop. Municipality and sport counts reuse the
     * exact same `competingMunicipalities()`/`contestedSports()` data
     * `home()`/`sports()` already compute; the school count is the one
     * genuinely new (but trivial, single-query) aggregate this WP adds
     * — distinct schools among this meet's registered athletes,
     * counted from the athlete's own `school_id` (Division initiative:
     * the athlete's home school, not the delegation's, same source
     * `docs/medal-tally.md`'s school-level grouping already uses).
     * Unpublished meets 404.
     */
    public function about(int $meet): Response
    {
        $meet = Meet::query()->published()->findOrFail($meet);

        return Inertia::render('portal/about', [
            'meet' => $this->meetSummary($meet),
            'municipalityCount' => count($this->competingMunicipalities($meet)),
            'schoolCount' => $this->participatingSchoolIds($meet)->count(),
            'sportCount' => count($this->contestedSports($meet)),
        ]);
    }

    /**
     * Distinct school IDs actually participating in this meet, derived
     * from the athlete's own `school_id` (Division initiative: the
     * athlete's home school, not the delegation's — same source
     * `MedalTallyService`/`docs/medal-tally.md` already use). Shared by
     * `about()`'s school count (WP-11-04) and `search()`'s school-name
     * matching (WP-11-06), so a school search can never surface a
     * school with no real participation in this meet.
     *
     * @return Collection<int, int>
     */
    private function participatingSchoolIds(Meet $meet): Collection
    {
        return Athlete::query()
            ->whereHas('delegation', fn ($query) => $query->where('meet_id', $meet->id))
            ->distinct()
            ->pluck('school_id');
    }

    /**
     * FAQs page (WP-11-05): common questions about how the portal
     * works. Question copy is written text (like any section heading),
     * but every factual claim traces to real data — `meetSummary()`,
     * reused exactly, the same dates/venue/status every other page
     * already shows — or already-documented behavior (`docs/public-
     * portal.md`'s publication/validation/live-provisional rules,
     * `tally.tsx`'s own rank-order disclaimer). Nothing hardcoded
     * beyond what's true right now. Unpublished meets 404.
     */
    public function faqs(int $meet): Response
    {
        $meet = Meet::query()->published()->findOrFail($meet);

        return Inertia::render('portal/faqs', [
            'meet' => $this->meetSummary($meet),
        ]);
    }

    /**
     * Public role guide for tournament personnel and coaches. The page
     * contains no private meet data; the meet summary only supplies the
     * same public context used throughout the portal.
     */
    public function support(int $meet): Response
    {
        $meet = Meet::query()->published()->findOrFail($meet);

        return Inertia::render('portal/support', [
            'meet' => $this->meetSummary($meet),
        ]);
    }

    /**
     * Cross-content search (WP-11-06): the exact same privacy boundary
     * every existing public route already enforces, just applied to a
     * new entry point that queries several tables at once instead of
     * one. Plain `LIKE`/`whereHas` queries only — no search-index
     * dependency, matching this phase's own "no new dependency" rule.
     * An empty query runs no query at all (every group empty). Every
     * group is independently scoped to this meet:
     * - Schools: only schools with real participation in this meet
     *   (`participatingSchoolIds()`, shared with `about()`) — never the
     *   whole system-wide school catalog.
     * - Sports: reuses `contestedSports()` (shared with `sports()`/
     *   `gallery()`) filtered by name — never the whole sport catalog.
     * - Announcements: `Announcement::published()` for this meet only —
     *   same scope `news()` already uses.
     * - Result placements: **validated** results for this meet only,
     *   matched by athlete name or school name — the exact same
     *   rank/athlete/school/mark triple already public on `/results`,
     *   never any athlete field beyond that (no birthdate, LRN, grade
     *   level, contact/guardian info). Unpublished meets 404.
     */
    public function search(Request $request, int $meet): Response
    {
        $meet = Meet::query()->published()->findOrFail($meet);

        $term = trim($request->string('q')->toString());

        if ($term === '') {
            return Inertia::render('portal/search', [
                'meet' => $this->meetSummary($meet),
                'query' => '',
                'schools' => [],
                'sports' => [],
                'announcements' => [],
                'placements' => [],
            ]);
        }

        $schools = School::query()
            ->whereIn('id', $this->participatingSchoolIds($meet))
            ->where('name', 'like', "%{$term}%")
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name'])
            ->map(fn (School $school): array => ['id' => $school->id, 'name' => $school->name])
            ->all();

        $sports = collect($this->contestedSports($meet))
            ->filter(fn (array $sport): bool => str_contains(strtolower($sport['name']), strtolower($term)))
            ->values()
            ->all();

        $announcements = Announcement::query()
            ->published()
            ->where('meet_id', $meet->id)
            ->where('title', 'like', "%{$term}%")
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn (Announcement $announcement): array => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'published_at' => $announcement->published_at?->format('M j, Y g:i A'),
            ])
            ->all();

        $placements = ResultPlacement::query()
            ->whereHas('result', fn ($query) => $query
                ->where('meet_id', $meet->id)
                ->where('status', ResultStatus::Official->value))
            ->where(function ($query) use ($term) {
                $query->whereHas('entry.athlete', fn ($athlete) => $athlete
                    ->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%"))
                    ->orWhereHas('entry.athlete.school', fn ($school) => $school
                        ->where('name', 'like', "%{$term}%"));
            })
            ->with([
                'result.event.sport:id,name',
                'entry.athlete:id,first_name,last_name,school_id',
                'entry.athlete.school:id,name,district_id',
                'entry.athlete.school.district:id,name',
            ])
            ->orderBy('rank')
            ->limit(20)
            ->get()
            ->map(fn (ResultPlacement $placement): array => [
                'event' => sprintf(
                    '%s — %s',
                    $placement->result->event->sport->name,
                    $placement->result->event->name,
                ),
                'sport_id' => $placement->result->event->sport->id,
                'rank' => $placement->rank,
                'athlete' => $placement->entry->athlete->fullName(),
                'school' => $placement->entry->athlete->school->name,
                'delegation' => $placement->entry->athlete->school->district->name,
                'mark' => $placement->mark,
                'is_tie' => $placement->is_tie,
            ])
            ->values()
            ->all();

        return Inertia::render('portal/search', [
            'meet' => $this->meetSummary($meet),
            'query' => $term,
            'schools' => $schools,
            'sports' => $sports,
            'announcements' => $announcements,
            'placements' => $placements,
        ]);
    }

    /**
     * Lightweight sport mini portal (Phase 12): a permanent, meet-
     * agnostic URL per sport (`/basketball`) resolving to whichever
     * meet is currently active + published — the same resolution
     * `home()` already uses, not a new concept. Standings, Leading
     * Scorers, and a real Tournament Bracket all render an honest
     * "not available yet" state on the frontend (per-sport config) —
     * no team win/loss aggregation, per-athlete point attribution, or
     * bracket-tree data exists anywhere in this schema (`docs/phases/
     * phase-12-lightweight-sport-mini-portals/DATA-CONTRACT-MAP.md`
     * §D/E/F), and none is fabricated here. `$sportSlug` is
     * constrained by the route's own `whereIn`, so `SportPortalSlug::
     * from()` never throws on an unknown value in practice.
     */
    public function sportPortal(string $sportSlug): Response
    {
        $slug = SportPortalSlug::from($sportSlug);
        $sport = Sport::query()->where('name', $slug->sportName())->first();
        $meet = Meet::query()->published()->active()->first();

        $base = [
            'sport' => $sport === null ? $this->emptySportProfile($slug) : $this->sportProfile($slug, $sport, $meet),
            'meet' => $meet === null ? null : $this->meetSummary($meet),
            // WP-12-08 (brief §12): a real, stable canonical URL per sport
            // route — `route()` resolves through `APP_URL`, so this is an
            // absolute URL, not a relative path.
            'canonicalUrl' => route('public.sport-portal', $slug->value),
        ];

        if ($sport === null || $meet === null) {
            return Inertia::render('portal/sport-portal', [
                ...$base,
                'liveNow' => null,
                'otherLiveCount' => 0,
                'todayGames' => [],
                'completedGames' => [],
                'upcomingGames' => [],
                'venues' => [],
            ]);
        }

        [$liveNow, $otherLiveCount, $todayGames, $completedGames, $upcomingGames, $venues]
            = $this->sportPortalData($meet, $sport);

        if (! auth()->check()) {
            $liveNow = null;
            $otherLiveCount = 0;
            $todayGames = collect($todayGames)->map(fn (array $game): array => [
                ...$game,
                'score_a' => null,
                'score_b' => null,
            ])->all();
        }

        return Inertia::render('portal/sport-portal', [
            ...$base,
            'liveNow' => $liveNow,
            'otherLiveCount' => $otherLiveCount,
            'todayGames' => $todayGames,
            'completedGames' => $completedGames,
            'upcomingGames' => $upcomingGames,
            'venues' => $venues,
        ]);
    }

    /**
     * The dedicated live-scoreboard page (`/live/{sportSlug}`) — the
     * sport portal page's own "Live now" section can link out here
     * instead of embedding the full scoreboard inline (WP: basketball's
     * mega scoreboard was crowding the sport portal page). Shares
     * `sportPortalLiveNow()` with `sportPortalPoll()` below, so the two
     * endpoints' payloads never drift apart.
     */
    public function liveSportPortal(string $sportSlug): Response
    {
        $slug = SportPortalSlug::from($sportSlug);
        $sport = Sport::query()->where('name', $slug->sportName())->first();
        $meet = Meet::query()->published()->active()->first();

        $base = [
            'sport' => $sport === null ? $this->emptySportProfile($slug) : $this->sportProfile($slug, $sport, $meet),
            'meet' => $meet === null ? null : $this->meetSummary($meet),
            'canonicalUrl' => route('public.live-sport-portal', $slug->value),
        ];

        if ($sport === null || $meet === null) {
            return Inertia::render('portal/live-sport', [
                ...$base,
                'liveNow' => null,
                'otherLiveCount' => 0,
            ]);
        }

        [$liveNow, $otherLiveCount] = $this->sportPortalLiveNow($meet, $sport);

        return Inertia::render('portal/live-sport', [
            ...$base,
            'liveNow' => $liveNow,
            'otherLiveCount' => $otherLiveCount,
        ]);
    }

    /**
     * The mini portal's upper-section profile — photo/description/
     * categories/personnel, built from real `Sport`/`MeetSport`/
     * `MeetSportAssignment`/`sport_user` data (see
     * `docs/reports/public-sports-and-mini-portals-review.md`). Venue and
     * schedule information reuse `sportPortalData()`'s own
     * `venues`/`todayGames`/`upcomingGames` — not duplicated here.
     *
     * @return array<string, mixed>
     */
    private function sportProfile(SportPortalSlug $slug, Sport $sport, ?Meet $meet): array
    {
        $meetSport = $meet === null
            ? null
            : MeetSport::query()->where('meet_id', $meet->id)->where('sport_id', $sport->id)->first();

        return [
            'slug' => $slug->value,
            'name' => $slug->sportName(),
            'is_paragames' => $sport->classification === 'paragames' || str_starts_with($sport->name, 'Paragames'),
            'short_description' => $sport->short_description,
            'description' => $sport->description,
            'photo_url' => $sport->photoUrl(),
            'categories' => $this->sportProfileCategories($sport, $meetSport),
            'tournament_management' => $meetSport === null ? [] : $this->sportProfileTournamentManagement($meetSport),
            'technical_officials' => $this->sportProfileTechnicalOfficials($sport, $meetSport),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptySportProfile(SportPortalSlug $slug): array
    {
        return [
            'slug' => $slug->value,
            'name' => $slug->sportName(),
            'is_paragames' => in_array($slug, [SportPortalSlug::Bocce, SportPortalSlug::GoalBall, SportPortalSlug::ParagamesAthletics, SportPortalSlug::ParagamesSwimming], true),
            'short_description' => null,
            'description' => null,
            'photo_url' => null,
            'categories' => [],
            'tournament_management' => [],
            'technical_officials' => [],
        ];
    }

    /**
     * Catalog-wide categories (`meet_sport_id` null) plus this specific
     * meet's own scoped categories — same union `PortalSportsController::
     * categoryCount()` counts, here returning the real rows for display.
     *
     * @return array<int, array{id: int, display_name: string, level: string|null, sex: string|null}>
     */
    private function sportProfileCategories(Sport $sport, ?MeetSport $meetSport): array
    {
        $catalogWide = SportCategory::query()
            ->where('sport_id', $sport->id)
            ->whereNull('meet_sport_id')
            ->where('active', true)
            ->get();

        $meetScoped = $meetSport === null
            ? collect()
            : $meetSport->categories()->where('active', true)->get();

        return $catalogWide->concat($meetScoped)
            ->sortBy('display_name')
            ->map(fn (SportCategory $category): array => [
                'id' => $category->id,
                'display_name' => $category->display_name,
                'level' => $category->level?->label(),
                'sex' => $category->sex?->label(),
            ])
            ->values()
            ->all();
    }

    /**
     * Tournament Manager/Assistant/Track/Field/Boys/Girls/Category TM,
     * Tournament Secretary, and Tournament ICT assignments for this
     * meet's inclusion of the sport — `TechnicalOfficial`-role rows are
     * excluded here (public Technical Officials come from `sport_user`
     * instead, see `sportProfileTechnicalOfficials()`, since that's the
     * table with real current data). Public-safe fields only: name, role
     * label, category, lead flag — never phone/email/address/birth date/
     * employee id/medical/account.
     *
     * @return array<int, array{name: string, role_label: string, category: string|null, is_lead: bool}>
     */
    private function sportProfileTournamentManagement(MeetSport $meetSport): array
    {
        return MeetSportAssignment::query()
            ->where('meet_sport_id', $meetSport->id)
            ->where('role', '!=', MeetSportAssignmentRole::TechnicalOfficial->value)
            ->with(['user:id,name', 'person:id,full_name', 'sportCategory:id,display_name'])
            ->get()
            ->sortBy(fn (MeetSportAssignment $assignment): int => $this->tournamentManagementRolePriority($assignment->role))
            ->map(fn (MeetSportAssignment $assignment): array => [
                'name' => $assignment->user?->name ?? $assignment->person?->full_name ?? __('Unknown person'),
                'role_label' => $assignment->role->label(),
                'category' => $assignment->sportCategory?->display_name,
                'is_lead' => $assignment->is_lead,
            ])
            ->values()
            ->all();
    }

    /**
     * Display order for Tournament Management roles — Manager first,
     * Technical Official last (though that role is filtered out before
     * this is ever called), matching the order the brief itself lists
     * them in rather than the enum's declaration order (which happens to
     * already match, but this stays explicit rather than relying on it).
     */
    private function tournamentManagementRolePriority(MeetSportAssignmentRole $role): int
    {
        return match ($role) {
            MeetSportAssignmentRole::TournamentManager => 0,
            MeetSportAssignmentRole::AssistantTournamentManager => 1,
            MeetSportAssignmentRole::TrackTournamentManager => 2,
            MeetSportAssignmentRole::FieldTournamentManager => 3,
            MeetSportAssignmentRole::BoysTournamentManager => 4,
            MeetSportAssignmentRole::GirlsTournamentManager => 5,
            MeetSportAssignmentRole::CategoryTournamentManager => 6,
            MeetSportAssignmentRole::TournamentSecretary => 7,
            MeetSportAssignmentRole::TournamentICT => 8,
            MeetSportAssignmentRole::TechnicalOfficial => 9,
        };
    }

    /**
     * Technical Officials for this sport — `sport_user` (meet-unscoped,
     * see `Sport::technicalOfficials()`), the table with real live-
     * authorization-backing data today. `duty` renders as the generic
     * "Technical Official" label on the frontend when `null` (no admin
     * form sets it yet — see the `sport_user.duty` migration). Queried
     * directly against the pivot table rather than through
     * `Sport::technicalOfficials()`'s `->withPivot('duty')` accessor —
     * Eloquent's dynamic `pivot` property isn't statically typed, and a
     * dedicated typed pivot model would force `Sport::technicalOfficials()`
     * itself (used elsewhere for `sync()`/id-listing) into a narrower,
     * unrelated return type just for this one read.
     *
     * @return array<int, array{name: string, duty: string|null}>
     */
    private function sportProfileTechnicalOfficials(Sport $sport, ?MeetSport $meetSport): array
    {
        $meetOfficials = $meetSport === null
            ? collect()
            : MeetSportAssignment::query()
                ->where('meet_sport_id', $meetSport->id)
                ->where('role', MeetSportAssignmentRole::TechnicalOfficial->value)
                ->whereNotIn('status', ['declined', 'ended'])
                ->with(['user:id,name', 'person:id,full_name'])
                ->get()
                ->map(function (MeetSportAssignment $assignment): array {
                    $designation = trim((string) $assignment->original_designation);

                    return [
                        'name' => $assignment->user?->name ?? $assignment->person?->full_name ?? __('Unknown person'),
                        'duty' => $designation !== '' && strcasecmp($designation, 'Technical Official') !== 0
                            ? $designation
                            : null,
                    ];
                });

        $legacyOfficials = DB::table('sport_user')
            ->join('users', 'users.id', '=', 'sport_user.user_id')
            ->where('sport_user.sport_id', $sport->id)
            ->get(['users.name as name', 'sport_user.duty as duty'])
            ->map(fn (object $row): array => [
                'name' => (string) $row->name,
                'duty' => $row->duty === null ? null : (string) $row->duty,
            ]);

        return $meetOfficials
            ->concat($legacyOfficials)
            ->unique(fn (array $official): string => mb_strtolower($official['name'].'|'.($official['duty'] ?? '')))
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /**
     * Polling contract for the sport portal's Live Now section. The
     * featured match can change between polls (a different match may
     * go live) unlike the single-match public scoreboard, so this
     * re-resolves fresh each time rather than tracking one match id.
     */
    public function sportPortalPoll(string $sportSlug): JsonResponse
    {
        $slug = SportPortalSlug::from($sportSlug);
        $sport = Sport::query()->where('name', $slug->sportName())->first();
        $meet = Meet::query()->published()->active()->first();

        if ($sport === null || $meet === null) {
            return response()->json(['liveNow' => null, 'otherLiveCount' => 0]);
        }

        [$liveNow, $otherLiveCount] = $this->sportPortalLiveNow($meet, $sport);

        return response()->json(['liveNow' => $liveNow, 'otherLiveCount' => $otherLiveCount]);
    }

    /**
     * Athletics and Swimming (Phase 12, WP-12-05) are individual, heat/
     * event-based disciplines with no real `EventMatch` concept at all
     * — confirmed against `athletics()` above, which already reads
     * schedule/results this same way and never touches `EventMatch`.
     * Every other sport (including Boxing and Chess, both genuinely
     * head-to-head) fits the generic match-based shape correctly.
     */
    private const INDIVIDUAL_EVENT_SPORTS = ['Athletics', 'Swimming'];

    /**
     * @return array{0: array<string, mixed>|null, 1: int, 2: array<int, array<string, mixed>>, 3: array<int, array<string, mixed>>, 4: array<int, array<string, mixed>>, 5: array<int, array<string, mixed>>}
     */
    private function sportPortalData(Meet $meet, Sport $sport): array
    {
        if (in_array($sport->name, self::INDIVIDUAL_EVENT_SPORTS, true)) {
            return $this->individualEventSportPortalData($meet, $sport);
        }

        $matches = EventMatch::query()
            ->where('meet_id', $meet->id)
            ->whereHas('event', fn ($query) => $query->where('sport_id', $sport->id))
            ->with([
                'event:id,sport_id,name,gender,age_division',
                'schedule:id,scheduled_date,starts_at,ends_at,venue_id',
                'schedule.venue:id,name,address',
                'entries.athlete:id,school_id',
                'entries.athlete.school:id,name',
            ])
            ->get();

        $sessionsByMatch = ScoringSession::query()
            ->whereIn('match_id', $matches->pluck('id'))
            ->orderByDesc('id')
            ->get()
            ->unique('match_id')
            ->keyBy('match_id');

        $today = today()->toDateString();

        $scheduled = $matches->filter(
            fn (EventMatch $match): bool => $match->status === MatchStatus::Scheduled && $match->schedule !== null,
        );

        $todayGames = $scheduled
            ->filter(fn (EventMatch $match): bool => $match->schedule->scheduled_date->toDateString() === $today)
            ->sortBy(fn (EventMatch $match): string => $match->schedule->starts_at)
            ->take(10)
            ->map(fn (EventMatch $match): array => $this->sportPortalGameRow($match, $sessionsByMatch->get($match->id)))
            ->values()
            ->all();

        $upcomingGames = $scheduled
            ->filter(fn (EventMatch $match): bool => $match->schedule->scheduled_date->toDateString() > $today)
            ->sortBy(fn (EventMatch $match): string => $match->schedule->scheduled_date->toDateString().' '.$match->schedule->starts_at)
            ->take(10)
            ->map(fn (EventMatch $match): array => $this->sportPortalGameRow($match, $sessionsByMatch->get($match->id)))
            ->values()
            ->all();

        $completedGames = $matches
            ->filter(fn (EventMatch $match): bool => in_array($match->status, [MatchStatus::Completed, MatchStatus::Walkover], true))
            ->sortByDesc(fn (EventMatch $match) => $match->updated_at)
            ->take(10)
            ->map(fn (EventMatch $match): array => $this->sportPortalGameRow($match, $sessionsByMatch->get($match->id)))
            ->values()
            ->all();

        $venues = $matches
            ->map(fn (EventMatch $match): ?Venue => $match->schedule?->venue)
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->map(fn (Venue $venue): array => [
                'id' => $venue->id,
                'name' => $venue->name,
                'address' => $venue->address,
                'directions_url' => $this->mapsSearchUrl($venue->name, $venue->address),
            ])
            ->values()
            ->all();

        [$liveNow, $otherLiveCount] = $this->sportPortalLiveNow($meet, $sport);

        return [$liveNow, $otherLiveCount, $todayGames, $completedGames, $upcomingGames, $venues];
    }

    /**
     * The featured live match for this sport in this meet, plus a
     * count of any other simultaneously-live matches — a lighter,
     * standalone query (not derived from `sportPortalData()`'s already-
     * loaded matches) so the polling endpoint doesn't have to re-fetch
     * every match/schedule/entry just to check what's live.
     *
     * @return array{0: array<string, mixed>|null, 1: int}
     */
    private function sportPortalLiveNow(Meet $meet, Sport $sport): array
    {
        $liveSessions = ScoringSession::query()
            ->where('status', '!=', ScoringSessionStatus::Ended->value)
            ->whereHas('match', fn ($query) => $query
                ->where('meet_id', $meet->id)
                ->whereHas('event', fn ($event) => $event->where('sport_id', $sport->id)))
            ->with([
                'match.event:id,sport_id,name,gender,age_division',
                'match.schedule.venue:id,name',
            ])
            ->orderBy('id')
            ->get();

        if ($liveSessions->isEmpty()) {
            return [null, 0];
        }

        $featured = $liveSessions->first();
        $match = $featured->match;

        return [
            [
                'match_id' => $match->id,
                'round_label' => $match->round_label,
                'category' => sprintf('%s %s', $match->event->gender->label(), $match->event->age_division->label()),
                'venue' => $match->schedule?->venue?->name,
                // Same format as `sportPortalGameRow()` — the sport hub
                // page's basketball event-strip needs a date/time, not
                // just venue, to mirror the per-match scoreboard's own.
                'scheduled_date' => $match->schedule?->scheduled_date?->format('M j, Y'),
                'starts_at' => $match->schedule === null ? null : substr($match->schedule->starts_at, 0, 5),
                'session' => $featured->toLivePayload(),
            ],
            $liveSessions->count() - 1,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sportPortalGameRow(EventMatch $match, ?ScoringSession $session): array
    {
        [$sideA, $sideB] = $this->matchCompetitors($match, $session);

        return [
            'id' => $match->id,
            'round_label' => $match->round_label,
            'status' => $match->status->value,
            'status_label' => $match->status->label(),
            'category' => sprintf('%s %s', $match->event->gender->label(), $match->event->age_division->label()),
            'venue' => $match->schedule?->venue?->name,
            'scheduled_date' => $match->schedule?->scheduled_date?->format('M j, Y'),
            'starts_at' => $match->schedule === null ? null : substr($match->schedule->starts_at, 0, 5),
            'side_a' => $sideA,
            'side_b' => $sideB,
            'score_a' => $session?->score_a,
            'score_b' => $session?->score_b,
            'mark' => null,
        ];
    }

    /**
     * Athletics/Swimming's own data path (WP-12-05) — real
     * `EventSchedule`/`EventResult` rows, the exact same source
     * `athletics()` already reads, never `EventMatch`/`ScoringSession`
     * (neither exists for these two sports in real usage). Live Now
     * needs no special-casing here: `sportPortalLiveNow()`'s `EventMatch`
     * query naturally finds nothing for a sport that has none.
     *
     * @return array{0: null, 1: int, 2: array<int, array<string, mixed>>, 3: array<int, array<string, mixed>>, 4: array<int, array<string, mixed>>, 5: array<int, array<string, mixed>>}
     */
    private function individualEventSportPortalData(Meet $meet, Sport $sport): array
    {
        $slots = EventSchedule::query()
            ->where('meet_id', $meet->id)
            ->whereHas('event', fn ($query) => $query->where('sport_id', $sport->id))
            ->with(['venue:id,name,address', 'event:id,name,gender,age_division'])
            ->get();

        $results = EventResult::query()
            ->where('meet_id', $meet->id)
            ->where('status', ResultStatus::Official->value)
            ->whereHas('event', fn ($query) => $query->where('sport_id', $sport->id))
            ->with([
                'placements' => fn ($placements) => $placements->orderBy('rank')->limit(1),
                'placements.entry.athlete:id,first_name,last_name,school_id',
                'placements.entry.athlete.school:id,name',
            ])
            ->get()
            ->keyBy('event_id');

        $today = today()->toDateString();

        $completedSlots = $slots->filter(fn (EventSchedule $slot): bool => $results->has($slot->event_id));
        $unresolvedSlots = $slots->filter(fn (EventSchedule $slot): bool => ! $results->has($slot->event_id));

        $todayGames = $unresolvedSlots
            ->filter(fn (EventSchedule $slot): bool => $slot->scheduled_date->toDateString() === $today)
            ->sortBy(fn (EventSchedule $slot): string => $slot->starts_at)
            ->take(10)
            ->map(fn (EventSchedule $slot): array => $this->individualEventGameRow($slot, null))
            ->values()
            ->all();

        $upcomingGames = $unresolvedSlots
            ->filter(fn (EventSchedule $slot): bool => $slot->scheduled_date->toDateString() > $today)
            ->sortBy(fn (EventSchedule $slot): string => $slot->scheduled_date->toDateString().' '.$slot->starts_at)
            ->take(10)
            ->map(fn (EventSchedule $slot): array => $this->individualEventGameRow($slot, null))
            ->values()
            ->all();

        $completedGames = $completedSlots
            ->sortByDesc(fn (EventSchedule $slot) => $results->get($slot->event_id)->validated_at)
            ->take(10)
            ->map(fn (EventSchedule $slot): array => $this->individualEventGameRow($slot, $results->get($slot->event_id)))
            ->values()
            ->all();

        $venues = $slots
            ->map(fn (EventSchedule $slot): ?Venue => $slot->venue)
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->map(fn (Venue $venue): array => [
                'id' => $venue->id,
                'name' => $venue->name,
                'address' => $venue->address,
                'directions_url' => $this->mapsSearchUrl($venue->name, $venue->address),
            ])
            ->values()
            ->all();

        return [null, 0, $todayGames, $completedGames, $upcomingGames, $venues];
    }

    /**
     * @return array<string, mixed>
     */
    private function individualEventGameRow(EventSchedule $slot, ?EventResult $result): array
    {
        $winner = null;
        $mark = null;

        $placement = $result?->placements->first();

        if ($placement !== null) {
            $winner = sprintf(
                '1st: %s (%s)',
                $placement->entry->athlete->fullName(),
                $placement->entry->athlete->school->name,
            );
            $mark = $placement->mark;
        }

        return [
            'id' => $slot->id,
            'round_label' => $slot->event->name,
            'status' => $result !== null ? 'completed' : 'scheduled',
            'status_label' => $result !== null ? 'Completed' : 'Scheduled',
            'category' => sprintf('%s %s', $slot->event->gender->label(), $slot->event->age_division->label()),
            'venue' => $slot->venue?->name,
            'scheduled_date' => $slot->scheduled_date->format('M j, Y'),
            'starts_at' => substr($slot->starts_at, 0, 5),
            'side_a' => $winner,
            'side_b' => null,
            'score_a' => null,
            'score_b' => null,
            'mark' => $mark,
        ];
    }

    /**
     * The match's two competitor labels — the live/final session's own
     * labels once one exists (the authoritative, actually-entered
     * names), falling back to the two registered entries' schools
     * before any scoring session starts (the same real-data "suggested
     * labels" pattern `ScoringSessionController::board()` already
     * established, never fabricated).
     *
     * @return array{0: string|null, 1: string|null}
     */
    private function matchCompetitors(EventMatch $match, ?ScoringSession $session): array
    {
        if ($session !== null) {
            return [$session->side_a_label, $session->side_b_label];
        }

        $entries = $match->entries;

        if ($entries->count() === 2) {
            return [
                $entries[0]->athlete->school->name,
                $entries[1]->athlete->school->name,
            ];
        }

        return [null, null];
    }

    /**
     * A generic external map-search link built from real venue name/
     * address text — no stored geo field exists on `Venue`, and the
     * brief's own rule is no heavy embedded map anyway.
     */
    private function mapsSearchUrl(string $name, ?string $address): string
    {
        $query = $address === null ? $name : "{$name}, {$address}";

        return 'https://www.google.com/maps/search/?api=1&query='.urlencode($query);
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
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
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
            ->where('status', ResultStatus::Official->value)
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
            'starts_at_iso' => $meet->starts_at->toIso8601String(),
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
     * "competing entry" list; `logo_url` is null (falls back to an
     * initials placeholder client-side) until an admin uploads a real
     * crest for that municipality.
     *
     * @return array<int, array{id: int, name: string, nickname: string|null, logo_url: string|null, team_logo_url: string|null, slug: string}>
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
                'logo_url' => $municipality->logoUrl(),
                'team_logo_url' => $municipality->teamLogoUrl(),
                'slug' => Str::slug($municipality->name),
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
            ->where('status', ResultStatus::Official->value)
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
