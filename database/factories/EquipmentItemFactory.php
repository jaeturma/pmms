<?php

namespace Database\Factories;

use App\Enums\EquipmentCondition;
use App\Models\EquipmentCategory;
use App\Models\EquipmentItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EquipmentItem>
 */
class EquipmentItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'equipment_category_id' => EquipmentCategory::factory(),
            'venue_id' => null,
            'quantity' => fake()->numberBetween(5, 50),
            'condition' => EquipmentCondition::Good,
            'notes' => null,
        ];
    }
}
