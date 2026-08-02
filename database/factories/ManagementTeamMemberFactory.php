<?php

namespace Database\Factories;

use App\Enums\ManagementTeamMemberStatus;
use App\Models\ManagementTeam;
use App\Models\ManagementTeamMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ManagementTeamMember>
 */
class ManagementTeamMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'management_team_id' => ManagementTeam::factory(),
            'user_id' => User::factory(),
            'is_head' => false,
            'status' => ManagementTeamMemberStatus::Pending,
        ];
    }
}
