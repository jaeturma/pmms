<?php

namespace Database\Factories;

use App\Enums\Sex;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Athlete>
 */
class AthleteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * `school_id` is deliberately absent here — it's resolved in
     * `configure()` once `delegation_id` is known, to avoid creating an
     * orphaned School row per athlete via a nested factory that then gets
     * immediately overridden.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'delegation_id' => Delegation::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'sex' => fake()->randomElement(Sex::cases()),
            'birthdate' => fake()->dateTimeBetween('-16 years', '-8 years')->format('Y-m-d'),
            'lrn' => (string) fake()->unique()->numberBetween(100000000000, 999999999999),
            'grade_level' => fake()->numberBetween(1, 12),
        ];
    }

    /**
     * A school-rooted delegation only ever has one valid school for its
     * members — default the athlete to that school. A district-rooted
     * (municipal) delegation has no single derivable school, so fall back
     * to a fresh one. Either way, a test overriding `school_id` explicitly
     * is left alone.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Athlete $athlete): void {
            if (array_key_exists('school_id', $athlete->getAttributes())) {
                return;
            }

            $delegation = Delegation::find($athlete->delegation_id);

            $athlete->school_id = $delegation !== null && $delegation->school_id !== null
                ? $delegation->school_id
                : School::factory()->create()->id;
        });
    }
}
