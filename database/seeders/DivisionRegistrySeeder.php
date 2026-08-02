<?php

namespace Database\Seeders;

use App\Enums\DivisionType;
use App\Models\CongressionalDistrict;
use App\Models\District;
use App\Models\Division;
use App\Models\SchoolDistrict;
use Illuminate\Database\Seeder;

/**
 * Seeds the real default division configuration for this deployment: a
 * Province division (Davao de Oro) with its 11 municipalities as District
 * rows — see docs/division.md. This is genuine reference configuration
 * (not sample/demo data), so it runs in every environment and is
 * idempotent so re-seeding is safe. Municipality creation is create-only
 * (`firstOrCreate`, never un-archives one an admin deactivated), but
 * nickname/congressional_district are authoritative confirmed facts, so
 * they're synced on every run rather than seeded once and left for manual
 * editing.
 */
class DivisionRegistrySeeder extends Seeder
{
    /**
     * Real DepEd school districts, seeded only for municipalities that
     * actually split into more than one — a municipality with a single
     * district (e.g. Mabini, Mawab, Montevista, New Bataan) gets no
     * SchoolDistrict row at all, and standings fall back to the
     * municipality's own name (see District::schoolDistricts()'s
     * docblock).
     *
     * @var array<string, list<string>>
     */
    private const SCHOOL_DISTRICTS = [
        'Compostela' => ['Compostela East', 'Compostela West'],
        'Laak' => ['Laak North', 'Laak South'],
        'Maco' => ['Maco North', 'Maco South'],
        'Maragusan' => ['Maragusan East', 'Maragusan West'],
        'Monkayo' => ['Monkayo East', 'Monkayo West'],
        'Nabunturan' => ['Nabunturan East', 'Nabunturan West'],
        'Pantukan' => ['Pantukan North', 'Pantukan South'],
    ];

    /**
     * Municipality name => [delegation nickname, congressional district].
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const MUNICIPALITIES = [
        'Compostela' => ['Level-up Warriors', 'First'],
        'Laak' => ['Mighty Bamboo', 'Second'],
        'Mabini' => ['Gulf Jewels', 'Second'],
        'Maco' => ['Power Voltz', 'Second'],
        'Maragusan' => ['Maroon Knights', 'First'],
        'Mawab' => ['Pick Hammer', 'Second'],
        'Monkayo' => ['Spiders', 'First'],
        'Montevista' => ['Blazing Fighters', 'First'],
        'Nabunturan' => ['Black Mamba', 'Second'],
        'New Bataan' => ['Rock Wreckers', 'First'],
        'Pantukan' => ['Blue Phoenix', 'Second'],
    ];

    public function run(): void
    {
        Division::query()->firstOrCreate([], [
            'type' => DivisionType::Province,
            'name' => 'Davao de Oro',
        ]);

        // Keyed by the same "First"/"Second" strings used in MUNICIPALITIES
        // below, so each municipality's congressional_district_id backfill
        // is a plain lookup — see
        // docs/architecture/pmms-data-migration-plan.md §5.
        $congressionalDistricts = collect(['First', 'Second'])
            ->mapWithKeys(fn (string $name): array => [
                $name => CongressionalDistrict::query()->firstOrCreate(['name' => $name], ['active' => true]),
            ]);

        foreach (self::MUNICIPALITIES as $name => [$nickname, $congressionalDistrict]) {
            $municipality = District::query()->firstOrCreate(
                ['name' => $name],
                ['active' => true],
            );

            $municipality->fill([
                'nickname' => $nickname,
                'congressional_district' => $congressionalDistrict,
                'congressional_district_id' => $congressionalDistricts[$congressionalDistrict]->id,
            ]);

            if ($municipality->isDirty()) {
                $municipality->save();
            }

            foreach (self::SCHOOL_DISTRICTS[$name] ?? [] as $schoolDistrictName) {
                SchoolDistrict::query()->firstOrCreate(
                    ['district_id' => $municipality->id, 'name' => $schoolDistrictName],
                    ['active' => true],
                );
            }
        }
    }
}
