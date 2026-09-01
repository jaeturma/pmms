<?php

use App\Models\Delegation;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\EventVenue;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\User;
use App\Models\Venue;
use App\Services\MeetReadinessService;

function readinessFixture(): array
{
    $meet = Meet::factory()->create(['is_active' => true]);
    $event = Event::factory()->create(['active' => true, 'is_medal_event' => false]);
    $meet->events()->attach($event);
    MeetSport::factory()->create(['meet_id' => $meet->id, 'sport_id' => $event->sport_id, 'active' => true]);

    return [$meet, $event];
}

test('authorized management can view provincial meet readiness', function () {
    [$meet] = readinessFixture();
    $this->withoutVite();
    $this->actingAs(User::factory()->admin()->create())->get('/monitoring/readiness?meet_id='.$meet->id)
        ->assertOk()->assertInertia(fn ($page) => $page->component('meet-readiness/index')->where('summary.sports_total', 1)->where('summary.events_total', 1));
});

test('unassigned viewers cannot view executive readiness', function () {
    [$meet] = readinessFixture();
    $this->withoutVite();
    $this->actingAs(User::factory()->create())->get('/monitoring/readiness?meet_id='.$meet->id)->assertForbidden();
});

test('readiness identifies venue entry and schedule prerequisites deterministically', function () {
    [$meet, $event] = readinessFixture();
    $service = app(MeetReadinessService::class);
    $first = $service->calculate($meet);
    expect($first['events']['data'][0]['venue'])->toBeFalse()
        ->and($first['events']['data'][0]['entries'])->toBe(0)
        ->and($first['events']['data'][0]['schedule'])->toBeFalse()
        ->and($first['events']['data'][0]['status'])->toBe('not_ready');

    $venue = Venue::factory()->create();
    EventVenue::query()->create(['event_id' => $event->id, 'venue_id' => $venue->id]);
    EventSchedule::factory()->create(['meet_id' => $meet->id, 'event_id' => $event->id, 'venue_id' => $venue->id]);
    $delegation = Delegation::factory()->create(['meet_id' => $meet->id]);
    Entry::factory()->create(['delegation_id' => $delegation->id, 'event_id' => $event->id]);
    $second = $service->calculate($meet);
    expect($second['events']['data'][0]['venue'])->toBeTrue()
        ->and($second['events']['data'][0]['entries'])->toBe(1)
        ->and($second['events']['data'][0]['schedule'])->toBeTrue()
        ->and($second['overall'])->toBeInt();
});
