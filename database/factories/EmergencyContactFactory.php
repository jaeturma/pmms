<?php

namespace Database\Factories;

use App\Models\EmergencyContact;
use App\Models\Meet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmergencyContact>
 */
class EmergencyContactFactory extends Factory
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
            'name' => fake()->company(),
            'phone' => fake()->phoneNumber(),
        ];
    }
}
