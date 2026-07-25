<?php

namespace Database\Factories;

use App\Enums\ScoringSessionStatus;
use App\Models\EventMatch;
use App\Models\ScoringSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScoringSession>
 */
class ScoringSessionFactory extends Factory
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
            'status' => ScoringSessionStatus::InProgress,
            'side_a_label' => 'Side A',
            'side_b_label' => 'Side B',
            'score_a' => 0,
            'score_b' => 0,
            'started_by' => User::factory()->organizer(),
            'started_at' => now(),
        ];
    }

    /**
     * Indicate that the session is paused.
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ScoringSessionStatus::Paused,
        ]);
    }

    /**
     * Indicate that the session has ended.
     */
    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ScoringSessionStatus::Ended,
            'ended_by' => User::factory()->organizer(),
            'ended_at' => now(),
        ]);
    }
}
