<?php

namespace Database\Factories;

use App\Models\DrrmEquipment;
use App\Models\Meet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DrrmEquipment>
 */
class DrrmEquipmentFactory extends Factory
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
            'name' => fake()->randomElement(['First Aid Kit', 'Megaphone', 'Emergency Radio', 'Stretcher']),
            'quantity' => fake()->numberBetween(1, 10),
        ];
    }
}
