<?php

use App\Enums\MatchStatus;
use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\ResultStatus;
use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\AuditLog;
use App\Models\Delegation;
use App\Models\District;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\EventResult;
use App\Models\EventSchedule;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\MedalAward;
use App\Models\ResultPlacement;
use App\Models\School;
use App\Models\ScoringSession;
use App\Models\Sport;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

/**
 * A confirmed entry for the given meet+event pair.
 */
function placeableEntry(Meet $meet, Event $event): Entry
{
    $delegation = Delegation::factory()->approved()->create(['meet_id' => $meet->id]);
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);

    return Entry::factory()->confirmed()->create([
        'athlete_id' => $athlete->id,
        'delegation_id' => $delegation->id,
        'event_id' => $event->id,
    ]);
}

/**
 * @return array{meet: Meet, event: Event, match: EventMatch, entries: array<int, Entry>}
 */
function resultFixture(int $entryCount = 2): array
{
    $meet = Meet::factory()->active()->create();
    $event = Event::factory()->create();
    $meet->events()->attach($event);

    $entries = [];

    for ($i = 0; $i < $entryCount; $i++) {
        $entries[] = placeableEntry($meet, $event);
    }

    $schedule = EventSchedule::factory()->create(['meet_id' => $meet->id, 'event_id' => $event->id]);
    $match = EventMatch::factory()->create([
        'meet_id' => $meet->id,
        'event_id' => $event->id,
        'event_schedule_id' => $schedule->id,
        'status' => MatchStatus::Completed,
    ]);
    $match->entries()->sync(collect($entries)->pluck('id'));

    return ['meet' => $meet, 'event' => $event, 'match' => $match, 'entries' => $entries];
}

test('a placement shows the athlete\'s own school, not the municipal delegation\'s', function () {
    $meet = Meet::factory()->active()->create();
    $event = Event::factory()->create();
    $meet->events()->attach($event);

    $district = District::factory()->create();
    $delegation = Delegation::factory()->approved()->create([
        'meet_id' => $meet->id,
        'school_id' => null,
        'district_id' => $district->id,
    ]);
    $school = School::factory()->create(['district_id' => $district->id, 'name' => 'Nabunturan National High School']);
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id, 'school_id' => $school->id]);
    $entry = Entry::factory()->confirmed()->create([
        'athlete_id' => $athlete->id,
        'delegation_id' => $delegation->id,
        'event_id' => $event->id,
    ]);

    $result = EventResult::factory()->create(['meet_id' => $meet->id, 'event_id' => $event->id]);
    ResultPlacement::factory()->create(['event_result_id' => $result->id, 'entry_id' => $entry->id, 'rank' => 1]);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/results')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('results.data.0.placements.0.school', 'Nabunturan National High School'));
});

test('guests are redirected from results', function () {
    $this->get('/results')->assertRedirect('/login');
});

test('unvalidated results are visible to managers only', function () {
    $meet = Meet::factory()->active()->create();
    EventResult::factory()->count(2)->create(['meet_id' => $meet->id]);
    $validated = EventResult::factory()->validated()->create(['meet_id' => $meet->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/results')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('results/index')
            ->has('results.data', 3)
            ->where('canManage', true));

    foreach ([User::factory()->create(), User::factory()->delegationOfficer()->create()] as $user) {
        $this->actingAs($user)
            ->get('/results')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('results.data', 1)
                ->where('results.data.0.id', $validated->id)
                ->where('canManage', false));
    }
});

test('a technical official only sees results for their assigned sport', function () {
    $ownSport = Sport::factory()->create();
    $otherSport = Sport::factory()->create();
    $ownEvent = Event::factory()->create(['sport_id' => $ownSport->id]);
    $otherEvent = Event::factory()->create(['sport_id' => $otherSport->id]);
    $meet = Meet::factory()->active()->create();

    $ownEncoded = EventResult::factory()->create(['meet_id' => $meet->id, 'event_id' => $ownEvent->id]);
    EventResult::factory()->create(['meet_id' => $meet->id, 'event_id' => $otherEvent->id]);
    EventResult::factory()->validated()->create(['meet_id' => $meet->id]);

    $official = User::factory()->technicalOfficial()->create();
    $official->sports()->attach($ownSport->id);

    $this->actingAs($official)
        ->get('/results')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('results.data', 1)
            ->where('results.data.0.id', $ownEncoded->id)
            ->where('canManage', false)
            ->where('canEncode', false)
            ->where('encodedEventKeys', []));
});

