<?php

use App\Models\EventSchedule;
use App\Models\Meet;
use App\Models\Sport;
use App\Models\Venue;
use Database\Seeders\BasketballSecondaryBoysScheduleSeeder;
use Database\Seeders\DdOPAA2026MeetSeeder;

test('the DdOPAA meet seeder creates and attaches basketball secondary boys', function () {
    $this->seed(DdOPAA2026MeetSeeder::class);

    $meet = Meet::query()->where('name', 'DdOPAA Meet 2026')->firstOrFail();
    $event = $meet->events()
        ->whereHas('sport', fn ($sports) => $sports->where('name', 'Basketball'))
        ->where('gender', 'boys')
        ->where('age_division', 'secondary')
        ->first();

    expect($event)->not->toBeNull()
        ->and($event->name)->toBe('Basketball')
        ->and($event->is_team_event)->toBeTrue()
        ->and($event->max_entries_per_delegation)->toBe(12);
});

test('the basketball secondary boys schedule is evenly distributed and idempotent', function () {
    Meet::factory()->create(['name' => 'DdOPAA Provincial Meet 2026']);
    Sport::factory()->create(['name' => 'Basketball', 'active' => true]);
    Venue::factory()->create(['name' => 'Municipal Gym', 'active' => true]);

    $this->seed(BasketballSecondaryBoysScheduleSeeder::class);
    $this->seed(BasketballSecondaryBoysScheduleSeeder::class);

    expect(EventSchedule::query()->count())->toBe(20)
        ->and(EventSchedule::query()->whereDate('scheduled_date', '2026-09-05')->count())->toBe(7)
        ->and(EventSchedule::query()->whereDate('scheduled_date', '2026-09-06')->count())->toBe(7)
        ->and(EventSchedule::query()->whereDate('scheduled_date', '2026-09-07')->count())->toBe(5)
        ->and(EventSchedule::query()->whereDate('scheduled_date', '2026-09-08')->count())->toBe(1)
        ->and(EventSchedule::query()->orderBy('scheduled_date')->orderBy('starts_at')->value('starts_at'))->toBe('07:00:00')
        ->and(EventSchedule::query()->orderByDesc('scheduled_date')->orderByDesc('ends_at')->value('ends_at'))->toBe('11:00:00');

    $septemberFifth = EventSchedule::query()
        ->whereDate('scheduled_date', '2026-09-05')
        ->orderBy('starts_at')
        ->get();

    expect($septemberFifth->first()->starts_at)->toBe('07:00:00')
        ->and($septemberFifth->last()->ends_at)->toBe('18:00:00');

    foreach ($septemberFifth->sliding(2) as $pair) {
        expect($pair->first()->ends_at)->toBe($pair->last()->starts_at);
    }
});
