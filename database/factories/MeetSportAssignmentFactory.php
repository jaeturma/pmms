<?php

namespace Database\Factories;

use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeetSportAssignment>
 */
class MeetSportAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meet_sport_id' => MeetSport::factory(),
            'user_id' => User::factory(),
            'role' => MeetSportAssignmentRole::TechnicalOfficial,
            'is_lead' => false,
            'status' => MeetSportAssignmentStatus::Pending,
        ];
    }
}
