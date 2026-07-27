<?php

namespace Database\Factories;

use App\Models\District;
use App\Models\SchoolDistrict;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolDistrict>
 */
class SchoolDistrictFactory extends Factory
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
            'name' => fake()->unique()->streetName().' District',
            'active' => true,
        ];
    }

    /**
     * Indicate that the school district is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }
}
