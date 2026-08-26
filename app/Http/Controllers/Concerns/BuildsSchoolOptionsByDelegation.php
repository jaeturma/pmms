<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Delegation;
use App\Models\School;
use Illuminate\Support\Collection;

/**
 * For each delegation, the schools a registered member may be attributed
 * to: exactly the delegation's own school (City), or every active school
 * in its municipality (Province) — never a school outside where the
 * delegation actually registered. Shared by AthleteController and
 * PersonnelController.
 */
trait BuildsSchoolOptionsByDelegation
{
    /**
     * @param  Collection<int, Delegation>  $delegations
     * @return array<int, array<int, array{id: int, name: string}>>
     */
    protected function schoolOptionsByDelegation(Collection $delegations): array
    {
        $schools = School::query()
            ->where('active', true)
            ->orderBy('name')
            ->with(['district:id,name', 'schoolDistrict:id,name'])
            ->get(['id', 'name', 'school_id_code', 'district_id', 'school_district_id']);

        return $delegations
            ->mapWithKeys(function (Delegation $delegation) use ($schools): array {
                return [
                    $delegation->id => $schools
                        ->map(fn (School $school): array => [
                            'id' => $school->id,
                            'name' => $school->name,
                            'school_id_code' => $school->school_id_code,
                            'district_id' => $school->district_id,
                            'district' => $school->district?->name ?? '',
                            'school_district_id' => $school->school_district_id,
                            'school_district' => $school->schoolDistrict?->name ?? $school->district?->name ?? '',
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->all();
    }
}
