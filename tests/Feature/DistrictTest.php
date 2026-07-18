<?php

use App\Models\AuditLog;
use App\Models\District;
use App\Models\School;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('guests are redirected from the district registry', function () {
    $this->get('/districts')->assertRedirect('/login');
});

test('the district registry renders with the manage flag per role', function () {
    District::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get('/districts')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('registry/districts')
            ->has('districts', 1)
            ->where('canManage', false));

    $this->actingAs(User::factory()->admin()->create())
        ->get('/districts')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('canManage', true));
});

test('organizers can create districts', function () {
    $this->actingAs(User::factory()->organizer()->create())
        ->post('/districts', ['name' => 'District I'])
        ->assertRedirect();

    $this->assertDatabaseHas('districts', ['name' => 'District I', 'active' => true]);

    expect(AuditLog::query()->where('action', 'district.created')->exists())->toBeTrue();
});

test('viewers and delegation officers cannot create districts', function (User $user) {
    $this->actingAs($user)
        ->post('/districts', ['name' => 'District X'])
        ->assertForbidden();

    $this->assertDatabaseMissing('districts', ['name' => 'District X']);
})->with([
    'viewer' => fn () => User::factory()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
]);

test('district names must be unique', function () {
    District::factory()->create(['name' => 'District I']);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/districts', ['name' => 'District I'])
        ->assertSessionHasErrors('name');
});

test('admins can update a district', function () {
    $district = District::factory()->create(['name' => 'District I']);

    $this->actingAs(User::factory()->admin()->create())
        ->put("/districts/{$district->id}", ['name' => 'District I — Renamed'])
        ->assertRedirect();

    expect($district->refresh()->name)->toBe('District I — Renamed')
        ->and(AuditLog::query()->where('action', 'district.updated')->exists())->toBeTrue();
});

test('archiving and restoring a district toggles active', function () {
    $district = District::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/districts/{$district->id}/archive")
        ->assertRedirect();

    expect($district->refresh()->active)->toBeFalse()
        ->and(AuditLog::query()->where('action', 'district.archived')->exists())->toBeTrue();

    $this->actingAs($admin)
        ->patch("/districts/{$district->id}/restore")
        ->assertRedirect();

    expect($district->refresh()->active)->toBeTrue()
        ->and(AuditLog::query()->where('action', 'district.restored')->exists())->toBeTrue();
});

test('districts with schools cannot be deleted', function () {
    $school = School::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete("/districts/{$school->district_id}")
        ->assertRedirect();

    $this->assertDatabaseHas('districts', ['id' => $school->district_id]);
});

test('districts without schools can be deleted', function () {
    $district = District::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete("/districts/{$district->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('districts', ['id' => $district->id]);

    expect(AuditLog::query()->where('action', 'district.deleted')->exists())->toBeTrue();
});
