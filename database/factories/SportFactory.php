<?php

namespace Database\Factories;

use App\Models\Sport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sport>
 */
class SportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Sport '.ucfirst(fake()->unique()->word()),
            'active' => true,
        ];
    }

    /**
     * Indicate that the sport is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }
}
