<?php

use App\Enums\EntryStatus;
use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\MeetStatus;
use App\Models\Athlete;
use App\Models\AuditLog;
use App\Models\Delegation;
use App\Models\District;
use App\Models\EligibilityReview;
use App\Models\Entry;
use App\Models\Event;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\School;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

/**
 * A registration-open meet with an attached boys-elementary event (cap 2)
 * and a matching grade-5 male athlete under a delegation.
 *
 * @return array{0: Meet, 1: Delegation, 2: Athlete, 3: Event}
 */
function entrySetup(array $eventOverrides = []): array
{
    $meet = Meet::factory()->registrationOpen()->create();
    $delegation = Delegation::factory()->create(['meet_id' => $meet->id]);
    $athlete = Athlete::factory()->create([
        'delegation_id' => $delegation->id,
        'sex' => 'male',
        'grade_level' => 5,
    ]);
    $event = Event::factory()->create([
        'gender' => 'boys',
        'age_division' => 'elementary',
        'max_entries_per_delegation' => 2,
        ...$eventOverrides,
    ]);
    $meet->events()->attach($event);
    EligibilityReview::factory()->approved()->create([
        'athlete_id' => $athlete->id,
        'meet_id' => $meet->id,
    ]);

    return [$meet, $delegation, $athlete, $event];
}

function entryOfficerFor(Delegation $delegation): User
{
    $officer = User::factory()->delegationOfficer()->create();
    $delegation->officers()->attach($officer);

    return $officer;
}

test('guests are redirected and viewers are forbidden', function () {
    $this->get('/entries')->assertRedirect('/login');

    $this->actingAs(User::factory()->create())
        ->get('/entries')
        ->assertForbidden();
});

test('officers see only their own entries while managers see all', function () {
    [, $delegation, $athlete, $event] = entrySetup();
    $officer = entryOfficerFor($delegation);
    Entry::factory()->create([
        'athlete_id' => $athlete->id,
        'event_id' => $event->id,
        'delegation_id' => $delegation->id,
    ]);
    Entry::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get('/entries')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('entries/index')
            ->has('entries.data', 2));

    $this->actingAs($officer)
        ->get('/entries')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('entries.data', 1));
});

test('an officer can submit a valid entry', function () {
    [, $delegation, $athlete, $event] = entrySetup();
    $officer = entryOfficerFor($delegation);

    $this->actingAs($officer)
        ->post('/entries', ['athlete_id' => $athlete->id, 'event_id' => $event->id])
        ->assertRedirect()
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('entries', [
        'athlete_id' => $athlete->id,
        'event_id' => $event->id,
        'delegation_id' => $delegation->id,
        'status' => EntryStatus::Submitted->value,
    ]);

    expect(AuditLog::query()->where('action', 'entry.submitted')->exists())->toBeTrue();
});