test('managers can encode a result for an active meet', function () {
    ['meet' => $meet, 'event' => $event, 'match' => $match, 'entries' => $entries] = resultFixture();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/results', [
            'meet_id' => $meet->id,
            'event_id' => $event->id,
            'match_id' => $match->id,
            'placements' => [
                ['entry_id' => $entries[0]->id, 'rank' => 1, 'mark' => '11.2s', 'is_tie' => false],
                ['entry_id' => $entries[1]->id, 'rank' => 2, 'mark' => null, 'is_tie' => false],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $result = EventResult::query()->firstOrFail();

    expect($result->status)->toBe(ResultStatus::Encoded)
        ->and($result->encoded_by)->not->toBeNull()
        ->and($result->placements()->count())->toBe(2)
        ->and(AuditLog::query()->where('action', 'result.manually_entered')->exists())->toBeTrue();
});

test('a match can be finalized with a result and no live scoring session was ever started (Phase 7)', function () {
    ['meet' => $meet, 'event' => $event, 'match' => $match, 'entries' => $entries] = resultFixture();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/results', [
            'meet_id' => $meet->id,
            'event_id' => $event->id,
            'match_id' => $match->id,
            'placements' => [
                ['entry_id' => $entries[0]->id, 'rank' => 1, 'mark' => '11.2s', 'is_tie' => false],
                ['entry_id' => $entries[1]->id, 'rank' => 2, 'mark' => null, 'is_tie' => false],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(EventResult::query()->count())->toBe(1)
        ->and(ScoringSession::query()->count())->toBe(0);
});

test('results cannot be encoded unless the meet is active', function () {
    $meet = Meet::factory()->registrationClosed()->create();
    $event = Event::factory()->create();
    $meet->events()->attach($event);
    $entry = placeableEntry($meet, $event);
    $schedule = EventSchedule::factory()->create(['meet_id' => $meet->id, 'event_id' => $event->id]);
    $match = EventMatch::factory()->create([
        'meet_id' => $meet->id,
        'event_id' => $event->id,
        'event_schedule_id' => $schedule->id,
        'status' => MatchStatus::Completed,
    ]);
    $match->entries()->attach($entry);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/results', [
            'meet_id' => $meet->id,
            'event_id' => $event->id,
            'match_id' => $match->id,
            'placements' => [
                ['entry_id' => $entry->id, 'rank' => 1, 'mark' => null, 'is_tie' => false],
            ],
        ])
        ->assertSessionHasErrors('meet_id');

    $this->assertDatabaseCount('event_results', 0);
});

test('an event gets only one result per meet', function () {
    ['meet' => $meet, 'event' => $event, 'match' => $match, 'entries' => $entries] = resultFixture(1);

    EventResult::factory()->create(['meet_id' => $meet->id, 'event_id' => $event->id, 'match_id' => $match->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/results', [
            'meet_id' => $meet->id,
            'event_id' => $event->id,
            'match_id' => $match->id,
            'placements' => [
                ['entry_id' => $entries[0]->id, 'rank' => 1, 'mark' => null, 'is_tie' => false],
            ],
        ])
        ->assertSessionHasErrors('match_id');
});

