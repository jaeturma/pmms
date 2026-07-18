<?php

use App\Models\AuditLog;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('guests are redirected from the audit log', function () {
    $this->get('/audit-logs')->assertRedirect('/login');
});

test('non-admin roles cannot view the audit log', function (User $user) {
    $this->actingAs($user)
        ->get('/audit-logs')
        ->assertForbidden();
})->with([
    'organizer' => fn () => User::factory()->organizer()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
    'viewer' => fn () => User::factory()->create(),
]);

test('admins see the paginated audit trail newest first', function () {
    $actor = User::factory()->create();
    AuditLog::factory()->count(20)->for($actor)->create(['action' => 'school.created']);
    $latest = AuditLog::factory()->for($actor)->create(['action' => 'meet.created']);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/audit-logs')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('audit/index')
            ->has('logs.data', 15)
            ->where('logs.total', 21)
            ->where('logs.data.0.id', $latest->id)
            ->has('actionOptions', 2));
});

test('the audit log can be searched by action and user name', function () {
    $ana = User::factory()->create(['name' => 'Ana Reyes']);
    $ben = User::factory()->create(['name' => 'Ben Cruz']);
    AuditLog::factory()->for($ana)->create(['action' => 'athlete.viewed']);
    AuditLog::factory()->for($ben)->create(['action' => 'school.created']);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/audit-logs?search=Reyes')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('logs.data', 1)
            ->where('logs.data.0.action', 'athlete.viewed'));

    $this->actingAs($admin)
        ->get('/audit-logs?search=school.created')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('logs.data', 1)
            ->where('logs.data.0.user', 'Ben Cruz'));
});

test('the audit log can be filtered to a single action', function () {
    $actor = User::factory()->create();
    AuditLog::factory()->count(2)->for($actor)->create(['action' => 'athlete.viewed']);
    AuditLog::factory()->for($actor)->create(['action' => 'school.created']);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/audit-logs?action=athlete.viewed')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('logs.data', 2)
            ->where('filters.action', 'athlete.viewed'));
});

test('forbidden audit access renders the permission denied page', function () {
    $this->actingAs(User::factory()->organizer()->create())
        ->get('/audit-logs')
        ->assertForbidden()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('error')
            ->where('status', 403));
});
