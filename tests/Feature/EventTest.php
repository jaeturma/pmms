<?php

use App\Enums\AgeDivision;
use App\Enums\GenderCategory;
use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\Sport;
use App\Models\User;
use Database\Seeders\SportsCatalogSeeder;
use Inertia\Testing\AssertableInertia;

function validEventPayload(Sport $sport): array
{
    return [
        'sport_id' => $sport->id,
        'name' => '100 Meter Dash',
        'gender' => GenderCategory::Boys->value,
        'age_division' => AgeDivision::Elementary->value,
        'is_team_event' => false,
        'max_entries_per_delegation' => 2,
    ];
}

test('guests are redirected from the events catalog', function () {
    $this->get('/events')->assertRedirect('/login');
});

test('the events catalog renders with events and sport options', function () {
    Event::factory()->create();
    Sport::factory()->archived()->create();

    $this->actingAs(User::factory()->create())
        ->get('/events')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('catalog/events')
            ->has('events.data', 1)
            ->has('sports', 1)
            ->where('canManage', false));
});

test('sports events can be filtered by sport while preserving the selected filter', function () {
    $basketball = Sport::factory()->create(['name' => 'Basketball']);
    $athletics = Sport::factory()->create(['name' => 'Athletics']);
    Event::factory()->create(['sport_id' => $basketball->id]);
    Event::factory()->create(['sport_id' => $athletics->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->get("/events?sport_id={$basketball->id}")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('filters.sport_id', $basketball->id)
            ->has('events.data', 1)
            ->where('events.data.0.sport_id', $basketball->id));
});

test('organizers can create events', function () {
    $sport = Sport::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/events', validEventPayload($sport))
        ->assertRedirect();

    $this->assertDatabaseHas('events', [
        'name' => '100 Meter Dash',
        'sport_id' => $sport->id,
    ]);

    expect(AuditLog::query()->where('action', 'event.created')->exists())->toBeTrue();
});

test('viewers and delegation officers cannot create events', function (User $user) {
    $sport = Sport::factory()->create();

    $this->actingAs($user)
        ->post('/events', validEventPayload($sport))
        ->assertForbidden();
})->with([
    'viewer' => fn () => User::factory()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
]);

test('event validation rejects bad payloads', function (array $overrides, string $errorField) {
    $sport = Sport::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/events', [...validEventPayload($sport), ...$overrides])
        ->assertSessionHasErrors($errorField);
})->with([
    'missing sport' => [['sport_id' => 999999], 'sport_id'],
    'invalid gender' => [['gender' => 'coed'], 'gender'],
    'invalid division' => [['age_division' => 'college'], 'age_division'],
    'zero max entries' => [['max_entries_per_delegation' => 0], 'max_entries_per_delegation'],
    'missing name' => [['name' => ''], 'name'],
]);

test('event names must be unique per sport, gender, and division', function () {
    $sport = Sport::factory()->create();
    Event::factory()->create([
        'sport_id' => $sport->id,
        'name' => '100 Meter Dash',
        'gender' => GenderCategory::Boys,
        'age_division' => AgeDivision::Elementary,
    ]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/events', validEventPayload($sport))
        ->assertSessionHasErrors('name');

    $this->actingAs($admin)
        ->post('/events', [...validEventPayload($sport), 'gender' => GenderCategory::Girls->value])
        ->assertRedirect()
        ->assertSessionDoesntHaveErrors();
});

test('admins can update an event', function () {
    $event = Event::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->put("/events/{$event->id}", [
            'sport_id' => $event->sport_id,
            'name' => '4x400 Meter Relay',
            'gender' => $event->gender->value,
            'age_division' => $event->age_division->value,
            'is_team_event' => true,
            'max_entries_per_delegation' => 1,
        ])
        ->assertRedirect();

    expect($event->refresh())
        ->name->toBe('4x400 Meter Relay')
        ->is_team_event->toBeTrue()
        ->and(AuditLog::query()->where('action', 'event.updated')->exists())->toBeTrue();
});

test('tournament ICT and secretaries can create update and delete events only for assigned sports', function (MeetSportAssignmentRole $role, UserRole $userRole) {
    $meet = Meet::current();
    $assignedSport = Sport::factory()->create();
    $otherSport = Sport::factory()->create();
    $meetSport = MeetSport::factory()->create(['meet_id' => $meet->id, 'sport_id' => $assignedSport->id]);
    $user = User::factory()->create(['role' => $userRole]);
    MeetSportAssignment::factory()->create([
        'meet_sport_id' => $meetSport->id,
        'user_id' => $user->id,
        'role' => $role,
        'status' => MeetSportAssignmentStatus::Active,
    ]);
    $outsideEvent = Event::factory()->create(['sport_id' => $otherSport->id]);

    $this->actingAs($user)->get('/events')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('canManage', true)
        ->where('canArchive', false)
        ->where('sports', fn ($sports) => collect($sports)->pluck('id')->all() === [$assignedSport->id])
        ->where('events.data', fn ($events) => collect($events)->every(fn ($event) => $event['sport_id'] === $assignedSport->id)));

    $this->actingAs($user)->post('/events', validEventPayload($assignedSport))->assertSessionHasNoErrors();
    $this->actingAs($user)->post('/events', [...validEventPayload($otherSport), 'name' => 'Forbidden Event'])->assertForbidden();

    $event = Event::factory()->create(['sport_id' => $assignedSport->id]);
    $this->actingAs($user)->put("/events/{$event->id}", [...validEventPayload($assignedSport), 'name' => 'Allowed Update'])->assertSessionHasNoErrors();
    expect($event->fresh()->name)->toBe('Allowed Update');
    $this->actingAs($user)->patch("/events/{$event->id}/archive")->assertForbidden();
    $this->actingAs($user)->delete("/events/{$outsideEvent->id}")->assertForbidden();
    $this->actingAs($user)->delete("/events/{$event->id}")->assertSessionHasNoErrors();

    $this->assertDatabaseMissing('events', ['id' => $event->id]);
    $this->assertDatabaseHas('events', ['id' => $outsideEvent->id]);
})->with([
    'Tournament ICT' => [MeetSportAssignmentRole::TournamentICT, UserRole::TournamentICT],
    'Tournament Secretary' => [MeetSportAssignmentRole::TournamentSecretary, UserRole::TournamentSecretary],
]);

test('events support a combined elementary and secondary division', function () {
    $sport = Sport::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/events', [
            ...validEventPayload($sport),
            'name' => 'Combined Division Event',
            'age_division' => AgeDivision::ElementaryAndSecondary->value,
        ])
        ->assertSessionDoesntHaveErrors();

    expect(Event::query()->where('name', 'Combined Division Event')->sole()->age_division)
        ->toBe(AgeDivision::ElementaryAndSecondary);
});

