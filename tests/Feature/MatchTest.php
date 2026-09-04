<?php

use App\Enums\MatchStatus;
use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
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
use App\Models\School;
use App\Models\User;
use App\Models\TeamEntry;
use Inertia\Testing\AssertableInertia;

function confirmedEntryFor(EventMatch $match): Entry
{
    $delegation = Delegation::factory()->approved()->create(['meet_id' => $match->meet_id]);
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);

    return Entry::factory()->confirmed()->create([
        'athlete_id' => $athlete->id,
        'delegation_id' => $delegation->id,
        'event_id' => $match->event_id,
    ]);
}

test('guests are redirected from the match list', function () {
    $this->get('/matches')->assertRedirect('/login');
});

test('live scoring can be enabled only for sports with an implemented production board', function (string $sportName, bool $allowed) {
    $meet = Meet::factory()->active()->create();
    $sport = \App\Models\Sport::factory()->create(['name' => $sportName]);
    $event = Event::factory()->create(['sport_id' => $sport->id]);
    $meet->events()->attach($event);

    $response = $this->actingAs(User::factory()->admin()->create())->post('/matches', [
        'event_id' => $event->id,
        'event_schedule_id' => null,
        'round_label' => 'Final',
        'sequence' => 1,
        'live_scoring_enabled' => true,
    ]);

    if ($allowed) {
        $response->assertSessionHasNoErrors();
    } else {
        $response->assertSessionHasErrors('live_scoring_enabled');
        $this->assertDatabaseMissing('matches', ['event_id' => $event->id]);
    }
})->with([
    'Basketball' => ['Basketball', true],
    'Softball' => ['Softball', true],
    'Baseball' => ['Baseball', true],
    'Boxing' => ['Boxing', true],
    'Volleyball' => ['Volleyball', true],
    'Athletics' => ['Athletics', false],
]);

test('viewers cannot see matches; officers only their own delegation\'s', function () {
    $match = EventMatch::factory()->create();
    $entry = confirmedEntryFor($match);
    $match->entries()->attach($entry);

    EventMatch::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get('/matches')
        ->assertForbidden();

    $officer = User::factory()->delegationOfficer()->create();
    $entry->delegation->officers()->attach($officer);

    $this->actingAs($officer)
        ->get('/matches')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('matches/index')
            ->has('matches.data', 1)
            ->where('matches.data.0.id', $match->id)
            ->where('canManage', false));

    $this->actingAs(User::factory()->admin()->create())
        ->get('/matches')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('matches.data', 2)
            ->where('canManage', true));
});

test('a technical official only sees matches for their assigned sport and can link participants', function () {
    $ownSportMatch = EventMatch::factory()->create();
    $otherSportMatch = EventMatch::factory()->create();

    $official = User::factory()->technicalOfficial()->create();
    $official->sports()->attach($ownSportMatch->event->sport_id);

    $this->actingAs($official)
        ->get('/matches')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('matches.data', 1)
            ->where('matches.data.0.id', $ownSportMatch->id)
            ->where('matches.data.0.can_manage_participants', true)
            ->where('canManage', false));

    $entry = confirmedEntryFor($ownSportMatch);
    $this->actingAs($official)
        ->put("/matches/{$ownSportMatch->id}/participants", ['entry_ids' => [$entry->id]])
        ->assertSessionHasNoErrors();

    expect($ownSportMatch->entries()->whereKey($entry->id)->exists())->toBeTrue();

    expect($otherSportMatch->id)->not->toBe($ownSportMatch->id);
});

