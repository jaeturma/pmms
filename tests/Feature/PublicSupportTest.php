<?php

use App\Models\Meet;
use Inertia\Testing\AssertableInertia;

test('guests can view support for a published meet', function () {
    $meet = Meet::factory()->active()->published()->create();

    $this->get("/meets/{$meet->id}/support")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('portal/support')
            ->where('meet.id', $meet->id));
});

test('support for an unpublished meet is not public', function () {
    $meet = Meet::factory()->create();

    $this->get("/meets/{$meet->id}/support")->assertNotFound();
});
