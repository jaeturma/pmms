<?php

use App\Models\CompetitionArea;
use App\Models\GameCoordinatorAssignment;
use App\Models\Meet;
use App\Models\MeetSportVenue;
use App\Models\Person;
use App\Models\Sport;
use App\Models\Venue;
use Database\Seeders\DdOPAA2026VenueSeeder;

beforeEach(function () {
    Meet::factory()->create(['name' => 'DdOPAA Meet 2026']);
    $codes = collect(require database_path('data/ddopaa2026/venues.php'))->pluck('sport')->unique();
    foreach ($codes as $code) {
        Sport::factory()->create(['code' => $code, 'name' => str($code)->replace('_', ' ')->title()]);
    }
});

test('production venue seeder creates normalized venues areas and coordinators', function () {
    $this->seed(DdOPAA2026VenueSeeder::class);

    expect(Venue::query()->whereNotNull('source_code')->count())->toBeGreaterThan(20)
        ->and(MeetSportVenue::count())->toBeGreaterThan(25)
        ->and(GameCoordinatorAssignment::count())->toBeGreaterThan(20)
        ->and(CompetitionArea::whereHas('venue', fn ($query) => $query->where('name', 'Tamia Brgy Gym'))->count())->toBe(8)
        ->and(CompetitionArea::whereHas('venue', fn ($query) => $query->where('name', 'Luzano Lot'))->count())->toBe(2)
        ->and(CompetitionArea::whereHas('venue', fn ($query) => $query->where('name', 'Tent City'))->count())->toBe(2);

    $ddosc = Venue::where('name', 'DDOSC')->firstOrFail();
    expect($ddosc->meetSportAssignments()->count())->toBe(2);
});

test('venue seeder reruns without duplicates and preserves administrative edits', function () {
    $this->seed(DdOPAA2026VenueSeeder::class);
    $venue = Venue::where('name', 'Tamia Brgy Gym')->firstOrFail();
    $venue->update(['name' => 'Tamia Barangay Gymnasium', 'address' => 'Administrator verified address']);
    $counts = [Venue::count(), MeetSportVenue::count(), CompetitionArea::count(), GameCoordinatorAssignment::count(), Person::count()];

    $this->seed(DdOPAA2026VenueSeeder::class);

    expect([Venue::count(), MeetSportVenue::count(), CompetitionArea::count(), GameCoordinatorAssignment::count(), Person::count()])->toBe($counts)
        ->and($venue->refresh()->name)->toBe('Tamia Barangay Gymnasium')
        ->and($venue->address)->toBe('Administrator verified address');
});

test('private coordinator contacts are not stored in public venue notes', function () {
    $this->seed(DdOPAA2026VenueSeeder::class);

    expect(Venue::query()->where('public_notes', 'like', '%09107159999%')->exists())->toBeFalse()
        ->and(GameCoordinatorAssignment::query()->where('source_contact_text', '09107159999')->exists())->toBeTrue();
});
