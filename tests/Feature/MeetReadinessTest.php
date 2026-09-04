<?php

use App\Models\Delegation;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\EventVenue;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\UserRole;
use App\Models\SportRosterMember;
use App\Models\User;
use App\Models\Venue;
use App\Services\MeetReadinessService;
use Illuminate\Database\Eloquent\Model;

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

test('tournament ICT readiness is scoped to assigned sport and exposes safe actions', function () {
    [$meet, $event] = readinessFixture();
    $otherEvent = Event::factory()->create(['active' => true]);
    $meet->events()->attach($otherEvent);
    MeetSport::factory()->create(['meet_id' => $meet->id, 'sport_id' => $otherEvent->sport_id, 'active' => true]);
    $ict = User::factory()->create(['role' => UserRole::TournamentICT]);
    $meetSport = MeetSport::query()->where('meet_id', $meet->id)->where('sport_id', $event->sport_id)->firstOrFail();
    MeetSportAssignment::factory()->create([
        'user_id' => $ict->id,
        'meet_sport_id' => $meetSport->id,
        'role' => MeetSportAssignmentRole::TournamentICT,
        'status' => MeetSportAssignmentStatus::Active,
    ]);

    $this->withoutVite();
    $this->actingAs($ict)->get('/monitoring/readiness?meet_id='.$meet->id)
        ->assertOk()->assertInertia(fn ($page) => $page
            ->where('summary.events_total', 1)
            ->where('events.data.0.id', $event->id)
            ->where('issues.data', fn ($issues) => collect($issues)->contains(fn ($issue) => $issue['domain'] === 'Schedule'
                && $issue['can_act'] === true
                && str_starts_with($issue['target'], '/schedule'))));
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

test('readiness reports distinct athlete totals by team and sport', function () {
    [$meet, $event] = readinessFixture();
    $meetSport = MeetSport::query()->where('meet_id', $meet->id)->firstOrFail();
    $delegation = Delegation::factory()->create(['meet_id' => $meet->id]);
    $athlete = Entry::factory()->create([
        'delegation_id' => $delegation->id,
        'event_id' => $event->id,
    ])->athlete;

    SportRosterMember::query()->create([
        'meet_sport_id' => $meetSport->id,
        'delegation_id' => $delegation->id,
        'athlete_id' => $athlete->id,
        'level' => $event->age_division->value,
        'gender' => $event->gender->value,
    ]);

    $report = app(MeetReadinessService::class)->calculate($meet);

    expect($report['summary']['athletes_total'])->toBe(1)
        ->and($report['sports'][0]['athletes'])->toBe(1)
        ->and($report['teams'][0]['athletes'])->toBe(1);
});

test('readiness returns a bounded data payload without lazy loading or model serialization', function () {
    [$meet] = readinessFixture();
    Model::preventLazyLoading();

    try {
        $report = app(MeetReadinessService::class)->calculate($meet);
    } finally {
        Model::preventLazyLoading(false);
    }

    $containsModel = function (mixed $value) use (&$containsModel): bool {
        if ($value instanceof Model) {
            return true;
        }

        foreach (is_iterable($value) ? $value : [] as $child) {
            if ($containsModel($child)) {
                return true;
            }
        }

        return false;
    };

    expect($containsModel($report))->toBeFalse()
        ->and($report['events']['per_page'])->toBe(10)
        ->and($report['issues']['per_page'])->toBe(10);
});
