<?php

use App\Models\Accreditation;
use App\Models\Delegation;
use App\Models\EventResult;
use App\Models\EventSchedule;
use App\Models\Incident;
use App\Models\Meet;
use App\Models\Protest;
use App\Models\ResultPlacement;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('the operations block is absent without an active meet', function () {
    Meet::factory()->registrationOpen()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('operations', null));
});

test('today\'s slots show only the active meet\'s slots for today', function () {
    $meet = Meet::factory()->active()->create();

    $today = EventSchedule::factory()->create([
        'meet_id' => $meet->id,
        'scheduled_date' => today()->toDateString(),
        'starts_at' => '08:00:00',
        'ends_at' => '10:00:00',
    ]);
    EventSchedule::factory()->create([
        'meet_id' => $meet->id,
        'scheduled_date' => today()->addDay()->toDateString(),
    ]);
    EventSchedule::factory()->create([
        'scheduled_date' => today()->toDateString(),
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('operations.meet.name', $meet->name)
            ->has('operations.todaySlots', 1)
            ->where('operations.todaySlots.0.id', $today->id)
            ->where('operations.todaySlots.0.starts_at', '08:00'));
});

test('viewers get schedule and tally summaries but no queues or protests', function () {
    $meet = Meet::factory()->active()->create();

    $result = EventResult::factory()->validated()->create(['meet_id' => $meet->id]);
    ResultPlacement::factory()->create(['event_result_id' => $result->id, 'rank' => 1]);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('operations.tallyTop', 1)
            ->where('operations.tallyTop.0.gold', 1)
            ->where('operations.queues', null)
            ->where('operations.myProtests', null));
});

test('managers see the operational queues with correct counts', function () {
    $meet = Meet::factory()->active()->create();

    EventResult::factory()->create(['meet_id' => $meet->id]);
    EventResult::factory()->validated()->create(['meet_id' => $meet->id]);

    $delegation = Delegation::factory()->approved()->create(['meet_id' => $meet->id]);
    Protest::factory()->create(['delegation_id' => $delegation->id]);
    Protest::factory()->dismissed()->create(['delegation_id' => $delegation->id]);

    Incident::factory()->create(['meet_id' => $meet->id]);
    Incident::factory()->resolved()->create(['meet_id' => $meet->id]);

    $accreditation = Accreditation::factory()->create(['delegation_id' => $delegation->id]);

    $this->actingAs(User::factory()->organizer()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('operations.queues.pending_results', 1)
            ->where('operations.queues.open_protests', 1)
            ->where('operations.queues.open_incidents', 1)
            ->where('operations.queues.accredited', 1)
            ->where('operations.queues.accreditable', 1));
});

test('officers see their own delegation\'s protests only', function () {
    $meet = Meet::factory()->active()->create();

    $mine = Delegation::factory()->approved()->create(['meet_id' => $meet->id]);
    $theirs = Delegation::factory()->approved()->create(['meet_id' => $meet->id]);

    $myProtest = Protest::factory()->create(['delegation_id' => $mine->id]);
    Protest::factory()->create(['delegation_id' => $theirs->id]);

    $officer = User::factory()->delegationOfficer()->create();
    $mine->officers()->attach($officer);

    $this->actingAs($officer)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('operations.queues', null)
            ->has('operations.myProtests', 1)
            ->where('operations.myProtests.0.id', $myProtest->id));
});
