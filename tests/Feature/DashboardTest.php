<?php

use App\Models\Athlete;
use App\Models\AuditLog;
use App\Models\Delegation;
use App\Models\School;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('the dashboard provides stats and recent activity', function () {
    $user = User::factory()->create();
    AuditLog::factory()->for($user)->create(['action' => 'auth.login']);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('dashboard')
            ->has('stats', 6)
            ->where('stats.4.key', 'users')
            ->where('stats.4.value', 1)
            ->has('recentActivity')
            ->where('recentActivity.0.action', 'auth.login')
            ->where('recentActivity.0.user', $user->name),
    );
});

test('the dashboard stats reflect real registration counts', function () {
    School::factory()->count(2)->create();
    $delegation = Delegation::factory()->create();
    Athlete::factory()->count(3)->for($delegation)->create();

    $response = $this->actingAs(User::factory()->create())->get(route('dashboard'));

    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('dashboard')
            ->where('stats.0.key', 'schools')
            ->where('stats.0.value', 3)
            ->where('stats.1.key', 'delegations')
            ->where('stats.1.value', 1)
            ->where('stats.2.key', 'athletes')
            ->where('stats.2.value', 3)
            ->where('stats.3.key', 'entries')
            ->where('stats.3.value', 0),
    );
});
