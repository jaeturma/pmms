<?php

namespace Database\Factories;

use App\Enums\DelegationStatus;
use App\Models\Delegation;
use App\Models\Meet;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Delegation>
 */
class DelegationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meet_id' => Meet::factory()->registrationOpen(),
            'school_id' => School::factory(),
            'head_name' => fake()->name(),
            'head_phone' => fake()->phoneNumber(),
            'head_email' => fake()->safeEmail(),
            'status' => DelegationStatus::Draft,
        ];
    }

    /**
     * Indicate that the delegation has been submitted.
     */
    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DelegationStatus::Submitted,
        ]);
    }

    /**
     * Indicate that the delegation has been approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DelegationStatus::Approved,
        ]);
    }
}
