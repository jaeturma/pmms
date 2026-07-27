<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * DdOPAA 2025 Reference Dataset — WP5: Standard tier.
 *
 * Local development and testing only. A single, clearly-named command
 * for the initiative's full realistic dataset — chains WP1–WP4 in their
 * required order via `$this->call()`, exactly the same pattern
 * `DatabaseSeeder` itself already uses. Each sub-seeder is independently
 * idempotent and already guards itself with the same
 * `if (! app()->environment(['local', 'testing'])) return;` check, so
 * running this — or any of its four steps individually, or re-running
 * this seeder again later — is always safe and purely additive: never
 * `migrate:fresh`, never deletes or overwrites existing rows.
 *
 * Not to be confused with `DdopaaStandardSeeder` (WP2 alone — just the
 * delegation/school/athlete/personnel volume); this class is the
 * "standard tier" the request's own demo/standard/load-test framing
 * (Part 12) asks for, i.e. WP1 + WP2 + WP3 + WP4 together.
 */
class DdopaaStandardTierSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $this->call(DdopaaReferenceSeeder::class);
        $this->call(DdopaaStandardSeeder::class);
        $this->call(DdopaaResultsSeeder::class);
        $this->call(DdopaaLiveScoringSeeder::class);
    }
}
