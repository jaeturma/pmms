<?php

use App\Enums\MeetStatus;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Meet;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

function validMeetPayload(): array
{
    return [
        'name' => 'Provincial Meet 2026',
        'school_year' => '2025-2026',
        'starts_at' => '2026-11-09',
        'ends_at' => '2026-11-13',
        'venue' => 'Provincial Sports Complex',
    ];
}

test('guests are redirected from the meets page', function () {
    $this->get('/meets')->assertRedirect('/login');
});

test('the meets page renders with meets and event options', function () {
    Meet::factory()->create();
    Event::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get('/meets')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('meets/index')
            ->has('meets', 1)
            ->has('eventOptions', 1)
            ->where('canManage', false));
});

test('organizers can create meets as drafts', function () {
    $this->actingAs(User::factory()->organizer()->create())
        ->post('/meets', validMeetPayload())
        ->assertRedirect();

    $this->assertDatabaseHas('meets', [
        'name' => 'Provincial Meet 2026',
        'status' => MeetStatus::Draft->value,
    ]);

    expect(AuditLog::query()->where('action', 'meet.created')->exists())->toBeTrue();
});

test('viewers and delegation officers cannot create meets', function (User $user) {
    $this->actingAs($user)
        ->post('/meets', validMeetPayload())
        ->assertForbidden();
})->with([
    'viewer' => fn () => User::factory()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
]);

test('meet validation rejects bad payloads', function (array $overrides, string $errorField) {
    $this->actingAs(User::factory()->admin()->create())
        ->post('/meets', [...validMeetPayload(), ...$overrides])
        ->assertSessionHasErrors($errorField);
})->with([
    'bad school year' => [['school_year' => 'SY 2025'], 'school_year'],
    'ends before starts' => [['ends_at' => '2026-11-01'], 'ends_at'],
    'missing name' => [['name' => ''], 'name'],
]);

test('admins can update meet details', function () {
    $meet = Meet::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->put("/meets/{$meet->id}", [...validMeetPayload(), 'name' => 'Renamed Meet'])
        ->assertRedirect();

    expect($meet->refresh()->name)->toBe('Renamed Meet')
        ->and(AuditLog::query()->where('action', 'meet.updated')->exists())->toBeTrue();
});

test('valid lifecycle transitions are applied and audited', function () {
    $meet = Meet::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/meets/{$meet->id}/status", ['status' => MeetStatus::RegistrationOpen->value])
        ->assertRedirect();

    expect($meet->refresh()->status)->toBe(MeetStatus::RegistrationOpen);

    $log = AuditLog::query()->where('action', 'meet.status_changed')->first();

    expect($log)->not->toBeNull()
        ->and($log->context)->toMatchArray([
            'from' => 'draft',
            'to' => 'registration_open',
        ]);
});

test('invalid lifecycle transitions are rejected', function () {
    $meet = Meet::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/meets/{$meet->id}/status", ['status' => MeetStatus::Completed->value])
        ->assertRedirect();

    expect($meet->refresh()->status)->toBe(MeetStatus::Draft)
        ->and(AuditLog::query()->where('action', 'meet.status_changed')->exists())->toBeFalse();
});

test('closed registration can reopen', function () {
    $meet = Meet::factory()->create();
    $meet->forceFill(['status' => MeetStatus::RegistrationClosed])->save();

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/meets/{$meet->id}/status", ['status' => MeetStatus::RegistrationOpen->value])
        ->assertRedirect();

    expect($meet->refresh()->status)->toBe(MeetStatus::RegistrationOpen);
});

test('meet events can be synced', function () {
    $meet = Meet::factory()->create();
    $events = Event::factory()->count(3)->create();

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put("/meets/{$meet->id}/events", ['event_ids' => [$events[0]->id, $events[1]->id]])
        ->assertRedirect();

    expect($meet->events()->count())->toBe(2)
        ->and(AuditLog::query()->where('action', 'meet.events_updated')->exists())->toBeTrue();

    $this->actingAs($admin)
        ->put("/meets/{$meet->id}/events", ['event_ids' => [$events[2]->id]]);

    expect($meet->events()->pluck('events.id')->all())->toBe([$events[2]->id]);
});

test('meet event sync rejects unknown events', function () {
    $meet = Meet::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->put("/meets/{$meet->id}/events", ['event_ids' => [999999]])
        ->assertSessionHasErrors('event_ids.0');
});

test('events attached to a meet cannot be deleted from the catalog', function () {
    $meet = Meet::factory()->create();
    $event = Event::factory()->create();
    $meet->events()->attach($event);

    $this->actingAs(User::factory()->admin()->create())
        ->delete("/events/{$event->id}")
        ->assertRedirect();

    $this->assertDatabaseHas('events', ['id' => $event->id]);
});

test('draft meets can be deleted but started meets cannot', function () {
    $draft = Meet::factory()->create();
    $open = Meet::factory()->registrationOpen()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->delete("/meets/{$draft->id}")->assertRedirect();
    $this->assertDatabaseMissing('meets', ['id' => $draft->id]);

    $this->actingAs($admin)->delete("/meets/{$open->id}")->assertRedirect();
    $this->assertDatabaseHas('meets', ['id' => $open->id]);
});

test('the registration window hook follows meet status', function () {
    expect(Meet::factory()->registrationOpen()->create()->isRegistrationOpen())->toBeTrue()
        ->and(Meet::factory()->create()->isRegistrationOpen())->toBeFalse();
});

test('the dashboard shows the current meet', function () {
    Meet::factory()->completed()->create(['name' => 'Old Meet']);
    Meet::factory()->registrationOpen()->create(['name' => 'Current Meet']);

    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dashboard')
            ->where('currentMeet.name', 'Current Meet')
            ->where('currentMeet.status', 'registration_open'));
});

test('the dashboard shows no meet card when only completed meets exist', function () {
    Meet::factory()->completed()->create();

    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('currentMeet', null));
});
