<?php

use App\Enums\AgeDivision;
use App\Enums\GenderCategory;
use App\Models\Event;
use App\Models\MeetSport;
use App\Models\Sport;
use App\Models\SportCategory;
use Illuminate\Database\QueryException;

/**
 * SportCategory (WP-REALIGN-03) is model/schema only in this phase — no
 * controller or route exists yet, same scoping as MeetSportTest.
 */
test('a sport has many categories, with structured level/sex casts', function () {
    $sport = Sport::factory()->create();

    $category = SportCategory::factory()->create([
        'sport_id' => $sport->id,
        'level' => AgeDivision::Elementary,
        'sex' => GenderCategory::Boys,
        'display_name' => 'Elementary Boys Track',
    ]);

    expect($sport->categories()->first()->id)->toBe($category->id)
        ->and($category->sport->id)->toBe($sport->id)
        ->and($category->level)->toBe(AgeDivision::Elementary)
        ->and($category->sex)->toBe(GenderCategory::Boys)
        ->and($category->active)->toBeTrue();
});

test('a category may be catalog-wide (no meet_sport_id) or scoped to one meet\'s inclusion of the sport', function () {
    $catalogWide = SportCategory::factory()->create(['meet_sport_id' => null]);

    $meetSport = MeetSport::factory()->create();
    $meetScoped = SportCategory::factory()->create([
        'sport_id' => $meetSport->sport_id,
        'meet_sport_id' => $meetSport->id,
    ]);

    expect($catalogWide->meetSport)->toBeNull()
        ->and($meetScoped->meetSport->id)->toBe($meetSport->id)
        ->and($meetSport->categories()->first()->id)->toBe($meetScoped->id);
});

test('a sport cannot be deleted while it has a category', function () {
    $category = SportCategory::factory()->create();

    expect(fn () => $category->sport->delete())->toThrow(QueryException::class);
});

test('a category is deleted when its meet sport is deleted, but survives its meet sport being null', function () {
    $meetSport = MeetSport::factory()->create();
    $scoped = SportCategory::factory()->create(['sport_id' => $meetSport->sport_id, 'meet_sport_id' => $meetSport->id]);

    $meetSport->delete();

    expect(SportCategory::query()->whereKey($scoped->id)->exists())->toBeFalse();
});

test('an event may optionally reference a sport category without its own gender/age_division changing', function () {
    $category = SportCategory::factory()->create([
        'level' => AgeDivision::Secondary,
        'sex' => GenderCategory::Girls,
    ]);

    $event = Event::factory()->create([
        'sport_id' => $category->sport_id,
        'sport_category_id' => $category->id,
        'gender' => GenderCategory::Girls,
        'age_division' => AgeDivision::Secondary,
    ]);

    expect($event->sportCategory->id)->toBe($category->id)
        ->and($event->gender)->toBe(GenderCategory::Girls)
        ->and($event->age_division)->toBe(AgeDivision::Secondary)
        ->and($category->events()->first()->id)->toBe($event->id);
});

test('an event with no sport category still works exactly as before', function () {
    $event = Event::factory()->create();

    expect($event->sport_category_id)->toBeNull()
        ->and($event->sportCategory)->toBeNull();
});

test('deleting a sport category does not delete the events that reference it', function () {
    $category = SportCategory::factory()->create();
    $event = Event::factory()->create(['sport_id' => $category->sport_id, 'sport_category_id' => $category->id]);

    $category->delete();

    expect($event->fresh()->sport_category_id)->toBeNull();
});
