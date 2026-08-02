<?php

namespace Database\Factories;

use App\Models\MedicalAccessLog;
use App\Models\MedicalClearance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MedicalAccessLog>
 */
class MedicalAccessLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'medical_clearance_id' => MedicalClearance::factory(),
            'accessed_by_user_id' => User::factory(),
            'reason' => fake()->sentence(),
            'accessed_at' => now(),
        ];
    }
}
