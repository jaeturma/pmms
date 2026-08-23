<?php

use App\Models\Athlete;
use App\Models\Delegation;
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

test('the dashboard provides compact athlete and entry stats without recent activity', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('dashboard')
            ->has('stats', 2)
            ->missing('recentActivity'),
    );
});

test('the dashboard stats show only athlete and entry counts', function () {
    $delegation = Delegation::factory()->create();
    Athlete::factory()->count(3)->for($delegation)->create();

    $response = $this->actingAs(User::factory()->create())->get(route('dashboard'));

    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('dashboard')
            ->has('stats', 2)
            ->where('stats.0.key', 'athletes')
            ->where('stats.0.value', 3)
            ->where('stats.1.key', 'entries')
            ->where('stats.1.value', 0),
    );
});
