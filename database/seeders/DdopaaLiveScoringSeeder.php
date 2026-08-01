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

        $now = Carbon::now();

        $liveSession = $this->inProgressMatch(
            $meet, $event, $venue, 'Semifinal', $now, $sideA, $sideB, $admin,
            scoreA: 38, scoreB: 41, periodLabel: 'Q3',
            // SYNTHETIC_DEMO (WP4 basketball, now richer for the reskinned
            // live scoreboard, docs/ui-ux/basketball.html): every key past
            // `fouls_a`/`fouls_b` is optional, additive `sport_state` —
            // `PortalBasketballScoreboard`/`PortalBasketballSidebar` fall
            // back to an honest "not tracked" empty state for any real
            // basketball session that doesn't carry them.
            sportState: [
                'fouls_a' => 3,
                'fouls_b' => 2,
                'timeouts_a' => 2,
                'timeouts_b' => 1,
                'shot_clock_seconds' => 18,
                'quarters' => [
                    ['label' => 'Q1', 'a' => 14, 'b' => 12],
                    ['label' => 'Q2', 'a' => 15, 'b' => 16],
                    ['label' => 'Q3', 'a' => 9, 'b' => 13],
                ],
                'team_stats' => [
                    'a' => [
                        'fg_made' => 14, 'fg_att' => 30,
                        'three_made' => 4, 'three_att' => 11,
                        'ft_made' => 6, 'ft_att' => 8,
                        'rebounds' => 22, 'assists' => 10, 'turnovers' => 7, 'steals' => 5, 'blocks' => 3,
                    ],
                    'b' => [
                        'fg_made' => 15, 'fg_att' => 33,
                        'three_made' => 3, 'three_att' => 9,
                        'ft_made' => 8, 'ft_att' => 10,
                        'rebounds' => 24, 'assists' => 8, 'turnovers' => 9, 'steals' => 6, 'blocks' => 2,
                    ],
                ],
                'box_score' => [
                    'a' => [
                        ['number' => '8', 'name' => 'Miguel Aranas', 'pts' => 16, 'reb' => 5, 'ast' => 3],
                        ['number' => '12', 'name' => 'John Villaruz', 'pts' => 10, 'reb' => 3, 'ast' => 2],
                        ['number' => '5', 'name' => 'Carlo Mabini', 'pts' => 6, 'reb' => 7, 'ast' => 1],
                    ],
                    'b' => [
                        ['number' => '10', 'name' => 'Kevin Bacal', 'pts' => 14, 'reb' => 3, 'ast' => 4],
                        ['number' => '7', 'name' => 'Mark Suson', 'pts' => 12, 'reb' => 5, 'ast' => 2],
                        ['number' => '11', 'name' => 'Paolo Enot', 'pts' => 9, 'reb' => 6, 'ast' => 1],
                    ],
                ],
            ],
            events: $this->basketballPlayByPlay($now->copy()->subMinutes(25)),
        );

        // `slot()`/`match()` key the "live" match to *today's* date, so
        // re-running this seeder on a later day creates a fresh
        // `EventMatch`/`ScoringSession` rather than reusing yesterday's —
        // which would otherwise leave that old session stuck "in
        // progress" forever, competing with today's as a second "live"
        // basketball game. Ending every other non-ended session on this
        // same event keeps exactly one live at a time, same as a real
        // meet would only ever have one Girls Secondary basketball game
        // live simultaneously on one court.
        ScoringSession::query()
            ->where('id', '!=', $liveSession->id)
            ->where('status', '!=', ScoringSessionStatus::Ended->value)
            ->whereHas('match', fn ($query) => $query->where('event_id', $event->id))
            ->get()
            ->each(fn (ScoringSession $stale) => $stale->forceFill([
                'status' => ScoringSessionStatus::Ended,
                'ended_by' => $admin?->id,
                'ended_at' => $now,
            ])->save());

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

        $now = Carbon::now();

        // Two of three rounds fought — score is the sum of rounds so far.
        $liveSession = $this->inProgressMatch(
            $meet, $event, $venue, 'Bout 2', $now, $sideA, $sideB, $admin,
            scoreA: 20, scoreB: 17, periodLabel: 'Round 3',
            // SYNTHETIC_DEMO (WP4 boxing, now richer for the reskinned
            // live scoreboard, docs/ui-ux/boxing.html): `rounds` is the
            // only real key — every boxing session gets it from
            // `ScoringSessionController::round()`. Everything else is
            // optional, additive `sport_state`; `PortalBoxingScoreboard`/
            // `PortalBoxingSidebar` fall back to an honest "not tracked"
            // empty state for any real bout that doesn't carry them.
            sportState: [
                'rounds' => [
                    ['round' => 1, 'score_a' => 10, 'score_b' => 9],
                    ['round' => 2, 'score_a' => 10, 'score_b' => 8],
                ],
                'total_rounds' => 3,
                'rounds_format' => '3 × 2 minutes',
                'weight_class' => 'Lightweight — 57 kg',
                'ring' => 'Ring A',
                'knockdowns_a' => 1,
                'knockdowns_b' => 0,
                'judges' => [
                    ['name' => 'Judge 1', 'red' => 10, 'blue' => 9],
                    ['name' => 'Judge 2', 'red' => 9, 'blue' => 10],
                    ['name' => 'Judge 3', 'red' => 10, 'blue' => 9],
                ],
                'bout_stats' => [
                    'a' => ['punches_landed' => 34, 'punches_thrown' => 62],
                    'b' => ['punches_landed' => 31, 'punches_thrown' => 65],
                ],
                'warnings_a' => 0,
                'warnings_b' => 1,
                'deductions' => 0,
                'officials' => [
                    ['name' => 'Ramon Villanueva', 'role' => 'Referee'],
                    ['name' => 'Liza Mendoza', 'role' => 'Judge 1'],
                    ['name' => 'Carlo Garcia', 'role' => 'Judge 2'],
                ],
            ],
            events: $this->boxingPlayByPlay($now->copy()->subMinutes(25)),
        );

        // Same reasoning as basketball's cleanup above: `slot()`'s
        // date-keyed lookup makes a fresh `EventMatch`/`ScoringSession`
        // every time this seeder runs on a new day, so end any other
        // still-live boxing session on this event rather than let two
        // "live" bouts coexist.
        ScoringSession::query()
            ->where('id', '!=', $liveSession->id)
            ->where('status', '!=', ScoringSessionStatus::Ended->value)
            ->whereHas('match', fn ($query) => $query->where('event_id', $event->id))
            ->get()
            ->each(fn (ScoringSession $stale) => $stale->forceFill([
                'status' => ScoringSessionStatus::Ended,
                'ended_by' => $admin?->id,
                'ended_at' => $now,
            ])->save());

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

        $now = Carbon::now();

        // Partway through the top of the 4th — innings so far sum to the
        // running score.
        $liveSession = $this->inProgressMatch(
            $meet, $event, $venue, 'Semifinal', $now, $sideA, $sideB, $admin,
            scoreA: 3, scoreB: 1, periodLabel: 'Top 4th',
            // SYNTHETIC_DEMO, same additive-`sport_state` approach as the
            // basketball demo above: `inning`/`half`/`outs`/`balls`/
            // `strikes`/`innings` are real (every softball session gets
            // them from `ScoringSessionController::count()`/
            // `inningRun()`); everything past that is optional and
            // `PortalSoftballScoreboard`/`PortalSoftballSidebar` fall
            // back to an honest "not tracked" state without it.
            sportState: [
                'inning' => 4, 'half' => 'top', 'outs' => 1, 'balls' => 1, 'strikes' => 2,
                'innings' => [
                    ['inning' => 1, 'runs_a' => 1, 'runs_b' => 0],
                    ['inning' => 2, 'runs_a' => 0, 'runs_b' => 1],
                    ['inning' => 3, 'runs_a' => 2, 'runs_b' => 0],
                ],
                'runners' => ['first' => true, 'second' => false, 'third' => true],
                'hits_a' => 8, 'hits_b' => 5,
                'errors_a' => 1, 'errors_b' => 2,
                'team_stats' => [
                    'a' => ['walks' => 5, 'strikeouts' => 6, 'stolen_bases' => 2, 'batting_avg' => '.400', 'slugging_pct' => '.750'],
                    'b' => ['walks' => 3, 'strikeouts' => 4, 'stolen_bases' => 1, 'batting_avg' => '.263', 'slugging_pct' => '.500'],
                ],
                'box_score' => [
                    'a' => [
                        ['number' => '12', 'name' => 'Juan Dela Cruz', 'line' => '2-3, 2 RBI, 1 2B'],
                        ['number' => '7', 'name' => 'Mark Santos', 'line' => '1-2, 1 RBI, 1 BB'],
                        ['number' => '15', 'name' => 'Carlo Reyes', 'line' => '3.0 IP, 2 H, 1 R, 3 K'],
                    ],
                    'b' => [
                        ['number' => '10', 'name' => 'Kevin Garcia', 'line' => '2-3, 1 RBI, 1 2B'],
                        ['number' => '8', 'name' => 'Miguel Antonio', 'line' => '1-2, 1 R, 1 BB'],
                        ['number' => '11', 'name' => 'Paolo Mendoza', 'line' => '2.1 IP, 6 H, 5 R, 2 K'],
                    ],
                ],
                'pitchers' => [
                    'a' => ['number' => '15', 'name' => 'Carlo Reyes', 'throws' => 'RHP', 'line' => '3.0 IP, 2 H, 1 R, 3 K', 'pitches' => 48],
                    'b' => ['number' => '11', 'name' => 'Paolo Mendoza', 'throws' => 'RHP', 'line' => '2.1 IP, 6 H, 5 R, 2 K', 'pitches' => 52],
                ],
            ],
            events: $this->softballPlayByPlay($now->copy()->subMinutes(25)),
        );

        // Same reasoning as basketball's cleanup above: `slot()`'s
        // date-keyed lookup makes a fresh `EventMatch`/`ScoringSession`
        // every time this seeder runs on a new day, so end any other
        // still-live softball session on this event rather than let two
        // "live" games coexist.
        ScoringSession::query()
            ->where('id', '!=', $liveSession->id)
            ->where('status', '!=', ScoringSessionStatus::Ended->value)
            ->whereHas('match', fn ($query) => $query->where('event_id', $event->id))
            ->get()
            ->each(fn (ScoringSession $stale) => $stale->forceFill([
                'status' => ScoringSessionStatus::Ended,
                'ended_by' => $admin?->id,
                'ended_at' => $now,
            ])->save());

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

    /**
     * A full-game synthetic point-by-point log for the basketball demo,
     * timestamped across the ~25 minutes since `$startedAt` — real
     * `ScoreEvent` rows so `ScoringSession::playByPlay()`'s replay lands
     * on exactly the seeded `score_a`/`score_b` (38/41): side "a" deltas
     * sum to 38, side "b" deltas sum to 41. Three fouls land on side "a"
     * and two on side "b", matching `sport_state.fouls_a`/`fouls_b`.
     *
     * @return array<int, array{type: ScoreEventType, payload: array<string, mixed>, at: Carbon}>
     */
    private function basketballPlayByPlay(Carbon $startedAt): array
    {
        $aPoints = [2, 3, 2, 2, 3, 2, 3, 2, 2, 3, 2, 2, 3, 2, 2, 3];
        $bPoints = [3, 2, 3, 2, 3, 3, 2, 3, 2, 3, 2, 3, 2, 3, 3, 2];

        $events = [];

        foreach ($aPoints as $index => $delta) {
            $events[] = ['type' => ScoreEventType::Point, 'payload' => ['side' => 'a', 'delta' => $delta]];
            $events[] = ['type' => ScoreEventType::Point, 'payload' => ['side' => 'b', 'delta' => $bPoints[$index]]];
        }

        // Fouls sprinkled through the game, spliced in at fixed positions
        // in the interleaved feed above.
        foreach ([4 => 'a', 10 => 'b', 18 => 'a', 24 => 'b', 28 => 'a'] as $position => $side) {
            array_splice($events, $position, 0, [[
                'type' => ScoreEventType::Foul,
                'payload' => ['side' => $side],
            ]]);
        }

        $secondsPerEvent = intdiv(1400, count($events));

        return array_map(
            fn (array $event, int $index): array => [...$event, 'at' => $startedAt->copy()->addSeconds($secondsPerEvent * ($index + 1))],
            $events,
            array_keys($events),
        );
    }

    /**
     * A synthetic two-round `RoundScore` log for the boxing demo, timed
     * across the ~25 minutes since `$startedAt` — real `ScoreEvent` rows
     * whose cumulative totals land on exactly the seeded `score_a`/
     * `score_b` (20/17): round 1 (10-9) plus round 2 (10-8).
     *
     * @return array<int, array{type: ScoreEventType, payload: array<string, mixed>, at: Carbon}>
     */
    private function boxingPlayByPlay(Carbon $startedAt): array
    {
        return [
            [
                'type' => ScoreEventType::RoundScore,
                'payload' => ['round' => 1, 'score_a' => 10, 'score_b' => 9],
                'at' => $startedAt->copy()->addMinutes(12),
            ],
            [
                'type' => ScoreEventType::RoundScore,
                'payload' => ['round' => 2, 'score_a' => 10, 'score_b' => 8],
                'at' => $startedAt->copy()->addMinutes(24),
            ],
        ];
    }

    /**
     * A full-game synthetic count/inning-run log for the softball demo,
     * timestamped across the ~25 minutes since `$startedAt` — real
     * `ScoreEvent` rows whose `InningRun` entries sum to exactly the
     * seeded `score_a`/`score_b` (3/1), and whose final `Count` entry
     * matches the current `sport_state` exactly (1 out, 1-2 count),
     * so the live feed's most recent row agrees with the scoreboard.
     *
     * @return array<int, array{type: ScoreEventType, payload: array<string, mixed>, at: Carbon}>
     */
    private function softballPlayByPlay(Carbon $startedAt): array
    {
        $events = [
            $this->softballCountEvent('ball', outs: 0, balls: 1, strikes: 0),
            $this->softballCountEvent('strike', outs: 0, balls: 1, strikes: 1),
            $this->softballRunEvent(side: 'a', runs: 1, inning: 1),
            $this->softballCountEvent('out', outs: 1, balls: 0, strikes: 0),
            $this->softballCountEvent('out', outs: 2, balls: 0, strikes: 0),
            $this->softballCountEvent('out', outs: 3, balls: 0, strikes: 0),
            $this->softballCountEvent('ball', outs: 0, balls: 1, strikes: 0),
            $this->softballRunEvent(side: 'b', runs: 1, inning: 2),
            $this->softballCountEvent('strike', outs: 0, balls: 0, strikes: 1),
            $this->softballCountEvent('out', outs: 1, balls: 0, strikes: 0),
            $this->softballRunEvent(side: 'a', runs: 2, inning: 3),
            $this->softballCountEvent('ball', outs: 0, balls: 1, strikes: 0),
            $this->softballCountEvent('strike', outs: 0, balls: 1, strikes: 1),
            $this->softballCountEvent('out', outs: 1, balls: 0, strikes: 0),
            $this->softballCountEvent('strike', outs: 1, balls: 1, strikes: 2),
        ];

        $secondsPerEvent = intdiv(1400, count($events));

        return array_map(
            fn (array $event, int $index): array => [...$event, 'at' => $startedAt->copy()->addSeconds($secondsPerEvent * ($index + 1))],
            $events,
            array_keys($events),
        );
    }

    /**
     * @return array{type: ScoreEventType, payload: array<string, mixed>}
     */
    private function softballCountEvent(string $action, int $outs, int $balls, int $strikes): array
    {
        return [
            'type' => ScoreEventType::Count,
            'payload' => ['action' => $action, 'outs' => $outs, 'balls' => $balls, 'strikes' => $strikes],
        ];
    }

    /**
     * @return array{type: ScoreEventType, payload: array<string, mixed>}
     */
    private function softballRunEvent(string $side, int $runs, int $inning): array
    {
        return [
            'type' => ScoreEventType::InningRun,
            'payload' => ['side' => $side, 'runs' => $runs, 'inning' => $inning],
        ];
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
     * @param  array<int, array{type: ScoreEventType, payload: array<string, mixed>, at: Carbon}>|null  $events
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
        ?array $events = null,
    ): ScoringSession {
        $slot = $this->slot($meet, $event, $venue, $date, 'Live — in progress');
        $match = $this->match($meet, $event, $slot, $roundLabel);

        $session = $match->scoringSessions()->where('status', '!=', ScoringSessionStatus::Ended->value)->first();

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

        // Re-seedable: every run replaces this session's synthetic event
        // log with a fresh one timed relative to the `started_at` just
        // saved above, rather than only seeding it once on first creation
        // — otherwise re-running this seeder against an already-live demo
        // session (the common case) would silently keep stale timestamps.
        if ($events !== null) {
            $session->events()->delete();

            foreach ($events as $eventSpec) {
                ScoreEvent::create([
                    'scoring_session_id' => $session->id,
                    'type' => $eventSpec['type'],
                    'payload' => $eventSpec['payload'],
                    'recorded_by' => $admin?->id,
                ])->forceFill(['created_at' => $eventSpec['at']])->save();
            }
        }

        return $session;
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
