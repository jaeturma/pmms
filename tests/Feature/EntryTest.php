<?php

use App\Enums\EntryStatus;
use App\Enums\MeetStatus;
use App\Models\Athlete;
use App\Models\AuditLog;
use App\Models\Delegation;
use App\Models\Entry;
use App\Models\Event;
use App\Models\Meet;
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

    $this->actingAs(User::factory()->organizer()->create())
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

    $this->actingAs(User::factory()->organizer()->create())
        ->patch("/entries/{$entry->id}/confirm")
        ->assertRedirect();

    expect($entry->refresh()->status)->toBe(EntryStatus::Confirmed)
        ->and(AuditLog::query()->where('action', 'entry.confirmed')->exists())->toBeTrue();
});

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
