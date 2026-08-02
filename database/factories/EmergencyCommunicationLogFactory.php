<?php

namespace Database\Factories;

use App\Models\EmergencyCommunicationLog;
use App\Models\EmergencyIncident;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmergencyCommunicationLog>
 */
class EmergencyCommunicationLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'emergency_incident_id' => EmergencyIncident::factory(),
            'message' => fake()->sentence(),
            'sent_by_user_id' => User::factory(),
            'sent_at' => now(),
        ];
    }
}
