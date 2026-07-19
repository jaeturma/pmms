<?php

use App\Models\AuditLog;
use App\Models\EventResult;
use App\Models\EventSchedule;
use App\Models\ResultPlacement;
use App\Models\User;
use App\Models\Venue;
use Inertia\Testing\AssertableInertia;

test('the official result sheet renders validated results for every role', function () {
    $placement = ResultPlacement::factory()->create();
    $result = $placement->result;
    $result->forceFill([
        'status' => 'validated',
        'validated_by' => User::factory()->organizer()->create()->id,
        'validated_at' => now(),
    ])->save();

    $this->actingAs(User::factory()->create())
        ->get("/reports/results/{$result->id}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('reports/result-sheet')
            ->has('placements', 1)
            ->where('placements.0.rank', 1)
            ->where('result.validated_by', $result->validatedBy->name)
            ->whereNot('result.validated_at', null));
});

test('unvalidated results have no official sheet', function () {
    $result = EventResult::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get("/reports/results/{$result->id}")
        ->assertNotFound();

    $this->actingAs(User::factory()->admin()->create())
        ->get("/reports/results/{$result->id}/download")
        ->assertNotFound();
});

test('result sheet CSV downloads are audited', function () {
    $placement = ResultPlacement::factory()->create();
    $result = $placement->result;
    $result->forceFill(['status' => 'validated', 'validated_at' => now()])->save();

    $response = $this->actingAs(User::factory()->create())
        ->get("/reports/results/{$result->id}/download");

    $response->assertOk();

    expect($response->streamedContent())->toContain('Rank,Athlete,School')
        ->and(AuditLog::query()->where('action', 'report.result_sheet_exported')->exists())->toBeTrue();
});

test('the medal tally report counts validated results only', function () {
    $validated = ResultPlacement::factory()->create();
    $validated->result->forceFill(['status' => 'validated', 'validated_at' => now()])->save();

    ResultPlacement::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get('/reports/tally')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('reports/medal-tally')
            ->has('schools', 1)
            ->where('schools.0.gold', 1));
});

test('medal tally CSV downloads are audited and carry both sections', function () {
    $placement = ResultPlacement::factory()->create();
    $placement->result->forceFill(['status' => 'validated', 'validated_at' => now()])->save();

    $response = $this->actingAs(User::factory()->delegationOfficer()->create())
        ->get('/reports/tally/download');

    $response->assertOk();

    expect($response->streamedContent())->toContain('School')
        ->toContain('District')
        ->and(AuditLog::query()->where('action', 'report.tally_exported')->exists())->toBeTrue();
});

test('the daily schedule sheet shows one day grouped by venue', function () {
    $venueA = Venue::factory()->create(['name' => 'Alpha Gym']);
    $venueB = Venue::factory()->create(['name' => 'Beta Field']);

    EventSchedule::factory()->create([
        'venue_id' => $venueA->id,
        'scheduled_date' => '2026-08-10',
        'starts_at' => '10:00:00',
        'ends_at' => '12:00:00',
    ]);
    EventSchedule::factory()->create([
        'venue_id' => $venueA->id,
        'scheduled_date' => '2026-08-10',
        'starts_at' => '08:00:00',
        'ends_at' => '09:00:00',
    ]);
    EventSchedule::factory()->create([
        'venue_id' => $venueB->id,
        'scheduled_date' => '2026-08-10',
    ]);
    EventSchedule::factory()->create(['scheduled_date' => '2026-08-11']);

    $this->actingAs(User::factory()->create())
        ->get('/reports/schedule?date=2026-08-10')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('reports/schedule-sheet')
            ->where('date', '2026-08-10')
            ->has('venues', 2)
            ->where('venues.0.venue', 'Alpha Gym')
            ->has('venues.0.slots', 2)
            ->where('venues.0.slots.0.starts_at', '08:00')
            ->where('venues.1.venue', 'Beta Field'));
});

test('schedule sheet CSV downloads are audited', function () {
    EventSchedule::factory()->create(['scheduled_date' => '2026-08-10']);

    $response = $this->actingAs(User::factory()->create())
        ->get('/reports/schedule/download?date=2026-08-10');

    $response->assertOk();

    expect($response->streamedContent())->toContain('Venue,Start,End')
        ->and(AuditLog::query()->where('action', 'report.schedule_exported')->exists())->toBeTrue();
});

test('report pages require authentication', function () {
    $this->get('/reports/tally')->assertRedirect('/login');
    $this->get('/reports/schedule')->assertRedirect('/login');
});
