<?php

namespace Database\Seeders;

use App\Enums\AgeDivision;
use App\Enums\DelegationStatus;
use App\Enums\EntryStatus;
use App\Enums\GenderCategory;
use App\Enums\PersonnelRole;
use App\Enums\SchoolLevel;
use App\Enums\Sex;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\District;
use App\Models\Entry;
use App\Models\Event;
use App\Models\Meet;
use App\Models\Personnel;
use App\Models\School;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * DdOPAA 2025 Reference Dataset — WP2: Standard Dataset (delegations,
 * schools, athletes, personnel, entries).
 *
 * Local development and testing only. Requires WP1
 * (`DdopaaReferenceSeeder`) to have already run — returns early,
 * idempotently, if "DdOPAA Meet 2025" doesn't exist yet. See
 * docs/phases/ddopaa-2025-reference-dataset/ for the full plan and
 * docs/data-reference/ddopaa-2025-source-register.md for what's actually
 * corroborated. Everything in this seeder is SYNTHETIC_DERIVED (schools,
 * the athlete roster shape, entry distribution) except the five
 * "guaranteed entry" delegation/event pairings in `guaranteedEntries()`,
 * which are PARTIALLY_VERIFIED per the source register — no school name
 * here is an actual verified roster, no athlete name is real (Part 7).
 *
 * Deterministic by design (index-based name/attribute selection, never
 * `random_int`) so re-running produces byte-identical rosters — this is
 * what makes it safely idempotent via `firstOrCreate`/unique LRNs, not a
 * database-level uniqueness trick alone.
 */
class DdopaaStandardSeeder extends Seeder
{
    /** Same order as DivisionRegistrySeeder's own municipality list. */
    private const array MUNICIPALITIES = [
        'Compostela', 'Laak', 'Mabini', 'Maco', 'Maragusan', 'Mawab',
        'Monkayo', 'Montevista', 'Nabunturan', 'New Bataan', 'Pantukan',
    ];

    /**
     * SYNTHETIC_DERIVED: 10–25 per municipality (the request's own
     * suggested range), aligned by index to MUNICIPALITIES. Sums to 177
     * schools; at 3 athletes/school that lands the roster at ~530,
     * within the requested 500–1,000 standard-tier target.
     */
    private const array SCHOOL_COUNTS = [16, 14, 18, 12, 22, 15, 17, 11, 20, 13, 19];

    private const int ATHLETES_PER_SCHOOL = 3;

    private const array MALE_FIRST_NAMES = [
        'Juan', 'Jose', 'Miguel', 'Gabriel', 'Rafael', 'Daniel', 'Emmanuel',
        'Joshua', 'Christian', 'Vincent', 'Angelo', 'Carlos', 'Mark', 'John',
        'Paulo', 'Ronnel', 'Kenneth', 'Ronan', 'Aljon', 'Jerico',
    ];

    private const array FEMALE_FIRST_NAMES = [
        'Maria', 'Ana', 'Grace', 'Angel', 'Princess', 'Nica', 'Kate', 'Faith',
        'Joy', 'Charmaine', 'Rosalie', 'Jasmine', 'Kristine', 'Angelica',
        'Michelle', 'Camille', 'Bea', 'Andrea', 'Erika', 'Trisha',
    ];

    private const array LAST_NAMES = [
        'Santos', 'Reyes', 'Cruz', 'Bautista', 'Garcia', 'Torres', 'Ramos',
        'Flores', 'Mendoza', 'Castillo', 'Villanueva', 'Aquino', 'Delos Reyes',
        'Gonzales', 'Rivera', 'Fernandez', 'Aguilar', 'Salazar', 'Domingo', 'Pascual',
    ];

    private int $schoolCodeSequence = 941010001;

    private int $lrnSequence = 941000000001;

