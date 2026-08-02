<?php

namespace Database\Factories;

use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\Sport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeetSport>
 */
class MeetSportFactory extends Factory
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
            'sport_id' => Sport::factory(),
            'active' => true,
        ];
    }
}
