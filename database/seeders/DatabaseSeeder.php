<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /** Seed only the production DdOPAA Meet 2026 dataset. */
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);
        $this->call(DivisionRegistrySeeder::class);
        $this->call(SportsCatalogSeeder::class);

        $this->call(DdOPAA2026FinalSeeder::class);

        // Ddopaa2026ShowcaseSeeder guards itself to local/testing, so it's
        // always safe to call — a production run no-ops.

        // RoleShowcaseSeeder needs Ddopaa2026ShowcaseSeeder's meet and
        // delegations; same local/testing self-guard, always safe to call.

        // Dedicated, single-sport Tournament ICT accounts for exercising
        // the Basketball, Baseball, and Boxing live scoreboards.

        // Player-attributed Basketball points/fouls and substitutions need
        // real confirmed entries plus a match roster for both sides.
    }
}
