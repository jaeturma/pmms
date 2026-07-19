<?php

namespace Database\Factories;

use App\Enums\ProtestStatus;
use App\Models\Delegation;
use App\Models\EventResult;
use App\Models\Protest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Protest>
 */
class ProtestFactory extends Factory
{
    /**
     * Define the model's default state: a filed protest against an event
     * result of the delegation's own meet.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'delegation_id' => Delegation::factory()->approved(),
            'event_result_id' => function (array $attributes) {
                $delegation = Delegation::query()->findOrFail((int) $attributes['delegation_id']);

                return EventResult::factory()->validated()
                    ->create(['meet_id' => $delegation->meet_id])
                    ->id;
            },
            'match_id' => null,
            'grounds' => 'Scoring discrepancy observed at the finish line.',
            'status' => ProtestStatus::Filed,
            'filed_by' => User::factory()->delegationOfficer(),
        ];
    }

    /**
     * Indicate that the protest is under review.
     */
    public function underReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProtestStatus::UnderReview,
        ]);
    }

    /**
     * Indicate that the protest was upheld.
     */
    public function upheld(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProtestStatus::Upheld,
            'decided_by' => User::factory()->organizer(),
            'decided_at' => now(),
            'remarks' => 'Video review confirmed the claim.',
        ]);
    }

    /**
     * Indicate that the protest was dismissed.
     */
    public function dismissed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProtestStatus::Dismissed,
            'decided_by' => User::factory()->organizer(),
            'decided_at' => now(),
            'remarks' => 'No supporting evidence found.',
        ]);
    }
}
