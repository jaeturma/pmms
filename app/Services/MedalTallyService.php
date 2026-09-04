<?php

namespace App\Services;

use App\Enums\AgeDivision;
use App\Enums\DelegationStatus;
use App\Enums\ResultStatus;
use App\Models\Delegation;
use App\Models\EventResult;
use App\Models\ResultPlacement;
use App\Models\School;
use App\Models\SchoolDistrict;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Medal standings derived at read time from validated results only —
 * there is no stored tally to drift out of sync, so a validated
 * correction changes the tally automatically.
 */
class MedalTallyService
{
    /**
     * Gold=3/Silver=2/Bronze=1 — a display-only weighting shown alongside
     * the standings (WP-08-05's "Points" column and "Top by points"
     * widget). It never changes row order: `ordered()`'s conventional
     * gold-then-silver-then-bronze-then-name sort is this app's
     * documented official tie-breaking rule (docs/medal-tally.md) and is
     * left untouched here.
     */
    private const GOLD_POINTS = 3;

    private const SILVER_POINTS = 2;

    private const BRONZE_POINTS = 1;

    /**
     * Divisions represented by official results in the selected tally scope.
     * Event configuration is authoritative; gender and legacy sport categories
     * must not create additional division options.
     *
     * @return array<int, array{id: string, label: string}>
     */
    public function ageDivisionOptions(int $meetId, ?int $sportId = null): array
    {
        $values = EventResult::query()
            ->real()
            ->where('meet_id', $meetId)
            ->where('status', ResultStatus::Official->value)
            ->when($sportId !== null, fn ($query) => $query->whereHas('event', fn ($events) => $events->where('sport_id', $sportId)))
            ->with('event:id,age_division')
            ->get()
            ->pluck('event.age_division')
            ->map(fn (AgeDivision $division): string => $division->value)
            ->unique();

        return collect(AgeDivision::cases())
            ->filter(fn (AgeDivision $division): bool => $values->contains($division->value))
            ->map(fn (AgeDivision $division): array => ['id' => $division->value, 'label' => $division->label()])
            ->values()
            ->all();
    }

