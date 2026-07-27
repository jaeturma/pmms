<?php

namespace Database\Seeders;

use App\Enums\DivisionType;
use App\Models\District;
use App\Models\Division;
use App\Models\SchoolDistrict;
use Illuminate\Database\Seeder;

/**
 * Seeds the real default division configuration for this deployment: a
 * Province division (Davao de Oro) with its 11 municipalities as District
 * rows — see docs/division.md. This is genuine reference configuration
 * (not sample/demo data, unlike SampleRegistrySeeder), so it runs in every
 * environment and is idempotent (firstOrCreate) so re-seeding is safe.
 */
class DivisionRegistrySeeder extends Seeder
{
    /**
     * Real DepEd school districts, seeded only where actually known —
     * same "seeded where known, editable via the registry otherwise"
     * policy as the municipality nicknames below. Laak is the only
     * municipality with a confirmed multi-district split (North/South);
     * the rest are left for an admin to add via the school districts
     * registry rather than guessed at.
     *
     * @var array<string, list<string>>
     */
    private const SCHOOL_DISTRICTS = [
        'Laak' => ['Laak North', 'Laak South'],
    ];

    /**
     * Municipality name => delegation nickname (blank until confirmed).
     *
     * @var array<string, string|null>
     */
    private const MUNICIPALITIES = [
        'Compostela' => null,
        'Laak' => null,
        'Mabini' => null,
        'Maco' => 'Tigers',
        'Maragusan' => null,
        'Mawab' => null,
        'Monkayo' => null,
        'Montevista' => null,
        'Nabunturan' => null,
        'New Bataan' => null,
        'Pantukan' => null,
    ];

    public function run(): void
    {
        Division::query()->firstOrCreate([], [
            'type' => DivisionType::Province,
            'name' => 'Davao de Oro',
        ]);

        foreach (self::MUNICIPALITIES as $name => $nickname) {
            $municipality = District::query()->firstOrCreate(
                ['name' => $name],
                ['nickname' => $nickname, 'active' => true],
            );

            foreach (self::SCHOOL_DISTRICTS[$name] ?? [] as $schoolDistrictName) {
                SchoolDistrict::query()->firstOrCreate(
                    ['district_id' => $municipality->id, 'name' => $schoolDistrictName],
                    ['active' => true],
                );
            }
        }
    }
}
