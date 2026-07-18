<?php

namespace Database\Factories;

use App\Enums\EligibilityStatus;
use App\Models\Athlete;
use App\Models\EligibilityReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EligibilityReview>
 */
class EligibilityReviewFactory extends Factory
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
            'meet_id' => function (array $attributes): int {
                return Athlete::query()
                    ->whereKey($attributes['athlete_id'])
                    ->firstOrFail()
                    ->delegation
                    ->meet_id;
            },
            'status' => EligibilityStatus::Pending,
        ];
    }

    /**
     * Indicate that the review is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EligibilityStatus::Approved,
            'decided_at' => now(),
        ]);
    }

    /**
     * Indicate that the review was returned for correction.
     */
    public function returned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EligibilityStatus::Returned,
            'remarks' => 'Please upload a clearer birth certificate.',
            'decided_at' => now(),
        ]);
    }
}
