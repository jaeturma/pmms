<?php

use App\Models\Meet;
use Inertia\Testing\AssertableInertia;

test('guests can view the faqs page for a published meet; unpublished meets 404', function () {
    $meet = Meet::factory()->active()->published()->create();

    $this->get("/meets/{$meet->id}/faqs")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('portal/faqs')
            ->where('meet.name', $meet->name)
            ->has('meet.starts_at')
            ->has('meet.ends_at')
            ->has('meet.school_year')
            ->has('meet.status_label'));

    $hidden = Meet::factory()->active()->create();

    $this->get("/meets/{$hidden->id}/faqs")->assertNotFound();
});

test('the faqs page carries only the public-safe meet summary, no internal fields', function () {
    $meet = Meet::factory()->active()->published()->create();

    $this->get("/meets/{$meet->id}/faqs")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('meet', fn (AssertableInertia $row) => $row
                ->hasAll(['id', 'name', 'school_year', 'starts_at', 'starts_at_iso', 'ends_at', 'venue', 'status_label'])
                ->missing('is_published')
                ->missing('is_active')
                ->missing('created_at')
                ->missing('updated_at')));
});
