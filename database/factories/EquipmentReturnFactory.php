<?php

namespace Database\Factories;

use App\Enums\EquipmentCondition;
use App\Models\EquipmentIssue;
use App\Models\EquipmentReturn;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EquipmentReturn>
 */
class EquipmentReturnFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'equipment_issue_id' => EquipmentIssue::factory(),
            'quantity' => 1,
            'condition_on_return' => EquipmentCondition::Good,
            'received_by_user_id' => User::factory(),
            'notes' => null,
            'returned_at' => now(),
        ];
    }
}
