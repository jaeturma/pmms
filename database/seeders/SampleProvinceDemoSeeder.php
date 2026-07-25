<?php

namespace Database\Seeders;

use App\Enums\AgeDivision;
use App\Enums\DelegationStatus;
use App\Enums\EntryStatus;
use App\Enums\GenderCategory;
use App\Enums\MeetStatus;
use App\Enums\PersonnelRole;
use App\Enums\ResultStatus;
use App\Enums\SchoolLevel;
use App\Enums\Sex;
use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\District;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventResult;
use App\Models\Meet;
use App\Models\Personnel;
use App\Models\ResultPlacement;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Demonstrates the Province division's municipal-delegation feature end to
 * end: a delegation registered by municipality (not school), pooling
 * athletes from several schools, whose medals still split correctly by
 * school in the tally and roll up into one municipality total.
 *
 * Local development and testing only — every record is prefixed "Sample"
 * so it can never be mistaken for real SDO data. These are separate rows
 * from the real 11 municipalities DivisionRegistrySeeder seeds — sample
 * schools are never attached to real reference data.
 */
class SampleProvinceDemoSeeder extends Seeder
{
    private int $schoolCodeSequence = 900101;

    private int $lrnSequence = 900000000001;

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $event = Event::query()
            ->where('name', '100 Meter Dash')
            ->where('gender', GenderCategory::Boys->value)
            ->where('age_division', AgeDivision::Secondary->value)
            ->first();

        if ($event === null) {
            // Sports catalog not seeded yet — nothing to demo results
            // against. SportsCatalogSeeder runs before this one in
            // DatabaseSeeder, so this should not happen in practice.
            return;
        }

        $admin = User::query()->where('role', UserRole::Admin->value)->first();

        $meet = $this->meet();
        $meet->events()->syncWithoutDetaching([$event->id]);

        $alpha = $this->municipality('Sample Municipality — Alpha', 'Falcons', [
            'Sample Alpha Central School',
            'Sample Alpha North School',
        ]);
        $bravo = $this->municipality('Sample Municipality — Bravo', 'Sharks', [
            'Sample Bravo Central School',
        ]);

        $delegationAlpha = $this->delegation($meet, $alpha['district']);
        $delegationBravo = $this->delegation($meet, $bravo['district']);

        $this->coach($delegationAlpha, $alpha['schools'][0]);
        $this->coach($delegationBravo, $bravo['schools'][0]);

        // Two schools pooled under the same municipal delegation — the
        // scenario this whole seeder exists to demonstrate.
        $athleteAlphaCentral = $this->athlete($delegationAlpha, $alpha['schools'][0], 'Rico', 'Bautista');
        $athleteAlphaNorth = $this->athlete($delegationAlpha, $alpha['schools'][1], 'Emman', 'Tolentino');
        $athleteBravo = $this->athlete($delegationBravo, $bravo['schools'][0], 'Kier', 'Salvador');

        $entryAlphaCentral = $this->confirmedEntry($delegationAlpha, $athleteAlphaCentral, $event);
        $entryAlphaNorth = $this->confirmedEntry($delegationAlpha, $athleteAlphaNorth, $event);
        $entryBravo = $this->confirmedEntry($delegationBravo, $athleteBravo, $event);

