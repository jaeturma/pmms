<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);
        $this->call(DivisionRegistrySeeder::class);
        $this->call(SportsCatalogSeeder::class);

        $hasTestUser = User::query()->where('email', 'test@example.com')->exists();

        if (! $hasTestUser && app()->environment(['local', 'testing'])) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }

        // Ddopaa2026ShowcaseSeeder guards itself to local/testing, so it's
        // always safe to call — a production run no-ops.
        $this->call(Ddopaa2026ShowcaseSeeder::class);
    }
}
