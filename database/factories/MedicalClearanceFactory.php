<?php

namespace Database\Factories;

use App\Enums\MedicalClearanceStatus;
use App\Models\Athlete;
use App\Models\MedicalClearance;
use App\Models\Meet;
use App\Models\Personnel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MedicalClearance>
 */
class MedicalClearanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meet_id' => Meet::factory(),
            'athlete_id' => Athlete::factory(),
            'status' => MedicalClearanceStatus::Pending,
            'consent_confirmed' => false,
        ];
    }

    /**
     * Indicate this clearance is for a Personnel row instead of an
     * Athlete.
     */
    public function forPersonnel(): static
    {
        return $this->state(fn (array $attributes) => [
            'athlete_id' => null,
            'personnel_id' => Personnel::factory(),
        ]);
    }
}
