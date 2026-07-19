<?php

use App\Enums\ResultStatus;
use App\Models\Athlete;
use App\Models\AuditLog;
use App\Models\Delegation;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventResult;
use App\Models\Meet;
use App\Models\ResultPlacement;
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
 * @return array{meet: Meet, event: Event, entries: array<int, Entry>}
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

    return ['meet' => $meet, 'event' => $event, 'entries' => $entries];
}

test('guests are redirected from results', function () {
    $this->get('/results')->assertRedirect('/login');
});

test('unvalidated results are visible to managers only', function () {
    EventResult::factory()->create();
    ResultPlacement::factory()->create();

    $validated = EventResult::factory()->validated()->create();

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

test('managers can encode a result for an active meet', function () {
    ['meet' => $meet, 'event' => $event, 'entries' => $entries] = resultFixture();

    $this->actingAs(User::factory()->organizer()->create())
        ->post('/results', [
            'meet_id' => $meet->id,
            'event_id' => $event->id,
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
        ->and(AuditLog::query()->where('action', 'result.encoded')->exists())->toBeTrue();
});

test('results cannot be encoded unless the meet is active', function () {
    $meet = Meet::factory()->registrationClosed()->create();
    $event = Event::factory()->create();
    $meet->events()->attach($event);
    $entry = placeableEntry($meet, $event);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/results', [
            'meet_id' => $meet->id,
            'event_id' => $event->id,
            'placements' => [
                ['entry_id' => $entry->id, 'rank' => 1, 'mark' => null, 'is_tie' => false],
            ],
        ])
        ->assertSessionHasErrors('meet_id');

    $this->assertDatabaseCount('event_results', 0);
});

test('an event gets only one result per meet', function () {
    ['meet' => $meet, 'event' => $event, 'entries' => $entries] = resultFixture(1);

    EventResult::factory()->create(['meet_id' => $meet->id, 'event_id' => $event->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/results', [
            'meet_id' => $meet->id,
            'event_id' => $event->id,
            'placements' => [
                ['entry_id' => $entries[0]->id, 'rank' => 1, 'mark' => null, 'is_tie' => false],
            ],
        ])
        ->assertSessionHasErrors('event_id');
});

test('only confirmed entries of the meet event are placeable', function () {
    ['meet' => $meet, 'event' => $event, 'entries' => $entries] = resultFixture(1);

    $submitted = placeableEntry($meet, $event);
    $submitted->forceFill(['status' => 'submitted'])->save();

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/results', [
            'meet_id' => $meet->id,
            'event_id' => $event->id,
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
            'placements' => [
                ['entry_id' => $foreign->id, 'rank' => 1, 'mark' => null, 'is_tie' => false],
            ],
        ])
        ->assertSessionHasErrors('placements');

    $this->assertDatabaseCount('event_results', 0);
});

test('duplicate ranks are rejected unless flagged as ties', function () {
    ['meet' => $meet, 'event' => $event, 'entries' => $entries] = resultFixture();

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/results', [
            'meet_id' => $meet->id,
            'event_id' => $event->id,
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
            'placements' => [
                ['entry_id' => $entries[0]->id, 'rank' => 1, 'mark' => null, 'is_tie' => true],
                ['entry_id' => $entries[1]->id, 'rank' => 1, 'mark' => null, 'is_tie' => true],
            ],
        ])
        ->assertSessionHasNoErrors();

    expect(EventResult::query()->firstOrFail()->placements()->count())->toBe(2);
});

test('an entry cannot be placed twice in one result', function () {
    ['meet' => $meet, 'event' => $event, 'entries' => $entries] = resultFixture(1);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/results', [
            'meet_id' => $meet->id,
            'event_id' => $event->id,
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

test('encoded results can be deleted; validated results cannot', function () {
    $encoded = EventResult::factory()->create();
    $validated = EventResult::factory()->validated()->create();

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->delete("/results/{$encoded->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('event_results', ['id' => $encoded->id]);

    expect(AuditLog::query()->where('action', 'result.deleted')->exists())->toBeTrue();

    $this->actingAs($admin)
        ->delete("/results/{$validated->id}")
        ->assertRedirect();

    $this->assertDatabaseHas('event_results', ['id' => $validated->id]);
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

test('entries with recorded placements cannot be deleted', function () {
    $placement = ResultPlacement::factory()->create();
    $entry = $placement->entry;
    $entry->forceFill(['status' => 'withdrawn'])->save();

    $this->actingAs(User::factory()->admin()->create())
        ->delete("/entries/{$entry->id}")
        ->assertRedirect();

    $this->assertDatabaseHas('entries', ['id' => $entry->id]);
});
