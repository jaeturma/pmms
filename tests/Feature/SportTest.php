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
            ->has('sports.data', 1)
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

test('the sports catalog lists technical official options and current assignments', function () {
    $sport = Sport::factory()->create();
    $official = User::factory()->technicalOfficial()->create();
    $sport->technicalOfficials()->attach($official);

    $this->actingAs(User::factory()->organizer()->create())
        ->get('/sports')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sports.data.0.technical_officials.0.id', $official->id)
            ->has('technicalOfficialOptions', 1));
});

test('organizers can assign technical officials to a sport', function () {
    $sport = Sport::factory()->create();
    $official = User::factory()->technicalOfficial()->create();

    $this->actingAs(User::factory()->organizer()->create())
        ->put("/sports/{$sport->id}/technical-officials", ['user_ids' => [$official->id]])
        ->assertRedirect();

    expect($sport->technicalOfficials()->whereKey($official->id)->exists())->toBeTrue()
        ->and(AuditLog::query()->where('action', 'sport.technical_officials_updated')->exists())->toBeTrue();
});

test('assigning technical officials rejects a user id that is not a technical official', function () {
    $sport = Sport::factory()->create();
    $notAnOfficial = User::factory()->delegationOfficer()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->put("/sports/{$sport->id}/technical-officials", ['user_ids' => [$notAnOfficial->id]])
        ->assertSessionHasErrors('user_ids.0');

    expect($sport->technicalOfficials()->count())->toBe(0);
});

test('viewers and delegation officers cannot assign technical officials', function (User $user) {
    $sport = Sport::factory()->create();

    $this->actingAs($user)
        ->put("/sports/{$sport->id}/technical-officials", ['user_ids' => []])
        ->assertForbidden();
})->with([
    'viewer' => fn () => User::factory()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
]);
