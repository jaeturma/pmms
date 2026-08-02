<?php

namespace Database\Factories;

use App\Models\Meet;
use App\Models\Venue;
use App\Models\VenueEmergencyPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VenueEmergencyPlan>
 */
class VenueEmergencyPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            'meet_id' => Meet::factory(),
            'plan_detail' => fake()->paragraph(),
        ];
    }
}
