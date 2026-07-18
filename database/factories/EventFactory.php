<?php

namespace Database\Factories;

use App\Enums\AgeDivision;
use App\Enums\GenderCategory;
use App\Models\Event;
use App\Models\Sport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sport_id' => Sport::factory(),
            'name' => 'Event '.ucfirst(fake()->unique()->word()).' '.fake()->numberBetween(1, 999),
            'gender' => fake()->randomElement(GenderCategory::cases()),
            'age_division' => fake()->randomElement(AgeDivision::cases()),
            'is_team_event' => false,
            'max_entries_per_delegation' => 2,
            'active' => true,
        ];
    }

    /**
     * Indicate that the event is a team event.
     */
    public function team(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_team_event' => true,
            'max_entries_per_delegation' => 1,
        ]);
    }

    /**
     * Indicate that the event is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }
}
