<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DdOPAA2026FinalSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DdOPAA2026MeetSeeder::class,
            DdOPAA2026DelegationSeeder::class,
            DdOPAA2026TWGSeeder::class,
            DdOPAA2026DSCSeeder::class,
            DdOPAA2026SportPersonnelSeeder::class,
            DdOPAA2026VenueSeeder::class,
            DdOPAA2026AccountProvisionSeeder::class,
        ]);
    }
}
