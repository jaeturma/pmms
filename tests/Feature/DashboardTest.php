<?php

use App\Models\AuditLog;
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
            ->has('stats', 3)
            ->where('stats.0.key', 'users')
            ->where('stats.0.value', 1)
            ->has('recentActivity')
            ->where('recentActivity.0.action', 'auth.login')
            ->where('recentActivity.0.user', $user->name),
    );
});
