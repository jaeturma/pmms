<?php

use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventResult;
use App\Models\ResultPlacement;
use App\Models\School;
use App\Models\User;
use App\Services\MedalAwardService;
use App\Services\MedalTallyService;

function configuredMedalResult(int $physical, int $tally, bool $team = true): array
{
    $event = Event::factory()->create(['is_team_event' => $team, 'is_medal_event' => true]);
    $event->medalConfig()->create([
        'awards_medals' => true,
        'award_type' => $team ? 'TEAM' : 'INDIVIDUAL',
        'physical_quantity_mode' => 'FIXED',
        'gold_physical_quantity' => $physical, 'silver_physical_quantity' => $physical, 'bronze_physical_quantity' => $physical,
        'gold_tally_quantity' => $tally, 'silver_tally_quantity' => $tally, 'bronze_tally_quantity' => $tally,
    ]);
    $result = EventResult::factory()->validated()->create(['event_id' => $event->id]);
    $school = School::factory()->create();
    $delegation = Delegation::factory()->approved()->create(['meet_id' => $result->meet_id, 'school_id' => $school->id]);
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id, 'school_id' => $school->id]);
    $entry = Entry::factory()->confirmed()->create(['delegation_id' => $delegation->id, 'athlete_id' => $athlete->id, 'event_id' => $event->id]);
    ResultPlacement::factory()->create(['event_result_id' => $result->id, 'entry_id' => $entry->id, 'rank' => 1]);
    app(MedalAwardService::class)->synchronize($result, User::factory()->admin()->create());

    return [$result, $school];
}

test('a three-medal event contributes three official golds and nine ranking points', function () {
    [$result, $school] = configuredMedalResult(3, 3);
    $award = $result->medalAwards()->sole();
    $row = collect(app(MedalTallyService::class)->standings($result->meet_id)['schools'])->firstWhere('school', $school->name);

    expect($award->physical_quantity)->toBe(3)
        ->and($award->tally_quantity)->toBe(3)
        ->and($row['gold'])->toBe(3)
        ->and($row['points'])->toBe(9);
});

test('physical pieces and official tally quantities are independent', function (int $physical, int $tally) {
    [$result, $school] = configuredMedalResult($physical, $tally);
    $award = $result->medalAwards()->sole();
    $row = collect(app(MedalTallyService::class)->standings($result->meet_id)['schools'])->firstWhere('school', $school->name);

    expect($award->physical_quantity)->toBe($physical)
        ->and($award->tally_quantity)->toBe($tally)
        ->and($row['gold'])->toBe($tally)
        ->and($row['points'])->toBe($tally * 3);
})->with([
    'team counts as one' => [5, 1],
    'relay counts as one' => [4, 1],
    'custom multi-medal' => [4, 2],
]);

test('editing event configuration does not change an official result snapshot', function () {
    [$result] = configuredMedalResult(3, 3);
    $result->event->medalConfig()->update(['gold_physical_quantity' => 9, 'gold_tally_quantity' => 1]);

    expect($result->medalAwards()->sole()->physical_quantity)->toBe(3)
        ->and($result->medalAwards()->sole()->tally_quantity)->toBe(3);
});
