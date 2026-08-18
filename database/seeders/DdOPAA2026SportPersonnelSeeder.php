<?php

namespace Database\Seeders;

use App\Models\AccountProvision;
use App\Models\District;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\Person;
use App\Models\SchoolDistrict;
use App\Services\DdOPAA2026Source;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DdOPAA2026SportPersonnelSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $source = app(DdOPAA2026Source::class);
            $meet = Meet::query()->where('name', 'DdOPAA Meet 2026')->firstOrFail();
            $meetSports = MeetSport::query()->with('sport')->where('meet_id', $meet->id)->get()->keyBy(fn (MeetSport $ms) => $ms->sport->code);
            $municipalities = District::query()->get()->keyBy(fn (District $district) => Str::upper($district->name));
            $schoolDistricts = SchoolDistrict::query()->get()->keyBy(fn (SchoolDistrict $district) => Str::upper($district->nickname ?: Str::slug($district->name, '_')));

            foreach ($source->sportPersonnelAssignments() as $row) {
                // The source explicitly marks this combined header as needing confirmation.
                if ($row['sport'] === 'WEIGHTLIFTING_KICKBOXING' || ! isset($meetSports[$row['sport']])) {
                    continue;
                }
                $person = Person::query()->where('source_key', $row['person'])->firstOrFail();
                $district = $row['municipality'] ? ($municipalities[$row['municipality']] ?? null) : null;
                $schoolDistrict = $row['school_district'] ? ($schoolDistricts[$row['school_district']] ?? null) : null;

                foreach ($this->roles($row['role_code']) as $role) {
                    MeetSportAssignment::query()->updateOrCreate([
                        'meet_sport_id' => $meetSports[$row['sport']]->id,
                        'person_id' => $person->id,
                        'role' => $role,
                        'source_sequence' => $row['sequence'],
                    ], [
                        'user_id' => $person->user_id,
                        'original_designation' => $row['role_label'],
                        'assignment_scope' => $row['scope'] ?: ($row['role_code'] === 'ASSISTANT_TM_TRACK' ? 'TRACK' : null),
                        'source_district_text' => $row['district_text'],
                        'district_id' => $district?->id,
                        'school_district_id' => $schoolDistrict?->id,
                        'requires_system_user' => true,
                        'is_lead' => str_starts_with($row['role_code'], 'TOURNAMENT_MANAGER'),
                        'status' => 'pending',
                    ]);
                }

                AccountProvision::query()->updateOrCreate(['person_id' => $person->id], [
                    'suggested_username' => Str::lower(str_replace('_', '.', $person->source_key)),
                    'target_role' => str_contains($row['role_code'], 'TOURNAMENT_MANAGER') ? 'tournament_manager' : 'technical_official',
                    'status' => $person->user_id ? 'linked' : 'pending',
                    'reason' => 'DdOPAA 2026 final TM/TO worksheet assignment',
                ]);
            }
        });
    }

    /** @return list<string> */
    private function roles(string $sourceRole): array
    {
        return match ($sourceRole) {
            'TOURNAMENT_MANAGER' => ['tournament_manager'],
            'TOURNAMENT_MANAGER_TRACK' => ['track_tournament_manager'],
            'TOURNAMENT_MANAGER_FIELD' => ['field_tournament_manager'],
            'ASSISTANT_TOURNAMENT_MANAGER', 'ASSISTANT_TM_TRACK' => ['assistant_tournament_manager'],
            'TOURNAMENT_SECRETARY' => ['tournament_secretary'],
            'TOURNAMENT_SECRETARY_ICT' => ['tournament_secretary', 'tournament_ict'],
            'TOURNAMENT_ICT_TECHNICAL_OFFICIAL' => ['tournament_ict', 'technical_official'],
            default => ['technical_official'],
        };
    }
}