test('a tournament manager only sees matches for their managed sport, and can manage them', function () {
    $ownSportMatch = EventMatch::factory()->create();
    $otherSportMatch = EventMatch::factory()->create();

    $manager = User::factory()->tournamentManager()->create();
    $ownSportMatch->event->sport->forceFill(['tournament_manager_id' => $manager->id])->save();

    $this->actingAs($manager)
        ->get('/matches')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('matches.data', 1)
            ->where('matches.data.0.id', $ownSportMatch->id)
            // The list is already scoped to their own sport (unlike
            // Schedule/Results, which stay unscoped and express a
            // Tournament Manager's narrower access per-row instead), so
            // `canManage` is safely page-level true here — every visible
            // row is one they're allowed to manage.
            ->where('canManage', true));

    expect($otherSportMatch->id)->not->toBe($ownSportMatch->id);
});

test('assigned tournament staff can create and update matches but only ICT can delete', function (MeetSportAssignmentRole $role, UserRole $userRole) {
    $meet = Meet::factory()->active()->create();
    $event = Event::factory()->create();
    $meet->events()->attach($event);
    $meetSport = MeetSport::factory()->create(['meet_id' => $meet->id, 'sport_id' => $event->sport_id]);
    $user = User::factory()->create(['role' => $userRole]);
    MeetSportAssignment::factory()->create([
        'meet_sport_id' => $meetSport->id,
        'user_id' => $user->id,
        'role' => $role,
        'status' => MeetSportAssignmentStatus::Active,
    ]);

    $this->actingAs($user)->post('/matches', [
        'meet_id' => $meet->id,
        'event_id' => $event->id,
        'event_schedule_id' => null,
        'round_label' => 'Final',
        'sequence' => 1,
    ])->assertSessionHasNoErrors();

    $match = EventMatch::query()->where('event_id', $event->id)->firstOrFail();
    $this->actingAs($user)->put("/matches/{$match->id}", [
        'meet_id' => $meet->id,
        'event_id' => $event->id,
        'event_schedule_id' => null,
        'round_label' => 'Semifinal',
        'sequence' => 2,
    ])->assertSessionHasNoErrors();

    expect($match->fresh()->round_label)->toBe('Semifinal');

    $response = $this->actingAs($user)->delete("/matches/{$match->id}");
    if ($role === MeetSportAssignmentRole::TournamentICT) {
        $response->assertRedirect();
        $this->assertDatabaseMissing('matches', ['id' => $match->id]);
    } else {
        $response->assertForbidden();
        $this->assertDatabaseHas('matches', ['id' => $match->id]);
    }
})->with([
    'Tournament Manager' => [MeetSportAssignmentRole::TournamentManager, UserRole::TournamentManager],
    'Tournament Secretary' => [MeetSportAssignmentRole::TournamentSecretary, UserRole::TournamentSecretary],
    'Tournament ICT' => [MeetSportAssignmentRole::TournamentICT, UserRole::TournamentICT],
]);

