<?php

namespace Database\Seeders;

use App\Enums\AgeDivision;
use App\Enums\GenderCategory;
use App\Models\Event;
use App\Models\Sport;
use Illuminate\Database\Seeder;

class SportsCatalogSeeder extends Seeder
{
    /**
     * Seed the common provincial-meet sports catalog.
     *
     * Genuine reference configuration (not sample data): the sports usually
     * played at a DepEd provincial meet, plus standard athletics track
     * events as a starting event set. Idempotent — safe to re-run; the SDO
     * adjusts the catalog through the UI afterwards.
     */
    public function run(): void
    {
        $sports = [
            'Arnis',
            'Athletics',
            'Badminton',
            'Baseball',
            'Basketball',
            'Chess',
            'Football',
            'Gymnastics',
            'Sepak Takraw',
            'Softball',
            'Swimming',
            'Table Tennis',
            'Taekwondo',
            'Volleyball',
        ];

        foreach ($sports as $name) {
            Sport::query()->firstOrCreate(['name' => $name]);
        }

        $athletics = Sport::query()->where('name', 'Athletics')->firstOrFail();

        $trackEvents = [
            ['100 Meter Dash', false, 2],
            ['200 Meter Dash', false, 2],
            ['400 Meter Dash', false, 2],
            ['4x100 Meter Relay', true, 1],
        ];

        foreach ($trackEvents as [$name, $team, $maxEntries]) {
            foreach (GenderCategory::cases() as $gender) {
                if ($gender === GenderCategory::Mixed) {
                    continue;
                }

                foreach (AgeDivision::cases() as $division) {
                    Event::query()->firstOrCreate(
                        [
                            'sport_id' => $athletics->id,
                            'name' => $name,
                            'gender' => $gender,
                            'age_division' => $division,
                        ],
                        [
                            'is_team_event' => $team,
                            'max_entries_per_delegation' => $maxEntries,
                        ],
                    );
                }
            }
        }
    }
}
