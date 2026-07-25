<?php

namespace Database\Factories;

use App\Enums\DivisionType;
use App\Models\Division;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Division>
 */
class DivisionFactory extends Factory
{
    protected $model = Division::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => DivisionType::Province,
            'name' => 'Provincial Meet Division',
        ];
    }

    /**
     * Indicate the division is a City division.
     */
    public function city(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DivisionType::City,
        ]);
    }

    /**
     * Indicate the division is a Province division.
     */
    public function province(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DivisionType::Province,
        ]);
    }
}