test('a tournament manager can manage a match but cannot delete it', function () {
    $meet = Meet::factory()->active()->create();
    $event = Event::factory()->create();
    $meet->events()->attach($event);

    $manager = User::factory()->tournamentManager()->create();
    $event->sport->forceFill(['tournament_manager_id' => $manager->id])->save();

    $this->actingAs($manager)
        ->post('/matches', [
            'meet_id' => $meet->id,
            'event_id' => $event->id,
            'event_schedule_id' => null,
            'round_label' => 'Final',
            'sequence' => 1,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $match = EventMatch::query()->where('event_id', $event->id)->firstOrFail();

    $this->actingAs($manager)
        ->put("/matches/{$match->id}", [
            'meet_id' => $meet->id,
            'event_id' => $event->id,
            'event_schedule_id' => null,
            'round_label' => 'Semifinal',
            'sequence' => 2,
        ])
        ->assertSessionHasNoErrors();

    expect($match->refresh()->round_label)->toBe('Semifinal');

    $entry = confirmedEntryFor($match);

    $this->actingAs($manager)
        ->put("/matches/{$match->id}/participants", ['entry_ids' => [$entry->id]])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($match->entries()->count())->toBe(1);

    $this->actingAs($manager)
        ->patch("/matches/{$match->id}/status", ['status' => 'walkover'])
        ->assertRedirect();

    expect($match->refresh()->status->value)->toBe('walkover');

    $this->actingAs($manager)
        ->delete("/matches/{$match->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('matches', ['id' => $match->id]);
});

test('a tournament manager cannot create, update, or manage a match outside their managed sport', function () {
    $ownSportMatch = EventMatch::factory()->create();
    $otherSportMatch = EventMatch::factory()->create();

    $manager = User::factory()->tournamentManager()->create();
    $ownSportMatch->event->sport->forceFill(['tournament_manager_id' => $manager->id])->save();

    $this->actingAs($manager)
        ->post('/matches', [
            'meet_id' => $otherSportMatch->meet_id,
            'event_id' => $otherSportMatch->event_id,
            'event_schedule_id' => null,
            'round_label' => 'Final',
            'sequence' => 1,
        ])
        ->assertForbidden();

    $this->actingAs($manager)
        ->put("/matches/{$otherSportMatch->id}", [
            'meet_id' => $otherSportMatch->meet_id,
            'event_id' => $otherSportMatch->event_id,
            'event_schedule_id' => null,
            'round_label' => 'Semifinal',
            'sequence' => 2,
        ])
        ->assertForbidden();

    $this->actingAs($manager)
        ->put("/matches/{$otherSportMatch->id}/participants", ['entry_ids' => []])
        ->assertForbidden();

    $this->actingAs($manager)
        ->patch("/matches/{$otherSportMatch->id}/status", ['status' => 'walkover'])
        ->assertForbidden();

    $this->actingAs($manager)
        ->delete("/matches/{$otherSportMatch->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('matches', ['id' => $otherSportMatch->id]);
});

test('managers can create a match for an event in the meet', function () {
    $meet = Meet::factory()->active()->create();
    $event = Event::factory()->create();
    $meet->events()->attach($event);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/matches', [
            'meet_id' => $meet->id,
            'event_id' => $event->id,
            'event_schedule_id' => null,
            'round_label' => 'Final',
            'sequence' => 1,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('matches', [
        'meet_id' => $meet->id,
        'event_id' => $event->id,
        'round_label' => 'Final',
        'status' => 'scheduled',
    ]);

    expect(AuditLog::query()->where('action', 'match.created')->exists())->toBeTrue();
});

test('matches cannot be created for events outside the meet', function () {
    $meet = Meet::factory()->active()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/matches', [
            'meet_id' => $meet->id,
            'event_id' => Event::factory()->create()->id,
            'round_label' => 'Final',
            'sequence' => 1,
        ])
        ->assertSessionHasErrors('event_id');

    $this->assertDatabaseCount('matches', 0);
});

test('a linked schedule slot must belong to the same meet and event', function () {
    $meet = Meet::factory()->active()->create();
    $event = Event::factory()->create();
    $meet->events()->attach($event);

    $foreignSlot = EventSchedule::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/matches', [
            'meet_id' => $meet->id,
            'event_id' => $event->id,
            'event_schedule_id' => $foreignSlot->id,
            'round_label' => 'Final',
            'sequence' => 1,
        ])
        ->assertSessionHasErrors('event_schedule_id');
});

test('viewers and delegation officers cannot manage matches', function (User $user) {
    $match = EventMatch::factory()->create();

    $this->actingAs($user)->post('/matches', [])->assertForbidden();
    $this->actingAs($user)->delete("/matches/{$match->id}")->assertForbidden();
})->with([
    'viewer' => fn () => User::factory()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
]);

