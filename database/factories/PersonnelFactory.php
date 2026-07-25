<?php

namespace Database\Factories;

use App\Enums\PersonnelRole;
use App\Models\Delegation;
use App\Models\Personnel;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Personnel>
 */
class PersonnelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * `school_id` is deliberately absent here — see `configure()`.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'delegation_id' => Delegation::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'role' => fake()->randomElement(PersonnelRole::cases()),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
        ];
    }

    /**
     * Indicate that the person is a coach.
     */
    public function coach(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => PersonnelRole::Coach,
        ]);
    }

    /**
     * Indicate that the person is a chaperone.
     */
    public function chaperone(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => PersonnelRole::Chaperone,
        ]);
    }

    /**
     * A school-rooted delegation only ever has one valid school for its
     * members — default the person to that school. A district-rooted
     * (municipal) delegation has no single derivable school, so fall back
     * to a fresh one. Either way, a test overriding `school_id` explicitly
     * is left alone.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Personnel $person): void {
            if (array_key_exists('school_id', $person->getAttributes())) {
                return;
            }

            $delegation = Delegation::find($person->delegation_id);

            $person->school_id = $delegation !== null && $delegation->school_id !== null
                ? $delegation->school_id
                : School::factory()->create()->id;
        });
    }
}