test('only confirmed entries of the meet event are placeable', function () {
    ['meet' => $meet, 'event' => $event, 'match' => $match, 'entries' => $entries] = resultFixture(1);

    $submitted = placeableEntry($meet, $event);
    $submitted->forceFill(['status' => 'submitted'])->save();

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/results', [
            'meet_id' => $meet->id,
            'event_id' => $event->id,
            'match_id' => $match->id,
            'placements' => [
                ['entry_id' => $submitted->id, 'rank' => 1, 'mark' => null, 'is_tie' => false],
            ],
        ])
        ->assertSessionHasErrors('placements');

    $foreign = placeableEntry(Meet::factory()->active()->create(), Event::factory()->create());

    $this->actingAs($admin)
        ->post('/results', [
            'meet_id' => $meet->id,
            'event_id' => $event->id,
            'match_id' => $match->id,
            'placements' => [
                ['entry_id' => $foreign->id, 'rank' => 1, 'mark' => null, 'is_tie' => false],
            ],
        ])
        ->assertSessionHasErrors('placements');

    $this->assertDatabaseCount('event_results', 0);
});

test('duplicate ranks are rejected unless flagged as ties', function () {
    ['meet' => $meet, 'event' => $event, 'match' => $match, 'entries' => $entries] = resultFixture();

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/results', [
            'meet_id' => $meet->id,
            'event_id' => $event->id,
            'match_id' => $match->id,
            'placements' => [
                ['entry_id' => $entries[0]->id, 'rank' => 1, 'mark' => null, 'is_tie' => false],
                ['entry_id' => $entries[1]->id, 'rank' => 1, 'mark' => null, 'is_tie' => false],
            ],
        ])
        ->assertSessionHasErrors('placements');

    $this->actingAs($admin)
        ->post('/results', [
            'meet_id' => $meet->id,
            'event_id' => $event->id,
            'match_id' => $match->id,
            'placements' => [
                ['entry_id' => $entries[0]->id, 'rank' => 1, 'mark' => null, 'is_tie' => true],
                ['entry_id' => $entries[1]->id, 'rank' => 1, 'mark' => null, 'is_tie' => true],
            ],
        ])
        ->assertSessionHasNoErrors();

    expect(EventResult::query()->firstOrFail()->placements()->count())->toBe(2);
});

test('an entry cannot be placed twice in one result', function () {
    ['meet' => $meet, 'event' => $event, 'match' => $match, 'entries' => $entries] = resultFixture(1);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/results', [
            'meet_id' => $meet->id,
            'event_id' => $event->id,
            'match_id' => $match->id,
            'placements' => [
                ['entry_id' => $entries[0]->id, 'rank' => 1, 'mark' => null, 'is_tie' => false],
                ['entry_id' => $entries[0]->id, 'rank' => 2, 'mark' => null, 'is_tie' => false],
            ],
        ])
        ->assertSessionHasErrors();

    $this->assertDatabaseCount('event_results', 0);
});

test('encoded results can be re-encoded; validated results are locked', function () {
    $placement = ResultPlacement::factory()->create();
    $result = $placement->result;
    $replacement = placeableEntry($result->meet, $result->event);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put("/results/{$result->id}", [
            'event_id' => $result->event_id,
            'placements' => [
                ['entry_id' => $replacement->id, 'rank' => 1, 'mark' => 'new', 'is_tie' => false],
            ],
        ])
        ->assertSessionHasNoErrors();

    expect($result->placements()->count())->toBe(1)
        ->and($result->placements()->firstOrFail()->entry_id)->toBe($replacement->id);

    $result->forceFill(['status' => ResultStatus::Validated, 'validated_at' => now()])->save();

    $this->actingAs($admin)
        ->put("/results/{$result->id}", [
            'event_id' => $result->event_id,
            'placements' => [
                ['entry_id' => $replacement->id, 'rank' => 2, 'mark' => null, 'is_tie' => false],
            ],
        ])
        ->assertRedirect();

    expect($result->placements()->firstOrFail()->rank)->toBe(1);
});