    /**
     * District rows are the official standings — school rows are grouped
     * the same way but exist only as a reference for which school each
     * medal came from. Both carry gold/silver/bronze/total/points counts
     * plus a 1-based position, in conventional medal order.
     *
     * @return array{districts: array<int, array<string, mixed>>, schools: array<int, array<string, mixed>>}
     */
    public function standings(?int $meetId = null, ?int $sportId = null, ?string $ageDivision = null): array
    {
        $placements = $this->basePlacements($meetId, $sportId, $ageDivision)
            ->with('result.event', 'entry.delegation', 'entry.athlete.school.district', 'entry.athlete.school.schoolDistrict')
            ->get();
        $tallyPlacements = $this->medalUnits($placements);

        // Start with every approved delegation in the meet, rather than only
        // the delegations that already own a medal. This keeps the tally and
        // standings useful before the first result is submitted: registered
        // municipalities/schools appear with zero counts and zero points.
        $delegations = Delegation::query()
            ->where('status', DelegationStatus::Approved->value)
            ->when($meetId !== null && $meetId > 0, fn ($query) => $query->where('meet_id', $meetId))
            ->with(['school.district', 'school.schoolDistrict', 'district', 'athletes.school.district', 'athletes.school.schoolDistrict'])
            ->get();

        $eligibleSchools = $delegations
            ->flatMap(fn (Delegation $delegation): Collection => collect([$delegation->school])
                ->merge($delegation->athletes->pluck('school')))
            ->filter()
            ->keyBy('id');

        // Placements remain authoritative and are included defensively for
        // historical data whose delegation may predate approval enforcement.
        $placementSchools = $tallyPlacements->pluck('entry.athlete.school')->keyBy('id');
        // Once medals exist, retain the established medal-winner-only rows.
        // The approved-delegation fallback is specifically for the otherwise
        // blank pre-results state requested by the tally views.
        $allSchools = $placements->isEmpty() ? $eligibleSchools : $placementSchools;
        // An incomplete school record cannot form a legitimate public
        // standings row. Preserve its result, but withhold it from both
        // rollups until a municipality is assigned in the school registry.
        $allSchools = $allSchools->filter(
            fn (School $school): bool => $school->district_id !== null
        );
        $multiDistrictMunicipalityIds = $this->multiDistrictMunicipalityIdsForSchools($allSchools);

        // Grouped by the placed athlete's own school — not the delegation's
        // — so a municipal delegation's medals split correctly across the
        // several schools it pools, and the district/municipality rollup
        // below (unchanged) sums them back up automatically.
        $medalsBySchool = $tallyPlacements
            ->groupBy(fn (ResultPlacement $placement): int => $placement->entry->athlete->school_id)
            ->map(fn (Collection $group): array => $this->medals($group));

        $schools = $allSchools
            ->map(function ($school) use ($multiDistrictMunicipalityIds, $medalsBySchool): array {
                $showSchoolDistrict = $school->school_district_id !== null
                    && $multiDistrictMunicipalityIds->has($school->district_id);
                $medals = $medalsBySchool->get($school->id, [
                    'gold' => 0,
                    'silver' => 0,
                    'bronze' => 0,
                    'total' => 0,
                ]);

                return [
                    'school' => $school->name,
                    // The grouping key for the municipality-level rollup
                    // below — always the municipality name, never the
                    // finer-grained school district.
                    'municipality' => $school->district?->name ?? __('Not assigned'),
                    // Carried through so the rollup below can attach each
                    // municipality's real crest (`District::logoUrl()`)
                    // without a second name-matched lookup.
                    'municipality_id' => $school->district_id,
                    'district' => $showSchoolDistrict
                        ? $school->schoolDistrict->name
                        : ($school->district?->name ?? __('Not assigned')),
                    ...$medals,
                    'points' => $this->points($medals['gold'], $medals['silver'], $medals['bronze']),
                ];
            });

        $districts = $schools
            ->groupBy('municipality')
            ->map(function (Collection $group, string $district): array {
                $gold = (int) $group->sum('gold');
                $silver = (int) $group->sum('silver');
                $bronze = (int) $group->sum('bronze');

                return [
                    'district' => $district,
                    'district_id' => $group->first()['municipality_id'],
                    'gold' => $gold,
                    'silver' => $silver,
                    'bronze' => $bronze,
                    'total' => $gold + $silver + $bronze,
                    'points' => $this->points($gold, $silver, $bronze),
                ];
            });

        if ($placements->isEmpty()) {
            $delegations
                ->map(fn (Delegation $delegation) => $delegation->district ?? $delegation->school?->district)
                ->filter()
                ->unique('id')
                ->each(function ($district) use ($districts): void {
                    if (! $districts->has($district->name)) {
                        $districts->put($district->name, [
                            'district' => $district->name,
                            'district_id' => $district->id,
                            'gold' => 0,
                            'silver' => 0,
                            'bronze' => 0,
                            'total' => 0,
                            'points' => 0,
                        ]);
                    }
                });
        }

        return [
            'districts' => $this->ordered($districts, 'district'),
            'schools' => $this->ordered($schools, 'school'),
        ];
    }

    /**
     * Gold/silver/bronze/total awarded per sport (WP-08-05's "Medals by
     * sport" widget) — same validated-only, filtered placement set as
     * `standings()`, grouped differently. Ordered by total descending,
     * then name, so the busiest sport leads.
     *
     * @return array<int, array{sport: string, gold: int, silver: int, bronze: int, total: int}>
     */
    public function medalsBySport(?int $meetId = null, ?int $sportId = null, ?string $ageDivision = null): array
    {
        $placements = $this->basePlacements($meetId, $sportId, $ageDivision)
            ->with('entry.event.sport')
            ->get();

        return $placements
            ->groupBy(fn (ResultPlacement $placement): string => $placement->entry->event->sport->name)
            ->map(function (Collection $group, string $sport): array {
                $medals = $this->medals($group);

                return ['sport' => $sport, ...$medals];
            })
            ->sort(fn (array $a, array $b): int => [$b['total'], $a['sport']] <=> [$a['total'], $b['sport']])
            ->values()
            ->all();
    }

    /**
     * Gold/silver/bronze/total awarded within the last `$hours` — the
     * summary cards' "in the last 24 hours" delta. Real, computed from
     * `event_results.validated_at`, not a stored snapshot.
     *
     * @return array{gold: int, silver: int, bronze: int, total: int}
     */
    public function recentMedals(?int $meetId = null, ?int $sportId = null, ?string $ageDivision = null, int $hours = 24): array
    {
        $placements = $this->basePlacements($meetId, $sportId, $ageDivision)
            ->whereHas('result', fn ($result) => $result->where('validated_at', '>=', Carbon::now()->subHours($hours)))
            ->get();

        return $this->medals($placements);
    }

