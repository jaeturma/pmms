<?php

use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\Entry;
use App\Models\EventResult;
use App\Models\Meet;
use App\Models\ResultPlacement;
use App\Models\School;
use Inertia\Testing\AssertableInertia;

/**
 * Place a school at the given rank in a result via a confirmed entry —
 * same helper shape as PublicTallyTest's own, since Rankings reuses the
 * exact same MedalTallyService::standings() data.
 */
function publicRankingsPlacement(EventResult $result, School $school, int $rank): ResultPlacement
{
    $delegation = Delegation::query()
        ->where('meet_id', $result->meet_id)
        ->where('school_id', $school->id)
        ->first()
        ?? Delegation::factory()->approved()->create([
            'meet_id' => $result->meet_id,
            'school_id' => $school->id,
        ]);

    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);

    $entry = Entry::factory()->confirmed()->create([
        'athlete_id' => $athlete->id,
        'delegation_id' => $delegation->id,
        'event_id' => $result->event_id,
    ]);

    return ResultPlacement::factory()->create([
        'event_result_id' => $result->id,
        'entry_id' => $entry->id,
        'rank' => $rank,
    ]);
}

test('guests can view the public rankings page; unpublished meets 404', function () {
    $meet = Meet::factory()->active()->published()->create();

    $this->get("/meets/{$meet->id}/rankings")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('portal/standings')
            ->where('meet.name', $meet->name)
            ->has('districts', 0)
            ->has('generatedAt'));

    $hidden = Meet::factory()->active()->create();

    $this->get("/meets/{$hidden->id}/rankings")->assertNotFound();
});

test('rankings reflect validated results only, ranked by gold-silver-bronze', function () {
    $meet = Meet::factory()->active()->published()->create();

    $validated = EventResult::factory()->validated()->create(['meet_id' => $meet->id]);
    $goldSchool = School::factory()->create(['name' => 'Gold School']);
    publicRankingsPlacement($validated, $goldSchool, 1);

    $encoded = EventResult::factory()->create(['meet_id' => $meet->id]);
    publicRankingsPlacement($encoded, School::factory()->create(), 1);

    $this->get("/meets/{$meet->id}/rankings")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('districts', 1)
            ->where('districts.0.district', $goldSchool->district->name)
            ->where('districts.0.gold', 1)
            ->where('districts.0.points', 3));
});

test('rankings exclude other meets', function () {
    $meet = Meet::factory()->active()->published()->create();

    $foreign = EventResult::factory()->validated()->create();
    publicRankingsPlacement($foreign, School::factory()->create(), 1);

    $this->get("/meets/{$meet->id}/rankings")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('districts', 0));
});

test('rankings rows carry no internal or unrelated fields', function () {
    $meet = Meet::factory()->active()->published()->create();

    $result = EventResult::factory()->validated()->create(['meet_id' => $meet->id]);
    publicRankingsPlacement($result, School::factory()->create(), 1);

    $this->get("/meets/{$meet->id}/rankings")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('districts.0', fn (AssertableInertia $row) => $row
                ->hasAll(['position', 'district', 'gold', 'silver', 'bronze', 'total', 'points'])
                ->missing('created_at')
                ->missing('updated_at')));
});
