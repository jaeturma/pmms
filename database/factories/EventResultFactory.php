<?php

namespace Database\Factories;

use App\Enums\ResultStatus;
use App\Models\Event;
use App\Models\EventResult;
use App\Models\Meet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventResult>
 */
class EventResultFactory extends Factory
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
            'status' => ResultStatus::Encoded,
            'encoded_by' => User::factory()->organizer(),
            'encoded_at' => now(),
            'validated_by' => null,
            'validated_at' => null,
        ];
    }

    /**
     * Indicate that the result is validated.
     */
    public function validated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ResultStatus::Validated,
            'validated_by' => User::factory()->organizer(),
            'validated_at' => now(),
        ]);
    }

    /**
     * Keep factory data valid: the result's event must belong to the meet.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (EventResult $result) {
            $result->meet->events()->syncWithoutDetaching([$result->event_id]);
        });
    }
}