test('sex mismatches are rejected', function () {
    [, $delegation, , $event] = entrySetup();
    $girl = Athlete::factory()->create([
        'delegation_id' => $delegation->id,
        'sex' => 'female',
        'grade_level' => 5,
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/entries', ['athlete_id' => $girl->id, 'event_id' => $event->id])
        ->assertSessionHasErrors('event_id');
});

test('mixed events accept both sexes', function () {
    [, $delegation] = entrySetup();
    $meet = $delegation->meet;
    $mixedEvent = Event::factory()->create([
        'gender' => 'mixed',
        'age_division' => 'elementary',
        'max_entries_per_delegation' => 4,
    ]);
    $meet->events()->attach($mixedEvent);

    $girl = Athlete::factory()->create([
        'delegation_id' => $delegation->id,
        'sex' => 'female',
        'grade_level' => 4,
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/entries', ['athlete_id' => $girl->id, 'event_id' => $mixedEvent->id])
        ->assertSessionDoesntHaveErrors();
});

test('age division mismatches are rejected', function () {
    [, $delegation, , $event] = entrySetup();
    $secondary = Athlete::factory()->create([
        'delegation_id' => $delegation->id,
        'sex' => 'male',
        'grade_level' => 9,
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/entries', ['athlete_id' => $secondary->id, 'event_id' => $event->id])
        ->assertSessionHasErrors('event_id');
});

test('events outside the athlete\'s meet are rejected', function () {
    [, , $athlete] = entrySetup();
    $foreignEvent = Event::factory()->create([
        'gender' => 'boys',
        'age_division' => 'elementary',
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/entries', ['athlete_id' => $athlete->id, 'event_id' => $foreignEvent->id])
        ->assertSessionHasErrors('event_id');
});

test('duplicate entries for the same athlete and event are rejected', function () {
    [, , $athlete, $event] = entrySetup();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/entries', ['athlete_id' => $athlete->id, 'event_id' => $event->id]);

    $this->actingAs($admin)
        ->post('/entries', ['athlete_id' => $athlete->id, 'event_id' => $event->id])
        ->assertSessionHasErrors('event_id');
});

test('one athlete can enter multiple individual events without duplicate athlete records', function () {
    [$meet, $delegation, $athlete, $firstEvent] = entrySetup(['name' => 'Floor Exercise']);
    $meet->forceFill(['max_events_per_athlete' => 4])->save();

    $events = collect([
        $firstEvent,
        Event::factory()->create(['name' => 'Vault', 'gender' => 'boys', 'age_division' => 'elementary', 'is_team_event' => false]),
        Event::factory()->create(['name' => 'Individual All-Around', 'gender' => 'boys', 'age_division' => 'elementary', 'is_team_event' => false]),
    ]);
    $eventIds = $events->pluck('id')->all();
    $meet->events()->syncWithoutDetaching($eventIds);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/entries', ['athlete_id' => $athlete->id, 'event_ids' => $eventIds])
        ->assertSessionDoesntHaveErrors();

    expect(Athlete::query()->where('lrn', $athlete->lrn)->count())->toBe(1)
        ->and($athlete->entries()->count())->toBe(3);
});

test('team events cannot be submitted as individual entries', function () {
    [, , $athlete, $event] = entrySetup(['name' => 'Gymnastics Team', 'is_team_event' => true]);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/entries', ['athlete_id' => $athlete->id, 'event_id' => $event->id])
        ->assertSessionHasErrors('event_id');

    expect($athlete->entries()->count())->toBe(0);
});

test('multi-event submission is atomic when the athlete event limit would be exceeded', function () {
    [$meet, , $athlete, $firstEvent] = entrySetup();
    $meet->forceFill(['max_events_per_athlete' => 1])->save();
    $secondEvent = Event::factory()->create([
        'gender' => 'boys', 'age_division' => 'elementary', 'max_entries_per_delegation' => 2,
    ]);
    $meet->events()->attach($secondEvent);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/entries', ['athlete_id' => $athlete->id, 'event_ids' => [$firstEvent->id, $secondEvent->id]])
        ->assertSessionHasErrors('event_ids');

    expect($athlete->entries()->count())->toBe(0);
});

test('the per-delegation entry cap is enforced and withdrawn entries free it', function () {
    [, $delegation, $athlete, $event] = entrySetup(['max_entries_per_delegation' => 1]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/entries', ['athlete_id' => $athlete->id, 'event_id' => $event->id])
        ->assertSessionDoesntHaveErrors();

    $second = Athlete::factory()->create([
        'delegation_id' => $delegation->id,
        'sex' => 'male',
        'grade_level' => 6,
    ]);

    $this->actingAs($admin)
        ->post('/entries', ['athlete_id' => $second->id, 'event_id' => $event->id])
        ->assertSessionHasErrors('event_id');

    Entry::query()->sole()->forceFill(['status' => EntryStatus::Withdrawn])->save();

    $this->actingAs($admin)
        ->post('/entries', ['athlete_id' => $second->id, 'event_id' => $event->id])
        ->assertSessionDoesntHaveErrors();
});

test('officers cannot submit when registration is closed but managers can', function () {
    [$meet, $delegation, $athlete, $event] = entrySetup();
    $officer = entryOfficerFor($delegation);
    $meet->forceFill(['status' => MeetStatus::RegistrationClosed])->save();

    $this->actingAs($officer)
        ->post('/entries', ['athlete_id' => $athlete->id, 'event_id' => $event->id])
        ->assertForbidden();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/entries', ['athlete_id' => $athlete->id, 'event_id' => $event->id])
        ->assertSessionDoesntHaveErrors();
});

test('officers cannot submit entries for foreign delegations', function () {
    [, , $athlete, $event] = entrySetup();

    $this->actingAs(User::factory()->delegationOfficer()->create())
        ->post('/entries', ['athlete_id' => $athlete->id, 'event_id' => $event->id])
        ->assertForbidden();
});

test('organizers can confirm submitted entries and officers cannot', function () {
    [, $delegation, $athlete, $event] = entrySetup();
    $officer = entryOfficerFor($delegation);
    $entry = Entry::factory()->create([
        'athlete_id' => $athlete->id,
        'event_id' => $event->id,
        'delegation_id' => $delegation->id,
    ]);

    $this->actingAs($officer)
        ->patch("/entries/{$entry->id}/confirm")
        ->assertForbidden();

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/entries/{$entry->id}/confirm")
        ->assertRedirect();

    expect($entry->refresh()->status)->toBe(EntryStatus::Confirmed)
        ->and(AuditLog::query()->where('action', 'entry.confirmed')->exists())->toBeTrue();
});

test('an entry cannot be confirmed before DSAC approves athlete eligibility', function () {
    [, $delegation, $athlete, $event] = entrySetup();
    $athlete->eligibilityReview->forceFill(['status' => 'pending'])->save();
    $entry = Entry::factory()->create([
        'athlete_id' => $athlete->id,
        'event_id' => $event->id,
        'delegation_id' => $delegation->id,
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/entries/{$entry->id}/confirm")
        ->assertSessionHasErrors('entry');

    expect($entry->fresh()->status)->toBe(EntryStatus::Submitted);
});

test('assigned tournament leaders can confirm eligible entries for their sport', function (MeetSportAssignmentRole $role) {
    [$meet, $delegation, $athlete, $event] = entrySetup();
    $meetSport = MeetSport::factory()->create([
        'meet_id' => $meet->id,
        'sport_id' => $event->sport_id,
    ]);
    $user = User::factory()->create();
    MeetSportAssignment::factory()->create([
        'meet_sport_id' => $meetSport->id,
        'user_id' => $user->id,
        'role' => $role,
        'status' => MeetSportAssignmentStatus::Active,
    ]);
    $entry = Entry::factory()->create([
        'athlete_id' => $athlete->id,
        'event_id' => $event->id,
        'delegation_id' => $delegation->id,
    ]);

    $this->actingAs($user)->patch("/entries/{$entry->id}/confirm")
        ->assertSessionDoesntHaveErrors();

    expect($entry->fresh()->status)->toBe(EntryStatus::Confirmed);
})->with([
    MeetSportAssignmentRole::TournamentManager,
    MeetSportAssignmentRole::AssistantTournamentManager,
    MeetSportAssignmentRole::TournamentSecretary,
]);

test('an officer can withdraw their own submitted entry but not a confirmed one', function () {
    [, $delegation, $athlete, $event] = entrySetup();
    $officer = entryOfficerFor($delegation);
    $entry = Entry::factory()->create([
        'athlete_id' => $athlete->id,
        'event_id' => $event->id,
        'delegation_id' => $delegation->id,
    ]);

    $this->actingAs($officer)
        ->patch("/entries/{$entry->id}/withdraw")
        ->assertRedirect();

    expect($entry->refresh()->status)->toBe(EntryStatus::Withdrawn)
        ->and(AuditLog::query()->where('action', 'entry.withdrawn')->exists())->toBeTrue();

    $confirmed = Entry::factory()->confirmed()->create([
        'athlete_id' => Athlete::factory()->create(['delegation_id' => $delegation->id])->id,
        'event_id' => $event->id,
        'delegation_id' => $delegation->id,
    ]);

    $this->actingAs($officer)
        ->patch("/entries/{$confirmed->id}/withdraw")
        ->assertForbidden();

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/entries/{$confirmed->id}/withdraw")
        ->assertRedirect();

    expect($confirmed->refresh()->status)->toBe(EntryStatus::Withdrawn);
});

test('only withdrawn entries can be deleted', function () {
    $submitted = Entry::factory()->create();
    $withdrawn = Entry::factory()->withdrawn()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->delete("/entries/{$submitted->id}")
        ->assertForbidden();

    $this->actingAs($admin)
        ->delete("/entries/{$withdrawn->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('entries', ['id' => $withdrawn->id]);

    expect(AuditLog::query()->where('action', 'entry.deleted')->exists())->toBeTrue();
});

test('the entry list can be filtered by event and delegation', function () {
    [, $delegation, $athlete, $event] = entrySetup();
    Entry::factory()->create([
        'athlete_id' => $athlete->id,
        'event_id' => $event->id,
        'delegation_id' => $delegation->id,
    ]);
    $other = Entry::factory()->create();

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get("/entries?event_id={$event->id}")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('entries.data', 1));

    $this->actingAs($admin)
        ->get("/entries?delegation_id={$other->delegation_id}")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('entries.data', 1));
});

test('the entry list shows each athlete\'s own school, not the municipal delegation\'s', function () {
    $meet = Meet::factory()->registrationOpen()->create();
    $district = District::factory()->create();
    $delegation = Delegation::factory()->approved()->create([
        'meet_id' => $meet->id,
        'school_id' => null,
        'district_id' => $district->id,
    ]);
    $school = School::factory()->create(['district_id' => $district->id, 'name' => 'Maco Central School']);
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id, 'school_id' => $school->id]);
    $event = Event::factory()->create();
    $meet->events()->attach($event);

    $entry = Entry::factory()->create([
        'athlete_id' => $athlete->id,
        'event_id' => $event->id,
        'delegation_id' => $delegation->id,
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/entries')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('entries.data.0.id', $entry->id)
            ->where('entries.data.0.school', 'Maco Central School'));
});

test('the entry list can be searched by athlete name', function () {
    [, $delegation, $athlete, $event] = entrySetup();
    $athlete->update(['first_name' => 'Zenaida', 'last_name' => 'Cordero']);
    $target = Entry::factory()->create([
        'athlete_id' => $athlete->id,
        'event_id' => $event->id,
        'delegation_id' => $delegation->id,
    ]);
    Entry::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get('/entries?search=Cordero')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('entries.data', 1)
            ->where('entries.data.0.id', $target->id));
});
