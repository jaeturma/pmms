<?php

namespace Database\Factories;

use App\Models\EquipmentCategory;
use App\Models\Meet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EquipmentCategory>
 */
class EquipmentCategoryFactory extends Factory
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
            'name' => fake()->unique()->words(2, true),
            'description' => null,
            'is_consumable' => false,
        ];
    }

    /**
     * Indicate that the category is consumable (no return expected).
     */
    public function consumable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_consumable' => true,
        ]);
    }
}
