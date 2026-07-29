<?php

use App\Models\Event;
use App\Models\Meet;
use App\Models\Sport;
use Inertia\Testing\AssertableInertia;

test('guests can view the gallery page for a published meet; unpublished meets 404', function () {
    $meet = Meet::factory()->active()->published()->create();

    $this->get("/meets/{$meet->id}/gallery")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('public/gallery')
            ->where('meet.name', $meet->name));

    $hidden = Meet::factory()->active()->create();

    $this->get("/meets/{$hidden->id}/gallery")->assertNotFound();
});

test('the gallery lists sports contested in the meet with a real event count each', function () {
    $meet = Meet::factory()->active()->published()->create();

    $athletics = Sport::factory()->create(['name' => 'Athletics']);
    $athleticsEventA = Event::factory()->create(['sport_id' => $athletics->id]);
    $athleticsEventB = Event::factory()->create(['sport_id' => $athletics->id]);
    $meet->events()->attach([$athleticsEventA->id, $athleticsEventB->id]);

    $basketball = Sport::factory()->create(['name' => 'Basketball']);
    $basketballEvent = Event::factory()->create(['sport_id' => $basketball->id]);
    $meet->events()->attach($basketballEvent->id);

    $this->get("/meets/{$meet->id}/gallery")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('sports', 2)
            ->where('sports.0.name', 'Athletics')
            ->where('sports.0.event_count', 2)
            ->where('sports.1.name', 'Basketball')
            ->where('sports.1.event_count', 1));
});

test('sports not attached to this meet never appear in the gallery', function () {
    $meet = Meet::factory()->active()->published()->create();

    $sport = Sport::factory()->create(['name' => 'Volleyball']);
    Event::factory()->create(['sport_id' => $sport->id]);

    $this->get("/meets/{$meet->id}/gallery")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('sports', 0));
});

test('gallery rows carry no internal or unrelated fields', function () {
    $meet = Meet::factory()->active()->published()->create();

    $sport = Sport::factory()->create(['name' => 'Chess']);
    $event = Event::factory()->create(['sport_id' => $sport->id]);
    $meet->events()->attach($event->id);

    $this->get("/meets/{$meet->id}/gallery")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('sports.0', fn (AssertableInertia $row) => $row
                ->hasAll(['id', 'name', 'event_count'])
                ->missing('active')
                ->missing('created_at')
                ->missing('updated_at')));
});
