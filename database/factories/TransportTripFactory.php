<?php

namespace Database\Factories;

use App\Enums\TransportTripStatus;
use App\Models\Meet;
use App\Models\TransportTrip;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransportTrip>
 */
class TransportTripFactory extends Factory
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
            'vehicle_id' => Vehicle::factory(),
            'pickup_location' => fake()->streetAddress(),
            'dropoff_location' => fake()->streetAddress(),
            'status' => TransportTripStatus::Dispatched,
            'scheduled_at' => now()->addHours(2),
        ];
    }
}
