<?php

namespace App\Http\Controllers;

use App\Enums\ResultStatus;
use App\Enums\ScoringSessionStatus;
use App\Models\Announcement;
use App\Models\EventMatch;
use App\Models\EventResult;
use App\Models\EventSchedule;
use App\Models\Meet;
use App\Models\ResultPlacement;
use App\Models\ScoringSession;
use App\Services\MedalTallyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
     * Portal home: the published meets.
     */
    public function home(): Response
    {
        return Inertia::render('public/home', [
            'meets' => Meet::query()
                ->published()
                ->orderByDesc('starts_at')
                ->get()
                ->map(fn (Meet $meet): array => [
                    'id' => $meet->id,
                    'name' => $meet->name,
                    'school_year' => $meet->school_year,
                    'starts_at' => $meet->starts_at->format('M j, Y'),
                    'ends_at' => $meet->ends_at->format('M j, Y'),
                    'venue' => $meet->venue,
                    'status_label' => $meet->status->label(),
                ])
                ->values(),
            'announcements' => $this->publishedAnnouncements(),
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
                'placements.entry.athlete.school:id,name',
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

        $standings = $tally->standings($meet->id, $sportId > 0 ? $sportId : null);

        return Inertia::render('public/tally', [
            'meet' => $this->meetSummary($meet),
            'schools' => $standings['schools'],
            'districts' => $standings['districts'],
            'filters' => ['sport_id' => $sportId > 0 ? $sportId : null],
            'sportOptions' => $this->validatedSportOptions($meet),
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
            ->with('event.sport:id,name')
            ->findOrFail($match);

        $session = $match->scoringSessions()->latest('id')->first();

        return Inertia::render('public/scoreboard', [
            'meet' => $this->meetSummary($meet),
            'match' => [
                'id' => $match->id,
                'event' => sprintf('%s — %s', $match->event->sport->name, $match->event->name),
                'round_label' => $match->round_label,
            ],
            'session' => $session === null ? null : $session->toLivePayload(),
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