    /**
     * Individual athletes ranked by their own medal count (gold, then
     * silver, then bronze, then name) — the "Top Medalist" leaderboard.
     * Same validated-only, filtered placement set as `standings()`, just
     * grouped by athlete instead of school/district. `sport` lists every
     * sport the athlete medaled in within this filtered set (comma-
     * separated) — an athlete competing across sports is not split into
     * multiple rows.
     *
     * @return array<int, array<string, mixed>>
     */
    public function topMedalists(?int $meetId = null, ?int $sportId = null, ?string $ageDivision = null, int $limit = 20): array
    {
        $placements = $this->basePlacements($meetId, $sportId, $ageDivision)
            ->with([
                'entry.athlete.school.district',
                'entry.athlete.school.schoolDistrict',
                'entry.event.sport',
            ])
            ->get();

        $multiDistrictMunicipalityIds = $this->multiDistrictMunicipalityIds($placements);

        return $placements
            ->groupBy(fn (ResultPlacement $placement): int => $placement->entry->athlete_id)
            ->map(function (Collection $group) use ($multiDistrictMunicipalityIds): array {
                $athlete = $group->first()->entry->athlete;
                $school = $athlete->school;
                $showSchoolDistrict = $school->school_district_id !== null
                    && $multiDistrictMunicipalityIds->has($school->district_id);

                return [
                    'athlete' => $athlete->fullName(),
                    'grade_level' => $athlete->grade_level,
                    'sport' => $group
                        ->map(fn (ResultPlacement $placement): string => $placement->entry->event->sport->name)
                        ->unique()
                        ->sort()
                        ->implode(', '),
                    'school' => $school->name,
                    'municipality' => $school->district?->name ?? __('Not assigned'),
                    'district' => $showSchoolDistrict
                        ? $school->schoolDistrict->name
                        : ($school->district?->name ?? __('Not assigned')),
                    ...$this->medals($group),
                ];
            })
            ->sort(fn (array $a, array $b): int => [$b['gold'], $b['silver'], $b['bronze'], $a['athlete']]
                <=> [$a['gold'], $a['silver'], $a['bronze'], $b['athlete']])
            ->values()
            ->take($limit)
            ->map(fn (array $row, int $i): array => ['position' => $i + 1, ...$row])
            ->all();
    }

    /**
     * @return Builder<ResultPlacement>
     */
    private function basePlacements(
        ?int $meetId,
        ?int $sportId,
        ?string $ageDivision,
        ?int $districtId = null,
        ?bool $paragames = null,
    ): Builder {
        return ResultPlacement::query()
            ->with('medalAward')
            ->whereIn('rank', [1, 2, 3])
            ->whereHas('result', fn ($result) => $result
                ->whereNull('demo_scenario_id')
                ->where('status', ResultStatus::Official->value)
                ->where(fn ($eligible) => $eligible
                    ->whereNull('match_id')
                    ->orWhereHas('match', fn ($match) => $match->where('awards_medals', true)))
                ->when($meetId !== null && $meetId > 0, fn ($query) => $query->where('meet_id', $meetId)))
            ->when(
                $sportId !== null && $sportId > 0,
                fn ($query) => $query->whereHas(
                    'entry.event',
                    fn ($event) => $event->where('sport_id', $sportId),
                ),
            )
            ->when(
                $ageDivision !== null && $ageDivision !== '',
                fn ($query) => $query->whereHas(
                    'entry.event',
                    fn ($event) => $event->where('age_division', $ageDivision),
                ),
            )
            // The placed athlete's own school's municipality — not the
            // delegation's — same "grouped by the athlete's real school"
            // reasoning `standings()` already documents, so a Province-
            // division municipality's medals resolve correctly regardless
            // of which pooled school the medalist actually attends.
            ->when(
                $districtId !== null,
                fn ($query) => $query->whereHas(
                    'entry.athlete.school',
                    fn ($school) => $school->where('district_id', $districtId),
                ),
            )
            // Paragames is a real, seeded Sport-name prefix
            // ('Paragames - Athletics', 'Paragames - Swimming' —
            // `SportsCatalogSeeder`), not an `AgeDivision` case — this app
            // has no separate Paragames classification field. `true` scopes
            // to Paragames sports only; `false` explicitly excludes them
            // (so the Elementary/Secondary tabs never double-count a
            // Paragames medal that also happens to carry an Elementary/
            // Secondary `age_division`); `null` (default) applies no filter
            // at all.
            ->when($paragames === true, fn ($query) => $query->whereHas(
                'entry.event.sport',
                fn ($sport) => $sport->where('name', 'like', 'Paragames%'),
            ))
            ->when($paragames === false, fn ($query) => $query->whereHas(
                'entry.event.sport',
                fn ($sport) => $sport->where('name', 'not like', 'Paragames%'),
            ));
    }

