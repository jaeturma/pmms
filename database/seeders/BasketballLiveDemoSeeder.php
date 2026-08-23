<?php

namespace Database\Seeders;

use App\Enums\AgeDivision;
use App\Enums\DelegationStatus;
use App\Enums\EntryStatus;
use App\Enums\GenderCategory;
use App\Enums\MatchStatus;
use App\Enums\MeetStatus;
use App\Enums\SchoolLevel;
use App\Enums\ScoreEventType;
use App\Enums\ScoringSessionStatus;
use App\Enums\Sex;
use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\District;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\MatchRosterPlayer;
use App\Models\Meet;
use App\Models\School;
use App\Models\ScoreEvent;
use App\Models\ScoringSession;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Standalone, local-development-only sample dataset for the live
 * basketball scoreboard control feature (roster, on-court subs, clocks,
 * possession, player-attributed scoring) — lets a reviewer open the
 * scoreboard and immediately see it "in action" instead of hand-building a
 * match/roster/session first. Own Meet/Event/Schools/Delegations, doesn't
 * touch or depend on DdOPAA2026ShowcaseSeeder's dataset.
 *
 * Idempotent throughout (`firstOrCreate`), safe to re-run — re-running
 * resets the demo session's play-by-play back to its starting scripted
 * state rather than accumulating duplicate events.
 *
 * Run with: php artisan db:seed --class=BasketballLiveDemoSeeder
 */
class BasketballLiveDemoSeeder extends Seeder
{
    public function run(): void
    {
        $sport = Sport::query()->firstOrCreate(['name' => 'Basketball'], ['active' => true]);

        $meet = Meet::query()->firstOrCreate(['name' => 'Basketball Scoreboard Demo'], [
            'school_year' => '2026-2027',
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addDays(3)->toDateString(),
            'venue' => 'Demo Gymnasium',
            'status' => MeetStatus::Active,
        ]);

        $event = Event::query()->firstOrCreate(
            ['sport_id' => $sport->id, 'name' => 'Basketball Boys Secondary (Demo)'],
            [
                'gender' => GenderCategory::Boys,
                'age_division' => AgeDivision::Secondary,
                'is_team_event' => true,
                'max_entries_per_delegation' => 12,
                'active' => true,
            ],
        );
        $meet->events()->syncWithoutDetaching([$event->id]);

        [$schoolA, $delegationA] = $this->school($meet, 'Warriors Demo High School');
        [$schoolB, $delegationB] = $this->school($meet, 'Eagles Demo High School');

        $rosterA = $this->confirmedSquad($delegationA, $schoolA, $event, 'Warrior');
        $rosterB = $this->confirmedSquad($delegationB, $schoolB, $event, 'Eagle');

        $match = EventMatch::query()->firstOrCreate(
            ['meet_id' => $meet->id, 'event_id' => $event->id, 'round_label' => 'Demo Game'],
            ['sequence' => 1, 'status' => MatchStatus::Scheduled],
        );
        $match->entries()->sync([$rosterA[0]->id, $rosterB[0]->id]);

        MatchRosterPlayer::query()->where('match_id', $match->id)->delete();
        $playersA = $this->buildRoster($match, 'a', $rosterA);
        $playersB = $this->buildRoster($match, 'b', $rosterB);

        $admin = User::query()->where('role', UserRole::Admin->value)->first()
            ?? User::factory()->admin()->create(['email' => 'admin@pmms.local']);

        ScoringSession::query()->where('match_id', $match->id)->delete();
        $this->scriptSession($match, $schoolA, $schoolB, $playersA, $playersB, $admin);

        $this->command?->info("Basketball scoreboard demo ready — /matches/{$match->id}/scoreboard");
    }

