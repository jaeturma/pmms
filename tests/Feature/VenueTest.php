<?php

use App\Models\AuditLog;
use App\Models\GameCoordinatorAssignment;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\Person;
use App\Models\Sport;
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

test('venue details include coordinator contact and map coordinates', function () {
    $venue = Venue::factory()->create([
        'latitude' => '7.1234567',
        'longitude' => '125.1234567',
    ]);
    $sport = Sport::factory()->create(['name' => 'Table Tennis']);
    $meetSport = MeetSport::factory()->create([
        'meet_id' => Meet::current()->id,
        'sport_id' => $sport->id,
    ]);
    $person = Person::create([
        'source_key' => 'venue-coordinator-test',
        'full_name' => 'Juan Dela Cruz',
        'normalized_name' => 'juan dela cruz',
    ]);
    GameCoordinatorAssignment::create([
        'meet_sport_id' => $meetSport->id,
        'venue_id' => $venue->id,
        'person_id' => $person->id,
        'is_lead' => true,
        'status' => 'active',
        'source_contact_text' => '0917 123 4567',
    ]);

    $this->actingAs(User::factory()->create())
        ->get('/venues')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('venues.data.0.latitude', 7.1234567)
            ->where('venues.data.0.longitude', 125.1234567)
            ->where('venues.data.0.game_coordinators.0.name', 'Juan Dela Cruz')
            ->where('venues.data.0.game_coordinators.0.contact_number', '0917 123 4567')
            ->where('venues.data.0.game_coordinators.0.sport', 'Table Tennis')
            ->where('venues.data.0.game_coordinators.0.is_lead', true));
});

test('organizers can create venues', function () {
    $this->actingAs(User::factory()->admin()->create())
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

test('google maps coordinates and URLs are accepted and persisted', function (string $location) {
    $this->actingAs(User::factory()->admin()->create())
        ->post('/venues', [
            'name' => 'Mapped Venue',
            'gps_location' => $location,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $venue = Venue::query()->where('name', 'Mapped Venue')->firstOrFail();

    expect((float) $venue->latitude)->toBe(7.123456)
        ->and((float) $venue->longitude)->toBe(125.123456);
})->with([
    'coordinates' => '7.123456, 125.123456',
    'maps query URL' => 'https://www.google.com/maps/search/?api=1&query=7.123456%2C125.123456',
    'maps place URL' => 'https://www.google.com/maps/place/Test/@7.123456,125.123456,15z',
]);

test('updating a venue replaces its saved google maps location', function () {
    $venue = Venue::factory()->create([
        'latitude' => 7.1,
        'longitude' => 125.1,
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->put("/venues/{$venue->id}", [
            'name' => $venue->name,
            'gps_location' => '7.765432, 125.765432',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect((float) $venue->refresh()->latitude)->toBe(7.765432)
        ->and((float) $venue->longitude)->toBe(125.765432);
});

test('invalid google maps locations are rejected', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post('/venues', [
            'name' => 'Invalid Map Venue',
            'gps_location' => 'not a coordinate or maps URL',
        ])
        ->assertSessionHasErrors('gps_location');
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

test('the venue registry paginates ten rows per page', function () {
    Venue::factory()->count(20)->create();

    $this->actingAs(User::factory()->create())
        ->get('/venues')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('venues.data', 10)
            ->where('venues.total', 20));
});
