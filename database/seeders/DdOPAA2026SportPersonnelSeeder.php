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
use App\Services\ProductionUsername;
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

            MeetSportAssignment::query()
                ->whereNotNull('person_id')
                ->orderBy('id')
                ->get()
                ->groupBy(fn (MeetSportAssignment $assignment): string => implode('|', [
                    $assignment->meet_sport_id,
                    $assignment->person_id,
                    $assignment->role->value,
                ]))
                ->each(fn ($duplicates) => $duplicates->slice(1)->each->delete());

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
                    $identity = [
                        'meet_sport_id' => $meetSports[$row['sport']]->id,
                        'role' => $role,
                    ];

                    // Once accounts have been provisioned, the original table's
                    // unique key is meet_sport_id/user_id/role. Match that key on
                    // reruns so we update the existing assignment instead of
                    // finding another row by person and colliding while saving.
                    if ($person->user_id !== null) {
                        $identity['user_id'] = $person->user_id;
                    } else {
                        $identity['person_id'] = $person->id;
                        $identity['source_sequence'] = $row['sequence'];
                    }

                    MeetSportAssignment::query()->updateOrCreate($identity, [
                        'person_id' => $person->id,
                        'user_id' => $person->user_id,
                        'source_sequence' => $row['sequence'],
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
                    'suggested_username' => app(ProductionUsername::class)->uniqueFor($person),
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