test('validation records the validator and audits the decision', function () {
    $result = EventResult::factory()->create();
    $admin = User::factory()->admin()->create();
    $result->forceFill([
        'status' => ResultStatus::Submitted,
        'submitted_by' => $admin->id,
        'submitted_at' => now(),
    ])->save();

    $this->actingAs($admin)
        ->patch("/results/{$result->id}/validate")
        ->assertRedirect();

    $result->refresh();

    expect($result->status)->toBe(ResultStatus::Validated)
        ->and($result->validated_by)->toBe($admin->id)
        ->and($result->validated_at)->not->toBeNull()
        ->and(AuditLog::query()->where('action', 'result.validated')->exists())->toBeTrue();
});

test('corrections require a reason, reopen the result, and preserve the standing', function () {
    $placement = ResultPlacement::factory()->create();
    $result = $placement->result;
    $result->forceFill(['status' => ResultStatus::Validated, 'validated_at' => now()])->save();

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/results/{$result->id}/correct", ['reason' => ''])
        ->assertSessionHasErrors('reason');

    $this->actingAs($admin)
        ->patch("/results/{$result->id}/correct", ['reason' => 'Protest upheld — lane infringement.'])
        ->assertSessionHasNoErrors();

    $result->refresh();

    expect($result->status)->toBe(ResultStatus::Encoded)
        ->and($result->validated_by)->toBeNull()
        ->and($result->validated_at)->toBeNull();

    $audit = AuditLog::query()->where('action', 'result.corrected')->firstOrFail();

    expect($audit->context['reason'])->toBe('Protest upheld — lane infringement.')
        ->and($audit->context['superseded_placements'])->toHaveCount(1);
});

test('encoded corrections are refused — editing is direct', function () {
    $result = EventResult::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/results/{$result->id}/correct", ['reason' => 'anything'])
        ->assertRedirect();

    expect($result->refresh()->status)->toBe(ResultStatus::Encoded)
        ->and(AuditLog::query()->where('action', 'result.corrected')->exists())->toBeFalse();
});