    /**
     * delegation_id => [event_id => count]. Seeded from the DB at the
     * start of `run()`, then kept in sync as this run creates entries —
     * capacity decisions must reflect true cumulative state, not just
     * this run's additions, or a later run (e.g. after WP4 adds a new
     * event) mis-simulates capacity and drifts from what it created the
     * first time. Found seeding WP5's standard-tier command twice in a
     * row: a from-scratch-every-run simulation stayed correct only as
     * long as the event catalog never changed between runs.
     *
     * @var array<int, array<int, int>>
     */
    private array $entryCounts = [];

    /** @var array<int, Collection<int, Athlete>> delegation_id => that delegation's Secondary athletes, for guaranteedEntries(). */
    private array $secondaryAthletesByDelegation = [];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $meet = Meet::query()->where('name', 'DdOPAA Meet 2025')->first();

        if ($meet === null) {
            // WP1 (DdopaaReferenceSeeder) hasn't run yet — nothing to
            // attach delegations/entries to.
            return;
        }

        $ddopaaEvents = $meet->events()->where('age_division', AgeDivision::Secondary->value)->get();
        $athleticsElementary = Event::query()
            ->whereHas('sport', fn ($sport) => $sport->where('name', 'Athletics'))
            ->where('name', '100 Meter Dash')
            ->where('age_division', AgeDivision::Elementary->value)
            ->get();

        // The real EntryController::store() requires an event be attached
        // to the athlete's meet (`meet_events`) before it can be entered
        // — this seeder bypasses that controller, so it must mirror the
        // rule itself rather than leave elementary entries pointing at
        // events the meet never actually offers.
        $meet->events()->syncWithoutDetaching($athleticsElementary->pluck('id'));

        $this->entryCounts = $this->existingEntryCounts($meet);

        foreach (self::MUNICIPALITIES as $index => $municipalityName) {
            $district = District::query()->where('name', $municipalityName)->firstOrFail();
            $delegation = $this->delegation($meet, $district);
            $this->secondaryAthletesByDelegation[$delegation->id] = collect();

            $schools = collect(range(1, self::SCHOOL_COUNTS[$index]))
                ->map(fn (int $n) => $this->school($district, $n));

            $this->personnel($delegation, $schools->first());

            foreach ($schools as $school) {
                foreach (range(1, self::ATHLETES_PER_SCHOOL) as $athleteIndex) {
                    $athlete = $this->athlete($delegation, $school, $athleteIndex);

                    if ($athlete->ageDivision() === AgeDivision::Secondary) {
                        $this->secondaryAthletesByDelegation[$delegation->id]->push($athlete);
                    }

                    if ($this->alreadyEntered($athlete)) {
                        // Never re-picked on a later run, even if the
                        // meet's event catalog has grown since (e.g. WP4
                        // adding "Softball" afterward) — without this
                        // guard, re-running this seeder once the catalog
                        // has changed gives already-seeded athletes a
                        // *second* entry in the newly available event,
                        // breaking idempotency. Found seeding WP5's
                        // standard-tier command twice in a row.
                        continue;
                    }

                    $event = $athlete->ageDivision() === AgeDivision::Secondary
                        ? $this->pickEvent($ddopaaEvents, $athlete->sex, $delegation, (int) $athlete->lrn)
                        : $this->pickEvent($athleticsElementary, $athlete->sex, $delegation, (int) $athlete->lrn);

                    if ($event !== null) {
                        $this->confirmedEntry($delegation, $athlete, $event);
                    }
                }
            }
        }

