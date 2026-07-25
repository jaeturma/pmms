<?php

use App\Enums\ResultStatus;
use App\Models\EventResult;
use App\Models\Meet;
use App\Models\ResultPlacement;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

function publishedMeetWithValidatedResult(): array
{
    $meet = Meet::factory()->active()->published()->create();

    $result = EventResult::factory()->validated()->create(['meet_id' => $meet->id]);
    $placement = ResultPlacement::factory()->create([
        'event_result_id' => $result->id,
        'rank' => 1,
        'mark' => '11.2s',
    ]);

    return [$meet, $result, $placement];
}

test('guests can view validated results of a published meet', function () {
    [$meet, $result, $placement] = publishedMeetWithValidatedResult();

    $this->get("/meets/{$meet->id}/results")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('public/results')
            ->has('results', 1)
            ->where('results.0.placements.0.rank', 1)
            ->where('results.0.placements.0.athlete', $placement->entry->athlete->fullName())
            ->where('results.0.placements.0.school', $placement->entry->delegation->school->name)
            ->where('results.0.placements.0.mark', '11.2s'));
});

test('unpublished meets have no public results page', function () {
    $meet = Meet::factory()->active()->create();
    EventResult::factory()->validated()->create(['meet_id' => $meet->id]);

    $this->get("/meets/{$meet->id}/results")->assertNotFound();
});

test('encoded results are structurally excluded from the public page', function () {
    $meet = Meet::factory()->active()->published()->create();

    $encoded = EventResult::factory()->create(['meet_id' => $meet->id]);
    ResultPlacement::factory()->create(['event_result_id' => $encoded->id]);

    $this->get("/meets/{$meet->id}/results")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('results', 0)
            ->has('sportOptions', 0));
});

test('public placements carry no sensitive or internal fields', function () {
    [$meet] = publishedMeetWithValidatedResult();

    $this->get("/meets/{$meet->id}/results")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('results.0', fn (AssertableInertia $result) => $result
                ->hasAll(['id', 'event', 'official_as_of', 'placements'])
                ->missing('validated_by')
                ->missing('encoded_by')
                ->missing('status'))
            ->has('results.0.placements.0', fn (AssertableInertia $placement) => $placement
                ->hasAll(['rank', 'athlete', 'school', 'mark', 'is_tie'])
                ->missing('entry_id')
                ->missing('entry')));
});

test('a correction removes the result from the portal automatically', function () {
    [$meet, $result] = publishedMeetWithValidatedResult();

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/results/{$result->id}/correct", ['reason' => 'Protest upheld.'])
        ->assertSessionHasNoErrors();

    expect($result->refresh()->status)->toBe(ResultStatus::Encoded);

    $this->get("/meets/{$meet->id}/results")
        ->assertInertia(fn (AssertableInertia $page) => $page->has('results', 0));
});

test('results can be filtered by sport', function () {
    [$meet, $resultA] = publishedMeetWithValidatedResult();

    $resultB = EventResult::factory()->validated()->create(['meet_id' => $meet->id]);
    ResultPlacement::factory()->create(['event_result_id' => $resultB->id]);

    $sportA = $resultA->event->sport_id;

    $this->get("/meets/{$meet->id}/results?sport_id={$sportA}")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('results', 1)
            ->where('results.0.id', $resultA->id)
            ->has('sportOptions', 2));
});

test('a published meet without validated results shows the empty state', function () {
    $meet = Meet::factory()->active()->published()->create();

    $this->get("/meets/{$meet->id}/results")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('results', 0)
            ->has('sportOptions', 0));
});