    /**
     * Gold/silver/bronze/total for one municipality, split into the public
     * portal's four category tabs. Elementary/Secondary explicitly exclude
     * Paragames-sport placements (see `basePlacements()`'s `$paragames`
     * doc) so a Paragames medal is never double-counted across two tabs;
     * `total` applies no age-division or Paragames filter at all, so it
     * always equals elementary+secondary+paragames combined.
     *
     * @return array{elementary: array{gold:int,silver:int,bronze:int,total:int}, secondary: array{gold:int,silver:int,bronze:int,total:int}, paragames: array{gold:int,silver:int,bronze:int,total:int}, total: array{gold:int,silver:int,bronze:int,total:int}}
     */
    public function municipalityMedalBreakdown(int $meetId, int $districtId): array
    {
        return [
            'elementary' => $this->medals(
                $this->basePlacements($meetId, null, AgeDivision::Elementary->value, $districtId, false)->get(),
            ),
            'secondary' => $this->medals(
                $this->basePlacements($meetId, null, AgeDivision::Secondary->value, $districtId, false)->get(),
            ),
            'paragames' => $this->medals(
                $this->basePlacements($meetId, null, null, $districtId, true)->get(),
            ),
            'total' => $this->medals(
                $this->basePlacements($meetId, null, null, $districtId, null)->get(),
            ),
        ];
    }

    /**
     * The individual medal-winning placements for one municipality —
     * unlike every aggregate method above, this returns one row per medal
     * actually won, for the public municipality profile's "Medal Winners"
     * list. `$category` matches the same four tabs as
     * `municipalityMedalBreakdown()` (`null` = every category, unfiltered).
     *
     * A team-event medal (`Event::is_team_event`) is recorded in this app
     * as N individual `ResultPlacement` rows — one per rostered, Confirmed-
     * Entry athlete — all sharing the same result and rank, tied together
     * via `is_tie` (see `ResultController::assertPlacementsValid()`; there
     * is no single "team" placement row anywhere in the schema). So this
     * groups placements by `(event_result_id, rank)` and renders any group
     * belonging to a team event as one team-medal row (roster names carried
     * separately) rather than N duplicate individual rows.
     *
     * @return array<int, array{medal: string, participant_type: string, athlete_name: ?string, team_name: ?string, roster: array<int, string>, sport: string, event: string, gender: string, level: string, school: ?string}>
     */
    public function municipalityMedalWinners(int $meetId, int $districtId, ?string $category = null): array
    {
        [$ageDivision, $paragames] = match ($category) {
            'elementary' => [AgeDivision::Elementary->value, false],
            'secondary' => [AgeDivision::Secondary->value, false],
            'paragames' => [null, true],
            default => [null, null],
        };

        $placements = $this->basePlacements($meetId, null, $ageDivision, $districtId, $paragames)
            ->with(['result.event.sport', 'entry.athlete.school.district'])
            ->get();

        return $placements
            ->groupBy(fn (ResultPlacement $placement): string => "{$placement->event_result_id}-{$placement->rank}")
            ->map(function (Collection $group): array {
                /** @var ResultPlacement $first */
                $first = $group->first();
                $event = $first->result->event;
                $isTeam = $event->is_team_event;
                $school = $first->entry->athlete->school;

                return [
                    'medal' => match ($first->rank) {
                        1 => 'gold',
                        2 => 'silver',
                        3 => 'bronze',
                        default => 'other',
                    },
                    'participant_type' => $isTeam ? 'team' : 'athlete',
                    'athlete_name' => $isTeam ? null : $first->entry->athlete->fullName(),
                    'team_name' => $isTeam
                        ? sprintf('%s %s Team', $school->district?->name ?? $school->name, $event->sport->name)
                        : null,
                    'roster' => $isTeam
                        ? $group->map(fn (ResultPlacement $p): string => $p->entry->athlete->fullName())->values()->all()
                        : [],
                    'sport' => $event->sport->name,
                    'event' => $event->name,
                    'gender' => $event->gender->label(),
                    'level' => $event->age_division->label(),
                    'school' => $school->name,
                ];
            })
            ->sortBy(fn (array $row): int => match ($row['medal']) {
                'gold' => 1,
                'silver' => 2,
                'bronze' => 3,
                default => 4,
            })
            ->values()
            ->all();
    }

