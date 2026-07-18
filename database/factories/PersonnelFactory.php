<?php

namespace Database\Factories;

use App\Enums\PersonnelRole;
use App\Models\Delegation;
use App\Models\Personnel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Personnel>
 */
class PersonnelFactory extends Factory
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
            'role' => fake()->randomElement(PersonnelRole::cases()),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
        ];
    }

    /**
     * Indicate that the person is a coach.
     */
    public function coach(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => PersonnelRole::Coach,
        ]);
    }

    /**
     * Indicate that the person is a chaperone.
     */
    public function chaperone(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => PersonnelRole::Chaperone,
        ]);
    }
}
