<?php

namespace Database\Seeders;

use App\Enums\DelegationStatus;
use App\Models\Delegation;
use App\Models\District;
use App\Models\Meet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DdOPAA2026DelegationSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $meet = Meet::query()->where('name', 'DdOPAA Meet 2026')->firstOrFail();

            District::query()->where('active', true)->orderBy('name')->each(
                fn (District $municipality) => Delegation::query()->firstOrCreate(
                    ['meet_id' => $meet->id, 'district_id' => $municipality->id],
                    [
                        'school_id' => null,
                        'head_name' => 'To be designated',
                        'status' => DelegationStatus::Draft,
                    ],
                ),
            );
        });
    }
}
