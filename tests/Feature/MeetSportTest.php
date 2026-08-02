<?php

use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\Sport;
use Illuminate\Database\QueryException;

/**
 * MeetSport (WP-REALIGN-02) is model/schema only in this phase — no
 * controller or route exists yet, so these are model-level tests, same
 * as the other new-table WPs in the DdOPAA realignment. The migration's
 * own one-time backfill (deriving initial rows from meet_events/events at
 * migrate time) isn't covered here: RefreshDatabase re-runs migrations
 * against an empty database for every test run, so there is no
 * pre-existing meet_events data for that backfill to act on inside the
 * test suite — its SQL is exercised for real only against a database
 * that already has meet/event history when the migration first deploys.
 */
test('a meet has many meet sports, and a sport has many meet sports', function () {
    $meet = Meet::factory()->create();
    $sport = Sport::factory()->create();

    $meetSport = MeetSport::factory()->create([
        'meet_id' => $meet->id,
        'sport_id' => $sport->id,
    ]);

    expect($meet->meetSports()->first()->id)->toBe($meetSport->id)
        ->and($sport->meetSports()->first()->id)->toBe($meetSport->id)
        ->and($meetSport->meet->id)->toBe($meet->id)
        ->and($meetSport->sport->id)->toBe($sport->id)
        ->and($meetSport->active)->toBeTrue();
});

test('a meet cannot include the same sport twice', function () {
    $meet = Meet::factory()->create();
    $sport = Sport::factory()->create();

    MeetSport::factory()->create(['meet_id' => $meet->id, 'sport_id' => $sport->id]);

    expect(fn () => MeetSport::factory()->create(['meet_id' => $meet->id, 'sport_id' => $sport->id]))
        ->toThrow(QueryException::class);
});

test('a sport cannot be deleted while it is included in a meet', function () {
    $meetSport = MeetSport::factory()->create();

    expect(fn () => $meetSport->sport->delete())->toThrow(QueryException::class);
});

test('a meet sport is deleted when its meet is deleted', function () {
    $meetSport = MeetSport::factory()->create();
    $meet = $meetSport->meet;

    $meet->delete();

    expect(MeetSport::query()->whereKey($meetSport->id)->exists())->toBeFalse();
});

test('a meet sport can be excluded from a meet without being deleted', function () {
    $meetSport = MeetSport::factory()->create(['active' => true]);

    $meetSport->forceFill(['active' => false, 'notes' => 'Cancelled due to venue conflict'])->save();

    expect($meetSport->fresh())
        ->active->toBeFalse()
        ->notes->toBe('Cancelled due to venue conflict');
});
