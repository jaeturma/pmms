<?php

use App\Enums\ManagementTeamMemberStatus;
use App\Enums\ManagementTeamType;
use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Models\Athlete;
use App\Models\CoachAssignmentRequest;
use App\Models\Delegation;
use App\Models\Entry;
use App\Models\Event;
use App\Models\ManagementTeam;
use App\Models\ManagementTeamMember;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\Sport;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('the dashboard provides compact athlete and entry stats without recent activity', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('dashboard')
            ->has('stats', 2)
            ->missing('recentActivity'),
    );
});

test('the dashboard stats show only athlete and entry counts', function () {
    $delegation = Delegation::factory()->create();
    Athlete::factory()->count(3)->for($delegation)->create();

    $response = $this->actingAs(User::factory()->create())->get(route('dashboard'));

    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('dashboard')
            ->has('stats', 2)
            ->where('stats.0.key', 'athletes')
            ->where('stats.0.value', 3)
            ->where('stats.1.key', 'entries')
            ->where('stats.1.value', 0),
    );
});

test('the dashboard reports athlete and coach totals for every event under each sport', function () {
    $meet = Meet::current();
    $sport = Sport::factory()->create(['name' => 'Athletics']);
    $events = Event::factory()->count(2)->create(['sport_id' => $sport->id]);
    $meet->events()->attach($events);
    $delegation = Delegation::factory()->create(['meet_id' => $meet->id]);
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);
    Entry::factory()->create([
        'delegation_id' => $delegation->id,
        'athlete_id' => $athlete->id,
        'event_id' => $events->first()->id,
    ]);
    CoachAssignmentRequest::query()->create([
        'user_id' => User::factory()->coach()->create()->id,
        'meet_sport_id' => MeetSport::factory()->create(['meet_id' => $meet->id, 'sport_id' => $sport->id])->id,
        'delegation_id' => $delegation->id,
        'event_id' => $events->first()->id,
        'school_id' => null,
        'status' => 'approved',
        'ended_at' => null,
    ]);

    $this->actingAs(User::factory()->admin()->create())->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sportsEventReport.sports_count', 1)
            ->where('sportsEventReport.events_count', 2)
            ->where('sportsEventReport.rows', fn ($rows) => collect($rows)
                ->contains(fn ($row) => $row['id'] === $events->first()->id
                    && $row['athletes_count'] === 1
                    && $row['coaches_count'] === 1)
                && collect($rows)->contains(fn ($row) => $row['id'] === $events->last()->id
                    && $row['athletes_count'] === 0)));
});

test('tournament personnel dashboard event reports respect active event scope', function (MeetSportAssignmentRole $role) {
    $meet = Meet::current();
    $assignedSport = Sport::factory()->create();
    $otherSport = Sport::factory()->create();
    $assignedEvent = Event::factory()->create(['sport_id' => $assignedSport->id]);
    $otherEvent = Event::factory()->create(['sport_id' => $otherSport->id]);
    $meet->events()->attach([$assignedEvent->id, $otherEvent->id]);
    $user = User::factory()->create();
    MeetSportAssignment::factory()->create([
        'meet_sport_id' => MeetSport::factory()->create(['meet_id' => $meet->id, 'sport_id' => $assignedSport->id])->id,
        'user_id' => $user->id,
        'role' => $role,
        'status' => MeetSportAssignmentStatus::Active,
    ]);

    $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
        ->has('sportsEventReport.rows', 1)
        ->where('sportsEventReport.rows.0.id', $assignedEvent->id));
})->with([
    'manager' => MeetSportAssignmentRole::TournamentManager,
    'assistant manager' => MeetSportAssignmentRole::AssistantTournamentManager,
    'secretary' => MeetSportAssignmentRole::TournamentSecretary,
    'ICT' => MeetSportAssignmentRole::TournamentICT,
    'technical official' => MeetSportAssignmentRole::TechnicalOfficial,
]);

test('central ICT dashboard receives system-wide sports and event data', function () {
    $meet = Meet::current();
    $sport = Sport::factory()->create();
    $events = Event::factory()->count(2)->create(['sport_id' => $sport->id]);
    $meet->events()->attach($events);
    $ict = User::factory()->create();
    $team = ManagementTeam::factory()->create(['meet_id' => $meet->id, 'team_type' => ManagementTeamType::ICT]);
    ManagementTeamMember::factory()->create([
        'management_team_id' => $team->id,
        'user_id' => $ict->id,
        'status' => ManagementTeamMemberStatus::Active,
    ]);

    $this->actingAs($ict)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('currentMeet')
        ->has('stats', 2)
        ->where('sportsEventReport.events_count', 2)
        ->has('sportsEventReport.rows', 2)
        ->has('announcements'));
});
