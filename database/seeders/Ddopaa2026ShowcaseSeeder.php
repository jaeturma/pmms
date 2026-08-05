<?php

namespace Database\Seeders;

use App\Enums\AgeDivision;
use App\Enums\DelegationStatus;
use App\Enums\EntryStatus;
use App\Enums\GenderCategory;
use App\Enums\MatchStatus;
use App\Enums\MeetStatus;
use App\Enums\PersonnelRole;
use App\Enums\ResultStatus;
use App\Enums\SchoolLevel;
use App\Enums\ScoreEventType;
use App\Enums\ScoringSessionStatus;
use App\Enums\Sex;
use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\District;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\EventResult;
use App\Models\EventSchedule;
use App\Models\Meet;
use App\Models\Personnel;
use App\Models\ResultPlacement;
use App\Models\School;
use App\Models\ScoreEvent;
use App\Models\ScoringSession;
use App\Models\Sport;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * "DdOPAA Meet 2026" showcase dataset — local development only, built so a
 * reviewer can walk through the whole system end to end (registries,
 * registration, schedule, live scoring, medal tally, portal, and the new
 * Technical Official role) without hand-creating anything first.
 *
 * Deliberately a *separate* meet from the "DdOPAA Meet 2025" reference
 * dataset (docs/phases/ddopaa-2025-reference-dataset/; that dataset's own
 * seeder classes have since been removed as sample/demo cleanup, this
 * showcase is the one that remains), not a rename of it — that dataset's
 * whole point was documenting real, source-corroborated facts about the
 * January 2025 event; repurposing its Meet row for a fictional 2026 date
 * would have corrupted that record. This seeder reuses the real,
 * meet-independent registry that dataset also reused (Division, the 11
 * real Davao de Oro municipalities via `DivisionRegistrySeeder`, the
 * sports catalog via `SportsCatalogSeeder`) but creates its own
 * delegations/schools/athletes/schedule/live-scoring — every fact below is
 * synthetic demonstration data, no source claims corroborate it.
 *
 * Idempotent throughout (`firstOrCreate`/`forceFill`), safe to re-run.
 */
class Ddopaa2026ShowcaseSeeder extends Seeder
{
    private const array MUNICIPALITIES = [
        'Compostela', 'Laak', 'Mabini', 'Maco', 'Maragusan', 'Mawab',
        'Monkayo', 'Montevista', 'Nabunturan', 'New Bataan', 'Pantukan',
    ];

    private const int ATHLETES_PER_SCHOOL = 4;

    private const array MALE_FIRST_NAMES = [
        'Juan', 'Jose', 'Miguel', 'Gabriel', 'Rafael', 'Daniel', 'Emmanuel',
        'Joshua', 'Christian', 'Vincent', 'Angelo', 'Carlos',
    ];

    private const array FEMALE_FIRST_NAMES = [
        'Maria', 'Ana', 'Grace', 'Angel', 'Princess', 'Nica', 'Kate', 'Faith',
        'Joy', 'Charmaine', 'Rosalie', 'Jasmine',
    ];

    private const array LAST_NAMES = [
        'Santos', 'Reyes', 'Cruz', 'Bautista', 'Garcia', 'Torres', 'Ramos',
        'Flores', 'Mendoza', 'Castillo', 'Villanueva', 'Aquino',
    ];

    private int $schoolCodeSequence = 943010001;

    private int $lrnSequence = 943000000001;

    /** @var array<int, array<int, int>> delegation_id => [event_id => count] this run. */
    private array $entryCounts = [];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $this->call(DivisionRegistrySeeder::class);
        $this->call(SportsCatalogSeeder::class);

        $meet = $this->meet();
        $venues = $this->venues();
        $secondaryEvents = $this->secondaryEvents($meet);
        $elementaryEvents = $this->elementaryEvents($meet);

        $delegationsByMunicipality = $this->delegationsAndRosters($meet, $secondaryEvents, $elementaryEvents);

        $this->schedule($meet, $secondaryEvents, $elementaryEvents, $venues);

        $admin = $this->accounts();

        $this->liveBasketball($meet, $venues, $delegationsByMunicipality, $admin);
        $this->liveBoxing($meet, $venues, $delegationsByMunicipality, $admin);
        $this->liveSoftball($meet, $venues, $delegationsByMunicipality, $admin);
        $this->liveBaseball($meet, $venues, $delegationsByMunicipality, $admin);

