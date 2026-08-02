<?php

namespace Database\Factories;

use App\Models\CongressionalDistrict;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CongressionalDistrict>
 */
class CongressionalDistrictFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['First', 'Second', 'Third', 'Fourth', 'Fifth']),
            'active' => true,
        ];
    }
}
