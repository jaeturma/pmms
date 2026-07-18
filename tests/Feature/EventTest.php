<?php

use App\Enums\AgeDivision;
use App\Enums\GenderCategory;
use App\Models\AuditLog;
use App\Models\Event;
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

test('organizers can create events', function () {
    $sport = Sport::factory()->create();

    $this->actingAs(User::factory()->organizer()->create())
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
    $this->seed(SportsCatalogSeeder::class);

    expect(Sport::query()->where('name', 'Athletics')->count())->toBe(1)
        ->and(Sport::query()->count())->toBe(14)
        ->and(Event::query()->count())->toBe(16);
});
