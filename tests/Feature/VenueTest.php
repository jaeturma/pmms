<?php

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Venue;
use Inertia\Testing\AssertableInertia;

test('guests are redirected from the venue registry', function () {
    $this->get('/venues')->assertRedirect('/login');
});

test('the venue registry renders with the manage flag per role', function () {
    Venue::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get('/venues')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('registry/venues')
            ->has('venues.data', 1)
            ->where('canManage', false));

    $this->actingAs(User::factory()->admin()->create())
        ->get('/venues')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('canManage', true));
});

test('organizers can create venues', function () {
    $this->actingAs(User::factory()->organizer()->create())
        ->post('/venues', [
            'name' => 'Provincial Sports Complex',
            'address' => 'Capitol Compound',
            'notes' => 'Track oval and grandstand',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('venues', [
        'name' => 'Provincial Sports Complex',
        'address' => 'Capitol Compound',
        'active' => true,
    ]);

    expect(AuditLog::query()->where('action', 'venue.created')->exists())->toBeTrue();
});

test('viewers and delegation officers cannot create venues', function (User $user) {
    $this->actingAs($user)
        ->post('/venues', ['name' => 'Forbidden Gym'])
        ->assertForbidden();

    $this->assertDatabaseMissing('venues', ['name' => 'Forbidden Gym']);
})->with([
    'viewer' => fn () => User::factory()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
]);

test('venue names must be unique', function () {
    Venue::factory()->create(['name' => 'Provincial Sports Complex']);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/venues', ['name' => 'Provincial Sports Complex'])
        ->assertSessionHasErrors('name');
});

test('admins can update a venue', function () {
    $venue = Venue::factory()->create(['name' => 'Old Gym']);

    $this->actingAs(User::factory()->admin()->create())
        ->put("/venues/{$venue->id}", [
            'name' => 'Renovated Gym',
            'address' => 'New Street',
            'notes' => null,
        ])
        ->assertRedirect();

    expect($venue->refresh()->name)->toBe('Renovated Gym')
        ->and($venue->address)->toBe('New Street')
        ->and(AuditLog::query()->where('action', 'venue.updated')->exists())->toBeTrue();
});

test('archiving and restoring a venue toggles active', function () {
    $venue = Venue::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/venues/{$venue->id}/archive")
        ->assertRedirect();

    expect($venue->refresh()->active)->toBeFalse()
        ->and(AuditLog::query()->where('action', 'venue.archived')->exists())->toBeTrue();

    $this->actingAs($admin)
        ->patch("/venues/{$venue->id}/restore")
        ->assertRedirect();

    expect($venue->refresh()->active)->toBeTrue()
        ->and(AuditLog::query()->where('action', 'venue.restored')->exists())->toBeTrue();
});

test('venues not in use can be deleted', function () {
    $venue = Venue::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete("/venues/{$venue->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('venues', ['id' => $venue->id]);

    expect(AuditLog::query()->where('action', 'venue.deleted')->exists())->toBeTrue();
});

test('the venue registry can be searched by name and address', function () {
    Venue::factory()->create([
        'name' => 'Provincial Sports Complex',
        'address' => 'Capitol Compound',
    ]);
    Venue::factory()->create([
        'name' => 'Municipal Gymnasium',
        'address' => 'Poblacion',
    ]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/venues?search=Municipal')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('venues.data', 1)
            ->where('venues.data.0.name', 'Municipal Gymnasium'));

    $this->actingAs($admin)
        ->get('/venues?search=Capitol')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('venues.data', 1)
            ->where('venues.data.0.name', 'Provincial Sports Complex'));
});

test('the venue registry paginates fifteen rows per page', function () {
    Venue::factory()->count(20)->create();

    $this->actingAs(User::factory()->create())
        ->get('/venues')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('venues.data', 15)
            ->where('venues.total', 20));
});
