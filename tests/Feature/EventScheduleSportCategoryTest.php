<?php

use App\Models\EventSchedule;
use App\Models\SportCategory;
use App\Models\Venue;

/**
 * EventSchedule.sport_category_id (WP-REALIGN-17) is model/schema only in
 * this phase — no controller or Inertia page change, same scoping as the
 * other new-column WPs in the DdOPAA realignment (SportCategoryTest).
 */
test('a schedule slot may optionally reference a sport category without its own event/venue changing', function () {
    $category = SportCategory::factory()->create();

    $schedule = EventSchedule::factory()->create([
        'sport_category_id' => $category->id,
    ]);

    expect($schedule->sportCategory->id)->toBe($category->id)
        ->and($category->schedules()->first()->id)->toBe($schedule->id);
});

test('a schedule slot with no sport category still works exactly as before', function () {
    $schedule = EventSchedule::factory()->create();

    expect($schedule->sport_category_id)->toBeNull()
        ->and($schedule->sportCategory)->toBeNull();
});

test('deleting a sport category does not delete the schedule slots that reference it', function () {
    $category = SportCategory::factory()->create();
    $schedule = EventSchedule::factory()->create(['sport_category_id' => $category->id]);

    $category->delete();

    expect($schedule->fresh()->sport_category_id)->toBeNull();
});

test('a category\'s venues are derived from its own schedule slots, not a direct relation', function () {
    $category = SportCategory::factory()->create();
    $venueA = Venue::factory()->create();
    $venueB = Venue::factory()->create();

    EventSchedule::factory()->create(['sport_category_id' => $category->id, 'venue_id' => $venueA->id]);
    EventSchedule::factory()->create(['sport_category_id' => $category->id, 'venue_id' => $venueB->id]);
    EventSchedule::factory()->create();

    $venueIds = $category->venues()->pluck('id')->sort()->values()->all();
    $expected = collect([$venueA->id, $venueB->id])->sort()->values()->all();

    expect($venueIds)->toBe($expected);
});
