<?php

namespace Database\Factories;

use App\Enums\AgeDivision;
use App\Enums\GenderCategory;
use App\Models\Sport;
use App\Models\SportCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SportCategory>
 */
class SportCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $level = fake()->randomElement(AgeDivision::cases());
        $sex = fake()->randomElement([GenderCategory::Boys, GenderCategory::Girls]);

        return [
            'sport_id' => Sport::factory(),
            'level' => $level,
            'sex' => $sex,
            'display_name' => "{$level->label()} {$sex->label()}",
            'active' => true,
        ];
    }
}
