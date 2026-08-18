<?php

use App\Models\Meet;
use App\Models\Sport;
use Database\Seeders\DdOPAA2026SportsSeeder;
use Database\Seeders\SportEventsSeeder;

test('the canonical sports seed is idempotent and classified', function () {
    $this->seed(SportEventsSeeder::class);
    $this->seed(SportEventsSeeder::class);

    expect(Sport::query()->count())->toBe(29)
        ->and(Sport::query()->distinct()->count('code'))->toBe(29)
        ->and(Sport::query()->where('classification', 'regular')->count())->toBe(25)
        ->and(Sport::query()->where('classification', 'paragames')->count())->toBe(4)
        ->and(Sport::query()->whereIn('name', ['Bocce', 'Goalball', 'Para Athletics', 'Para Swimming'])->count())->toBe(4);
});

test('level restrictions and category event relationships are preserved', function () {
    $this->seed(SportEventsSeeder::class);

    $archery = Sport::query()->where('code', 'ARCHERY')->firstOrFail();
    $threeByThree = Sport::query()->where('code', 'BASKETBALL_3X3')->firstOrFail();

    expect($archery->categories()->where('level', 'elementary')->exists())->toBeFalse()
        ->and($archery->categories()->where('level', 'secondary')->exists())->toBeTrue()
        ->and($threeByThree->categories()->where('level', 'elementary')->exists())->toBeFalse()
        ->and($threeByThree->categories()->where('level', 'secondary')->exists())->toBeTrue()
        ->and($archery->categories()->firstOrFail()->events()->where('is_medal_event', true)->exists())->toBeTrue();
});

test('display order is deterministic and sports can be scoped to the current meet', function () {
    $meet = Meet::factory()->create(['name' => 'DdOPAA Meet 2026', 'is_active' => true]);
    $this->seed(DdOPAA2026SportsSeeder::class);
    $this->seed(DdOPAA2026SportsSeeder::class);

    expect($meet->sports()->count())->toBe(29)
        ->and($meet->meetSports()->where('active', true)->count())->toBe(29)
        ->and(Sport::query()->where('classification', 'regular')->orderBy('display_order')->pluck('name')->all())
        ->toBe(Sport::query()->where('classification', 'regular')->orderBy('name')->pluck('name')->all());
});
