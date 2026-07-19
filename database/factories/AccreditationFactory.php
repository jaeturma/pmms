<?php

namespace Database\Factories;

use App\Models\Accreditation;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Accreditation>
 */
class AccreditationFactory extends Factory
{
    /**
     * Define the model's default state: an accredited athlete of an
     * approved delegation.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'delegation_id' => Delegation::factory()->approved(),
            'athlete_id' => fn (array $attributes) => Athlete::factory()
                ->create(['delegation_id' => $attributes['delegation_id']])
                ->id,
            'personnel_id' => null,
            'accredited_by' => User::factory()->organizer(),
            'accredited_at' => now(),
        ];
    }

    /**
     * Accredit a personnel member instead of an athlete.
     */
    public function forPersonnel(): static
    {
        return $this->state(fn (array $attributes) => [
            'athlete_id' => null,
            'personnel_id' => fn (array $resolved) => Personnel::factory()
                ->create(['delegation_id' => $resolved['delegation_id']])
                ->id,
        ]);
    }

    /**
     * Every accreditation carries a derived card number.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Accreditation $accreditation) {
            if ($accreditation->number === null) {
                $accreditation->assignNumber();
            }
        });
    }
}