        $this->results($meet, $admin);
    }

    /**
     * `status`/`is_published`/`is_active` are guarded (state transitions
     * only via `forceFill`, mirroring `MeetController`) so they can't go
     * in `firstOrCreate()`'s create array. Kept as the one active/featured
     * meet, matching `SampleProvinceDemoSeeder`'s own discipline — the
     * public landing page features exactly one meet.
     */
    private function meet(): Meet
    {
        $meet = Meet::query()->firstOrCreate(
            ['name' => 'DdOPAA Meet 2026'],
            [
                'school_year' => '2025-2026',
                // The real, announced dates for this meet — not a
                // relative-to-seed-time placeholder.
                'starts_at' => '2026-09-04',
                'ends_at' => '2026-09-08',
                'venue' => 'Compostela, Davao de Oro',
            ],
        );

        if ($meet->status !== MeetStatus::Active
            || ! $meet->is_published
            || ! $meet->is_active
            || $meet->venue !== 'Compostela, Davao de Oro'
            || $meet->starts_at->toDateString() !== '2026-09-04'
            || $meet->ends_at->toDateString() !== '2026-09-08'
        ) {
            $meet->forceFill([
                'status' => MeetStatus::Active,
                'is_published' => true,
                'is_active' => true,
                'venue' => 'Compostela, Davao de Oro',
                'starts_at' => '2026-09-04',
                'ends_at' => '2026-09-08',
            ])->save();
        }

        Meet::query()->where('id', '!=', $meet->id)->where('is_active', true)
            ->update(['is_active' => false]);

        return $meet;
    }

    /**
     * @return Collection<int, Venue>
     */
    private function venues(): Collection
    {
        $venues = [
            'Compostela Grandstand Arena' => 'Compostela, Davao de Oro',
            'Compostela Sports Complex Gymnasium' => 'Compostela, Davao de Oro',
            'Compostela Sports Complex Diamond' => 'Compostela, Davao de Oro',
            'Compostela Municipal Pool' => 'Compostela, Davao de Oro',
        ];

        return collect($venues)->map(
            fn (string $address, string $name) => Venue::query()->firstOrCreate(
                ['name' => $name],
                ['address' => $address, 'active' => true],
            ),
        )->values();
    }

    /**
     * Basketball/Volleyball/Boxing/Softball/Swimming/Gymnastics, Secondary
     * only, Boys+Girls — the same breadth the 2025 reference dataset
     * demonstrated, recreated here via identical `firstOrCreate`
     * criteria so the two datasets share the same underlying `Event` rows
     * rather than duplicating the catalog.
     *
     * @return Collection<int, Event>
     */
    private function secondaryEvents(Meet $meet): Collection
    {
        $basketball = Sport::query()->where('name', 'Basketball')->firstOrFail();
        $volleyball = Sport::query()->where('name', 'Volleyball')->firstOrFail();
        $softball = Sport::query()->where('name', 'Softball')->firstOrFail();
        $baseball = Sport::query()->where('name', 'Baseball')->firstOrFail();
        $swimming = Sport::query()->where('name', 'Swimming')->firstOrFail();
        $gymnastics = Sport::query()->where('name', 'Gymnastics')->firstOrFail();
        $boxing = Sport::query()->firstOrCreate(['name' => 'Boxing']);

        // [sport, name, gender, team, max_entries]
        $definitions = [
            [$basketball, 'Basketball', GenderCategory::Boys, true, 12],
            [$basketball, 'Basketball', GenderCategory::Girls, true, 12],
            [$volleyball, 'Volleyball', GenderCategory::Boys, true, 12],
            [$volleyball, 'Volleyball', GenderCategory::Girls, true, 12],
            [$boxing, 'Boxing', GenderCategory::Boys, false, 3],
            [$softball, 'Softball', GenderCategory::Girls, true, 15],
            [$baseball, 'Baseball', GenderCategory::Boys, true, 15],
            [$swimming, '50 Meter Freestyle', GenderCategory::Boys, false, 3],
            [$swimming, '50 Meter Freestyle', GenderCategory::Girls, false, 3],
            [$gymnastics, 'Artistic Gymnastics', GenderCategory::Boys, false, 3],
            [$gymnastics, 'Artistic Gymnastics', GenderCategory::Girls, false, 3],
        ];

        $events = collect($definitions)->map(
            fn (array $d) => Event::query()->firstOrCreate(
                [
                    'sport_id' => $d[0]->id,
                    'name' => $d[1],
                    'gender' => $d[2]->value,
                    'age_division' => AgeDivision::Secondary->value,
                ],
                ['is_team_event' => $d[3], 'max_entries_per_delegation' => $d[4]],
            ),
        );

        $meet->events()->syncWithoutDetaching($events->pluck('id'));

        return $events->values();
    }

    /**
     * Athletics track events (already seeded by `SportsCatalogSeeder`,
     * which builds every gender/division combination) — attach the
     * Elementary Boys/Girls 100m/200m Dash rows to this meet.
     *
     * @return Collection<int, Event>
     */
    private function elementaryEvents(Meet $meet): Collection
    {
        $athletics = Sport::query()->where('name', 'Athletics')->firstOrFail();

        $events = Event::query()
            ->where('sport_id', $athletics->id)
            ->where('age_division', AgeDivision::Elementary->value)
            ->whereIn('name', ['100 Meter Dash', '200 Meter Dash'])
            ->whereIn('gender', [GenderCategory::Boys->value, GenderCategory::Girls->value])
            ->get();

        $meet->events()->syncWithoutDetaching($events->pluck('id'));

        return $events;
    }

    /**
     * All 11 real municipalities get a delegation, two schools (one
     * Secondary, one Elementary — same naming convention the 2025
     * reference dataset used), a coach + an assistant coach, and a
     * handful of athletes each with a confirmed entry.
     *
     * @param  Collection<int, Event>  $secondaryEvents
     * @param  Collection<int, Event>  $elementaryEvents
     * @return array<string, Delegation> municipality name => Delegation
     */
    private function delegationsAndRosters(Meet $meet, Collection $secondaryEvents, Collection $elementaryEvents): array
    {
        $this->entryCounts = $this->existingEntryCounts($meet);

        $delegationsByMunicipality = [];

        foreach (self::MUNICIPALITIES as $index => $municipalityName) {
            $district = District::query()->where('name', $municipalityName)->firstOrFail();
            $delegation = $this->delegation($meet, $district);
            $delegationsByMunicipality[$municipalityName] = $delegation;

            $secondarySchool = $this->school($district, "{$district->name} National High School (2026)", SchoolLevel::Secondary, $index * 2 + 1);
            $elementarySchool = $this->school($district, "{$district->name} Central Elementary School (2026)", SchoolLevel::Elementary, $index * 2 + 2);

            $this->personnel($delegation, $secondarySchool, PersonnelRole::Coach);
            $this->personnel($delegation, $secondarySchool, PersonnelRole::AssistantCoach);

            foreach (range(1, self::ATHLETES_PER_SCHOOL) as $athleteIndex) {
                $this->rosterAthlete($delegation, $secondarySchool, $athleteIndex, 7 + ($athleteIndex % 4), $secondaryEvents);
                $this->rosterAthlete($delegation, $elementarySchool, $athleteIndex, 3 + ($athleteIndex % 4), $elementaryEvents);
            }
        }

        return $delegationsByMunicipality;
    }

    private function delegation(Meet $meet, District $district): Delegation
    {
        $slug = str($district->name)->slug()->toString();

        $delegation = Delegation::query()->firstOrCreate(
            ['meet_id' => $meet->id, 'district_id' => $district->id],
            [
                'head_name' => "{$district->name} DdOPAA 2026 Coordinator",
                'head_phone' => '09170000000',
                'head_email' => "ddopaa2026-{$slug}@example.test",
            ],
        );

        if ($delegation->status !== DelegationStatus::Approved) {
            $delegation->forceFill(['status' => DelegationStatus::Approved])->save();
        }

        return $delegation;
    }

    private function school(District $district, string $name, SchoolLevel $level, int $codeOffset): School
    {
        return School::query()->firstOrCreate(
            ['district_id' => $district->id, 'name' => $name],
            [
                'school_id_code' => (string) ($this->schoolCodeSequence + $codeOffset),
                'level' => $level,
                'address' => "{$district->name}, Davao de Oro (demonstration data)",
            ],
        );
    }

    private function personnel(Delegation $delegation, School $school, PersonnelRole $role): void
    {
        // Coach and AssistantCoach get distinct names for the same
        // delegation: the role's own position among PersonnelRole::cases()
        // offsets the name index so they never collide.
        $roleOffset = array_search($role, PersonnelRole::cases(), strict: true);

        Personnel::query()->firstOrCreate(
            [
                'delegation_id' => $delegation->id,
                'role' => $role->value,
            ],
            [
                'school_id' => $school->id,
                'first_name' => self::MALE_FIRST_NAMES[($delegation->id + $roleOffset) % count(self::MALE_FIRST_NAMES)],
                'last_name' => self::LAST_NAMES[($delegation->id + 1 + $roleOffset) % count(self::LAST_NAMES)],
            ],
        );
    }

    /**
     * Deterministic index-based selection (never `random_int`) so
     * re-seeding produces the identical roster and stays idempotent — LRN
     * is the true idempotency key.
     *
     * @param  Collection<int, Event>  $candidateEvents
     */
    private function rosterAthlete(Delegation $delegation, School $school, int $index, int $gradeLevel, Collection $candidateEvents): void
    {
        $seed = $this->lrnSequence;
        $sex = $seed % 2 === 0 ? Sex::Male : Sex::Female;
        $firstNames = $sex === Sex::Male ? self::MALE_FIRST_NAMES : self::FEMALE_FIRST_NAMES;
        $firstName = $firstNames[$seed % count($firstNames)];
        $lastName = self::LAST_NAMES[intdiv($seed, 7) % count(self::LAST_NAMES)];
        $lrn = (string) $this->lrnSequence++;

        $athlete = Athlete::query()->firstOrCreate(
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

        if (Entry::query()->where('athlete_id', $athlete->id)->exists()) {
            return;
        }

        $event = $this->pickEvent($candidateEvents, $sex, $delegation, $seed);

        if ($event !== null) {
            $this->confirmedEntry($delegation, $athlete, $event);
        }
    }

    /**
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

    /**
     * Validated results for every event with confirmed entries — the only
     * path Medal Tally/Rankings/Standings actually read
     * (`MedalTallyService::standings()` derives everything from
     * `ResultPlacement` at read time, no separate "medal award"
     * mechanism, same discipline the 2025 reference dataset followed).
     * Every placement here is synthetic — this is a fictional future
     * meet, nothing to corroborate against a source.
     */
    private function results(Meet $meet, ?User $admin): void
    {
        foreach ($meet->events()->with('sport')->get() as $event) {
            $entries = Entry::query()
                ->where('event_id', $event->id)
                ->where('status', EntryStatus::Confirmed->value)
                ->get();

            if ($entries->isEmpty()) {
                continue;
            }

            $placements = $event->is_team_event
                ? $this->teamPlacements($event, $entries)
                : $this->individualPlacements($event, $entries);

            if ($placements === []) {
                continue;
            }

            $this->validatedResult($meet, $event, $admin, $placements);
        }

        $this->guaranteeMunicipalityCoverage($meet);
    }

    /**
     * Groups entries by delegation (a team event's roster) and ranks
     * delegations, not individual entries — every teammate at a given
     * rank shares it with `is_tie = true`, since the medal award is "this
     * team placed Nth," not an individual finishing position. Same
     * approach the 2025 reference dataset's own team-placement logic
     * used, without that logic's corroborated-winner overrides (this
     * meet has none to reproduce).
     *
     * @param  Collection<int, Entry>  $entries
     * @return array<int, array{0: Entry, 1: int, 2: string|null, 3: bool}>
     */
    private function teamPlacements(Event $event, Collection $entries): array
    {
        $groups = $entries->groupBy('delegation_id');
        $order = $this->rotated($groups->keys(), $event->id);

        $placements = [];
        $rank = 1;

        foreach ($order->take(3) as $delegationId) {
            $groupEntries = $groups->get($delegationId);

            if ($groupEntries === null) {
                continue;
            }

            $isTie = $groupEntries->count() > 1;

            foreach ($groupEntries as $entry) {
                $placements[] = [$entry, $rank, null, $isTie];
            }

            $rank++;
        }

        return $placements;
    }

    /**
     * Individual events rank entries directly, one per placement, in a
     * deterministic rotation seeded by the event's own ID — never
     * `random_int` — so re-seeding produces the identical podium.
     *
     * @param  Collection<int, Entry>  $entries
     * @return array<int, array{0: Entry, 1: int, 2: string|null, 3: bool}>
     */
    private function individualPlacements(Event $event, Collection $entries): array
    {
        return $this->rotatedEntries($entries, $event->id)
            ->take(3)
            ->map(fn (Entry $entry, int $i) => [$entry, $i + 1, $this->mark($event, $entry), false])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, int>  $ids
     * @return Collection<int, int>
     */
    private function rotated(Collection $ids, int $seed): Collection
    {
        $sorted = $ids->sort()->values();

        if ($sorted->isEmpty()) {
            return $sorted;
        }

        $offset = $seed % $sorted->count();

        return $sorted->slice($offset)->concat($sorted->slice(0, $offset))->values();
    }

    /**
     * @param  Collection<int, Entry>  $entries
     * @return Collection<int, Entry>
     */
    private function rotatedEntries(Collection $entries, int $seed): Collection
    {
        $byId = $entries->keyBy('id');
        $order = $this->rotated($entries->pluck('id'), $seed);

        return $order->map(fn (int $id) => $byId->get($id))->filter()->values();
    }

    /**
     * SYNTHETIC_DEMO: no real score/time/method behind any placement —
     * plausible, deterministic (never `random_int`) text so re-seeding
     * produces identical marks.
     */
    private function mark(Event $event, Entry $entry): ?string
    {
        return match ($event->sport->name) {
            'Athletics' => sprintf('%d.%02ds', 12 + ($entry->id % 6), ($entry->id * 3) % 100),
            'Swimming' => sprintf('%d.%02ds', 26 + ($entry->id % 8), ($entry->id * 3) % 100),
            'Gymnastics' => sprintf('%.2f', 7.5 + (($entry->id % 20) / 10)),
            'Boxing' => $entry->id % 2 === 0 ? 'Decision' : 'TKO',
            default => null,
        };
    }

    /**
     * @param  array<int, array{0: Entry, 1: int, 2: string|null, 3: bool}>  $placements
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

        foreach ($placements as [$entry, $rank, $mark, $isTie]) {
            ResultPlacement::query()->firstOrCreate(
                ['event_result_id' => $result->id, 'entry_id' => $entry->id],
                ['rank' => $rank, 'mark' => $mark, 'is_tie' => $isTie],
            );
        }

        $result->forceFill([
            'status' => ResultStatus::Validated,
            'validated_by' => $admin?->id,
            'validated_at' => now(),
        ])->save();
    }

    /**
     * With 11 municipalities competing across a limited event catalog,
     * deterministic rotation can leave one or more with confirmed entries
     * but zero top-3 placements anywhere — invisible on the medal tally
     * page entirely (not just at zero), since
     * `MedalTallyService::standings()` only lists districts that appear
     * in at least one placement. Same fix the 2025 reference dataset
     * already needed: guarantee every delegation with a
     * confirmed entry has at least one placement, swapping a bronze from
     * a donor delegation that already has enough medals to spare one.
     */
    private function guaranteeMunicipalityCoverage(Meet $meet): void
    {
        $delegations = Delegation::query()
            ->where('meet_id', $meet->id)
            ->orderBy('district_id')
            ->get();

        foreach ($delegations as $delegation) {
            if ($this->hasAnyPlacement($meet, $delegation)) {
                continue;
            }

            $this->coverDelegation($meet, $delegation);
        }
    }

    private function hasAnyPlacement(Meet $meet, Delegation $delegation): bool
    {
        return ResultPlacement::query()
            ->whereHas('result', fn ($q) => $q->where('meet_id', $meet->id))
            ->whereHas('entry', fn ($q) => $q->where('delegation_id', $delegation->id))
            ->exists();
    }

    private function coverDelegation(Meet $meet, Delegation $delegation): void
    {
        $entries = Entry::query()
            ->where('delegation_id', $delegation->id)
            ->where('status', EntryStatus::Confirmed->value)
            ->whereHas('event', fn ($q) => $q->where('is_team_event', false))
            ->with('event')
            ->orderBy('id')
            ->get();

        foreach ($entries as $entry) {
            $result = EventResult::query()
                ->where('meet_id', $meet->id)
                ->where('event_id', $entry->event_id)
                ->where('status', ResultStatus::Validated->value)
                ->first();

            if ($result === null) {
                continue;
            }

            $bronze = ResultPlacement::query()
                ->where('event_result_id', $result->id)
                ->where('rank', 3)
                ->first();

            if ($bronze === null || $bronze->entry_id === $entry->id) {
                continue;
            }

            $donorDelegationId = (int) Entry::query()->whereKey($bronze->entry_id)->value('delegation_id');

            // Only swap from a donor with medals to spare — never create
            // a new zero-medal municipality while fixing this one. 2 is
            // the minimum: losing 1 bronze still leaves 1.
            if ($this->delegationMedalCount($meet, $donorDelegationId) < 2) {
                continue;
            }

            $bronze->forceFill([
                'entry_id' => $entry->id,
                'mark' => $this->mark($entry->event, $entry),
            ])->save();

            return;
        }
    }

    private function delegationMedalCount(Meet $meet, int $delegationId): int
    {
        return ResultPlacement::query()
            ->whereIn('rank', [1, 2, 3])
            ->whereHas('result', fn ($q) => $q->where('meet_id', $meet->id)->where('status', ResultStatus::Validated->value))
            ->whereHas('entry', fn ($q) => $q->where('delegation_id', $delegationId))
            ->count();
    }

    /**
     * A scheduled slot for every attached event, spread across the meet's
     * multi-day window, so the Schedule page has a full-looking program —
     * not just the three live-scoreboard sports.
     *
     * @param  Collection<int, Event>  $secondaryEvents
     * @param  Collection<int, Event>  $elementaryEvents
     * @param  Collection<int, Venue>  $venues
     */
    private function schedule(Meet $meet, Collection $secondaryEvents, Collection $elementaryEvents, Collection $venues): void
    {
        $allEvents = $secondaryEvents->concat($elementaryEvents)->values();

        foreach ($allEvents as $index => $event) {
            $venue = $venues[$index % $venues->count()];
            $date = Carbon::parse($meet->starts_at)->addDays($index % 5);

            $slot = EventSchedule::query()->firstOrNew([
                'meet_id' => $meet->id,
                'event_id' => $event->id,
                'venue_id' => $venue->id,
            ]);
            $slot->fill([
                'scheduled_date' => $date->toDateString(),
                'starts_at' => $date->copy()->setTime(8 + ($index % 4) * 2, 0)->format('H:i:s'),
                'ends_at' => $date->copy()->setTime(10 + ($index % 4) * 2, 0)->format('H:i:s'),
                'note' => 'DdOPAA Meet 2026 — showcase schedule',
            ])->save();
        }

        // One additional, purely-scheduled Volleyball match so the
        // Schedule page's live-link column shows a real "not started yet"
        // row alongside the three live-scoreboard sports below.
        $volleyball = $secondaryEvents->firstWhere('name', 'Volleyball');

        if ($volleyball !== null) {
            $slot = $venues->first();
            $volleyballSlot = EventSchedule::query()->firstOrNew([
                'meet_id' => $meet->id, 'event_id' => $volleyball->id, 'venue_id' => $slot->id,
            ]);
            $volleyballSlot->fill([
                'scheduled_date' => Carbon::parse($meet->starts_at)->addDays(4)->toDateString(),
                'starts_at' => '13:00:00',
                'ends_at' => '15:00:00',
                'note' => 'Pool Game 1',
            ])->save();

            EventMatch::query()->firstOrCreate(
                ['meet_id' => $meet->id, 'event_id' => $volleyball->id, 'event_schedule_id' => $volleyballSlot->id],
                ['round_label' => 'Pool Game 1', 'sequence' => 1],
            );
        }
    }

    /**
     * Three login accounts for exercising every role a reviewer would
     * want to try, on top of `AdminUserSeeder`'s own real administrator:
     * a second, clearly-labeled Administrator ("Division Admin"), an
     * Organizer ("Division Secretariat" — the DepEd term for meet
     * organizing staff), and a Technical Official assigned to all three
     * live-scoreboard sports (Basketball/Boxing/Softball) so one login
     * can operate any of them. Passwords are the same documented local
     * dev default (`docs/authorization.md`) — never used outside
     * local/testing (this whole seeder returns early otherwise).
     */
    private function accounts(): User
    {
        // 'role'/'email_verified_at' aren't mass-assignable
        // (`docs/authorization.md`) — `firstOrNew()->forceFill()->save()`
        // in one shot, same pattern `AdminUserSeeder` uses for the real
        // administrator account.
        $admin = User::query()->firstOrNew(['email' => 'division.admin@ddopaa2026.test']);
        $admin->forceFill([
            'name' => 'Division Admin (Demo)',
            'password' => 'password',
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ])->save();

        $secretariat = User::query()->firstOrNew(['email' => 'division.secretariat@ddopaa2026.test']);
        $secretariat->forceFill([
            'name' => 'Division Secretariat (Demo)',
            'password' => 'password',
            'role' => UserRole::Organizer,
            'email_verified_at' => now(),
        ])->save();

        $technicalOfficial = User::query()->firstOrNew(['email' => 'technical.official@ddopaa2026.test']);
        $technicalOfficial->forceFill([
            'name' => 'Technical Official (Demo)',
            'password' => 'password',
            'role' => UserRole::TechnicalOfficial,
            'email_verified_at' => now(),
        ])->save();

        $sportNames = ['Basketball', 'Boxing', 'Softball'];
        $sportIds = Sport::query()->whereIn('name', $sportNames)->pluck('id');
        $technicalOfficial->sports()->syncWithoutDetaching($sportIds);

        return $admin;
    }

    /**
     * Girls Basketball: one scheduled game, one live (in-progress) game
     * with a rich `sport_state` for the reskinned portal scoreboard
     * (`docs/ui-ux/basketball.html`), one completed game.
     *
     * @param  Collection<int, Venue>  $venues
     * @param  array<string, Delegation>  $delegations
     */
    private function liveBasketball(Meet $meet, Collection $venues, array $delegations, ?User $admin): void
    {
        $event = Event::query()
            ->whereHas('sport', fn ($q) => $q->where('name', 'Basketball'))
            ->where('gender', GenderCategory::Girls->value)
            ->where('age_division', AgeDivision::Secondary->value)
            ->firstOrFail();

        $venue = $venues->firstWhere('name', 'Compostela Sports Complex Gymnasium');
        $sideA = $delegations['Montevista']->registrantName();
        $sideB = $delegations['Nabunturan']->registrantName();

        $this->scheduledMatch($meet, $event, $venue, 'Pool Game 1', Carbon::now()->addDays(2));

        $now = Carbon::now();

        $liveSession = $this->inProgressMatch(
            $meet, $event, $venue, 'Semifinal', $now, $sideA, $sideB, $admin,
            scoreA: 38, scoreB: 41, periodLabel: 'Q3',
            sportState: [
                'fouls_a' => 3, 'fouls_b' => 2,
                'timeouts_a' => 2, 'timeouts_b' => 1,
                'shot_clock_seconds' => 18,
                'quarters' => [
                    ['label' => 'Q1', 'a' => 14, 'b' => 12],
                    ['label' => 'Q2', 'a' => 15, 'b' => 16],
                    ['label' => 'Q3', 'a' => 9, 'b' => 13],
                ],
                'team_stats' => [
                    'a' => ['fg_made' => 14, 'fg_att' => 30, 'three_made' => 4, 'three_att' => 11, 'ft_made' => 6, 'ft_att' => 8, 'rebounds' => 22, 'assists' => 10, 'turnovers' => 7, 'steals' => 5, 'blocks' => 3],
                    'b' => ['fg_made' => 15, 'fg_att' => 33, 'three_made' => 3, 'three_att' => 9, 'ft_made' => 8, 'ft_att' => 10, 'rebounds' => 24, 'assists' => 8, 'turnovers' => 9, 'steals' => 6, 'blocks' => 2],
                ],
                'box_score' => [
                    'a' => [
                        ['number' => '8', 'name' => 'Angel Torres', 'pts' => 16, 'reb' => 5, 'ast' => 3],
                        ['number' => '12', 'name' => 'Maria Villaruz', 'pts' => 10, 'reb' => 3, 'ast' => 2],
                    ],
                    'b' => [
                        ['number' => '10', 'name' => 'Grace Bacal', 'pts' => 14, 'reb' => 3, 'ast' => 4],
                        ['number' => '7', 'name' => 'Kate Suson', 'pts' => 12, 'reb' => 5, 'ast' => 2],
                    ],
                ],
            ],
            events: $this->pointByPoint($now->copy()->subMinutes(25), 38, 41),
        );

        $this->endOtherLiveSessions($event, $liveSession, $now, $admin);

        $this->completedMatch(
            $meet, $event, $venue, 'Final', Carbon::now()->subDay(), $sideA, $sideB, $admin,
            scoreA: 54, scoreB: 49, periodLabel: 'Final',
            sportState: ['fouls_a' => 5, 'fouls_b' => 4],
        );
    }

    /**
     * Boys Boxing: one scheduled bout, one live bout (2 of 3 rounds
     * fought), one completed bout with a full 3-round history.
     *
     * @param  Collection<int, Venue>  $venues
     * @param  array<string, Delegation>  $delegations
     */
    private function liveBoxing(Meet $meet, Collection $venues, array $delegations, ?User $admin): void
    {
        $event = Event::query()
            ->whereHas('sport', fn ($q) => $q->where('name', 'Boxing'))
            ->where('gender', GenderCategory::Boys->value)
            ->firstOrFail();

        $venue = $venues->firstWhere('name', 'Compostela Sports Complex Gymnasium');
        $sideA = $delegations['Nabunturan']->registrantName();
        $sideB = $delegations['New Bataan']->registrantName();

        $this->scheduledMatch($meet, $event, $venue, 'Bout 1', Carbon::now()->addDays(2));

        $now = Carbon::now();

        $liveSession = $this->inProgressMatch(
            $meet, $event, $venue, 'Bout 2', $now, $sideA, $sideB, $admin,
            scoreA: 20, scoreB: 17, periodLabel: 'Round 3',
            sportState: [
                'rounds' => [
                    ['round' => 1, 'score_a' => 10, 'score_b' => 9],
                    ['round' => 2, 'score_a' => 10, 'score_b' => 8],
                ],
                'total_rounds' => 3,
                'rounds_format' => '3 × 2 minutes',
                'weight_class' => 'Lightweight — 57 kg',
                'ring' => 'Ring A',
                'knockdowns_a' => 1, 'knockdowns_b' => 0,
                'judges' => [
                    ['name' => 'Judge 1', 'red' => 10, 'blue' => 9],
                    ['name' => 'Judge 2', 'red' => 9, 'blue' => 10],
                    ['name' => 'Judge 3', 'red' => 10, 'blue' => 9],
                ],
            ],
            events: [
                ['type' => ScoreEventType::RoundScore, 'payload' => ['round' => 1, 'score_a' => 10, 'score_b' => 9], 'at' => $now->copy()->subMinutes(13)],
                ['type' => ScoreEventType::RoundScore, 'payload' => ['round' => 2, 'score_a' => 10, 'score_b' => 8], 'at' => $now->copy()->subMinutes(1)],
            ],
        );

        $this->endOtherLiveSessions($event, $liveSession, $now, $admin);

        $this->completedMatch(
            $meet, $event, $venue, 'Bout 3', Carbon::now()->subDay(), $sideA, $sideB, $admin,
            scoreA: 29, scoreB: 28, periodLabel: 'Final',
            sportState: ['rounds' => [
                ['round' => 1, 'score_a' => 10, 'score_b' => 9],
                ['round' => 2, 'score_a' => 9, 'score_b' => 10],
                ['round' => 3, 'score_a' => 10, 'score_b' => 9],
            ]],
        );
    }

    /**
     * Girls Softball: one scheduled game, one live game (partway through
     * the top of the 4th), one completed 7-inning game.
     *
     * @param  Collection<int, Venue>  $venues
     * @param  array<string, Delegation>  $delegations
     */
    private function liveSoftball(Meet $meet, Collection $venues, array $delegations, ?User $admin): void
    {
        $event = Event::query()
            ->whereHas('sport', fn ($q) => $q->where('name', 'Softball'))
            ->where('gender', GenderCategory::Girls->value)
            ->firstOrFail();

        $venue = $venues->firstWhere('name', 'Compostela Sports Complex Diamond');
        $sideA = $delegations['Mawab']->registrantName();
        $sideB = $delegations['Maragusan']->registrantName();

        $this->scheduledMatch($meet, $event, $venue, 'Pool Game 1', Carbon::now()->addDays(2));

        $now = Carbon::now();

        $liveSession = $this->inProgressMatch(
            $meet, $event, $venue, 'Semifinal', $now, $sideA, $sideB, $admin,
            scoreA: 3, scoreB: 1, periodLabel: 'Top 4th',
            sportState: [
                'inning' => 4, 'half' => 'top', 'outs' => 1, 'balls' => 1, 'strikes' => 2,
                'innings' => [
                    ['inning' => 1, 'runs_a' => 1, 'runs_b' => 0],
                    ['inning' => 2, 'runs_a' => 0, 'runs_b' => 1],
                    ['inning' => 3, 'runs_a' => 2, 'runs_b' => 0],
                ],
                'runners' => ['first' => true, 'second' => false, 'third' => true],
                'hits_a' => 8, 'hits_b' => 5,
                'errors_a' => 1, 'errors_b' => 2,
            ],
            events: [
                ['type' => ScoreEventType::Count, 'payload' => ['action' => 'ball', 'outs' => 0, 'balls' => 1, 'strikes' => 0], 'at' => $now->copy()->subMinutes(24)],
                ['type' => ScoreEventType::InningRun, 'payload' => ['side' => 'a', 'runs' => 1, 'inning' => 1], 'at' => $now->copy()->subMinutes(20)],
                ['type' => ScoreEventType::InningRun, 'payload' => ['side' => 'b', 'runs' => 1, 'inning' => 2], 'at' => $now->copy()->subMinutes(14)],
                ['type' => ScoreEventType::InningRun, 'payload' => ['side' => 'a', 'runs' => 2, 'inning' => 3], 'at' => $now->copy()->subMinutes(8)],
                ['type' => ScoreEventType::Count, 'payload' => ['action' => 'strike', 'outs' => 1, 'balls' => 1, 'strikes' => 2], 'at' => $now->copy()->subMinute()],
            ],
        );

        $this->endOtherLiveSessions($event, $liveSession, $now, $admin);

        $this->completedMatch(
            $meet, $event, $venue, 'Final', Carbon::now()->subDay(), $sideA, $sideB, $admin,
            scoreA: 9, scoreB: 6, periodLabel: 'Final',
            sportState: [
                'inning' => 7, 'half' => 'bottom', 'outs' => 3, 'balls' => 0, 'strikes' => 0,
                'innings' => [
                    ['inning' => 1, 'runs_a' => 1, 'runs_b' => 0],
                    ['inning' => 2, 'runs_a' => 0, 'runs_b' => 1],
                    ['inning' => 3, 'runs_a' => 2, 'runs_b' => 0],
                    ['inning' => 4, 'runs_a' => 1, 'runs_b' => 2],
                    ['inning' => 5, 'runs_a' => 0, 'runs_b' => 0],
                    ['inning' => 6, 'runs_a' => 3, 'runs_b' => 1],
                    ['inning' => 7, 'runs_a' => 2, 'runs_b' => 2],
                ],
            ],
        );
    }

    /**
     * Boys Baseball: same shape as `liveSoftball()` above — Baseball and
     * Softball share one scoreboard (`ScoreboardType::SoftballBaseball`,
     * `App\Enums\ScoreboardType::forSport()` maps both sport names to it,
     * and the portal's `PortalSoftballScoreboard`/`PortalSoftballSidebar`
     * components are already sport-name-agnostic — they only read the
     * generic inning/outs/balls/strikes `sport_state` shape, never the
     * literal word "softball"). The only reason Baseball had no live
     * demo before now is that no Baseball `Event`/entries/matches existed
     * in this meet at all yet, not a frontend gap — `secondaryEvents()`
     * above now attaches one. Different delegations from the Softball
     * demo (Compostela vs. Monkayo, not Mawab vs. Maragusan) so the two
     * diamond sports don't look like a copy-pasted rerun of each other.
     *
     * @param  Collection<int, Venue>  $venues
     * @param  array<string, Delegation>  $delegations
     */
    private function liveBaseball(Meet $meet, Collection $venues, array $delegations, ?User $admin): void
    {
        $event = Event::query()
            ->whereHas('sport', fn ($q) => $q->where('name', 'Baseball'))
            ->where('gender', GenderCategory::Boys->value)
            ->firstOrFail();

        $venue = $venues->firstWhere('name', 'Compostela Sports Complex Diamond');
        $sideA = $delegations['Compostela']->registrantName();
        $sideB = $delegations['Monkayo']->registrantName();

        $this->scheduledMatch($meet, $event, $venue, 'Pool Game 1', Carbon::now()->addDays(3));

        $now = Carbon::now();

        $liveSession = $this->inProgressMatch(
            $meet, $event, $venue, 'Semifinal', $now, $sideA, $sideB, $admin,
            scoreA: 5, scoreB: 4, periodLabel: 'Bottom 6th',
            sportState: [
                'inning' => 6, 'half' => 'bottom', 'outs' => 2, 'balls' => 2, 'strikes' => 1,
                'innings' => [
                    ['inning' => 1, 'runs_a' => 0, 'runs_b' => 1],
                    ['inning' => 2, 'runs_a' => 2, 'runs_b' => 0],
                    ['inning' => 3, 'runs_a' => 0, 'runs_b' => 0],
                    ['inning' => 4, 'runs_a' => 1, 'runs_b' => 2],
                    ['inning' => 5, 'runs_a' => 2, 'runs_b' => 1],
                ],
                'runners' => ['first' => false, 'second' => true, 'third' => false],
                'hits_a' => 7, 'hits_b' => 6,
                'errors_a' => 0, 'errors_b' => 1,
            ],
            events: [
                ['type' => ScoreEventType::InningRun, 'payload' => ['side' => 'b', 'runs' => 1, 'inning' => 1], 'at' => $now->copy()->subMinutes(24)],
                ['type' => ScoreEventType::InningRun, 'payload' => ['side' => 'a', 'runs' => 2, 'inning' => 2], 'at' => $now->copy()->subMinutes(19)],
                ['type' => ScoreEventType::InningRun, 'payload' => ['side' => 'a', 'runs' => 1, 'inning' => 4], 'at' => $now->copy()->subMinutes(13)],
                ['type' => ScoreEventType::InningRun, 'payload' => ['side' => 'b', 'runs' => 2, 'inning' => 4], 'at' => $now->copy()->subMinutes(12)],
                ['type' => ScoreEventType::InningRun, 'payload' => ['side' => 'a', 'runs' => 2, 'inning' => 5], 'at' => $now->copy()->subMinutes(6)],
                ['type' => ScoreEventType::InningRun, 'payload' => ['side' => 'b', 'runs' => 1, 'inning' => 5], 'at' => $now->copy()->subMinutes(5)],
                ['type' => ScoreEventType::Count, 'payload' => ['action' => 'ball', 'outs' => 2, 'balls' => 2, 'strikes' => 1], 'at' => $now->copy()->subMinute()],
            ],
        );

        $this->endOtherLiveSessions($event, $liveSession, $now, $admin);

        $this->completedMatch(
            $meet, $event, $venue, 'Final', Carbon::now()->subDay(), $sideA, $sideB, $admin,
            scoreA: 8, scoreB: 3, periodLabel: 'Final',
            sportState: [
                'inning' => 7, 'half' => 'bottom', 'outs' => 3, 'balls' => 0, 'strikes' => 0,
                'innings' => [
                    ['inning' => 1, 'runs_a' => 1, 'runs_b' => 0],
                    ['inning' => 2, 'runs_a' => 0, 'runs_b' => 1],
                    ['inning' => 3, 'runs_a' => 2, 'runs_b' => 0],
                    ['inning' => 4, 'runs_a' => 0, 'runs_b' => 2],
                    ['inning' => 5, 'runs_a' => 3, 'runs_b' => 0],
                    ['inning' => 6, 'runs_a' => 1, 'runs_b' => 0],
                    ['inning' => 7, 'runs_a' => 1, 'runs_b' => 0],
                ],
            ],
        );
    }

    /**
     * A synthetic point-by-point log whose side "a"/"b" deltas sum exactly
     * to the given final scores, timed across the ~25 minutes since
     * `$startedAt`.
     *
     * @return array<int, array{type: ScoreEventType, payload: array<string, mixed>, at: Carbon}>
     */
    private function pointByPoint(Carbon $startedAt, int $totalA, int $totalB): array
    {
        $events = [];
        $remainingA = $totalA;
        $remainingB = $totalB;

        while ($remainingA > 0 || $remainingB > 0) {
            if ($remainingA > 0) {
                $delta = min($remainingA, $remainingA % 3 === 0 ? 3 : 2);
                $events[] = ['type' => ScoreEventType::Point, 'payload' => ['side' => 'a', 'delta' => $delta]];
                $remainingA -= $delta;
            }

            if ($remainingB > 0) {
                $delta = min($remainingB, $remainingB % 3 === 0 ? 3 : 2);
                $events[] = ['type' => ScoreEventType::Point, 'payload' => ['side' => 'b', 'delta' => $delta]];
                $remainingB -= $delta;
            }
        }

        $secondsPerEvent = intdiv(1400, max(1, count($events)));

        return array_map(
            fn (array $event, int $index): array => [...$event, 'at' => $startedAt->copy()->addSeconds($secondsPerEvent * ($index + 1))],
            $events,
            array_keys($events),
        );
    }

    /**
     * `slot()`/`match()` key the "live" match to *today's* date, so
     * re-running this seeder on a later day creates a fresh
     * `EventMatch`/`ScoringSession` rather than reusing an earlier one —
     * which would otherwise leave that stale session "in progress"
     * forever, competing with today's. Ending every other non-ended
     * session on this event keeps exactly one live at a time.
     */
    private function endOtherLiveSessions(Event $event, ScoringSession $keep, Carbon $now, ?User $admin): void
    {
        ScoringSession::query()
            ->where('id', '!=', $keep->id)
            ->where('status', '!=', ScoringSessionStatus::Ended->value)
            ->whereHas('match', fn ($query) => $query->where('event_id', $event->id))
            ->get()
            ->each(fn (ScoringSession $stale) => $stale->forceFill([
                'status' => ScoringSessionStatus::Ended,
                'ended_by' => $admin?->id,
                'ended_at' => $now,
            ])->save());
    }

    /**
     * `whereDate()`, not a plain `firstOrNew` key on `scheduled_date` —
     * Eloquent's `date` cast serializes through the query grammar's
     * default datetime format on save, but a `firstOrNew` match array is
     * compared as a literal string, so a bare `Y-m-d` lookup key never
     * matches what actually got stored (same fix the 2025 reference
     * dataset's live-scoring seeder already needed for the same reason).
     */
    private function slot(Meet $meet, Event $event, Venue $venue, Carbon $date, string $note): EventSchedule
    {
        $slot = EventSchedule::query()
            ->where('meet_id', $meet->id)
            ->where('event_id', $event->id)
            ->where('venue_id', $venue->id)
            ->whereDate('scheduled_date', $date->toDateString())
            ->first() ?? new EventSchedule([
                'meet_id' => $meet->id,
                'event_id' => $event->id,
                'venue_id' => $venue->id,
                'scheduled_date' => $date->toDateString(),
            ]);

        $slot->fill([
            'starts_at' => $date->copy()->setTime(8, 0)->format('H:i:s'),
            'ends_at' => $date->copy()->setTime(10, 0)->format('H:i:s'),
            'note' => $note,
        ])->save();

        $this->pruneStaleSlots($meet, $event, $note, $slot);

        return $slot;
    }

    /**
     * `slot()` keys today's Scheduled/Live/Completed row to today's date
     * (see `endOtherLiveSessions()`'s note on why), so re-running this
     * seeder on a later calendar day creates a fresh `EventSchedule`
     * rather than reusing an earlier one. Without this, every previous
     * day's now-superseded slot — and its `EventMatch`/`ScoringSession`/
     * `ScoreEvent` trio — piles up forever instead of being replaced.
     * Identified by the same `(event, note)` pairing `slot()` itself
     * dedupes new slots on, so exactly one Scheduled/Live/Completed row
     * survives per event at any time. Deletes bottom-up
     * (`ScoreEvent`→`ScoringSession`→`EventMatch`→`EventSchedule`) since
     * `scoring_sessions.match_id` and `matches.event_schedule_id` are
     * `restrictOnDelete()`/`nullOnDelete()`, not cascading.
     */
    private function pruneStaleSlots(Meet $meet, Event $event, string $note, EventSchedule $keep): void
    {
        $stale = EventSchedule::query()
            ->where('meet_id', $meet->id)
            ->where('event_id', $event->id)
            ->where('note', $note)
            ->where('id', '!=', $keep->id)
            ->get();

        foreach ($stale as $slot) {
            $matches = EventMatch::query()->where('event_schedule_id', $slot->id)->get();

            foreach ($matches as $match) {
                $match->scoringSessions()->get()->each(function (ScoringSession $session): void {
                    $session->events()->delete();
                    $session->delete();
                });

                $match->delete();
            }

            $slot->delete();
        }
    }

    private function match(Meet $meet, Event $event, EventSchedule $slot, string $roundLabel): EventMatch
    {
        return EventMatch::query()->firstOrCreate(
            ['meet_id' => $meet->id, 'event_id' => $event->id, 'event_schedule_id' => $slot->id],
            ['round_label' => $roundLabel, 'sequence' => 1],
        );
    }

    private function scheduledMatch(Meet $meet, Event $event, Venue $venue, string $roundLabel, Carbon $date): void
    {
        $slot = $this->slot($meet, $event, $venue, $date, 'Scheduled — awaiting live scoring');
        $this->match($meet, $event, $slot, $roundLabel);
    }

    /**
     * @param  array<string, mixed>  $sportState
     * @param  array<int, array{type: ScoreEventType, payload: array<string, mixed>, at: Carbon}>|null  $events
     */
    private function inProgressMatch(
        Meet $meet,
        Event $event,
        Venue $venue,
        string $roundLabel,
        Carbon $date,
        string $sideA,
        string $sideB,
        ?User $admin,
        int $scoreA,
        int $scoreB,
        string $periodLabel,
        array $sportState,
        ?array $events = null,
    ): ScoringSession {
        $slot = $this->slot($meet, $event, $venue, $date, 'Live — in progress');
        $match = $this->match($meet, $event, $slot, $roundLabel);

        $session = $match->scoringSessions()->where('status', '!=', ScoringSessionStatus::Ended->value)->first();

        if ($session === null) {
            $session = new ScoringSession(['match_id' => $match->id]);
        }

        $session->fill(['side_a_label' => $sideA, 'side_b_label' => $sideB]);
        $session->forceFill([
            'status' => ScoringSessionStatus::InProgress,
            'score_a' => $scoreA,
            'score_b' => $scoreB,
            'period_label' => $periodLabel,
            'sport_state' => $sportState,
            'started_by' => $admin?->id,
            'started_at' => $date->copy()->subMinutes(25),
        ])->save();

        if ($events !== null) {
            $session->events()->delete();

            foreach ($events as $eventSpec) {
                ScoreEvent::create([
                    'scoring_session_id' => $session->id,
                    'type' => $eventSpec['type'],
                    'payload' => $eventSpec['payload'],
                    'recorded_by' => $admin?->id,
                ])->forceFill(['created_at' => $eventSpec['at']])->save();
            }
        }

        return $session;
    }

    /**
     * @param  array<string, mixed>  $sportState
     */
    private function completedMatch(
        Meet $meet,
        Event $event,
        Venue $venue,
        string $roundLabel,
        Carbon $date,
        string $sideA,
        string $sideB,
        ?User $admin,
        int $scoreA,
        int $scoreB,
        string $periodLabel,
        array $sportState,
    ): void {
        $slot = $this->slot($meet, $event, $venue, $date, 'Completed');
        $match = $this->match($meet, $event, $slot, $roundLabel);

        if ($match->status !== MatchStatus::Completed) {
            $match->forceFill(['status' => MatchStatus::Completed])->save();
        }

        $session = $match->scoringSessions()->latest('id')->first();
        $isNew = $session === null;

        if ($session === null) {
            $session = new ScoringSession(['match_id' => $match->id]);
        }

        if ($session->status !== ScoringSessionStatus::Ended) {
            $session->fill(['side_a_label' => $sideA, 'side_b_label' => $sideB]);
            $session->forceFill([
                'status' => ScoringSessionStatus::Ended,
                'score_a' => $scoreA,
                'score_b' => $scoreB,
                'period_label' => $periodLabel,
                'sport_state' => $sportState,
                'started_by' => $admin?->id,
                'started_at' => $date->copy()->subHours(2),
                'ended_by' => $admin?->id,
                'ended_at' => $date,
            ])->save();
        }

        if ($isNew) {
            ScoreEvent::create([
                'scoring_session_id' => $session->id,
                'type' => ScoreEventType::Ended,
                'payload' => ['score_a' => $scoreA, 'score_b' => $scoreB],
                'recorded_by' => $admin?->id,
            ]);
        }
    }
}
