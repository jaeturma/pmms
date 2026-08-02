<?php

namespace Database\Factories;

use App\Enums\DrrmCategory;
use App\Models\Meet;
use App\Models\ReadinessChecklist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReadinessChecklist>
 */
class ReadinessChecklistFactory extends Factory
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
            'item' => fake()->sentence(),
            'is_complete' => false,
        ];
    }
}
