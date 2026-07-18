<?php

namespace Database\Factories;

use App\Enums\EntryStatus;
use App\Models\Athlete;
use App\Models\Entry;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Entry>
 */
class EntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'athlete_id' => Athlete::factory(),
            'event_id' => Event::factory(),
            'delegation_id' => function (array $attributes): int {
                return Athlete::query()->whereKey($attributes['athlete_id'])->firstOrFail()->delegation_id;
            },
            'status' => EntryStatus::Submitted,
        ];
    }

    /**
     * Indicate that the entry is confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EntryStatus::Confirmed,
        ]);
    }

    /**
     * Indicate that the entry is withdrawn.
     */
    public function withdrawn(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EntryStatus::Withdrawn,
        ]);
    }
}