test('archiving and restoring an event toggles active', function () {
    $event = Event::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/events/{$event->id}/archive")
        ->assertRedirect();

    expect($event->refresh()->active)->toBeFalse()
        ->and(AuditLog::query()->where('action', 'event.archived')->exists())->toBeTrue();

    $this->actingAs($admin)
        ->patch("/events/{$event->id}/restore")
        ->assertRedirect();

    expect($event->refresh()->active)->toBeTrue();
});

test('events can be deleted and the deletion is audited', function () {
    $event = Event::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete("/events/{$event->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('events', ['id' => $event->id]);

    expect(AuditLog::query()->where('action', 'event.deleted')->exists())->toBeTrue();
});

test('the sports catalog seeder is idempotent and seeds athletics events', function () {
    $this->seed(SportsCatalogSeeder::class);
    $eventCount = Event::query()->count();
    $this->seed(SportsCatalogSeeder::class);

    expect(Sport::query()->where('name', 'Athletics')->count())->toBe(1)
        ->and(Sport::query()->count())->toBe(29)
        ->and(Sport::query()->whereNull('short_description')->count())->toBe(0)
        ->and(Sport::query()->whereNull('description')->count())->toBe(0)
        ->and(Sport::query()->where('short_description', 'like', '%competition configured for the DdOPAA provincial sports program.')->count())->toBe(0)
        ->and(Sport::query()->where('name', 'Basketball 3x3')->value('description'))->toContain('half court')
        ->and(Event::query()->count())->toBe($eventCount)
        ->and(Event::query()->whereHas('sport', fn ($query) => $query->where('code', 'ATHLETICS'))->count())->toBe(12);
});
