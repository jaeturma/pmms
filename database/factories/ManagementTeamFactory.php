<?php

namespace Database\Factories;

use App\Enums\ManagementTeamStatus;
use App\Enums\ManagementTeamType;
use App\Models\ManagementTeam;
use App\Models\Meet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ManagementTeam>
 */
class ManagementTeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(ManagementTeamType::cases());

        return [
            'meet_id' => Meet::factory(),
            'team_type' => $type,
            'name' => $type->label(),
            'status' => ManagementTeamStatus::Forming,
        ];
    }
}
