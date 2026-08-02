<?php

namespace Database\Factories;

use App\Enums\TransportRequestStatus;
use App\Models\Delegation;
use App\Models\Meet;
use App\Models\TransportRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransportRequest>
 */
class TransportRequestFactory extends Factory
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
            'delegation_id' => Delegation::factory(),
            'pickup_location' => fake()->streetAddress(),
            'dropoff_location' => fake()->streetAddress(),
            'requested_at' => now()->addDay(),
            'passenger_count' => fake()->numberBetween(1, 20),
            'status' => TransportRequestStatus::Pending,
            'requested_by_user_id' => User::factory(),
        ];
    }
}
