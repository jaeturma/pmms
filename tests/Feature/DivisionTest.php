<?php

use App\Enums\DivisionType;
use App\Models\AuditLog;
use App\Models\Delegation;
use App\Models\District;
use App\Models\Division;
use App\Models\User;
use Database\Seeders\DivisionRegistrySeeder;
use Inertia\Testing\AssertableInertia;

test('Division::current creates a Province default on first access', function () {
    expect(Division::query()->count())->toBe(0);

    $division = Division::current();

    expect($division->type)->toBe(DivisionType::Province)
        ->and($division->areaLabel())->toBe('Municipality')
        ->and(Division::query()->count())->toBe(1);

    expect(Division::current()->id)->toBe($division->id);
});

test('the division is shared on every Inertia page with its area label', function () {
    Division::factory()->province()->create(['name' => 'Davao de Oro']);

    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('division.type', 'province')
            ->where('division.name', 'Davao de Oro')
            ->where('division.areaLabel', 'Municipality'));
});

test('the division type is locked once any delegation exists', function () {
    expect(Division::current()->typeIsLocked())->toBeFalse();

    Delegation::factory()->create();

    expect(Division::current()->typeIsLocked())->toBeTrue();
});

test('guests are redirected from division settings', function () {
    $this->get('/division')->assertRedirect('/login');
});

test('only admins can view or update division settings', function (User $user) {
    $this->actingAs($user)
        ->get('/division')
        ->assertForbidden();

    $this->actingAs($user)
        ->patch('/division', ['name' => 'X', 'type' => 'city'])
        ->assertForbidden();
})->with([
    'organizer' => fn () => User::factory()->organizer()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
    'viewer' => fn () => User::factory()->create(),
]);

test('admins can view division settings with the lock state', function () {
    Division::factory()->province()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get('/division')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('division/edit')
            ->where('division.type', 'province')
            ->where('typeLocked', false));
});

test('admins can update the division name and type when unlocked, audited', function () {
    Division::factory()->province()->create(['name' => 'Old Name']);

    $this->actingAs(User::factory()->admin()->create())
        ->patch('/division', ['name' => 'New Name', 'type' => 'city'])
        ->assertRedirect();

    $division = Division::current();

    expect($division->name)->toBe('New Name')
        ->and($division->type)->toBe(DivisionType::City)
        ->and(AuditLog::query()->where('action', 'division.updated')->exists())->toBeTrue();
});

test('the division type cannot be changed once locked, even if submitted', function () {
    Division::factory()->province()->create();
    Delegation::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patch('/division', ['name' => 'Still Province', 'type' => 'city'])
        ->assertRedirect();

    expect(Division::current()->type)->toBe(DivisionType::Province)
        ->and(Division::current()->name)->toBe('Still Province');
});

test('the division registry seeder creates the real default configuration', function () {
    (new DivisionRegistrySeeder)->run();

    $division = Division::current();

    expect($division->type)->toBe(DivisionType::Province)
        ->and($division->name)->toBe('Davao de Oro')
        ->and(District::query()->count())->toBe(11);

    $maco = District::query()->where('name', 'Maco')->firstOrFail();
    expect($maco->nickname)->toBe('Tigers');

    // Idempotent: re-running does not duplicate rows.
    (new DivisionRegistrySeeder)->run();
    expect(Division::query()->count())->toBe(1)
        ->and(District::query()->count())->toBe(11);
});
