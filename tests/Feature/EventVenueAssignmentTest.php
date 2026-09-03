<?php

use App\Models\Event;
use App\Models\CompetitionArea;
use App\Models\Person;
use App\Models\Sport;
use App\Models\User;
use App\Models\Venue;

test('an event supports multiple venues and one or two coordinators per venue', function () {
    $sport = Sport::factory()->create();
    $venueA = Venue::factory()->create();
    $venueB = Venue::factory()->create();
    $coordinators = collect(['Alex Coordinator', 'Blair Coordinator', 'Casey Coordinator'])
        ->map(fn (string $name, int $index) => Person::query()->create([
            'source_key' => 'event-coordinator-'.$index,
            'full_name' => $name,
            'normalized_name' => strtolower($name),
        ]));

    $this->actingAs(User::factory()->admin()->create())->post('/events', [
        'sport_id' => $sport->id,
        'name' => 'Venue Assignment Event',
        'gender' => 'mixed',
        'age_division' => 'secondary',
        'is_team_event' => true,
        'max_entries_per_delegation' => 1,
        'venues' => [
            ['venue_id' => $venueA->id, 'playing_area_type' => 'court', 'playing_area_count' => 2, 'coordinator_ids' => $coordinators->take(2)->pluck('id')->all()],
            ['venue_id' => $venueB->id, 'playing_area_type' => 'table', 'playing_area_count' => 6, 'coordinator_ids' => [$coordinators->last()->id]],
        ],
    ])->assertRedirect();

    $event = Event::query()->where('name', 'Venue Assignment Event')->firstOrFail();
    expect($event->venueAssignments()->count())->toBe(2)
        ->and($event->venueAssignments()->where('venue_id', $venueA->id)->firstOrFail()->coordinators()->count())->toBe(2)
        ->and($event->venueAssignments()->where('venue_id', $venueB->id)->firstOrFail()->playing_area_count)->toBe(6)
        ->and(CompetitionArea::query()->where('venue_id', $venueA->id)->pluck('name')->all())->toBe(['Court 1', 'Court 2'])
        ->and(CompetitionArea::query()->where('venue_id', $venueB->id)->pluck('name')->all())->toBe(['Table 1', 'Table 2', 'Table 3', 'Table 4', 'Table 5', 'Table 6']);
});

test('an event venue rejects more than two coordinators', function () {
    $people = collect(range(1, 3))->map(fn (int $index) => Person::query()->create([
        'source_key' => 'too-many-coordinators-'.$index,
        'full_name' => "Coordinator {$index}",
        'normalized_name' => "coordinator {$index}",
    ]));

    $this->actingAs(User::factory()->admin()->create())->post('/events', [
        'sport_id' => Sport::factory()->create()->id,
        'name' => 'Invalid Coordinator Event',
        'gender' => 'mixed',
        'age_division' => 'secondary',
        'is_team_event' => false,
        'max_entries_per_delegation' => 1,
        'venues' => [[
            'venue_id' => Venue::factory()->create()->id,
            'playing_area_type' => 'venue',
            'playing_area_count' => 1,
            'coordinator_ids' => $people->pluck('id')->all(),
        ]],
    ])->assertSessionHasErrors('venues.0.coordinator_ids');
});
