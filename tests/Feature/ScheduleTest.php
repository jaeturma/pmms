<?php

use App\Models\Athlete;
use App\Models\AuditLog;
use App\Models\Delegation;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\EventSchedule;
use App\Models\Meet;
use App\Models\ScoringSession;
use App\Models\User;
use App\Models\Venue;
use Inertia\Testing\AssertableInertia;

/**
 * A confirmed entry for the given match's event, in its own approved
 * delegation — mirrors MatchTest.php's own helper, kept local since Pest
 * test files don't share functions across files.
 */
function scheduleTestConfirmedEntryFor(EventMatch $match): Entry
{
    $delegation = Delegation::factory()->approved()->create(['meet_id' => $match->meet_id]);
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);

    return Entry::factory()->confirmed()->create([
        'athlete_id' => $athlete->id,
        'delegation_id' => $delegation->id,
        'event_id' => $match->event_id,
    ]);
}

/**
 * @return array{meet_id: int, event_id: int, venue_id: int, scheduled_date: string, starts_at: string, ends_at: string, note: null}
 */
function validSlotInput(?Meet $meet = null, ?Venue $venue = null): array
{
    $meet ??= Meet::factory()->active()->create();
    $event = Event::factory()->create();
    $meet->events()->attach($event);

    return [
        'meet_id' => $meet->id,
        'event_id' => $event->id,
        'venue_id' => ($venue ?? Venue::factory()->create())->id,
        'scheduled_date' => '2026-08-10',
        'starts_at' => '08:00',
        'ends_at' => '10:00',
        'note' => null,
    ];
}

test('guests are redirected from the schedule', function () {
    $this->get('/schedule')->assertRedirect('/login');
});

test('the schedule renders for every role with the manage flag', function () {
    EventSchedule::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get('/schedule')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('schedule/index')
            ->has('schedules.data', 1)
            ->where('canManage', false));

    $this->actingAs(User::factory()->organizer()->create())
        ->get('/schedule')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('canManage', true));
});

test('managers can schedule an event of a registration-closed or active meet', function (Meet $meet) {
    $this->actingAs(User::factory()->organizer()->create())
        ->post('/schedule', validSlotInput($meet))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('event_schedules', [
        'meet_id' => $meet->id,
        'starts_at' => '08:00:00',
        'ends_at' => '10:00:00',
    ]);

    expect(AuditLog::query()->where('action', 'schedule.created')->exists())->toBeTrue();
})->with([
    'registration closed' => fn () => Meet::factory()->registrationClosed()->create(),
    'active' => fn () => Meet::factory()->active()->create(),
]);

test('scheduling is rejected while the meet is draft, registration-open, or completed', function (Meet $meet) {
    $this->actingAs(User::factory()->admin()->create())
        ->post('/schedule', validSlotInput($meet))
        ->assertSessionHasErrors('meet_id');

    $this->assertDatabaseCount('event_schedules', 0);
})->with([
    'draft' => fn () => Meet::factory()->create(),
    'registration open' => fn () => Meet::factory()->registrationOpen()->create(),
    'completed' => fn () => Meet::factory()->completed()->create(),
]);

test('events not attached to the meet cannot be scheduled', function () {
    $input = validSlotInput();
    $input['event_id'] = Event::factory()->create()->id;

    $this->actingAs(User::factory()->admin()->create())
        ->post('/schedule', $input)
        ->assertSessionHasErrors('event_id');
});

test('archived venues cannot be scheduled', function () {
    $input = validSlotInput(venue: Venue::factory()->archived()->create());

    $this->actingAs(User::factory()->admin()->create())
        ->post('/schedule', $input)
        ->assertSessionHasErrors('venue_id');
});

test('the end time must be after the start time', function () {
    $input = validSlotInput();
    $input['ends_at'] = '08:00';

    $this->actingAs(User::factory()->admin()->create())
        ->post('/schedule', $input)
        ->assertSessionHasErrors('ends_at');
});

test('overlapping slots at the same venue on the same day are blocked', function () {
    $venue = Venue::factory()->create();
    EventSchedule::factory()->create([
        'venue_id' => $venue->id,
        'scheduled_date' => '2026-08-10',
        'starts_at' => '08:00:00',
        'ends_at' => '10:00:00',
    ]);

    $input = validSlotInput(venue: $venue);
    $input['starts_at'] = '09:00';
    $input['ends_at'] = '11:00';

    $this->actingAs(User::factory()->admin()->create())
        ->post('/schedule', $input)
        ->assertSessionHasErrors('starts_at');

    expect(EventSchedule::query()->count())->toBe(1);
});

test('back-to-back slots and other venues do not conflict', function () {
    $venue = Venue::factory()->create();
    EventSchedule::factory()->create([
        'venue_id' => $venue->id,
        'scheduled_date' => '2026-08-10',
        'starts_at' => '08:00:00',
        'ends_at' => '10:00:00',
    ]);

    $admin = User::factory()->admin()->create();

    $backToBack = validSlotInput(venue: $venue);
    $backToBack['starts_at'] = '10:00';
    $backToBack['ends_at'] = '12:00';

    $this->actingAs($admin)
        ->post('/schedule', $backToBack)
        ->assertSessionHasNoErrors();

    $otherVenue = validSlotInput();
    $otherVenue['starts_at'] = '08:30';
    $otherVenue['ends_at'] = '09:30';

    $this->actingAs($admin)
        ->post('/schedule', $otherVenue)
        ->assertSessionHasNoErrors();

    expect(EventSchedule::query()->count())->toBe(3);
});

