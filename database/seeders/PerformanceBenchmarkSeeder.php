<?php

namespace Database\Seeders;

use App\Enums\AgeDivision;
use App\Enums\DelegationStatus;
use App\Enums\EntryStatus;
use App\Enums\GenderCategory;
use App\Enums\MeetStatus;
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
use App\Models\ResultPlacement;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * WP-06-04: a realistic full-scale dataset for query/page-performance
 * profiling — distinct from `SampleProvinceDemoSeeder`, which is
 * deliberately kept small so its own "eyeball the Division feature"
 * demonstration stays easy to read. Not registered in `DatabaseSeeder`'s
 * default chain: run on demand only
 * (`php artisan db:seed --class=PerformanceBenchmarkSeeder`), since ~1,300
 * athletes would defeat that seeder's purpose if merged into the normal
 * chain and would slow every default `db:seed`/test setup for no reason.
 *
 * Unlike the other `Sample*` seeders, this one attaches its delegations to
 * the real 11 Davao de Oro municipalities (`DivisionRegistrySeeder`)
 * instead of inventing its own — the whole point is realistic query shape
 * and volume against what this deployment will actually have on meet day,
 * not an isolated demo. Schools/athletes/the meet itself are still
 * "Sample "-prefixed so this benchmark data is always easy to identify and
 * never mistaken for real SDO data.
 */
class PerformanceBenchmarkSeeder extends Seeder
{
    private const int SCHOOLS_PER_MUNICIPALITY = 8;

    private const int ATHLETES_PER_SCHOOL = 15;

    private int $schoolCodeSequence = 950101;

    private int $lrnSequence = 950000000001;

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        // Both idempotent (firstOrCreate) — safe to call even if already run.
        $this->call(DivisionRegistrySeeder::class);
        $this->call(SportsCatalogSeeder::class);

        $admin = User::query()->where('role', UserRole::Admin->value)->first();
        $meet = $this->meet();

        $individualEvents = Event::query()
            ->whereHas('sport', fn ($sport) => $sport->where('name', 'Athletics'))
            ->where('is_team_event', false)
            ->get();

        $meet->events()->syncWithoutDetaching($individualEvents->pluck('id'));

        /** @var Collection<string, Collection<int, Event>> $eventsByGenderAndDivision */
        $eventsByGenderAndDivision = $individualEvents->groupBy(
            fn (Event $event): string => $event->gender->value.'|'.$event->age_division->value,
        );

        /** @var array<int, array<int, Entry>> $entriesByEvent */
        $entriesByEvent = [];

        // Only the real municipalities (`DivisionRegistrySeeder`) — every
        // other Sample* seeder's own districts (e.g. `SampleRegistrySeeder`'s
        // "Sample District — East", `SampleProvinceDemoSeeder`'s
        // "Sample Municipality — Alpha") are also `active`, so they'd
        // otherwise be picked up here too and this benchmark would attach
        // delegations to demo data it was never meant to touch.
        $districts = District::query()
            ->where('active', true)
            ->where('name', 'not like', 'Sample %')
            ->orderBy('name')
            ->get();

        foreach ($districts as $district) {
            $delegation = $this->delegation($meet, $district);

            for ($schoolIndex = 1; $schoolIndex <= self::SCHOOLS_PER_MUNICIPALITY; $schoolIndex++) {
                $level = $schoolIndex % 2 === 0 ? SchoolLevel::Elementary : SchoolLevel::Secondary;
                $ageDivision = $level === SchoolLevel::Elementary ? AgeDivision::Elementary : AgeDivision::Secondary;
                $school = $this->school($district, $schoolIndex, $level);

                for ($athleteIndex = 1; $athleteIndex <= self::ATHLETES_PER_SCHOOL; $athleteIndex++) {
                    $sex = $athleteIndex % 2 === 0 ? Sex::Female : Sex::Male;
                    $gender = $sex === Sex::Male ? GenderCategory::Boys : GenderCategory::Girls;

                    $athlete = $this->athlete($delegation, $school, $sex, $athleteIndex);

                    $bucket = $eventsByGenderAndDivision->get($gender->value.'|'.$ageDivision->value);

                    foreach (($bucket ?? collect())->take(2) as $event) {
                        $entry = $this->confirmedEntry($delegation, $athlete, $event);
                        $entriesByEvent[$event->id][] = $entry;
                    }
                }
            }
        }

        foreach ($individualEvents as $event) {
            $entries = $entriesByEvent[$event->id] ?? [];

            if (count($entries) < 3) {
                continue;
            }

            $placements = collect($entries)->shuffle()->take(min(count($entries), 10))->values();

            $rows = $placements
                ->map(fn (Entry $entry, int $i): array => [
                    $entry,
                    $i + 1,
                    sprintf('%d.%02ds', 10 + intdiv($i, 2), random_int(0, 99)),
                ])
                ->all();

            $this->validatedResult($meet, $event, $admin, $rows);
        }
    }

    private function meet(): Meet
    {
        $meet = Meet::query()->firstOrCreate(
            ['name' => 'Sample Performance Benchmark Meet'],
            [
                'school_year' => '2025-2026',
                'starts_at' => Carbon::now()->subDay()->toDateString(),
                'ends_at' => Carbon::now()->addDays(2)->toDateString(),
                'venue' => 'Sample Provincial Sports Complex (Benchmark)',
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

    private function delegation(Meet $meet, District $district): Delegation
    {
        $delegation = Delegation::query()->firstOrCreate(
            ['meet_id' => $meet->id, 'district_id' => $district->id],
            [
                'head_name' => "Sample {$district->name} Benchmark Coordinator",
                'head_phone' => '09170000000',
                'head_email' => 'sample-benchmark@example.test',
            ],
        );

        if ($delegation->status !== DelegationStatus::Approved) {
            $delegation->forceFill(['status' => DelegationStatus::Approved])->save();
        }

        return $delegation;
    }

    private function school(District $district, int $index, SchoolLevel $level): School
    {
        return School::query()->firstOrCreate(
            ['district_id' => $district->id, 'name' => "Sample {$district->name} Benchmark School {$index}"],
            [
                'school_id_code' => (string) $this->schoolCodeSequence++,
                'level' => $level,
                'address' => 'Sample address (performance benchmark data)',
            ],
        );
    }

    private function athlete(Delegation $delegation, School $school, Sex $sex, int $index): Athlete
    {
        return Athlete::query()->firstOrCreate(
            [
                'delegation_id' => $delegation->id,
                'school_id' => $school->id,
                'first_name' => 'Sample',
                'last_name' => "Athlete {$school->name} #{$index}",
            ],
            [
                'sex' => $sex->value,
                'birthdate' => Carbon::now()->subYears(14 + ($index % 4))->toDateString(),
                'lrn' => (string) $this->lrnSequence++,
                'grade_level' => 7 + ($index % 6),
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