    /**
     * @return array{0: School, 1: Delegation}
     */
    private function school(Meet $meet, string $name): array
    {
        $district = District::query()->firstOrCreate(['name' => 'Demo District'], ['active' => true]);

        $school = School::query()->firstOrCreate(
            ['name' => $name],
            [
                'district_id' => $district->id,
                'school_id_code' => (string) (100000 + crc32($name) % 899999),
                'level' => SchoolLevel::Secondary,
                'address' => 'Demo Address',
                'active' => true,
            ],
        );

        $delegation = Delegation::query()->firstOrCreate(
            ['meet_id' => $meet->id, 'school_id' => $school->id],
            [
                'head_name' => "{$name} Coach",
                'head_phone' => '09171234567',
                'head_email' => strtolower(str_replace(' ', '.', $name)).'@demo.pmms.local',
                'status' => DelegationStatus::Approved,
            ],
        );

        return [$school, $delegation];
    }

    /**
     * Nine confirmed entries (5 starters + 4 bench) — a real squad size,
     * sourced the same way live registration would produce them (multiple
     * athletes registered to the same team event under one delegation, up
     * to `max_entries_per_delegation`).
     *
     * @return Collection<int, Entry>
     */
    private function confirmedSquad(Delegation $delegation, School $school, Event $event, string $prefix)
    {
        return collect(range(1, 9))->map(function (int $i) use ($delegation, $school, $event, $prefix) {
            $athlete = Athlete::query()->firstOrCreate(
                ['delegation_id' => $delegation->id, 'first_name' => $prefix, 'last_name' => "Player {$i}"],
                [
                    'school_id' => $school->id,
                    'sex' => Sex::Male,
                    'birthdate' => now()->subYears(16)->subDays($i),
                    'lrn' => (string) (100000000000 + crc32("{$prefix}{$i}") % 899999999999),
                    'grade_level' => 10,
                ],
            );

            $entry = Entry::query()->firstOrCreate(
                ['athlete_id' => $athlete->id, 'event_id' => $event->id],
                ['delegation_id' => $delegation->id],
            );
            $entry->forceFill(['status' => EntryStatus::Confirmed])->save();

            return $entry;
        });
    }

    /**
     * @param  Collection<int, Entry>  $entries
     * @return Collection<int, MatchRosterPlayer>
     */
    private function buildRoster(EventMatch $match, string $side, $entries)
    {
        return $entries->values()->map(function (Entry $entry, int $index) use ($match, $side) {
            return MatchRosterPlayer::create([
                'match_id' => $match->id,
                'entry_id' => $entry->id,
                'side' => $side,
                'jersey_number' => (string) ($index + 4),
                'is_starter' => $index < 5,
            ]);
        });
    }

