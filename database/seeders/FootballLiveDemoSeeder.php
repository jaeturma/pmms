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
use App\Models\Meet;
use App\Models\School;
use App\Models\ScoreEvent;
use App\Models\ScoringSession;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Standalone, local-development-only sample dataset for the live football/
 * futsal scoreboard control feature (goals via the generic score endpoint,
 * yellow/red card tallies, half stepper) — same purpose and conventions as
 * `BasketballLiveDemoSeeder`/`BoxingLiveDemoSeeder`/`SoftballLiveDemoSeeder`/
 * `VolleyballLiveDemoSeeder`. Own Meet/Event/Schools/Delegations, doesn't
 * touch or depend on any other seeder's dataset. Idempotent throughout
 * (`firstOrCreate`), safe to re-run.
 *
 * Run with: php artisan db:seed --class=FootballLiveDemoSeeder
 */
class FootballLiveDemoSeeder extends Seeder
{
    public function run(): void
    {
        $sport = Sport::query()->firstOrCreate(['name' => 'Football'], ['active' => true]);

        $meet = Meet::query()->firstOrCreate(['name' => 'Football Scoreboard Demo'], [
            'school_year' => '2026-2027',
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addDays(3)->toDateString(),
            'venue' => 'Demo Field',
            'status' => MeetStatus::Active,
        ]);

        $event = Event::query()->firstOrCreate(
            ['sport_id' => $sport->id, 'name' => 'Football Boys Secondary (Demo)'],
            [
                'gender' => GenderCategory::Boys,
                'age_division' => AgeDivision::Secondary,
                'is_team_event' => true,
                'max_entries_per_delegation' => 18,
                'active' => true,
            ],
        );
        $meet->events()->syncWithoutDetaching([$event->id]);

        $district = District::query()->firstOrCreate(['name' => 'Demo District'], ['active' => true]);
        [$schoolA, $entryA] = $this->captain($meet, $district, $event, 'United Demo High School', 'Home', 'Captain');
        [$schoolB, $entryB] = $this->captain($meet, $district, $event, 'City Demo High School', 'Away', 'Captain');

        $match = EventMatch::query()->firstOrCreate(
            ['meet_id' => $meet->id, 'event_id' => $event->id, 'round_label' => 'Demo Match'],
            ['sequence' => 1, 'status' => MatchStatus::Scheduled],
        );
        $match->entries()->sync([$entryA->id, $entryB->id]);

        $admin = User::query()->where('role', UserRole::Admin->value)->first()
            ?? User::factory()->admin()->create(['email' => 'admin@pmms.local']);

        ScoringSession::query()->where('match_id', $match->id)->delete();
        $this->scriptSession($match, $schoolA, $schoolB, $admin);

        $this->command?->info("Football scoreboard demo ready — /matches/{$match->id}/scoreboard");
    }

    /**
     * @return array{0: School, 1: Entry}
     */
    private function captain(Meet $meet, District $district, Event $event, string $schoolName, string $first, string $last): array
    {
        $school = School::query()->firstOrCreate(
            ['name' => $schoolName],
            [
                'district_id' => $district->id,
                'school_id_code' => (string) (100000 + crc32($schoolName) % 899999),
                'level' => SchoolLevel::Secondary,
                'address' => 'Demo Address',
                'active' => true,
            ],
        );

        $delegation = Delegation::query()->firstOrCreate(
            ['meet_id' => $meet->id, 'school_id' => $school->id],
            [
                'head_name' => "{$schoolName} Coach",
                'head_phone' => '09171234567',
                'head_email' => strtolower(str_replace(' ', '.', $schoolName)).'@demo.pmms.local',
                'status' => DelegationStatus::Approved,
            ],
        );

        $athlete = Athlete::query()->firstOrCreate(
            ['delegation_id' => $delegation->id, 'first_name' => $first, 'last_name' => $last],
            [
                'school_id' => $school->id,
                'sex' => Sex::Male,
                'birthdate' => now()->subYears(16),
                'lrn' => (string) (100000000000 + crc32($schoolName) % 899999999999),
                'grade_level' => 10,
            ],
        );

        $entry = Entry::query()->firstOrCreate(
            ['athlete_id' => $athlete->id, 'event_id' => $event->id],
            ['delegation_id' => $delegation->id],
        );
        $entry->forceFill(['status' => EntryStatus::Confirmed])->save();

        return [$school, $entry];
    }

    private function scriptSession(EventMatch $match, School $schoolA, School $schoolB, User $admin): void
    {
        $session = ScoringSession::create([
            'match_id' => $match->id,
            'side_a_label' => $schoolA->name,
            'side_b_label' => $schoolB->name,
        ]);
        $session->forceFill([
            'status' => ScoringSessionStatus::InProgress,
            'started_by' => $admin->id,
            'started_at' => now()->subMinutes(52),
        ])->save();

        $state = [
            'yellow_cards_a' => 1, 'yellow_cards_b' => 2,
            'red_cards_a' => 0, 'red_cards_b' => 1,
            'minutes_per_half' => 45,
        ];

        $t = now()->subMinutes(52);
        $log = function (ScoreEventType $type, array $payload = []) use (&$t, $session, $admin) {
            $t = $t->copy()->addSeconds(random_int(60, 180));
            $event = ScoreEvent::create([
                'scoring_session_id' => $session->id,
                'type' => $type,
                'payload' => $payload === [] ? null : $payload,
                'recorded_by' => $admin->id,
            ]);
            $event->forceFill(['created_at' => $t])->save();
        };

        $log(ScoreEventType::Point, ['side' => 'a', 'delta' => 1, 'result' => 1]);
        $log(ScoreEventType::Card, ['action' => 'add', 'side' => 'b', 'type' => 'yellow', 'yellow_cards_a' => 0, 'yellow_cards_b' => 1, 'red_cards_a' => 0, 'red_cards_b' => 0]);
        $log(ScoreEventType::Point, ['side' => 'b', 'delta' => 1, 'result' => 1]);
        $log(ScoreEventType::PeriodChange, ['period_label' => '2nd Half']);
        $log(ScoreEventType::Card, ['action' => 'add', 'side' => 'b', 'type' => 'yellow', 'yellow_cards_a' => 0, 'yellow_cards_b' => 2, 'red_cards_a' => 0, 'red_cards_b' => 0]);
        $log(ScoreEventType::Card, ['action' => 'add', 'side' => 'b', 'type' => 'red', 'yellow_cards_a' => 0, 'yellow_cards_b' => 2, 'red_cards_a' => 0, 'red_cards_b' => 1]);
        $log(ScoreEventType::Card, ['action' => 'add', 'side' => 'a', 'type' => 'yellow', 'yellow_cards_a' => 1, 'yellow_cards_b' => 2, 'red_cards_a' => 0, 'red_cards_b' => 1]);
        $log(ScoreEventType::Point, ['side' => 'a', 'delta' => 1, 'result' => 2]);

        $session->forceFill([
            'score_a' => 2,
            'score_b' => 1,
            'period_label' => '2nd Half',
            'sport_state' => $state,
        ])->save();
    }
}
