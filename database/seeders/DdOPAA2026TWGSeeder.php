<?php

namespace Database\Seeders;

use App\Models\ManagementTeam;
use App\Models\ManagementTeamMember;
use App\Models\Meet;
use App\Models\Person;
use App\Services\DdOPAA2026Source;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DdOPAA2026TWGSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $source = app(DdOPAA2026Source::class);
            $meet = Meet::query()->where('name', 'DdOPAA Meet 2026')->firstOrFail();
            foreach ($source->people() as $row) {
                Person::query()->updateOrCreate(['source_key' => $row['key']], ['full_name' => $row['name'], 'normalized_name' => Str::upper(preg_replace('/[^A-Z0-9]+/i', ' ', $row['name'])), 'source_flags' => $row['flags']]);
            }
            foreach ($source->twgUnits() as $row) {
                ManagementTeam::query()->updateOrCreate(['meet_id' => $meet->id, 'source_code' => $row['code']], [
                    'team_type' => $this->teamType($row['code']), 'name' => $row['name'], 'description' => $row['description'], 'display_order' => $row['order'], 'status' => 'active',
                ]);
            }
            foreach ($source->twgMemberships() as $row) {
                $team = ManagementTeam::query()->where('meet_id', $meet->id)->where('source_code', $row['unit'])->firstOrFail();
                $person = Person::query()->where('source_key', $row['person'])->firstOrFail();
                ManagementTeamMember::query()->updateOrCreate(
                    ['management_team_id' => $team->id, 'person_id' => $person->id, 'role_title' => $row['title']],
                    ['source_sequence' => $row['sequence'], 'is_head' => $row['sequence'] === 1, 'status' => 'active']
                );
            }
        });
    }

    private function teamType(string $code): string
    {
        return match ($code) {
            'TOP_MANAGEMENT' => 'top_management', 'DSAC' => 'division_screening_and_accreditation',
            'MEDICAL' => 'medical', 'FOOD_MEALS', 'KITCHEN_PERSONNEL' => 'food', 'BILLETING' => 'billeting',
            'LOGISTICS' => 'supply', 'INCIDENT_COMMAND' => 'drrm', 'EVENT_SECRETARIAT' => 'results_committee',
            default => 'meet_management',
        };
    }
}
