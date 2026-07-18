<?php

namespace Database\Factories;

use App\Enums\Sex;
use App\Models\Athlete;
use App\Models\Delegation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Athlete>
 */
class AthleteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'delegation_id' => Delegation::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'sex' => fake()->randomElement(Sex::cases()),
            'birthdate' => fake()->dateTimeBetween('-16 years', '-8 years')->format('Y-m-d'),
            'lrn' => (string) fake()->unique()->numberBetween(100000000000, 999999999999),
            'grade_level' => fake()->numberBetween(1, 12),
        ];
    }
}