        // Alpha Central gold, Alpha North silver — same municipal
        // delegation, two different schools, both medaling separately.
        // Bravo bronze — a second municipality on the board.
        $this->validatedResult($meet, $event, $admin, [
            [$entryAlphaCentral, 1, '11.42s'],
            [$entryAlphaNorth, 2, '11.58s'],
            [$entryBravo, 3, '11.71s'],
        ]);
    }

    private function meet(): Meet
    {
        // 'status' and 'is_published' are guarded (state transitions only
        // happen through Meet::forceFill(), mirroring MeetController) so
        // they cannot go in firstOrCreate()'s create array.
        $meet = Meet::query()->firstOrCreate(
            ['name' => 'Sample Provincial Meet'],
            [
                'school_year' => '2025-2026',
                'starts_at' => Carbon::now()->subDay()->toDateString(),
                'ends_at' => Carbon::now()->addDays(2)->toDateString(),
                'venue' => 'Sample Provincial Sports Complex',
            ],
        );

        if ($meet->status !== MeetStatus::Active || ! $meet->is_published) {
            $meet->forceFill([
                'status' => MeetStatus::Active,
                'is_published' => true,
            ])->save();
        }

        return $meet;
    }

    /**
     * @param  array<int, string>  $schoolNames
     * @return array{district: District, schools: array<int, School>}
     */
    private function municipality(string $name, string $nickname, array $schoolNames): array
    {
        $district = District::query()->firstOrCreate(
            ['name' => $name],
            ['nickname' => $nickname],
        );

        $schools = [];

        foreach ($schoolNames as $schoolName) {
            $schools[] = School::query()->firstOrCreate(
                ['district_id' => $district->id, 'name' => $schoolName],
                [
                    'school_id_code' => (string) $this->schoolCodeSequence++,
                    'level' => SchoolLevel::Secondary,
                    'address' => 'Sample address (demonstration data)',
                ],
            );
        }

        return ['district' => $district, 'schools' => $schools];
    }

    private function delegation(Meet $meet, District $district): Delegation
    {
        $delegation = Delegation::query()->firstOrCreate(
            ['meet_id' => $meet->id, 'district_id' => $district->id],
            [
                'head_name' => "Sample {$district->name} Coordinator",
                'head_phone' => '09170000000',
                'head_email' => 'sample-demo@example.test',
            ],
        );

        if ($delegation->status !== DelegationStatus::Approved) {
            $delegation->forceFill(['status' => DelegationStatus::Approved])->save();
        }

        return $delegation;
    }

    private function coach(Delegation $delegation, School $school): Personnel
    {
        return Personnel::query()->firstOrCreate(
            [
                'delegation_id' => $delegation->id,
                'first_name' => 'Sample',
                'last_name' => "Coach ({$school->name})",
            ],
            [
                'school_id' => $school->id,
                'role' => PersonnelRole::Coach->value,
            ],
        );
    }

    private function athlete(Delegation $delegation, School $school, string $firstName, string $lastName): Athlete
    {
        return Athlete::query()->firstOrCreate(
            [
                'delegation_id' => $delegation->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
            ],
            [
                'school_id' => $school->id,
                'sex' => Sex::Male->value,
                'birthdate' => Carbon::now()->subYears(16)->toDateString(),
                'lrn' => (string) $this->lrnSequence++,
                'grade_level' => 10,
            ],
        );
    }

    private function confirmedEntry(Delegation $delegation, Athlete $athlete, Event $event): Entry
    {
        $entry = Entry::query()->firstOrCreate(
            ['athlete_id' => $athlete->id, 'event_id' => $event->id],
            ['delegation_id' => $delegation->id],
        );

        if ($entry->status !== EntryStatus::Confirmed) {
            $entry->forceFill(['status' => EntryStatus::Confirmed])->save();
        }

        return $entry;
    }

    /**
     * @param  array<int, array{0: Entry, 1: int, 2: string}>  $placements
     */
    private function validatedResult(Meet $meet, Event $event, ?User $admin, array $placements): void
    {
        $result = EventResult::query()
            ->where('meet_id', $meet->id)
            ->where('event_id', $event->id)
            ->first();

        if ($result !== null && $result->status === ResultStatus::Validated) {
            return;
        }

        if ($result === null) {
            $result = new EventResult(['meet_id' => $meet->id, 'event_id' => $event->id]);
            $result->forceFill([
                'encoded_by' => $admin?->id,
                'encoded_at' => now(),
            ])->save();
        }

        foreach ($placements as [$entry, $rank, $mark]) {
            ResultPlacement::query()->firstOrCreate(
                ['event_result_id' => $result->id, 'entry_id' => $entry->id],
                ['rank' => $rank, 'mark' => $mark],
            );
        }

        $result->forceFill([
            'status' => ResultStatus::Validated,
            'validated_by' => $admin?->id,
            'validated_at' => now(),
        ])->save();
    }
}