    /**
     * A municipality with more than one real school district (e.g. Laak →
     * Laak North / Laak South) shows the finer-grained district name in
     * the "School standings"/"Top Medalist" district column; a
     * municipality with zero or one shows its own name — nothing to
     * disambiguate yet, and most municipalities have no school districts
     * registered at all (an admin fills these in as-needed via the
     * registry). Shared by `standings()` and `topMedalists()`.
     *
     * @param  Collection<int, ResultPlacement>  $placements
     * @return Collection<int, int>
     */
    private function multiDistrictMunicipalityIds(Collection $placements): Collection
    {
        return $this->multiDistrictMunicipalityIdsForSchools(
            $placements->pluck('entry.athlete.school')->keyBy('id'),
        );
    }

    /**
     * @param  Collection<int, School>  $schools
     * @return Collection<int, int>
     */
    private function multiDistrictMunicipalityIdsForSchools(Collection $schools): Collection
    {
        $municipalityIds = $schools
            ->pluck('district_id')
            ->unique()
            ->filter()
            ->values();

        return SchoolDistrict::query()
            ->whereIn('district_id', $municipalityIds)
            ->where('active', true)
            ->selectRaw('district_id, COUNT(*) as school_district_count')
            ->groupBy('district_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('district_id')
            ->flip();
    }

    private function points(int $gold, int $silver, int $bronze): int
    {
        return $gold * self::GOLD_POINTS + $silver * self::SILVER_POINTS + $bronze * self::BRONZE_POINTS;
    }

    /**
     * @param  Collection<int, ResultPlacement>  $placements
     * @return array{gold: int, silver: int, bronze: int, total: int}
     */
    private function medals(Collection $placements): array
    {
        $placements = $this->medalUnits($placements);
        $byRank = fn (int $rank): int => (int) $placements
            ->filter(fn (ResultPlacement $placement): bool => $placement->rank === $rank)
            ->sum(fn (ResultPlacement $placement): int => $placement->medalAward?->tally_quantity ?? 1);

        $gold = $byRank(1);
        $silver = $byRank(2);
        $bronze = $byRank(3);

        return [
            'gold' => $gold,
            'silver' => $silver,
            'bronze' => $bronze,
            'total' => $gold + $silver + $bronze,
        ];
    }

    /**
     * Collapse legacy per-athlete team placements to the one snapshotted
     * award whose tally quantity is authoritative. Individual placements
     * remain one-for-one.
     *
     * @param  Collection<int, ResultPlacement>  $placements
     * @return Collection<int, ResultPlacement>
     */
    private function medalUnits(Collection $placements): Collection
    {
        return $placements->unique(function (ResultPlacement $placement): string {
            if (! $placement->result->event->is_team_event) {
                return 'individual:'.$placement->id;
            }

            return implode(':', [
                'team',
                $placement->event_result_id,
                $placement->rank,
                $placement->team_entry_id ?? $placement->entry->delegation_id,
            ]);
        })->values();
    }

    /**
     * Conventional medal ordering: gold, then silver, then bronze, then name.
     *
     * @template TRow of array<string, mixed>
     *
     * @param  Collection<array-key, TRow>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function ordered(Collection $rows, string $nameKey): array
    {
        return $rows
            ->sort(fn (array $a, array $b): int => [$b['gold'], $b['silver'], $b['bronze'], $a[$nameKey]]
                <=> [$a['gold'], $a['silver'], $a['bronze'], $b[$nameKey]])
            ->values()
            ->map(fn (array $row, int $i): array => ['position' => $i + 1, ...$row])
            ->all();
    }
}
