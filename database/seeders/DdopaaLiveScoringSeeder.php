<?php

namespace Database\Seeders;

use App\Enums\AgeDivision;
use App\Enums\GenderCategory;
use App\Enums\MatchStatus;
use App\Enums\ScoreEventType;
use App\Enums\ScoringSessionStatus;
use App\Enums\UserRole;
use App\Models\Delegation;
use App\Models\District;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\EventSchedule;
use App\Models\Meet;
use App\Models\ScoreEvent;
use App\Models\ScoringSession;
use App\Models\Sport;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * DdOPAA 2025 Reference Dataset — WP4: Live Scoring Samples.
 *
 * Local development and testing only. Requires WP1+WP2 to have already
 * run — returns early, idempotently, if the DdOPAA meet or its
 * delegations don't exist yet. Every fact here is `SYNTHETIC_DEMO`: no
 * source has real score/round/inning data for any match. Mirrors the
 * ad-hoc `SampleProvinceDemoSeeder::liveBasketballGame()` pattern (same
 * `ScoringSession`/`ScoreEvent` fields, same `firstOrCreate`/`forceFill`
 * idempotency approach). Never creates or touches `EventResult`/
 * `ResultPlacement` — a live session is always provisional, exactly as
 * `ScoringSessionController::end()` guarantees.
 *
 * One scheduled, one in-progress, and one completed `EventMatch` for
 * each of the three sports Phase 7 built dedicated scoreboards for
 * (Basketball, Boxing, Softball/Baseball), so the Schedule page's live-
 * link column (added the session before this initiative) has real,
 * varied sport-specific state to exercise.
 */
class DdopaaLiveScoringSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $meet = Meet::query()->where('name', 'DdOPAA Meet 2025')->first();

        if ($meet === null || Delegation::query()->where('meet_id', $meet->id)->doesntExist()) {
            // WP1/WP2 haven't run yet.
            return;
        }

        $admin = User::query()->where('role', UserRole::Admin->value)->first();

        $this->basketball($meet, $admin);
        $this->boxing($meet, $admin);
        $this->softball($meet, $admin);
    }

    /**
     * Reuses the "Basketball" (Girls, team) event WP1 already created.
     */
    private function basketball(Meet $meet, ?User $admin): void
    {
        $sport = Sport::query()->where('name', 'Basketball')->firstOrFail();
        $event = Event::query()->firstOrCreate(
            [
                'sport_id' => $sport->id,
                'name' => 'Basketball',
                'gender' => GenderCategory::Girls->value,
                'age_division' => AgeDivision::Secondary->value,
            ],
            ['is_team_event' => true, 'max_entries_per_delegation' => 12],
        );
        $meet->events()->syncWithoutDetaching([$event->id]);

        $venue = $this->venue('Maragusan Sports Complex Gymnasium');
        [$sideA, $sideB] = $this->sides($meet, 'Montevista', 'Nabunturan');

        $this->scheduledMatch($meet, $event, $venue, 'Pool Game 1', Carbon::now()->addDays(2));

        $this->inProgressMatch(
            $meet, $event, $venue, 'Semifinal', Carbon::now(), $sideA, $sideB, $admin,
            scoreA: 38, scoreB: 41, periodLabel: 'Q3',
            sportState: ['fouls_a' => 3, 'fouls_b' => 2],
            openingEvent: function (ScoringSession $s) use ($admin): void {
                ScoreEvent::create([
                    'scoring_session_id' => $s->id,
                    'type' => ScoreEventType::Point,
                    'payload' => ['side' => 'a', 'delta' => 38, 'total' => 38],
                    'recorded_by' => $admin?->id,
                ]);
            },
        );

        $this->completedMatch(
            $meet, $event, $venue, 'Final', Carbon::now()->subDay(), $sideA, $sideB, $admin,
            scoreA: 54, scoreB: 49, periodLabel: 'Final',
            sportState: ['fouls_a' => 5, 'fouls_b' => 4],
        );
    }

    /**
     * Reuses the "Boxing" (Boys, individual) event WP1 already created —
     * a live bout is a separate, provisional concept from the official
     * `EventResult` WP3 already validated for this same event; both can
     * coexist, same as the real app allows.
     */
    private function boxing(Meet $meet, ?User $admin): void
    {
        $sport = Sport::query()->where('name', 'Boxing')->firstOrFail();
        $event = Event::query()->where('sport_id', $sport->id)
            ->where('name', 'Boxing')
            ->where('gender', GenderCategory::Boys->value)
            ->firstOrFail();

        $venue = $this->venue('Maragusan Sports Complex Gymnasium');
        [$sideA, $sideB] = $this->sides($meet, 'Nabunturan', 'New Bataan');

        $this->scheduledMatch($meet, $event, $venue, 'Bout 1', Carbon::now()->addDays(2));

        // Two of three rounds fought — score is the sum of rounds so far.
        $this->inProgressMatch(
            $meet, $event, $venue, 'Bout 2', Carbon::now(), $sideA, $sideB, $admin,
            scoreA: 20, scoreB: 17, periodLabel: 'Round 3',
            sportState: ['rounds' => [
                ['round' => 1, 'score_a' => 10, 'score_b' => 9],
                ['round' => 2, 'score_a' => 10, 'score_b' => 8],
            ]],
        );

        // Full three-round history summing to the final score.
        $this->completedMatch(
            $meet, $event, $venue, 'Bout 3', Carbon::now()->subDay(), $sideA, $sideB, $admin,
            scoreA: 29, scoreB: 28, periodLabel: 'Final',
            sportState: ['rounds' => [
                ['round' => 1, 'score_a' => 10, 'score_b' => 9],
                ['round' => 2, 'score_a' => 9, 'score_b' => 10],
                ['round' => 3, 'score_a' => 10, 'score_b' => 9],
            ]],
        );
    }

    /**
     * SYNTHETIC_DEMO: adds one new "Softball" (Girls, team) event — WP1's
     * catalog covered Basketball/Volleyball/Gymnastics/Swimming/Boxing
     * only, none of them map to `ScoreboardType::SoftballBaseball`
     * (`App\Enums\ScoreboardType::forSport()` requires a sport literally
     * named "Softball" or "Baseball" — both already exist in
     * `SportsCatalogSeeder`, unused until now). Same
     * documentation-only classification approach as WP1's own additions.
     */
    private function softball(Meet $meet, ?User $admin): void
    {
        $sport = Sport::query()->where('name', 'Softball')->firstOrFail();
        $event = Event::query()->firstOrCreate(
            [
                'sport_id' => $sport->id,
                'name' => 'Softball',
                'gender' => GenderCategory::Girls->value,
                'age_division' => AgeDivision::Secondary->value,
            ],
            ['is_team_event' => true, 'max_entries_per_delegation' => 15],
        );
        $meet->events()->syncWithoutDetaching([$event->id]);

        $venue = $this->venue('Maragusan Sports Complex Diamond');
        [$sideA, $sideB] = $this->sides($meet, 'Mawab', 'Maragusan');

        $this->scheduledMatch($meet, $event, $venue, 'Pool Game 1', Carbon::now()->addDays(2));

        // Partway through the top of the 4th — innings so far sum to the
        // running score.
        $this->inProgressMatch(
            $meet, $event, $venue, 'Semifinal', Carbon::now(), $sideA, $sideB, $admin,
            scoreA: 3, scoreB: 1, periodLabel: 'Top 4th',
            sportState: [
                'inning' => 4, 'half' => 'top', 'outs' => 1, 'balls' => 1, 'strikes' => 2,
                'innings' => [
                    ['inning' => 1, 'runs_a' => 1, 'runs_b' => 0],
                    ['inning' => 2, 'runs_a' => 0, 'runs_b' => 1],
                    ['inning' => 3, 'runs_a' => 2, 'runs_b' => 0],
                ],
            ],
        );

        // Full seven-inning breakdown summing to the final score.
        $this->completedMatch(
            $meet, $event, $venue, 'Final', Carbon::now()->subDay(), $sideA, $sideB, $admin,
            scoreA: 9, scoreB: 6, periodLabel: 'Final',
            sportState: [
                'inning' => 7, 'half' => 'bottom', 'outs' => 3, 'balls' => 0, 'strikes' => 0,
                'innings' => [
                    ['inning' => 1, 'runs_a' => 1, 'runs_b' => 0],
                    ['inning' => 2, 'runs_a' => 0, 'runs_b' => 1],
                    ['inning' => 3, 'runs_a' => 2, 'runs_b' => 0],
                    ['inning' => 4, 'runs_a' => 1, 'runs_b' => 2],
                    ['inning' => 5, 'runs_a' => 0, 'runs_b' => 0],
                    ['inning' => 6, 'runs_a' => 3, 'runs_b' => 1],
                    ['inning' => 7, 'runs_a' => 2, 'runs_b' => 2],
                ],
            ],
        );
    }

    private function venue(string $name): Venue
    {
        return Venue::query()->firstOrCreate(
            ['name' => $name],
            ['address' => 'Maragusan, Davao de Oro', 'active' => true],
        );
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function sides(Meet $meet, string $municipalityA, string $municipalityB): array
    {
        return [$this->delegationLabel($meet, $municipalityA), $this->delegationLabel($meet, $municipalityB)];
    }

    private function delegationLabel(Meet $meet, string $municipality): string
    {
        $district = District::query()->where('name', $municipality)->first();

        if ($district === null) {
            return $municipality;
        }

        $delegation = Delegation::query()
            ->where('meet_id', $meet->id)
            ->where('district_id', $district->id)
            ->first();

        return $delegation?->registrantName() ?? $municipality;
    }

    /**
     * `whereDate()`, not a plain `firstOrNew` key on `scheduled_date`, on
     * purpose: Eloquent's `date` cast serializes through the query
     * grammar's default datetime format ('Y-m-d H:i:s') when a new row
     * is saved, but a `firstOrNew` match array is compared as a literal
     * string — so a bare `Y-m-d` lookup key never matches what actually
     * got stored. MySQL's native `DATE` column silently truncates the
     * time part on insert, which happened to mask this everywhere this
     * seeder had been manually verified; SQLite has no such column type
     * and stores the full string verbatim, so re-running this seeder
     * against the (sqlite `:memory:`) test database created a duplicate
     * `EventSchedule`/`EventMatch` pair every time instead of finding the
     * existing one — caught by WP6's idempotency test, not by hand.
     * `whereDate()` compares only the date portion and is safe across
     * both engines.
     */
    private function slot(Meet $meet, Event $event, Venue $venue, Carbon $date, string $note): EventSchedule
    {
        $slot = EventSchedule::query()
            ->where('meet_id', $meet->id)
            ->where('event_id', $event->id)
            ->where('venue_id', $venue->id)
            ->whereDate('scheduled_date', $date->toDateString())
            ->first() ?? new EventSchedule([
                'meet_id' => $meet->id,
                'event_id' => $event->id,
                'venue_id' => $venue->id,
                'scheduled_date' => $date->toDateString(),
            ]);

        $slot->fill([
            'starts_at' => $date->copy()->setTime(8, 0)->format('H:i:s'),
            'ends_at' => $date->copy()->setTime(10, 0)->format('H:i:s'),
            'note' => $note,
        ])->save();

        return $slot;
    }

    private function match(Meet $meet, Event $event, EventSchedule $slot, string $roundLabel): EventMatch
    {
        return EventMatch::query()->firstOrCreate(
            ['meet_id' => $meet->id, 'event_id' => $event->id, 'event_schedule_id' => $slot->id],
            ['round_label' => $roundLabel, 'sequence' => 1],
        );
    }

    private function scheduledMatch(Meet $meet, Event $event, Venue $venue, string $roundLabel, Carbon $date): void
    {
        $slot = $this->slot($meet, $event, $venue, $date, 'Scheduled — awaiting live scoring');
        $this->match($meet, $event, $slot, $roundLabel);
    }

    /**
     * @param  array<string, mixed>  $sportState
     * @param  (callable(ScoringSession): void)|null  $openingEvent
     */
    private function inProgressMatch(
        Meet $meet,
        Event $event,
        Venue $venue,
        string $roundLabel,
        Carbon $date,
        string $sideA,
        string $sideB,
        ?User $admin,
        int $scoreA,
        int $scoreB,
        string $periodLabel,
        array $sportState,
        ?callable $openingEvent = null,
    ): void {
        $slot = $this->slot($meet, $event, $venue, $date, 'Live — in progress');
        $match = $this->match($meet, $event, $slot, $roundLabel);

        $session = $match->scoringSessions()->where('status', '!=', ScoringSessionStatus::Ended->value)->first();
        $isNew = $session === null;

        if ($session === null) {
            $session = new ScoringSession(['match_id' => $match->id]);
        }

        $session->fill(['side_a_label' => $sideA, 'side_b_label' => $sideB]);
        $session->forceFill([
            'status' => ScoringSessionStatus::InProgress,
            'score_a' => $scoreA,
            'score_b' => $scoreB,
            'period_label' => $periodLabel,
            'sport_state' => $sportState,
            'started_by' => $admin?->id,
            'started_at' => $date->copy()->subMinutes(25),
        ])->save();

        if ($isNew && $openingEvent !== null) {
            $openingEvent($session);
        }
    }

    /**
     * @param  array<string, mixed>  $sportState
     */
    private function completedMatch(
        Meet $meet,
        Event $event,
        Venue $venue,
        string $roundLabel,
        Carbon $date,
        string $sideA,
        string $sideB,
        ?User $admin,
        int $scoreA,
        int $scoreB,
        string $periodLabel,
        array $sportState,
    ): void {
        $slot = $this->slot($meet, $event, $venue, $date, 'Completed');
        $match = $this->match($meet, $event, $slot, $roundLabel);

        if ($match->status !== MatchStatus::Completed) {
            $match->forceFill(['status' => MatchStatus::Completed])->save();
        }

        $session = $match->scoringSessions()->latest('id')->first();
        $isNew = $session === null;

        if ($session === null) {
            $session = new ScoringSession(['match_id' => $match->id]);
        }

        if ($session->status !== ScoringSessionStatus::Ended) {
            $session->fill(['side_a_label' => $sideA, 'side_b_label' => $sideB]);
            $session->forceFill([
                'status' => ScoringSessionStatus::Ended,
                'score_a' => $scoreA,
                'score_b' => $scoreB,
                'period_label' => $periodLabel,
                'sport_state' => $sportState,
                'started_by' => $admin?->id,
                'started_at' => $date->copy()->subHours(2),
                'ended_by' => $admin?->id,
                'ended_at' => $date,
            ])->save();
        }

        if ($isNew) {
            ScoreEvent::create([
                'scoring_session_id' => $session->id,
                'type' => ScoreEventType::Ended,
                'payload' => ['score_a' => $scoreA, 'score_b' => $scoreB],
                'recorded_by' => $admin?->id,
            ]);
        }
    }
}
