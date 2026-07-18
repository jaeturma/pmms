<?php

use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Sport;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('guests are redirected from the sports catalog', function () {
    $this->get('/sports')->assertRedirect('/login');
});

test('the sports catalog renders with the manage flag per role', function () {
    Sport::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get('/sports')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('catalog/sports')
            ->has('sports', 1)
            ->where('canManage', false));

    $this->actingAs(User::factory()->organizer()->create())
        ->get('/sports')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('canManage', true));
});

test('organizers can create sports', function () {
    $this->actingAs(User::factory()->organizer()->create())
        ->post('/sports', ['name' => 'Athletics'])
        ->assertRedirect();

    $this->assertDatabaseHas('sports', ['name' => 'Athletics', 'active' => true]);

    expect(AuditLog::query()->where('action', 'sport.created')->exists())->toBeTrue();
});

test('viewers and delegation officers cannot create sports', function (User $user) {
    $this->actingAs($user)
        ->post('/sports', ['name' => 'Athletics'])
        ->assertForbidden();
})->with([
    'viewer' => fn () => User::factory()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
]);

test('sport names must be unique', function () {
    Sport::factory()->create(['name' => 'Athletics']);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/sports', ['name' => 'Athletics'])
        ->assertSessionHasErrors('name');
});

test('admins can update a sport', function () {
    $sport = Sport::factory()->create(['name' => 'Athletics']);

    $this->actingAs(User::factory()->admin()->create())
        ->put("/sports/{$sport->id}", ['name' => 'Track and Field'])
        ->assertRedirect();

    expect($sport->refresh()->name)->toBe('Track and Field')
        ->and(AuditLog::query()->where('action', 'sport.updated')->exists())->toBeTrue();
});

test('archiving and restoring a sport toggles active', function () {
    $sport = Sport::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/sports/{$sport->id}/archive")
        ->assertRedirect();

    expect($sport->refresh()->active)->toBeFalse()
        ->and(AuditLog::query()->where('action', 'sport.archived')->exists())->toBeTrue();

    $this->actingAs($admin)
        ->patch("/sports/{$sport->id}/restore")
        ->assertRedirect();

    expect($sport->refresh()->active)->toBeTrue();
});

test('sports with events cannot be deleted', function () {
    $event = Event::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete("/sports/{$event->sport_id}")
        ->assertRedirect();

    $this->assertDatabaseHas('sports', ['id' => $event->sport_id]);
});

test('sports without events can be deleted', function () {
    $sport = Sport::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete("/sports/{$sport->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('sports', ['id' => $sport->id]);

    expect(AuditLog::query()->where('action', 'sport.deleted')->exists())->toBeTrue();
});
