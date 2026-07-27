<?php

namespace App\Services;

use App\Enums\ResultStatus;
use App\Models\ResultPlacement;
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
            ->with('entry.athlete.school.district', 'entry.athlete.school.schoolDistrict')
            ->get();

        // A municipality with more than one real school district (e.g.
        // Laak → Laak North / Laak South) shows the finer-grained district
        // name in the "School standings" column below; a municipality with
        // zero or one shows its own name — nothing to disambiguate yet,
        // and most municipalities have no school districts registered at
        // all (an admin fills these in as-needed via the registry).
        $municipalityIds = $placements
            ->pluck('entry.athlete.school.district_id')
            ->unique()
            ->filter()
            ->values();

        $multiDistrictMunicipalityIds = SchoolDistrict::query()
            ->whereIn('district_id', $municipalityIds)
            ->where('active', true)
            ->selectRaw('district_id, COUNT(*) as school_district_count')
            ->groupBy('district_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('district_id')
            ->flip();

        // Grouped by the placed athlete's own school — not the delegation's
        // — so a municipal delegation's medals split correctly across the
        // several schools it pools, and the district/municipality rollup
        // below (unchanged) sums them back up automatically.
        $schools = $placements
            ->groupBy(fn (ResultPlacement $placement): int => $placement->entry->athlete->school_id)
            ->map(function (Collection $group) use ($multiDistrictMunicipalityIds): array {
                $school = $group->firstOrFail()->entry->athlete->school;
                $showSchoolDistrict = $school->school_district_id !== null
                    && $multiDistrictMunicipalityIds->has($school->district_id);

                return [
                    'school' => $school->name,
                    // The grouping key for the municipality-level rollup
                    // below — always the municipality name, never the
                    // finer-grained school district.
                    'municipality' => $school->district->name,
                    'district' => $showSchoolDistrict ? $school->schoolDistrict->name : $school->district->name,
                    ...$this->medals($group),
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
                    'gold' => $gold,
                    'silver' => $silver,
                    'bronze' => $bronze,
                    'total' => $gold + $silver + $bronze,
                    'points' => $this->points($gold, $silver, $bronze),
                ];
            });

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
     * @return Builder<ResultPlacement>
     */
    private function basePlacements(?int $meetId, ?int $sportId, ?string $ageDivision): Builder
    {
        return ResultPlacement::query()
            ->whereIn('rank', [1, 2, 3])
            ->whereHas('result', fn ($result) => $result
                ->where('status', ResultStatus::Validated->value)
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
            );
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
        $byRank = fn (int $rank): int => $placements
            ->filter(fn (ResultPlacement $placement): bool => $placement->rank === $rank)
            ->count();

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
