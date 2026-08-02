<?php

namespace Database\Factories;

use App\Enums\MealType;
use App\Models\MealSchedule;
use App\Models\Meet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MealSchedule>
 */
class MealScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meet_id' => Meet::factory(),
            'meal_type' => fake()->randomElement(MealType::cases()),
            'date' => fake()->date(),
            'venue_id' => null,
        ];
    }
}