test('participants must be confirmed entries of the match event', function () {
    $match = EventMatch::factory()->create();
    $confirmed = confirmedEntryFor($match);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put("/matches/{$match->id}/participants", ['entry_ids' => [$confirmed->id]])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($match->entries()->count())->toBe(1)
        ->and(AuditLog::query()->where('action', 'match.participants_updated')->exists())->toBeTrue();

    $submitted = confirmedEntryFor($match);
    $submitted->forceFill(['status' => 'submitted'])->save();

    $this->actingAs($admin)
        ->put("/matches/{$match->id}/participants", ['entry_ids' => [$submitted->id]])
        ->assertSessionHasErrors('entry_ids');

    $otherEvent = confirmedEntryFor(EventMatch::factory()->create());

    $this->actingAs($admin)
        ->put("/matches/{$match->id}/participants", ['entry_ids' => [$otherEvent->id]])
        ->assertSessionHasErrors('entry_ids');

    expect($match->entries()->count())->toBe(1);
});

test('match participant slots are generated per sport delegation and assigning an athlete creates the entry', function () {
    $match = EventMatch::factory()->create();
    $meetSport = MeetSport::factory()->create([
        'meet_id' => $match->meet_id,
        'sport_id' => $match->event->sport_id,
        'active' => true,
    ]);
    $delegation = Delegation::factory()->approved()->create(['meet_id' => $match->meet_id]);
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);
    \App\Models\SportRosterMember::query()->create([
        'meet_sport_id' => $meetSport->id,
        'delegation_id' => $delegation->id,
        'athlete_id' => $athlete->id,
        'level' => $match->event->age_division->value,
        'gender' => $match->event->gender->value,
    ]);

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->get('/matches')->assertOk();
    $slot = \App\Models\MatchParticipantSlot::query()
        ->where('match_id', $match->id)->where('delegation_id', $delegation->id)->firstOrFail();

    $this->actingAs($admin)->put("/matches/{$match->id}/participants", [
        'slot_assignments' => [['slot_id' => $slot->id, 'athlete_id' => $athlete->id]],
    ])->assertSessionHasNoErrors();

    $entry = Entry::query()->where('event_id', $match->event_id)->where('athlete_id', $athlete->id)->firstOrFail();
    expect($entry->status->value)->toBe('confirmed')
        ->and($slot->fresh()->entry_id)->toBe($entry->id)
        ->and($match->entries()->whereKey($entry->id)->exists())->toBeTrue();
});

test('creating and viewing a match does not automatically populate its entries', function () {
    $match = EventMatch::factory()->create();
    $meetSport = MeetSport::factory()->create([
        'meet_id' => $match->meet_id,
        'sport_id' => $match->event->sport_id,
        'active' => true,
    ]);
    $delegation = Delegation::factory()->approved()->create(['meet_id' => $match->meet_id]);
    $delegationWithoutAthletes = Delegation::factory()->approved()->create(['meet_id' => $match->meet_id]);
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);
    \App\Models\SportRosterMember::query()->create([
        'meet_sport_id' => $meetSport->id,
        'delegation_id' => $delegation->id,
        'athlete_id' => $athlete->id,
        'level' => $match->event->age_division->value,
        'gender' => $match->event->gender->value,
    ]);
    $entry = Entry::factory()->create([
        'event_id' => $match->event_id,
        'delegation_id' => $delegation->id,
        'athlete_id' => $athlete->id,
        'status' => 'submitted',
    ]);

    $this->actingAs(User::factory()->admin()->create())->get('/matches')->assertOk();

    expect(\App\Models\MatchParticipantSlot::query()
        ->where('match_id', $match->id)->whereNotNull('entry_id')->exists())->toBeFalse()
        ->and(\App\Models\MatchParticipantSlot::query()
            ->where('match_id', $match->id)
            ->where('delegation_id', $delegationWithoutAthletes->id)
            ->exists())->toBeTrue()
        ->and($match->entries()->exists())->toBeFalse()
        ->and($entry->fresh()->status->value)->toBe('submitted');
});

