<?php

namespace Database\Factories;

use App\Enums\InventoryAdjustmentType;
use App\Models\EquipmentItem;
use App\Models\InventoryAdjustment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryAdjustment>
 */
class InventoryAdjustmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'equipment_item_id' => EquipmentItem::factory(),
            'type' => InventoryAdjustmentType::Recount,
            'quantity_delta' => 1,
            'reason' => fake()->sentence(),
            'adjusted_by_user_id' => User::factory(),
            'adjusted_at' => now(),
        ];
    }
}
