<?php

use App\Enums\ResultStatus;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventResult;
use App\Models\MedalAward;
use App\Models\Meet;
use App\Models\ResultPlacement;
use App\Models\School;
use App\Models\Sport;
use App\Services\MeetProgressService;

function progressEvent(Meet $meet, array $tally = [1, 1, 1], array $physical = [1, 1, 1], bool $active = true): Event
{
    $event = Event::factory()->create([
        'sport_id' => Sport::factory()->create()->id,
        'is_medal_event' => true,
        'active' => $active,
    ]);
    $meet->events()->attach($event);
    $event->medalConfig()->create([
        'awards_medals' => true,
        'award_type' => 'INDIVIDUAL',
        'physical_quantity_mode' => 'FIXED',
        'gold_physical_quantity' => $physical[0],
        'silver_physical_quantity' => $physical[1],
        'bronze_physical_quantity' => $physical[2],
        'gold_tally_quantity' => $tally[0],
        'silver_tally_quantity' => $tally[1],
        'bronze_tally_quantity' => $tally[2],
    ]);

    return $event;
}

function progressAward(Meet $meet, Event $event, string $medal, int $quantity, ResultStatus $status = ResultStatus::Official): MedalAward
{
    $result = EventResult::factory()->create([
        'meet_id' => $meet->id,
        'event_id' => $event->id,
        'status' => $status,
        'official_at' => $status === ResultStatus::Official ? now() : null,
    ]);
    $school = School::factory()->create();
    $delegation = Delegation::factory()->approved()->create(['meet_id' => $meet->id, 'school_id' => $school->id]);
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id, 'school_id' => $school->id]);
    $entry = Entry::factory()->confirmed()->create([
        'athlete_id' => $athlete->id,
        'delegation_id' => $delegation->id,
        'event_id' => $event->id,
    ]);
    $rank = ['gold' => 1, 'silver' => 2, 'bronze' => 3][$medal];
    $placement = ResultPlacement::factory()->create([
        'event_result_id' => $result->id,
        'entry_id' => $entry->id,
        'rank' => $rank,
    ]);

    return MedalAward::query()->create([
        'event_result_id' => $result->id,
        'result_placement_id' => $placement->id,
        'delegation_id' => $delegation->id,
        'school_id' => $school->id,
        'rank' => $rank,
        'medal_type' => $medal,
        'physical_quantity' => 99,
        'tally_quantity' => $quantity,
        'result_version' => 1,
        'snapshotted_at' => now(),
    ]);
}

test('meet progress is official awarded tally quantity divided by configured expected tally quantity', function () {
    $meet = Meet::factory()->create();
    $event = progressEvent($meet, [10, 10, 10]);
    progressAward($meet, $event, 'gold', 3);
    progressAward($meet, $event, 'silver', 2);
    progressAward($meet, $event, 'bronze', 1);

    $summary = app(MeetProgressService::class)->summary($meet);

    expect($summary['expected'])->toMatchArray(['gold' => 10, 'silver' => 10, 'bronze' => 10, 'total' => 30])
        ->and($summary['awarded'])->toMatchArray(['gold' => 3, 'silver' => 2, 'bronze' => 1, 'total' => 6])
        ->and($summary['remaining']['total'])->toBe(24)
        ->and($summary['percentage'])->toBe(20.0);
});

test('configured team tally quantities determine expected progress', function () {
    $meet = Meet::factory()->create();
    $event = progressEvent($meet, [3, 3, 3], [5, 5, 5]);
    progressAward($meet, $event, 'gold', 3);

    $summary = app(MeetProgressService::class)->summary($meet);

    expect($summary['expected']['total'])->toBe(9)
        ->and($summary['awarded']['total'])->toBe(3)
        ->and($summary['percentage'])->toBe(33.3);
});

test('physical medal quantities never control progress', function () {
    $meet = Meet::factory()->create();
    progressEvent($meet, [1, 1, 1], [5, 5, 5]);

    expect(app(MeetProgressService::class)->summary($meet)['expected']['total'])->toBe(3);
});

test('non-official medal awards do not increase progress', function (ResultStatus $status) {
    $meet = Meet::factory()->create();
    $event = progressEvent($meet);
    progressAward($meet, $event, 'gold', 1, $status);

    expect(app(MeetProgressService::class)->summary($meet)['awarded']['total'])->toBe(0);
})->with([
    'submitted' => ResultStatus::Submitted,
    'returned' => ResultStatus::Returned,
    'validated' => ResultStatus::Validated,
]);

test('inactive and explicitly non-medal events do not increase expected totals', function () {
    $meet = Meet::factory()->create();
    progressEvent($meet, active: false);
    $nonMedal = progressEvent($meet);
    $nonMedal->medalConfig()->update(['awards_medals' => false]);

    expect(app(MeetProgressService::class)->summary($meet)['expected']['total'])->toBe(0);
});

test('missing medal configuration is surfaced as an incomplete provisional denominator', function () {
    $meet = Meet::factory()->create();
    $event = Event::factory()->create(['is_medal_event' => true, 'active' => true]);
    $meet->events()->attach($event);

    $summary = app(MeetProgressService::class)->summary($meet);

    expect($summary['configuration']['complete'])->toBeFalse()
        ->and($summary['configuration']['missing_events'])->toBe(1)
        ->and($summary['status'])->toBe('NEEDS ATTENTION');
});
