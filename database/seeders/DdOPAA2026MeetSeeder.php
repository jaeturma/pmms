<?php

namespace Database\Seeders;

use App\Enums\AgeDivision;
use App\Enums\GenderCategory;
use App\Models\Event;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\Sport;
use App\Services\DdOPAA2026Source;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DdOPAA2026MeetSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $meet = Meet::query()->firstOrCreate(['name' => 'DdOPAA Meet 2026'], [
                'school_year' => '2026-2027', 'starts_at' => '2026-01-01', 'ends_at' => '2026-01-05', 'venue' => 'Davao de Oro',
            ]);
            $meet->forceFill(['status' => 'active', 'is_active' => true, 'is_published' => true])->save();
            foreach (app(DdOPAA2026Source::class)->sports() as $row) {
                if ($row['code'] === 'WEIGHTLIFTING_KICKBOXING') {
                    continue;
                }
                $sport = Sport::query()
                    ->where('code', $row['code'])
                    ->orWhere('name', $row['name'])
                    ->first() ?? new Sport;
                $sport->fill([
                    'code' => $row['code'], 'name' => $row['name'], 'slug' => Str::slug($row['name']),
                    'classification' => $row['classification'], 'active' => true, 'display_order' => $row['order'],
                ])->save();
                MeetSport::query()->updateOrCreate(['meet_id' => $meet->id, 'sport_id' => $sport->id], ['active' => true, 'display_order' => $row['order']]);
            }

            $basketball = Sport::query()->where('name', 'Basketball')->firstOrFail();
            $secondaryBoys = Event::query()->firstOrCreate(
                [
                    'sport_id' => $basketball->id,
                    'name' => 'Basketball',
                    'gender' => GenderCategory::Boys->value,
                    'age_division' => AgeDivision::Secondary->value,
                ],
                [
                    'is_team_event' => true,
                    'max_entries_per_delegation' => 12,
                ],
            );

            $meet->events()->syncWithoutDetaching([$secondaryBoys->id]);
        });
    }
}
