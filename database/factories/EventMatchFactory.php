<?php

namespace Database\Factories;

use App\Enums\MatchStatus;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\Meet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventMatch>
 */
class EventMatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meet_id' => Meet::factory()->active(),
            'event_id' => Event::factory(),
            'event_schedule_id' => null,
            'round_label' => 'Heat 1',
            'sequence' => 1,
            'status' => MatchStatus::Scheduled,
        ];
    }

    /**
     * Indicate that the match is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MatchStatus::Completed,
        ]);
    }

    /**
     * Keep factory data valid: the match's event must belong to the meet.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (EventMatch $match) {
            $match->meet->events()->syncWithoutDetaching([$match->event_id]);
        });
    }
}
