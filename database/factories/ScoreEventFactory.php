<?php

namespace Database\Factories;

use App\Enums\ScoreEventType;
use App\Models\ScoreEvent;
use App\Models\ScoringSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScoreEvent>
 */
class ScoreEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'scoring_session_id' => ScoringSession::factory(),
            'type' => ScoreEventType::Point,
            'payload' => ['side' => 'a', 'delta' => 1],
            'recorded_by' => User::factory()->organizer(),
        ];
    }
}
