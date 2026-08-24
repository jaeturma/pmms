<?php

use App\Models\Meet;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('guests are redirected from backend support', function () {
    $this->get('/support')->assertRedirect('/login');
});

test('authenticated users can view backend support', function () {
    $this->actingAs(User::factory()->create())
        ->get('/support')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('support'));
});

test('support is no longer exposed in the public meet portal', function () {
    $meet = Meet::factory()->active()->published()->create();

    $this->get("/meets/{$meet->id}/support")->assertNotFound();
});
