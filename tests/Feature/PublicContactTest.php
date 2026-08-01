<?php

use App\Models\Meet;
use Inertia\Testing\AssertableInertia;

test('guests can view the contact page for a published meet; unpublished meets 404', function () {
    $meet = Meet::factory()->active()->published()->create(['venue' => 'Division Sports Complex']);

    $this->get("/meets/{$meet->id}/contact")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('portal/contact')
            ->where('meet.name', $meet->name)
            ->where('meet.venue', 'Division Sports Complex')
            ->where('meet.school_year', $meet->school_year));

    $hidden = Meet::factory()->active()->create();

    $this->get("/meets/{$hidden->id}/contact")->assertNotFound();
});

test('the contact page shows only real meet fields, no invented office-contact content', function () {
    $meet = Meet::factory()->active()->published()->create();

    $this->get("/meets/{$meet->id}/contact")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('meet', fn (AssertableInertia $row) => $row
                ->hasAll(['id', 'name', 'school_year', 'starts_at', 'starts_at_iso', 'ends_at', 'venue', 'status_label'])
                ->missing('contact_email')
                ->missing('contact_phone')
                ->missing('office_address')
                ->missing('status')));
});
