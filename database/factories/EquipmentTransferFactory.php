<?php

namespace Database\Factories;

use App\Models\EquipmentItem;
use App\Models\EquipmentTransfer;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EquipmentTransfer>
 */
class EquipmentTransferFactory extends Factory
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
            'from_venue_id' => Venue::factory(),
            'to_venue_id' => Venue::factory(),
            'quantity' => fake()->numberBetween(1, 5),
            'transferred_by_user_id' => User::factory(),
            'reason' => null,
            'transferred_at' => now(),
        ];
    }
}
