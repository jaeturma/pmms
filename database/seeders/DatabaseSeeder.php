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
        if (app()->environment('local') && blank(config('pmms.accounts.default_reset_password'))) {
            config()->set('pmms.accounts.default_reset_password', 'DdOPAA26!');
            $this->command?->warn(
                'PMMS_DEFAULT_RESET_PASSWORD is not set; using the explicitly approved local initial password DdOPAA26!.',
            );
        }

        if (app()->environment('local') && blank(config('pmms.admin.password'))) {
            config()->set('pmms.admin.password', 'DdOPAA26!');
        }

        $this->call(DivisionRegistrySeeder::class);
        $this->call(DdOPAA2026SchoolSeeder::class);
        $this->call(SportsCatalogSeeder::class);

        $this->call(DdOPAA2026FinalSeeder::class);
        $this->call(AdminUserSeeder::class);
        $this->call(DdOPAA2026UserProvisioningSeeder::class);

    }
}
