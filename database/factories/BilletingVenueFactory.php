<?php

namespace Database\Factories;

use App\Models\BilletingVenue;
use App\Models\Meet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BilletingVenue>
 */
class BilletingVenueFactory extends Factory
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
            'name' => fake()->unique()->company().' Lodge',
            'address' => fake()->address(),
            'capacity' => fake()->numberBetween(20, 200),
        ];
    }
}
