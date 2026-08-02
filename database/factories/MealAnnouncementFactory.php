<?php

namespace Database\Factories;

use App\Models\MealAnnouncement;
use App\Models\Meet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MealAnnouncement>
 */
class MealAnnouncementFactory extends Factory
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
            'title' => fake()->sentence(4),
            'message' => fake()->sentence(),
            'posted_by_user_id' => User::factory(),
        ];
    }
}