    /**
     * @param  Collection<int, MatchRosterPlayer>  $playersA
     * @param  Collection<int, MatchRosterPlayer>  $playersB
     */
    private function scriptSession(
        EventMatch $match,
        School $schoolA,
        School $schoolB,
        $playersA,
        $playersB,
        User $admin,
    ): void {
        $startersA = $playersA->take(5);
        $startersB = $playersB->take(5);
        $scorerA = $startersA[0];
        $scorerA2 = $startersA[1];
        $foulerA = $startersA[2];
        $scorerB = $startersB[0];
        $benchInA = $playersA[5];
        $benchedOutA = $startersA[4];

        $session = ScoringSession::create([
            'match_id' => $match->id,
            'side_a_label' => $schoolA->name,
            'side_b_label' => $schoolB->name,
        ]);
        $session->forceFill([
            'status' => ScoringSessionStatus::InProgress,
            'started_by' => $admin->id,
            'started_at' => now()->subMinutes(6),
        ])->save();

        $state = [
            'fouls_a' => 0, 'fouls_b' => 0,
            'on_court_a' => $startersA->pluck('id')->all(),
            'on_court_b' => $startersB->pluck('id')->all(),
            'possession' => 'a',
            'player_points' => [], 'player_fouls' => [],
            'game_clock_seconds' => 600, 'game_clock_updated_at' => null,
            'shot_clock_seconds' => 24, 'shot_clock_updated_at' => null,
            'minutes_per_period' => 10, 'shot_clock_duration' => 24,
            'team_color_a' => '#7f1d1d', 'team_color_b' => '#1e3a8a',
            'horn_sounded_at' => null, 'quarters' => 4,
        ];

        $t = now()->subMinutes(6);
        $log = function (ScoreEventType $type, array $payload = []) use (&$t, $session, $admin) {
            $t = $t->copy()->addSeconds(random_int(15, 45));
            $event = ScoreEvent::create([
                'scoring_session_id' => $session->id,
                'type' => $type,
                'payload' => $payload === [] ? null : $payload,
                'recorded_by' => $admin->id,
            ]);
            $event->forceFill(['created_at' => $t])->save();
        };

        // Tip-off: starters checked in, horn sounds to open the period.
        foreach ([...$startersA, ...$startersB] as $player) {
            $log(ScoreEventType::Substitution, [
                'side' => $player->side,
                'roster_player_id' => $player->id,
                'on_court' => true,
                'player_name' => $player->entry->athlete->fullName(),
            ]);
        }
        $state['horn_sounded_at'] = $t->toIso8601String();
        $log(ScoreEventType::Horn);

        // A basket, a three, an answer, a personal foul. score_a/score_b
        // are the session's own real columns, never duplicated into
        // sport_state — tracked here as plain locals just to compute the
        // running total for each event's payload and the final forceFill.
        $scoreA = 0;
        $scoreB = 0;

        $scoreA += 2;
        $state['player_points'][(string) $scorerA->id] = 2;
        $log(ScoreEventType::Point, ['side' => 'a', 'delta' => 2, 'result' => $scoreA, 'roster_player_id' => $scorerA->id, 'player_name' => $scorerA->entry->athlete->fullName()]);

        $scoreB += 3;
        $state['player_points'][(string) $scorerB->id] = 3;
        $log(ScoreEventType::Point, ['side' => 'b', 'delta' => 3, 'result' => $scoreB, 'roster_player_id' => $scorerB->id, 'player_name' => $scorerB->entry->athlete->fullName()]);

        $state['fouls_a'] = 1;
        $state['player_fouls'][(string) $foulerA->id] = 1;
        $log(ScoreEventType::Foul, ['side' => 'a', 'action' => 'add', 'fouls_a' => 1, 'fouls_b' => 0, 'player_name' => $foulerA->entry->athlete->fullName()]);

        $scoreA += 2;
        $state['player_points'][(string) $scorerA2->id] = 2;
        $log(ScoreEventType::Point, ['side' => 'a', 'delta' => 2, 'result' => $scoreA, 'roster_player_id' => $scorerA2->id, 'player_name' => $scorerA2->entry->athlete->fullName()]);

        // A dead ball: pause, substitute, resume — demonstrates the
        // "subs only while paused" rule with real history.
        $log(ScoreEventType::Paused);
        $state['on_court_a'] = array_values(array_diff($state['on_court_a'], [$benchedOutA->id]));
        $log(ScoreEventType::Substitution, [
            'side' => 'a', 'roster_player_id' => $benchedOutA->id, 'on_court' => false,
            'player_name' => $benchedOutA->entry->athlete->fullName(),
        ]);
        $state['on_court_a'][] = $benchInA->id;
        $log(ScoreEventType::Substitution, [
            'side' => 'a', 'roster_player_id' => $benchInA->id, 'on_court' => true,
            'player_name' => $benchInA->entry->athlete->fullName(),
        ]);
        $log(ScoreEventType::Resumed);

        $state['game_clock_seconds'] = 480;
        $state['game_clock_updated_at'] = now()->toIso8601String();
        $state['shot_clock_seconds'] = 18;
        $state['shot_clock_updated_at'] = now()->toIso8601String();

        $session->forceFill([
            'score_a' => $scoreA,
            'score_b' => $scoreB,
            'period_label' => 'Q1',
            'sport_state' => $state,
        ])->save();
    }
}
