<?php

namespace Database\Factories;

use App\Models\Meet;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
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
            'plate_number' => strtoupper(fake()->unique()->bothify('???-####')),
            'type' => fake()->randomElement(['Bus', 'Van', 'Car']),
            'capacity' => fake()->numberBetween(4, 50),
            'driver_name' => fake()->name(),
            'driver_phone' => fake()->phoneNumber(),
        ];
    }
}
