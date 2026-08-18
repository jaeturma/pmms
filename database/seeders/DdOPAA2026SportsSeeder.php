<?php

namespace Database\Seeders;

use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\Sport;
use Illuminate\Database\Seeder;

class DdOPAA2026SportsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SportEventsSeeder::class);
        $meet = Meet::query()->where('name', 'DdOPAA Meet 2026')->first();
        if ($meet === null) {
            $this->command?->warn('DdOPAA Meet 2026 does not exist; canonical sports were seeded but meet assignments were skipped.');

            return;
        }

        Sport::query()->orderBy('classification')->orderBy('display_order')->get()->each(function (Sport $sport) use ($meet): void {
            MeetSport::query()->updateOrCreate(
                ['meet_id' => $meet->id, 'sport_id' => $sport->id],
                ['status' => 'included', 'active' => true, 'display_order' => $sport->display_order],
            );
        });
    }
}