test('viewers and delegation officers cannot manage the schedule', function (User $user) {
    $this->actingAs($user)
        ->post('/schedule', validSlotInput())
        ->assertForbidden();

    $this->assertDatabaseCount('event_schedules', 0);
})->with([
    'viewer' => fn () => User::factory()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
]);

test('managers can update a slot without conflicting with itself', function () {
    $slot = EventSchedule::factory()->create([
        'scheduled_date' => '2026-08-10',
        'starts_at' => '08:00:00',
        'ends_at' => '10:00:00',
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->put("/schedule/{$slot->id}", [
            'meet_id' => $slot->meet_id,
            'event_id' => $slot->event_id,
            'venue_id' => $slot->venue_id,
            'scheduled_date' => '2026-08-10',
            'starts_at' => '09:00',
            'ends_at' => '11:00',
            'note' => 'Moved an hour later',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($slot->refresh()->starts_at)->toBe('09:00:00')
        ->and($slot->note)->toBe('Moved an hour later')
        ->and(AuditLog::query()->where('action', 'schedule.updated')->exists())->toBeTrue();
});

test('managers can delete a slot while the meet is schedulable', function () {
    $slot = EventSchedule::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete("/schedule/{$slot->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('event_schedules', ['id' => $slot->id]);

    expect(AuditLog::query()->where('action', 'schedule.deleted')->exists())->toBeTrue();
});

test('slots of completed meets cannot be deleted', function () {
    $slot = EventSchedule::factory()
        ->for(Meet::factory()->completed(), 'meet')
        ->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete("/schedule/{$slot->id}")
        ->assertRedirect();

    $this->assertDatabaseHas('event_schedules', ['id' => $slot->id]);
});

test('the schedule can be filtered per day and per venue', function () {
    $venue = Venue::factory()->create();
    EventSchedule::factory()->create([
        'venue_id' => $venue->id,
        'scheduled_date' => '2026-08-10',
    ]);
    EventSchedule::factory()->create(['scheduled_date' => '2026-08-11']);

    $viewer = User::factory()->create();

    $this->actingAs($viewer)
        ->get('/schedule?date=2026-08-10')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('schedules.data', 1)
            ->where('schedules.data.0.date', '2026-08-10'));

    $this->actingAs($viewer)
        ->get("/schedule?venue_id={$venue->id}")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('schedules.data', 1)
            ->where('schedules.data.0.venue', $venue->name));
});

test('venues with schedule slots cannot be deleted', function () {
    $slot = EventSchedule::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete("/venues/{$slot->venue_id}")
        ->assertRedirect();

    $this->assertDatabaseHas('venues', ['id' => $slot->venue_id]);
});

test('a slot with no match exposes no live-scoreboard link', function () {
    $slot = EventSchedule::factory()->create();

    $this->actingAs(User::factory()->organizer()->create())
        ->get('/schedule')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('schedules.data.0.match_id', null)
            ->where('schedules.data.0.is_live', false));
});

test('a slot with an in-progress live session is flagged live for managers', function () {
    $slot = EventSchedule::factory()->create();
    $match = EventMatch::factory()->create([
        'meet_id' => $slot->meet_id,
        'event_id' => $slot->event_id,
        'event_schedule_id' => $slot->id,
    ]);
    ScoringSession::factory()->create(['match_id' => $match->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/schedule')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('schedules.data.0.match_id', $match->id)
            ->where('schedules.data.0.is_live', true));
});

test('a slot with only an ended session is not flagged live', function () {
    $slot = EventSchedule::factory()->create();
    $match = EventMatch::factory()->create([
        'meet_id' => $slot->meet_id,
        'event_id' => $slot->event_id,
        'event_schedule_id' => $slot->id,
    ]);
    ScoringSession::factory()->ended()->create(['match_id' => $match->id]);

    $this->actingAs(User::factory()->organizer()->create())
        ->get('/schedule')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('schedules.data.0.match_id', $match->id)
            ->where('schedules.data.0.is_live', false));
});

test('viewers never get a live-scoreboard link, even for a live match', function () {
    $slot = EventSchedule::factory()->create();
    $match = EventMatch::factory()->create([
        'meet_id' => $slot->meet_id,
        'event_id' => $slot->event_id,
        'event_schedule_id' => $slot->id,
    ]);
    ScoringSession::factory()->create(['match_id' => $match->id]);

    $this->actingAs(User::factory()->create())
        ->get('/schedule')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('schedules.data.0.match_id', null)
            ->where('schedules.data.0.is_live', false));
});

test('delegation officers only get a live-scoreboard link for their own delegation\'s match', function () {
    $slot = EventSchedule::factory()->create();
    $match = EventMatch::factory()->create([
        'meet_id' => $slot->meet_id,
        'event_id' => $slot->event_id,
        'event_schedule_id' => $slot->id,
    ]);
    $entry = scheduleTestConfirmedEntryFor($match);
    $match->entries()->attach($entry);
    ScoringSession::factory()->create(['match_id' => $match->id]);

    $stranger = User::factory()->delegationOfficer()->create();

    $this->actingAs($stranger)
        ->get('/schedule')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('schedules.data.0.match_id', null)
            ->where('schedules.data.0.is_live', false));

    $officer = User::factory()->delegationOfficer()->create();
    $entry->delegation->officers()->attach($officer);

    $this->actingAs($officer)
        ->get('/schedule')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('schedules.data.0.match_id', $match->id)
            ->where('schedules.data.0.is_live', true));
});
