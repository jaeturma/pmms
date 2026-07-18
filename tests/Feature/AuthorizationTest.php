<?php

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;

test('users default to the viewer role', function () {
    $user = User::factory()->create();

    expect($user->role)->toBe(UserRole::Viewer);
});

test('factory states assign the expected roles', function () {
    expect(User::factory()->admin()->create()->role)->toBe(UserRole::Admin)
        ->and(User::factory()->organizer()->create()->role)->toBe(UserRole::Organizer)
        ->and(User::factory()->delegationOfficer()->create()->role)->toBe(UserRole::DelegationOfficer);
});

test('role is not mass assignable', function () {
    $user = new User([
        'name' => 'Escalation Attempt',
        'email' => 'escalation@example.com',
        'password' => 'irrelevant',
        'role' => 'admin',
    ]);

    expect($user->getAttributes())->not->toHaveKey('role');
});

test('gates authorize the expected roles', function (string $ability, UserRole $role, bool $allowed) {
    $user = User::factory()->create(['role' => $role]);

    expect(Gate::forUser($user)->allows($ability))->toBe($allowed);
})->with([
    ['administer', UserRole::Admin, true],
    ['administer', UserRole::Organizer, false],
    ['administer', UserRole::DelegationOfficer, false],
    ['administer', UserRole::Viewer, false],
    ['manage-meet-data', UserRole::Admin, true],
    ['manage-meet-data', UserRole::Organizer, true],
    ['manage-meet-data', UserRole::DelegationOfficer, false],
    ['manage-meet-data', UserRole::Viewer, false],
]);

test('role middleware blocks users without a required role', function () {
    Route::middleware(['web', 'auth', 'role:admin,organizer'])
        ->get('/testing/role-protected', fn () => response('ok'));

    $this->actingAs(User::factory()->create())
        ->get('/testing/role-protected')
        ->assertForbidden();

    $this->actingAs(User::factory()->organizer()->create())
        ->get('/testing/role-protected')
        ->assertOk();
});

test('forbidden requests render the permission denied page', function () {
    Route::middleware(['web', 'auth', 'role:admin'])
        ->get('/testing/admin-only', fn () => response('ok'));

    $this->actingAs(User::factory()->create())
        ->get('/testing/admin-only')
        ->assertForbidden()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('error')
            ->where('status', 403));
});

test('admin seeder creates a verified administrator account', function () {
    $this->seed(AdminUserSeeder::class);

    $admin = User::query()->where('email', 'admin@pmms.local')->firstOrFail();

    expect($admin->role)->toBe(UserRole::Admin)
        ->and($admin->email_verified_at)->not->toBeNull();
});

test('admin seeder is idempotent', function () {
    $this->seed(AdminUserSeeder::class);
    $this->seed(AdminUserSeeder::class);

    expect(User::query()->where('email', 'admin@pmms.local')->count())->toBe(1);
});