        $this->guaranteedEntries($ddopaaEvents);
    }

    private function delegation(Meet $meet, District $district): Delegation
    {
        $slug = str($district->name)->slug()->toString();

        $delegation = Delegation::query()->firstOrCreate(
            ['meet_id' => $meet->id, 'district_id' => $district->id],
            [
                'head_name' => "{$district->name} DdOPAA Coordinator",
                'head_phone' => '09170000000',
                'head_email' => "ddopaa-{$slug}@example.test",
            ],
        );

        if ($delegation->status !== DelegationStatus::Approved) {
            $delegation->forceFill(['status' => DelegationStatus::Approved])->save();
        }

        return $delegation;
    }

    /**
     * SYNTHETIC_DERIVED: plausible DepEd-style naming, not a verified
     * school roster — no source names any actual school in any
     * municipality.
     */
    private function school(District $district, int $index): School
    {
        [$name, $level] = match (true) {
            $index === 1 => ["{$district->name} National High School", SchoolLevel::Secondary],
            $index === 2 => ["{$district->name} Central Elementary School", SchoolLevel::Elementary],
            $index % 3 === 0 => ["{$district->name} Elementary School {$index}", SchoolLevel::Elementary],
            $index % 3 === 1 => ["{$district->name} National High School {$index}", SchoolLevel::Secondary],
            default => ["{$district->name} Integrated School {$index}", SchoolLevel::Integrated],
        };

        return School::query()->firstOrCreate(
            ['district_id' => $district->id, 'name' => $name],
            [
                'school_id_code' => (string) $this->schoolCodeSequence++,
                'level' => $level,
                'address' => "{$district->name}, Davao de Oro (demonstration data)",
            ],
        );
    }

    private function personnel(Delegation $delegation, School $school): void
    {
        Personnel::query()->firstOrCreate(
            [
                'delegation_id' => $delegation->id,
                'first_name' => self::MALE_FIRST_NAMES[$delegation->id % count(self::MALE_FIRST_NAMES)],
                'last_name' => self::LAST_NAMES[($delegation->id + 1) % count(self::LAST_NAMES)],
            ],
            ['school_id' => $school->id, 'role' => PersonnelRole::Coach->value],
        );
    }

    /**
     * Deterministic index-based selection throughout — never `random_int`
     * — so re-seeding produces the identical roster and stays idempotent.
     * LRN is the true idempotency key (globally unique per the schema);
     * name/sex/grade are cosmetic and don't need to be.
     */
    private function athlete(Delegation $delegation, School $school, int $index): Athlete
    {
        $seed = $this->lrnSequence;
        $sex = $seed % 2 === 0 ? Sex::Male : Sex::Female;
        $firstNames = $sex === Sex::Male ? self::MALE_FIRST_NAMES : self::FEMALE_FIRST_NAMES;
        $firstName = $firstNames[$seed % count($firstNames)];
        $lastName = self::LAST_NAMES[intdiv($seed, 7) % count(self::LAST_NAMES)];

        // Grade level follows the *school's* own level — an Elementary
        // school only enrolls grades 3–6, Secondary only 7–10; Integrated
        // spans both. Realistic-data fix: an earlier draft picked grade
        // independently of the school, producing nonsense like a Grade 9
        // student "attending" an Elementary school.
        $gradeLevel = match ($school->level) {
            SchoolLevel::Elementary => 3 + ($seed % 4),
            SchoolLevel::Secondary => 7 + ($seed % 4),
            SchoolLevel::Integrated => $seed % 2 === 0 ? (3 + ($seed % 4)) : (7 + ($seed % 4)),
        };

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
     * The next event of matching gender that hasn't yet reached this
     * delegation's `max_entries_per_delegation` for it — scanned starting
     * from a rotating point (`$rotationSeed`, the athlete's own LRN)
     * rather than always the first candidate in catalog order. Without
     * this, every athlete's search starts at the same event and the
     * highest-capacity one (Basketball, `max_entries_per_delegation=12`)
     * absorbs nearly every entry before lower-capacity events ever get a
     * turn — a real bug caught while validating WP3, not a hypothetical
     * one: the first draft left Swimming and half of Boxing/Gymnastics
     * with zero participants across all 11 delegations.
     *
     * @param  Collection<int, Event>  $events
     */
    private function pickEvent(Collection $events, Sex $sex, Delegation $delegation, int $rotationSeed): ?Event
    {
        $gender = $sex === Sex::Male ? GenderCategory::Boys : GenderCategory::Girls;
        $candidates = $events->where('gender', $gender)->values();

        if ($candidates->isEmpty()) {
            return null;
        }

        $start = $rotationSeed % $candidates->count();

        for ($offset = 0; $offset < $candidates->count(); $offset++) {
            $event = $candidates[($start + $offset) % $candidates->count()];
            $count = $this->entryCounts[$delegation->id][$event->id] ?? 0;

            if ($count < $event->max_entries_per_delegation) {
                return $event;
            }
        }

        return null;
    }

    private function alreadyEntered(Athlete $athlete): bool
    {
        return Entry::query()->where('athlete_id', $athlete->id)->exists();
    }

    /**
     * @return array<int, array<int, int>> delegation_id => [event_id => count]
     */
    private function existingEntryCounts(Meet $meet): array
    {
        return Entry::query()
            ->whereHas('delegation', fn ($q) => $q->where('meet_id', $meet->id))
            ->selectRaw('delegation_id, event_id, COUNT(*) as entry_count')
            ->groupBy('delegation_id', 'event_id')
            ->get()
            ->groupBy('delegation_id')
            ->map(fn (Collection $rows) => $rows->pluck('entry_count', 'event_id')->all())
            ->all();
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

        $this->entryCounts[$delegation->id][$event->id] = ($this->entryCounts[$delegation->id][$event->id] ?? 0) + 1;

        return $entry;
    }

    /**
     * PARTIALLY_VERIFIED (source register rows 4–6): guarantees the five
     * corroborated delegation/event outcomes have real entries to place
     * as winners in WP3, regardless of how the generic distribution
     * above happened to land. Reuses an already-created Secondary
     * athlete of matching sex from that delegation — never creates a new
     * one — and is a no-op if that delegation already has an entry in
     * the target event (the generic pass may already have placed one).
     *
     * Nabunturan's boxing result ("4 golds") doesn't map cleanly onto
     * this catalog's single, non-weight-classed Boxing event — real
     * competitive boxing has multiple weight classes, which this
     * simplified catalog doesn't model. Documented as a known limitation
     * in WP7 rather than force-fit; this method guarantees Nabunturan at
     * least has multiple Boxing entries to plausibly place highly in
     * WP3, not a literal 4-gold reconstruction.
     *
     * @param  Collection<int, Event>  $ddopaaEvents
     */
    private function guaranteedEntries(Collection $ddopaaEvents): void
    {
        $pairs = [
            ['Montevista', '3x3 Basketball', GenderCategory::Boys, 1],
            ['Nabunturan', 'Volleyball', GenderCategory::Girls, 1],
            ['Mawab', 'Volleyball', GenderCategory::Girls, 1],
            ['Maragusan', 'Volleyball', GenderCategory::Girls, 1],
            ['New Bataan', 'Artistic Gymnastics', GenderCategory::Boys, 1],
            ['Nabunturan', 'Boxing', GenderCategory::Boys, 3],
        ];

        foreach ($pairs as [$municipality, $eventName, $gender, $minEntries]) {
            $district = District::query()->where('name', $municipality)->first();
            $delegation = $district === null ? null : Delegation::query()
                ->where('district_id', $district->id)
                ->first();
            $event = $ddopaaEvents->first(
                fn (Event $e) => $e->name === $eventName && $e->gender === $gender,
            );

            if ($delegation === null || $event === null) {
                continue;
            }

            $existing = $this->entryCounts[$delegation->id][$event->id] ?? 0;
            $athletes = $this->secondaryAthletesByDelegation[$delegation->id]
                ->filter(fn (Athlete $athlete) => $athlete->sex === ($gender === GenderCategory::Boys ? Sex::Male : Sex::Female));

            $needed = max(0, $minEntries - $existing);

            foreach ($athletes->take($needed) as $athlete) {
                $this->confirmedEntry($delegation, $athlete, $event);
            }
        }
    }
}
