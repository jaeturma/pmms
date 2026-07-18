<?php

namespace Database\Seeders;

use App\Enums\SchoolLevel;
use App\Models\District;
use App\Models\School;
use Illuminate\Database\Seeder;

class SampleRegistrySeeder extends Seeder
{
    /**
     * Seed clearly-labeled sample reference data.
     *
     * Local development and testing only — every record is prefixed with
     * "Sample" so it can never be mistaken for real SDO reference data.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $districts = [
            'Sample District — East' => [
                ['Sample East Central Elementary School', SchoolLevel::Elementary],
                ['Sample East National High School', SchoolLevel::Secondary],
            ],
            'Sample District — West' => [
                ['Sample West Central Elementary School', SchoolLevel::Elementary],
                ['Sample West Integrated School', SchoolLevel::Integrated],
            ],
        ];

        $code = 900001;

        foreach ($districts as $districtName => $schools) {
            $district = District::query()->firstOrCreate(['name' => $districtName]);

            foreach ($schools as [$schoolName, $level]) {
                School::query()->firstOrCreate(
                    ['district_id' => $district->id, 'name' => $schoolName],
                    [
                        'school_id_code' => (string) $code++,
                        'level' => $level,
                        'address' => 'Sample address (demonstration data)',
                    ],
                );
            }
        }
    }
}