test('administrators can delete encoded and official results with their medal tally awards', function () {
    $encoded = EventResult::factory()->create();
    $validated = EventResult::factory()->validated()->create();
    $entry = placeableEntry($validated->meet, $validated->event);
    $placement = ResultPlacement::factory()->create([
        'event_result_id' => $validated->id,
        'entry_id' => $entry->id,
        'rank' => 1,
    ]);
    $award = MedalAward::query()->create([
        'event_result_id' => $validated->id,
        'result_placement_id' => $placement->id,
        'delegation_id' => $entry->delegation_id,
        'school_id' => $entry->athlete->school_id,
        'rank' => 1,
        'medal_type' => 'gold',
        'physical_quantity' => 1,
        'tally_quantity' => 1,
        'result_version' => 1,
        'snapshotted_at' => now(),
    ]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->delete("/results/{$encoded->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('event_results', ['id' => $encoded->id]);

    expect(AuditLog::query()->where('action', 'result.deleted')->exists())->toBeTrue();

    $this->actingAs($admin)
        ->delete("/results/{$validated->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('event_results', ['id' => $validated->id]);
    $this->assertDatabaseMissing('medal_awards', ['id' => $award->id]);
});

test('administrator result deletion cascades to its match and schedule but retains athletes and setup', function () {
    $meet = Meet::factory()->active()->create();
    $event = Event::factory()->create();
    $meet->events()->attach($event);
    $schedule = EventSchedule::factory()->create([
        'meet_id' => $meet->id,
        'event_id' => $event->id,
    ]);
    $match = EventMatch::factory()->create([
        'meet_id' => $meet->id,
        'event_id' => $event->id,
        'event_schedule_id' => $schedule->id,
    ]);
    $entry = placeableEntry($meet, $event);
    $match->entries()->attach($entry);
    $result = EventResult::factory()->create([
        'meet_id' => $meet->id,
        'event_id' => $event->id,
        'match_id' => $match->id,
        'event_schedule_id' => $schedule->id,
        'result_scope' => 'match',
    ]);
    ResultPlacement::factory()->create([
        'event_result_id' => $result->id,
        'entry_id' => $entry->id,
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->delete("/results/{$result->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('event_results', ['id' => $result->id]);
    $this->assertDatabaseMissing('matches', ['id' => $match->id]);
    $this->assertDatabaseMissing('event_schedules', ['id' => $schedule->id]);
    $this->assertDatabaseHas('athletes', ['id' => $entry->athlete_id]);
    $this->assertDatabaseHas('events', ['id' => $event->id]);
});

test('viewers and delegation officers cannot manage results', function (User $user) {
    $result = EventResult::factory()->create();

    $this->actingAs($user)->post('/results', [])->assertForbidden();
    $this->actingAs($user)->patch("/results/{$result->id}/validate")->assertForbidden();
    $this->actingAs($user)->delete("/results/{$result->id}")->assertForbidden();
})->with([
    'viewer' => fn () => User::factory()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
]);

test('a tournament secretary can accept a scoped result while ICT cannot validate it', function (MeetSportAssignmentRole $role, UserRole $userRole) {
    ['meet' => $meet, 'event' => $event, 'match' => $match, 'entries' => $entries] = resultFixture();

    $official = User::factory()->create(['role' => $userRole]);
    $meetSport = MeetSport::factory()->create(['meet_id' => $meet->id, 'sport_id' => $event->sport_id]);
    MeetSportAssignment::factory()->create([
        'meet_sport_id' => $meetSport->id,
        'user_id' => $official->id,
        'role' => $role,
        'status' => MeetSportAssignmentStatus::Active,
    ]);

    $this->actingAs($official)
        ->post('/results', [
            'meet_id' => $meet->id,
            'event_id' => $event->id,
            'match_id' => $match->id,
            'placements' => [
                ['entry_id' => $entries[0]->id, 'rank' => 1, 'mark' => null, 'is_tie' => false],
                ['entry_id' => $entries[1]->id, 'rank' => 2, 'mark' => null, 'is_tie' => false],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $result = EventResult::query()->firstOrFail();

    expect($result->status)->toBe(ResultStatus::Encoded)
        ->and($result->encoded_by)->toBe($official->id);

    $this->actingAs($official)
        ->put("/results/{$result->id}", [
            'meet_id' => $meet->id,
            'event_id' => $event->id,
            'placements' => [
                ['entry_id' => $entries[0]->id, 'rank' => 1, 'mark' => 'updated', 'is_tie' => false],
                ['entry_id' => $entries[1]->id, 'rank' => 2, 'mark' => null, 'is_tie' => false],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $result->forceFill(['status' => ResultStatus::Submitted])->save();
    $validation = $this->actingAs($official)->patch("/results/{$result->id}/validate");
    if ($role === MeetSportAssignmentRole::TournamentSecretary) {
        $validation->assertRedirect()->assertSessionHasNoErrors();
        expect($result->fresh()->status)->toBe(ResultStatus::Validated);
    } else {
        $validation->assertForbidden();
    }
    $this->actingAs($official)->patch("/results/{$result->id}/correct", ['reason' => 'needs a second look'])->assertForbidden();
    $this->actingAs($official)->delete("/results/{$result->id}")->assertForbidden();
})->with([
    'Tournament Secretary' => [MeetSportAssignmentRole::TournamentSecretary, UserRole::TournamentSecretary],
    'Tournament ICT' => [MeetSportAssignmentRole::TournamentICT, UserRole::TournamentICT],
]);

test('a technical official cannot encode a result for a sport they are not assigned to', function () {
    ['meet' => $meet, 'event' => $event, 'match' => $match, 'entries' => $entries] = resultFixture();

    // Deliberately not attached to $event's sport.
    $official = User::factory()->technicalOfficial()->create();

    $this->actingAs($official)
        ->post('/results', [
            'meet_id' => $meet->id,
            'event_id' => $event->id,
            'match_id' => $match->id,
            'placements' => [
                ['entry_id' => $entries[0]->id, 'rank' => 1, 'mark' => null, 'is_tie' => false],
                ['entry_id' => $entries[1]->id, 'rank' => 2, 'mark' => null, 'is_tie' => false],
            ],
        ])
        ->assertForbidden();

    expect(EventResult::query()->count())->toBe(0);
});

test('a tournament manager only sees their own sport results and cannot encode', function () {
    $ownSport = Sport::factory()->create();
    $otherSport = Sport::factory()->create();
    $ownEvent = Event::factory()->create(['sport_id' => $ownSport->id]);
    $otherEvent = Event::factory()->create(['sport_id' => $otherSport->id]);
    $meet = Meet::factory()->active()->create();

    $ownEncoded = EventResult::factory()->create(['meet_id' => $meet->id, 'event_id' => $ownEvent->id]);
    EventResult::factory()->create(['meet_id' => $meet->id, 'event_id' => $otherEvent->id]);
    EventResult::factory()->validated()->create(['meet_id' => $meet->id, 'event_id' => $otherEvent->id]);

    $manager = User::factory()->create(['role' => UserRole::TournamentManager]);
    $ownSport->forceFill(['tournament_manager_id' => $manager->id])->save();

    $this->actingAs($manager)
        ->get('/results')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('results.data', 1)
            ->where('results.data.0.id', $ownEncoded->id)
            ->where('results.data.0.can_manage', false)
            ->where('canManage', false)
            ->where('canEncode', false));

    ['meet' => $meet, 'event' => $encodeableEvent, 'match' => $match, 'entries' => $entries] = resultFixture(1);
    $encodeableEvent->forceFill(['sport_id' => $ownSport->id])->save();

    $this->actingAs($manager)
        ->post('/results', [
            'meet_id' => $meet->id,
            'event_id' => $encodeableEvent->id,
            'match_id' => $match->id,
            'placements' => [
                ['entry_id' => $entries[0]->id, 'rank' => 1, 'mark' => null, 'is_tie' => false],
            ],
        ])
        ->assertForbidden();
});

test('a tournament manager can confirm but cannot validate or delete a draft in their managed sport', function () {
    $sport = Sport::factory()->create();
    $event = Event::factory()->create(['sport_id' => $sport->id]);

    $manager = User::factory()->tournamentManager()->create();
    $sport->forceFill(['tournament_manager_id' => $manager->id])->save();

    $encoded = EventResult::factory()->create(['event_id' => $event->id]);

    $this->actingAs($manager)
        ->patch("/results/{$encoded->id}/validate")
        ->assertForbidden();

    expect($encoded->refresh()->status)->toBe(ResultStatus::Encoded);

    $this->actingAs($manager)
        ->delete("/results/{$encoded->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('event_results', ['id' => $encoded->id]);
});

test('a tournament manager cannot validate, correct, or delete a result outside their managed sport', function () {
    $ownSport = Sport::factory()->create();
    $manager = User::factory()->tournamentManager()->create();
    $ownSport->forceFill(['tournament_manager_id' => $manager->id])->save();

    $otherEncoded = EventResult::factory()->create();
    $otherValidated = EventResult::factory()->validated()->create();

    $this->actingAs($manager)
        ->patch("/results/{$otherEncoded->id}/validate")
        ->assertForbidden();

    $this->actingAs($manager)
        ->patch("/results/{$otherValidated->id}/correct", ['reason' => 'anything'])
        ->assertForbidden();

    $this->actingAs($manager)
        ->delete("/results/{$otherEncoded->id}")
        ->assertForbidden();

    expect($otherEncoded->refresh()->status)->toBe(ResultStatus::Encoded)
        ->and($otherValidated->refresh()->status)->toBe(ResultStatus::Official);
});

test('entries with recorded placements cannot be deleted', function () {
    $placement = ResultPlacement::factory()->create();
    $entry = $placement->entry;
    $entry->forceFill(['status' => 'withdrawn'])->save();

    $this->actingAs(User::factory()->admin()->create())
        ->delete("/entries/{$entry->id}")
        ->assertRedirect();

    $this->assertDatabaseHas('entries', ['id' => $entry->id]);
});
