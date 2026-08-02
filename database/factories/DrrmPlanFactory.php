<?php

namespace Database\Factories;

use App\Enums\DrrmCategory;
use App\Models\DrrmPlan;
use App\Models\Meet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DrrmPlan>
 */
class DrrmPlanFactory extends Factory
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
            'category' => fake()->randomElement(DrrmCategory::cases()),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
        ];
    }
}
