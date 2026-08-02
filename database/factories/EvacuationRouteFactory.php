<?php

namespace Database\Factories;

use App\Models\EvacuationRoute;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvacuationRoute>
 */
class EvacuationRouteFactory extends Factory
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
            'name' => fake()->words(3, true),
            'description' => fake()->paragraph(),
        ];
    }
}
