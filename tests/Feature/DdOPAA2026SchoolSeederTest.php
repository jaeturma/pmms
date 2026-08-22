<?php

use App\Models\District;
use App\Models\School;
use App\Models\SchoolDistrict;
use Database\Seeders\DdOPAA2026SchoolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('production school seeder imports all approved schools without inferred locations', function () {
    $this->seed(DdOPAA2026SchoolSeeder::class);

    expect(School::query()->count())->toBe(461)
        ->and(School::query()->where('school_type', 'Public')->count())->toBe(420)
        ->and(School::query()->where('school_type', 'Private')->count())->toBe(41)
        ->and(School::query()->whereNull('district_id')->count())->toBe(461)
        ->and(School::query()->whereNull('school_district_id')->count())->toBe(461)
        ->and(School::query()->whereNull('level')->count())->toBe(461)
        ->and(School::query()->distinct()->count('school_id_code'))->toBe(461);
});

test('school seeding is idempotent and preserves user-maintained assignments', function () {
    $this->seed(DdOPAA2026SchoolSeeder::class);

    $municipality = District::factory()->create();
    $schoolDistrict = SchoolDistrict::factory()->create(['district_id' => $municipality->id]);
    $school = School::query()->where('school_id_code', '464059')->sole();
    $school->forceFill([
        'district_id' => $municipality->id,
        'school_district_id' => $schoolDistrict->id,
        'address' => 'Verified local address',
        'active' => false,
    ])->save();

    $this->seed(DdOPAA2026SchoolSeeder::class);

    expect(School::query()->count())->toBe(461)
        ->and($school->fresh()->district_id)->toBe($municipality->id)
        ->and($school->fresh()->school_district_id)->toBe($schoolDistrict->id)
        ->and($school->fresh()->address)->toBe('Verified local address')
        ->and($school->fresh()->active)->toBeFalse();
});
