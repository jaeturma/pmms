<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\DistrictSportsCoordinatorAssignment;
use App\Models\Meet;
use App\Models\Person;
use App\Models\SchoolDistrict;
use App\Services\DdOPAA2026Source;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DdOPAA2026DSCSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $source = app(DdOPAA2026Source::class);
            $meet = Meet::query()->where('name', 'DdOPAA Meet 2026')->firstOrFail();
            $municipalities = [];
            foreach ($source->municipalities() as $row) {
                $municipalities[$row['code']] = District::query()->firstOrCreate(['name' => $row['name']]);
            }
            $schoolDistricts = [];
            foreach ($source->schoolDistricts() as $row) {
                $municipality = $municipalities[$row['municipality']] ?? null;
                if ($municipality) {
                    $schoolDistricts[$row['code']] = SchoolDistrict::query()->firstOrCreate(['district_id' => $municipality->id, 'name' => $row['name']], ['nickname' => $row['code']]);
                }
            }
            foreach ($source->dscAssignments() as $row) {
                $municipality = $municipalities[$row['municipality']] ?? null;
                $schoolDistrict = $schoolDistricts[$row['school_district']] ?? null;
                $person = Person::query()->where('source_key', $row['person'])->first();
                if ($municipality && $schoolDistrict && $person) {
                    DistrictSportsCoordinatorAssignment::query()->updateOrCreate(
                        ['meet_id' => $meet->id, 'school_district_id' => $schoolDistrict->id, 'person_id' => $person->id],
                        ['district_id' => $municipality->id, 'is_lead' => true, 'status' => 'active']
                    );
                }
            }
        });
    }
}
