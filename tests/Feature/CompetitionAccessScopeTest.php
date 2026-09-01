<?php

use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\Entry;
use App\Models\Event;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('a category assignment sees its event across delegations but not sibling or unrelated events', function () {
    $meet = Meet::factory()->registrationOpen()->create();
    $basketball = Sport::factory()->create(['name' => 'Basketball']);
    $volleyball = Sport::factory()->create(['name' => 'Volleyball']);
    $boys = SportCategory::factory()->create(['sport_id' => $basketball->id, 'display_name' => 'Secondary Boys']);
    $girls = SportCategory::factory()->create(['sport_id' => $basketball->id, 'display_name' => 'Secondary Girls']);
    $boysEvent = Event::factory()->create(['sport_id' => $basketball->id, 'sport_category_id' => $boys->id]);
    $girlsEvent = Event::factory()->create(['sport_id' => $basketball->id, 'sport_category_id' => $girls->id]);
    $volleyballEvent = Event::factory()->create(['sport_id' => $volleyball->id]);
    $meet->events()->attach([$boysEvent->id, $girlsEvent->id, $volleyballEvent->id]);

    $nabunturan = Delegation::factory()->create(['meet_id' => $meet->id]);
    $monkayo = Delegation::factory()->create(['meet_id' => $meet->id]);
    $nabunturanBoy = Athlete::factory()->create(['delegation_id' => $nabunturan->id]);
    $monkayoBoy = Athlete::factory()->create(['delegation_id' => $monkayo->id]);
    $basketballGirl = Athlete::factory()->create(['delegation_id' => $nabunturan->id]);
    $volleyballAthlete = Athlete::factory()->create(['delegation_id' => $monkayo->id]);

    foreach ([[$nabunturanBoy, $boysEvent, $nabunturan], [$monkayoBoy, $boysEvent, $monkayo], [$basketballGirl, $girlsEvent, $nabunturan], [$volleyballAthlete, $volleyballEvent, $monkayo]] as [$athlete, $event, $delegation]) {
        Entry::factory()->create(['athlete_id' => $athlete->id, 'event_id' => $event->id, 'delegation_id' => $delegation->id]);
    }

    $official = User::factory()->create(['role' => UserRole::TechnicalOfficial]);
    $meetSport = MeetSport::factory()->create(['meet_id' => $meet->id, 'sport_id' => $basketball->id]);
    MeetSportAssignment::factory()->create([
        'meet_sport_id' => $meetSport->id,
        'sport_category_id' => $boys->id,
        'user_id' => $official->id,
        'role' => MeetSportAssignmentRole::TechnicalOfficial,
        'status' => MeetSportAssignmentStatus::Active,
    ]);

    $this->actingAs($official)->get('/athletes')->assertInertia(fn (AssertableInertia $page) => $page
        ->has('athletes.data', 2)
        ->where('athletes.data.0.id', fn ($id) => in_array($id, [$nabunturanBoy->id, $monkayoBoy->id], true))
        ->where('athletes.data.1.id', fn ($id) => in_array($id, [$nabunturanBoy->id, $monkayoBoy->id], true)));

    $this->actingAs($official)->get('/dashboard')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('stats.0.key', 'athletes')
        ->where('stats.0.value', 2));

    $this->actingAs($official)->get("/athletes/{$nabunturanBoy->id}")->assertOk();
    $this->actingAs($official)->get("/athletes/{$monkayoBoy->id}")->assertOk();
    $this->actingAs($official)->get("/athletes/{$basketballGirl->id}")->assertForbidden();
    $this->actingAs($official)->get("/athletes/{$volleyballAthlete->id}")->assertForbidden();

    MeetSportAssignment::factory()->create([
        'meet_sport_id' => $meetSport->id,
        'sport_category_id' => $girls->id,
        'user_id' => $official->id,
        'role' => MeetSportAssignmentRole::TournamentICT,
        'status' => MeetSportAssignmentStatus::Active,
    ]);

    $this->actingAs($official)->get('/athletes')->assertInertia(fn (AssertableInertia $page) => $page
        ->has('athletes.data', 0));
    $this->actingAs($official)->get('/athletes?unassigned=1')->assertInertia(fn (AssertableInertia $page) => $page
        ->has('athletes.data', 4));

    MeetSportAssignment::factory()->create([
        'meet_sport_id' => $meetSport->id,
        'sport_category_id' => null,
        'user_id' => $official->id,
        'role' => MeetSportAssignmentRole::TournamentSecretary,
        'status' => MeetSportAssignmentStatus::Active,
    ]);

    $this->actingAs($official)->get('/athletes?unassigned=1')->assertInertia(fn (AssertableInertia $page) => $page
        ->has('athletes.data', 4));
});
