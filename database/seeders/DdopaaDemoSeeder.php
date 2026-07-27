<?php

namespace Database\Seeders;

use App\Enums\AgeDivision;
use App\Enums\DelegationStatus;
use App\Enums\EntryStatus;
use App\Enums\GenderCategory;
use App\Enums\SchoolLevel;
use App\Enums\Sex;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\District;
use App\Models\Entry;
use App\Models\Event;
use App\Models\Meet;
use App\Models\School;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * DdOPAA 2025 Reference Dataset — WP5: Demo tier.
 *
 * Local development and testing only. The "quick to eyeball" tier —
 * calls WP1 (`DdopaaReferenceSeeder`, already idempotent) to guarantee
 * the meet/venues/catalog exist, then adds a handful of DdOPAA-flavored
 * delegations/schools/athletes/entries: 3 municipalities × 6 athletes
 * (18 total), nowhere near WP2's `DdopaaStandardSeeder` 500+ athlete
 * volume. Every record `SYNTHETIC_DERIVED`/`SYNTHETIC_DEMO`, same as
 * WP2 — no school name or athlete name here is real.
 *
 * Uses its own LRN/school-code range (942xxx) distinct from WP2's
 * (941xxx) and WP-06-04's `PerformanceBenchmarkSeeder` (950xxx), so
 * running this tier and later also running the standard or load-test
 * tier in the same database never collides — each tier is purely
 * additive, never overwrites another's rows (`firstOrCreate` throughout,
 * matching every other seeder in this initiative).
 */
class DdopaaDemoSeeder extends Seeder
{
    /** The 3 of DdOPAA's 11 municipalities with a corroborated nickname/result (source register rows 4–6) — most recognizable for a quick walkthrough. */
    private const array MUNICIPALITIES = ['Nabunturan', 'Montevista', 'New Bataan'];

    private const int ATHLETES_PER_MUNICIPALITY = 6;

    private const array MALE_FIRST_NAMES = ['Juan', 'Miguel', 'Rafael', 'Emmanuel'];

    private const array FEMALE_FIRST_NAMES = ['Maria', 'Grace', 'Angel', 'Kate'];

    private const array LAST_NAMES = ['Santos', 'Reyes', 'Cruz', 'Bautista'];

    private int $schoolCodeSequence = 942010001;

    private int $lrnSequence = 942000000001;

    /** @var array<int, array<int, int>> delegation_id => [event_id => count] this run. */
    private array $entryCounts = [];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $this->call(DdopaaReferenceSeeder::class);

        $meet = Meet::query()->where('name', 'DdOPAA Meet 2025')->firstOrFail();
        $events = $meet->events()->where('age_division', AgeDivision::Secondary->value)->get();

        foreach (self::MUNICIPALITIES as $municipalityName) {
            $district = District::query()->where('name', $municipalityName)->firstOrFail();
            $delegation = $this->delegation($meet, $district);
            $school = $this->school($district);
            $this->entryCounts[$delegation->id] = $this->existingEntryCounts($delegation);

            foreach (range(1, self::ATHLETES_PER_MUNICIPALITY) as $athleteIndex) {
                $athlete = $this->athlete($delegation, $school, $athleteIndex);

                if (Entry::query()->where('athlete_id', $athlete->id)->exists()) {
                    // Same fix as WP2's DdopaaStandardSeeder: never
                    // re-pick an already-entered athlete, or a later run
                    // (after the standard tier's WP4 step has added a
                    // new event) gives them a second entry instead of
                    // staying idempotent.
                    continue;
                }

                $event = $this->pickEvent($events, $athlete->sex, $delegation);

                if ($event !== null) {
                    $this->confirmedEntry($delegation, $athlete, $event);
                }
            }
        }
    }

    /**
     * @return array<int, int> event_id => count
     */
    private function existingEntryCounts(Delegation $delegation): array
    {
        return Entry::query()
            ->where('delegation_id', $delegation->id)
            ->selectRaw('event_id, COUNT(*) as entry_count')
            ->groupBy('event_id')
            ->pluck('entry_count', 'event_id')
            ->all();
    }

    private function delegation(Meet $meet, District $district): Delegation
    {
        $slug = str($district->name)->slug()->toString();

        $delegation = Delegation::query()->firstOrCreate(
            ['meet_id' => $meet->id, 'district_id' => $district->id],
            [
                'head_name' => "{$district->name} DdOPAA Coordinator",
                'head_phone' => '09170000000',
                'head_email' => "ddopaa-demo-{$slug}@example.test",
            ],
        );

        if ($delegation->status !== DelegationStatus::Approved) {
            $delegation->forceFill(['status' => DelegationStatus::Approved])->save();
        }

        return $delegation;
    }

    private function school(District $district): School
    {
        return School::query()->firstOrCreate(
            ['district_id' => $district->id, 'name' => "{$district->name} National High School (Demo)"],
            [
                'school_id_code' => (string) $this->schoolCodeSequence++,
                'level' => SchoolLevel::Secondary,
                'address' => "{$district->name}, Davao de Oro (demonstration data)",
            ],
        );
    }

    private function athlete(Delegation $delegation, School $school, int $index): Athlete
    {
        $seed = $this->lrnSequence;
        $sex = $seed % 2 === 0 ? Sex::Male : Sex::Female;
        $firstNames = $sex === Sex::Male ? self::MALE_FIRST_NAMES : self::FEMALE_FIRST_NAMES;
        $firstName = $firstNames[$index % count($firstNames)];
        $lastName = self::LAST_NAMES[$index % count(self::LAST_NAMES)];
        $gradeLevel = 7 + ($index % 4);
        $lrn = (string) $this->lrnSequence++;

        return Athlete::query()->firstOrCreate(
            ['lrn' => $lrn],
            [
                'delegation_id' => $delegation->id,
                'school_id' => $school->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'sex' => $sex->value,
                'birthdate' => Carbon::now()->subYears($gradeLevel + 6)->toDateString(),
                'grade_level' => $gradeLevel,
            ],
        );
    }

    /**
     * @param  Collection<int, Event>  $events
     */
    private function pickEvent(Collection $events, Sex $sex, Delegation $delegation): ?Event
    {
        $gender = $sex === Sex::Male ? GenderCategory::Boys : GenderCategory::Girls;

        foreach ($events->where('gender', $gender) as $event) {
            $count = $this->entryCounts[$delegation->id][$event->id] ?? 0;

            if ($count < $event->max_entries_per_delegation) {
                return $event;
            }
        }

        return null;
    }

    private function confirmedEntry(Delegation $delegation, Athlete $athlete, Event $event): void
    {
        $entry = Entry::query()->firstOrCreate(
            ['athlete_id' => $athlete->id, 'event_id' => $event->id],
            ['delegation_id' => $delegation->id],
        );

        if ($entry->status !== EntryStatus::Confirmed) {
            $entry->forceFill(['status' => EntryStatus::Confirmed])->save();
        }

        $this->entryCounts[$delegation->id][$event->id] = ($this->entryCounts[$delegation->id][$event->id] ?? 0) + 1;
    }
}
