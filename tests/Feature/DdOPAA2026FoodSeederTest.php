<?php

use App\Enums\MealType;
use App\Models\MealSchedule;
use Database\Seeders\DdOPAA2026FoodSeeder;
use Database\Seeders\DdOPAA2026MeetSeeder;

test('the provincial meet food seeder creates every listed meal period idempotently', function () {
    $this->seed(DdOPAA2026MeetSeeder::class);
    $this->seed(DdOPAA2026FoodSeeder::class);
    $this->seed(DdOPAA2026FoodSeeder::class);

    expect(MealSchedule::query()->count())->toBe(26)
        ->and(MealSchedule::query()
            ->whereDate('date', '2026-09-04')
            ->where('meal_type', MealType::Snack->value)
            ->orderBy('starts_at')
            ->pluck('starts_at')
            ->all())
        ->toBe(['09:00', '14:30']);
});
