<?php

use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\District;
use App\Models\Event;
use App\Models\Meet;
use App\Models\School;
use App\Models\Sport;
use Inertia\Testing\AssertableInertia;

test('guests can view the about page for a published meet; unpublished meets 404', function () {
    $meet = Meet::factory()->active()->published()->create();

    $this->get("/meets/{$meet->id}/about")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('portal/about')
            ->where('meet.name', $meet->name)
            ->where('municipalityCount', 0)
            ->where('schoolCount', 0)
            ->where('sportCount', 0));

    $hidden = Meet::factory()->active()->create();

    $this->get("/meets/{$hidden->id}/about")->assertNotFound();
});

test('guests can access the stable about URL for the active published meet', function () {
    Meet::factory()->active()->featured()->published()->create(['name' => 'DdOPAA Provincial Meet 2026']);

    $this->get('/about')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('portal/about')
            ->where('meet.name', 'DdOPAA Provincial Meet 2026'));
});

test('the about page counts real competing municipalities, schools, and sports', function () {
    $meet = Meet::factory()->active()->published()->create();

    $district = District::factory()->create();
    $schoolA = School::factory()->create(['district_id' => $district->id]);
    $schoolB = School::factory()->create(['district_id' => $district->id]);

    $delegationA = Delegation::factory()->approved()->create(['meet_id' => $meet->id, 'school_id' => $schoolA->id]);
    Athlete::factory()->create(['delegation_id' => $delegationA->id, 'school_id' => $schoolA->id]);

    $delegationB = Delegation::factory()->approved()->create(['meet_id' => $meet->id, 'school_id' => $schoolB->id]);
    Athlete::factory()->create(['delegation_id' => $delegationB->id, 'school_id' => $schoolB->id]);

    $sport = Sport::factory()->create();
    $event = Event::factory()->create(['sport_id' => $sport->id]);
    $meet->events()->attach($event->id);

    $this->get("/meets/{$meet->id}/about")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('municipalityCount', 1)
            ->where('schoolCount', 2)
            ->where('sportCount', 1));
});

test('the about page excludes other meets from its counts', function () {
    $meet = Meet::factory()->active()->published()->create();

    $foreignDelegation = Delegation::factory()->approved()->create();
    Athlete::factory()->create(['delegation_id' => $foreignDelegation->id]);

    $this->get("/meets/{$meet->id}/about")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('municipalityCount', 0)
            ->where('schoolCount', 0));
});
