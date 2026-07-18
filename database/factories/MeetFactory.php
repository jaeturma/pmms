<?php

namespace Database\Factories;

use App\Enums\MeetStatus;
use App\Models\Meet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Meet>
 */
class MeetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Meet '.ucfirst(fake()->unique()->word()).' '.fake()->numberBetween(1, 9999),
            'school_year' => '2025-2026',
            'starts_at' => now()->addMonth()->toDateString(),
            'ends_at' => now()->addMonth()->addDays(4)->toDateString(),
            'venue' => fake()->city().' Sports Complex',
            'status' => MeetStatus::Draft,
        ];
    }

    /**
     * Indicate that registration is open.
     */
    public function registrationOpen(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MeetStatus::RegistrationOpen,
        ]);
    }

    /**
     * Indicate that the meet is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MeetStatus::Completed,
        ]);
    }
}
