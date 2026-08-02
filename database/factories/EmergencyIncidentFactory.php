<?php

namespace Database\Factories;

use App\Enums\DrrmCategory;
use App\Enums\EmergencyIncidentStatus;
use App\Models\EmergencyIncident;
use App\Models\Meet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmergencyIncident>
 */
class EmergencyIncidentFactory extends Factory
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
            'description' => fake()->paragraph(),
            'status' => EmergencyIncidentStatus::Reported,
            'reported_by_user_id' => User::factory(),
            'reported_at' => now(),
        ];
    }
}
