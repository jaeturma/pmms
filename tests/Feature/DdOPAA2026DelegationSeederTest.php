<?php

use App\Models\Delegation;
use App\Models\District;
use App\Models\Meet;
use Database\Seeders\DdOPAA2026DelegationSeeder;
use Database\Seeders\DdOPAA2026MeetSeeder;
use Database\Seeders\DivisionRegistrySeeder;

test('all municipalities are registered once as teams for the provincial meet', function () {
    $this->seed([DivisionRegistrySeeder::class, DdOPAA2026MeetSeeder::class, DdOPAA2026DelegationSeeder::class]);
    $this->seed(DdOPAA2026DelegationSeeder::class);

    $meet = Meet::query()->where('name', 'DdOPAA Meet 2026')->sole();
    expect(District::query()->count())->toBe(11)
        ->and(Delegation::query()->where('meet_id', $meet->id)->count())->toBe(11)
        ->and(Delegation::query()->where('meet_id', $meet->id)->whereNull('district_id')->count())->toBe(0);

    $this->get('/teams')->assertOk()->assertInertia(fn ($page) => $page->has('teams', 11));
});