test('team events allow only one team entry per delegation in a match', function () {
    $event = Event::factory()->create(['is_team_event' => true]);
    $match = EventMatch::factory()->create(['event_id' => $event->id]);

    $delegation = Delegation::factory()->approved()->create(['meet_id' => $match->meet_id]);
    $entries = Athlete::factory()->count(2)
        ->create(['delegation_id' => $delegation->id])
        ->map(fn (Athlete $athlete) => Entry::factory()->confirmed()->create([
            'athlete_id' => $athlete->id,
            'delegation_id' => $delegation->id,
            'event_id' => $event->id,
        ]));

    $this->actingAs(User::factory()->admin()->create())
        ->put("/matches/{$match->id}/participants", [
            'entry_ids' => $entries->pluck('id')->all(),
        ])
        ->assertSessionHasErrors('entry_ids');

    expect($match->entries()->count())->toBe(0);
});

test('team events treat different schools under one municipal delegation as one team entry', function () {
    $event = Event::factory()->create(['is_team_event' => true]);
    $match = EventMatch::factory()->create(['event_id' => $event->id]);

    $district = District::factory()->create();
    $delegation = Delegation::factory()->approved()->create([
        'meet_id' => $match->meet_id,
        'school_id' => null,
        'district_id' => $district->id,
    ]);
    $schoolA = School::factory()->create(['district_id' => $district->id]);
    $schoolB = School::factory()->create(['district_id' => $district->id]);

    $entryA = Entry::factory()->confirmed()->create([
        'athlete_id' => Athlete::factory()->create(['delegation_id' => $delegation->id, 'school_id' => $schoolA->id])->id,
        'delegation_id' => $delegation->id,
        'event_id' => $event->id,
    ]);
    $entryB = Entry::factory()->confirmed()->create([
        'athlete_id' => Athlete::factory()->create(['delegation_id' => $delegation->id, 'school_id' => $schoolB->id])->id,
        'delegation_id' => $delegation->id,
        'event_id' => $event->id,
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->put("/matches/{$match->id}/participants", [
            'entry_ids' => [$entryA->id, $entryB->id],
        ])
        ->assertSessionHasErrors('entry_ids');

    expect($match->entries()->count())->toBe(0);
});

test('team events still block two entries from the same school even under a municipal delegation', function () {
    $event = Event::factory()->create(['is_team_event' => true]);
    $match = EventMatch::factory()->create(['event_id' => $event->id]);

    $district = District::factory()->create();
    $delegation = Delegation::factory()->approved()->create([
        'meet_id' => $match->meet_id,
        'school_id' => null,
        'district_id' => $district->id,
    ]);
    $school = School::factory()->create(['district_id' => $district->id]);

    $entries = Athlete::factory()->count(2)
        ->create(['delegation_id' => $delegation->id, 'school_id' => $school->id])
        ->map(fn (Athlete $athlete) => Entry::factory()->confirmed()->create([
            'athlete_id' => $athlete->id,
            'delegation_id' => $delegation->id,
            'event_id' => $event->id,
        ]));

    $this->actingAs(User::factory()->admin()->create())
        ->put("/matches/{$match->id}/participants", [
            'entry_ids' => $entries->pluck('id')->all(),
        ])
        ->assertSessionHasErrors('entry_ids');

    expect($match->entries()->count())->toBe(0);
});

test('individual events accept several entries from one school', function () {
    $match = EventMatch::factory()->create();

    $delegation = Delegation::factory()->approved()->create(['meet_id' => $match->meet_id]);
    $entries = Athlete::factory()->count(2)
        ->create(['delegation_id' => $delegation->id])
        ->map(fn (Athlete $athlete) => Entry::factory()->confirmed()->create([
            'athlete_id' => $athlete->id,
            'delegation_id' => $delegation->id,
            'event_id' => $match->event_id,
        ]));

    $this->actingAs(User::factory()->admin()->create())
        ->put("/matches/{$match->id}/participants", [
            'entry_ids' => $entries->pluck('id')->all(),
        ])
        ->assertSessionHasNoErrors();

    expect($match->entries()->count())->toBe(2);
});

test('match participant choices identify individual athletes but collapse team entries to one delegation', function () {
    $meet = Meet::factory()->active()->create(['is_active' => true]);
    $individual = Event::factory()->create(['is_team_event' => false]);
    $team = Event::factory()->create(['is_team_event' => true]);
    $meet->events()->attach([$individual->id, $team->id]);
    $delegation = Delegation::factory()->approved()->create(['meet_id' => $meet->id]);
    $athletes = Athlete::factory()->count(2)->create(['delegation_id' => $delegation->id]);
    $individualEntry = Entry::factory()->confirmed()->create([
        'athlete_id' => $athletes->first()->id,
        'delegation_id' => $delegation->id,
        'event_id' => $individual->id,
    ]);
    foreach ($athletes as $athlete) {
        Entry::factory()->confirmed()->create([
            'athlete_id' => $athlete->id,
            'delegation_id' => $delegation->id,
            'event_id' => $team->id,
        ]);
    }

    $this->actingAs(User::factory()->admin()->create())->get('/matches')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('entryOptions', fn ($options) => collect($options)
                ->where('event_id', $individual->id)->sole()['label']
                    === $delegation->registrantName().' ('.$individualEntry->athlete->fullName().')'
                && collect($options)->where('event_id', $team->id)->count() === 1
                && collect($options)->where('event_id', $team->id)->sole()['label']
                    === $delegation->registrantName()));
});

test('participants are locked once the match leaves scheduled', function () {
    $match = EventMatch::factory()->completed()->create();
    $entry = confirmedEntryFor($match);

    $this->actingAs(User::factory()->admin()->create())
        ->put("/matches/{$match->id}/participants", ['entry_ids' => [$entry->id]])
        ->assertRedirect();

    expect($match->entries()->count())->toBe(0);
});

test('scheduled matches can be completed, walked over, or cancelled', function (string $target) {
    $match = EventMatch::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/matches/{$match->id}/status", ['status' => $target])
        ->assertRedirect();

    expect($match->refresh()->status->value)->toBe($target)
        ->and(AuditLog::query()->where('action', 'match.status_changed')->exists())->toBeTrue();
})->with(['completed', 'walkover', 'cancelled']);

test('terminal match statuses cannot change again', function () {
    $match = EventMatch::factory()->completed()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/matches/{$match->id}/status", ['status' => 'cancelled'])
        ->assertRedirect();

    expect($match->refresh()->status)->toBe(MatchStatus::Completed);
});

test('managers can update and delete matches with audit records', function () {
    $match = EventMatch::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put("/matches/{$match->id}", [
            'meet_id' => $match->meet_id,
            'event_id' => $match->event_id,
            'event_schedule_id' => null,
            'round_label' => 'Semifinal',
            'sequence' => 2,
        ])
        ->assertSessionHasNoErrors();

    expect($match->refresh()->round_label)->toBe('Semifinal')
        ->and(AuditLog::query()->where('action', 'match.updated')->exists())->toBeTrue();

    $this->actingAs($admin)
        ->delete("/matches/{$match->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('matches', ['id' => $match->id]);

    expect(AuditLog::query()->where('action', 'match.deleted')->exists())->toBeTrue();
});

test('tournament ICT can delete a match only after an administrator removes its result', function () {
    $match = EventMatch::factory()->create();
    $meetSport = MeetSport::factory()->create([
        'meet_id' => $match->meet_id,
        'sport_id' => $match->event->sport_id,
    ]);
    $ict = User::factory()->create(['role' => UserRole::TournamentICT]);
    MeetSportAssignment::factory()->create([
        'meet_sport_id' => $meetSport->id,
        'user_id' => $ict->id,
        'role' => MeetSportAssignmentRole::TournamentICT,
        'status' => MeetSportAssignmentStatus::Active,
    ]);
    $result = EventResult::factory()->create([
        'meet_id' => $match->meet_id,
        'event_id' => $match->event_id,
        'match_id' => $match->id,
        'result_scope' => 'match',
    ]);

    $this->actingAs($ict)->delete("/matches/{$match->id}")
        ->assertSessionHasErrors('match');
    $this->assertDatabaseHas('matches', ['id' => $match->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->delete("/results/{$result->id}")
        ->assertSessionDoesntHaveErrors();
    $this->actingAs($ict)->delete("/matches/{$match->id}")
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseMissing('matches', ['id' => $match->id]);
});

test('entries that took part in a match cannot be deleted', function () {
    $match = EventMatch::factory()->create();
    $entry = confirmedEntryFor($match);
    $match->entries()->attach($entry);

    $entry->forceFill(['status' => 'withdrawn'])->save();

    $this->actingAs(User::factory()->admin()->create())
        ->delete("/entries/{$entry->id}")
        ->assertRedirect();

    $this->assertDatabaseHas('entries', ['id' => $entry->id]);
});

test('all tournament ICT accounts can see and manage every match in the current meet', function () {
    $meet = Meet::factory()->active()->create();
    $events = Event::factory()->count(2)->create();
    $meet->events()->attach($events->modelKeys());
    EventMatch::factory()->create(['meet_id' => $meet->id, 'event_id' => $events[0]->id]);
    EventMatch::factory()->create(['meet_id' => $meet->id, 'event_id' => $events[1]->id]);
    $ict = User::factory()->create(['role' => UserRole::TournamentICT]);

    $this->actingAs($ict)->get('/matches')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('matches.data', 2)
            ->where('canManage', true));

    $this->actingAs($ict)->post('/matches', [
        'event_id' => $events[1]->id,
        'round_label' => 'Final',
        'sequence' => 2,
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('matches', [
        'event_id' => $events[1]->id,
        'round_label' => 'Final',
        'sequence' => 2,
    ]);
});

test('duplicate match round and sequence returns a clear validation error', function () {
    $match = EventMatch::factory()->create(['round_label' => 'Final', 'sequence' => 1]);

    $this->actingAs(User::factory()->admin()->create())->post('/matches', [
        'event_id' => $match->event_id,
        'round_label' => 'Final',
        'sequence' => 1,
    ])->assertSessionHasErrors('sequence');
});

test('team matches show every delegation and create one team entry when selected', function () {
    $event = Event::factory()->create(['is_team_event' => true]);
    $match = EventMatch::factory()->create(['event_id' => $event->id]);
    $meetSport = MeetSport::factory()->create([
        'meet_id' => $match->meet_id,
        'sport_id' => $event->sport_id,
        'active' => true,
    ]);
    $delegation = Delegation::factory()->approved()->create(['meet_id' => $match->meet_id]);
    $delegationWithoutEntries = Delegation::factory()->approved()->create(['meet_id' => $match->meet_id]);
    $athletes = Athlete::factory()->count(3)->create(['delegation_id' => $delegation->id]);
    foreach ($athletes as $athlete) {
        \App\Models\SportRosterMember::query()->create([
            'meet_sport_id' => $meetSport->id,
            'delegation_id' => $delegation->id,
            'athlete_id' => $athlete->id,
            'level' => $event->age_division->value,
            'gender' => $event->gender->value,
        ]);
    }

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->get('/matches')->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('teamDelegationOptions', fn ($options) => collect($options)
                ->pluck('id')->contains($delegation->id)
                && collect($options)->pluck('id')->contains($delegationWithoutEntries->id)),
    );

    expect(TeamEntry::query()->count())->toBe(0);
    $this->actingAs($admin)->put("/matches/{$match->id}/participants", [
        'participant_mode' => 'team',
        'delegation_ids' => [$delegationWithoutEntries->id],
    ])->assertSessionHasNoErrors();

    $team = TeamEntry::query()->sole();
    expect($match->teamEntries()->count())->toBe(1)
        ->and($team->delegation_id)->toBe($delegationWithoutEntries->id)
        ->and($team->members()->count())->toBe(0)
        ->and($match->entries()->count())->toBe(0)
        ->and($match->participantSlots()->count())->toBe(2);
});
