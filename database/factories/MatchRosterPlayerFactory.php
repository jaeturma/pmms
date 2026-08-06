<?php

namespace Database\Factories;

use App\Models\Entry;
use App\Models\EventMatch;
use App\Models\MatchRosterPlayer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MatchRosterPlayer>
 */
class MatchRosterPlayerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'match_id' => EventMatch::factory(),
            'entry_id' => Entry::factory(),
            'side' => 'a',
            'jersey_number' => (string) fake()->numberBetween(0, 99),
            'is_starter' => false,
        ];
    }

    public function side(string $side): static
    {
        return $this->state(fn (array $attributes) => ['side' => $side]);
    }

    public function starter(): static
    {
        return $this->state(fn (array $attributes) => ['is_starter' => true]);
    }
}
