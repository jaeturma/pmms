<?php

namespace Database\Factories;

use App\Enums\SchoolLevel;
use App\Models\District;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<School>
 */
class SchoolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'district_id' => District::factory(),
            'name' => fake()->unique()->city().' National High School',
            'school_id_code' => (string) fake()->unique()->numberBetween(100000, 999999),
            'level' => fake()->randomElement(SchoolLevel::cases()),
            'address' => fake()->streetAddress(),
            'active' => true,
        ];
    }

    /**
     * Indicate that the school is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }
}
