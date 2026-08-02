<?php

namespace Database\Factories;

use App\Enums\EquipmentIssueStatus;
use App\Models\EquipmentIssue;
use App\Models\EquipmentItem;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EquipmentIssue>
 */
class EquipmentIssueFactory extends Factory
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
            'venue_id' => Venue::factory(),
            'quantity' => fake()->numberBetween(1, 5),
            'custodian_name' => fake()->name(),
            'issued_by_user_id' => User::factory(),
            'purpose' => null,
            'status' => EquipmentIssueStatus::Issued,
            'issued_at' => now(),
        ];
    }
}
