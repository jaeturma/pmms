<?php

namespace Database\Seeders;

use App\Models\AccountProvision;
use App\Models\Person;
use App\Services\DdOPAA2026Source;
use App\Services\ProductionUsername;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DdOPAA2026AccountProvisionSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $usernames = app(ProductionUsername::class);
            foreach (app(DdOPAA2026Source::class)->accountProvisions() as $row) {
                $person = Person::query()->where('source_key', $row['person'])->first();

                if ($person === null) {
                    continue;
                }

                $provision = AccountProvision::query()->firstOrNew(['person_id' => $person->id]);
                $provision->suggested_username = $usernames->uniqueFor($person);
                $provision->target_role = $row['target_role'];
                $provision->reason = $row['reason'];

                if (! $provision->exists) {
                    $provision->status = 'pending';
                }

                $provision->save();
            }

            Person::query()
                ->where(fn ($query) => $query->whereHas('meetSportAssignments')->orWhereHas('managementTeamMemberships'))
                ->orderBy('id')
                ->each(function (Person $person) use ($usernames): void {
                    $provision = AccountProvision::query()->firstOrNew(['person_id' => $person->id]);
                    $provision->suggested_username = $usernames->uniqueFor($person);
                    $provision->target_role ??= $person->managementTeamMemberships()->exists() ? 'organizer' : 'technical_official';
                    $provision->reason ??= $person->managementTeamMemberships()->exists()
                        ? 'DdOPAA 2026 TWG management-team membership'
                        : 'DdOPAA 2026 sport assignment';
                    $provision->status ??= 'pending';
                    $provision->save();
                });
        });
    }
}
