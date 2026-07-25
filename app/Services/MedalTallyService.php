<?php

namespace App\Services;

use App\Enums\ResultStatus;
use App\Models\ResultPlacement;
use Illuminate\Support\Collection;

/**
 * Medal standings derived at read time from validated results only —
 * there is no stored tally to drift out of sync, so a validated
 * correction changes the tally automatically.
 */
class MedalTallyService
{
    /**
     * District rows are the official standings — school rows are grouped
     * the same way but exist only as a reference for which school each
     * medal came from. Both carry gold/silver/bronze/total counts plus a
     * 1-based position, in conventional medal order.
     *
     * @return array{districts: array<int, array<string, mixed>>, schools: array<int, array<string, mixed>>}
     */
    public function standings(?int $meetId = null, ?int $sportId = null): array
    {
        $placements = ResultPlacement::query()
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
            ->with('entry.athlete.school.district')
            ->get();

        // Grouped by the placed athlete's own school — not the delegation's
        // — so a municipal delegation's medals split correctly across the
        // several schools it pools, and the district/municipality rollup
        // below (unchanged) sums them back up automatically.
        $schools = $placements
            ->groupBy(fn (ResultPlacement $placement): int => $placement->entry->athlete->school_id)
            ->map(function (Collection $group): array {
                $school = $group->firstOrFail()->entry->athlete->school;

                return [
                    'school' => $school->name,
                    'district' => $school->district->name,
                    ...$this->medals($group),
                ];
            });

        $districts = $schools
            ->groupBy('district')
            ->map(fn (Collection $group, string $district): array => [
                'district' => $district,
                'gold' => (int) $group->sum('gold'),
                'silver' => (int) $group->sum('silver'),
                'bronze' => (int) $group->sum('bronze'),
                'total' => (int) $group->sum('total'),
            ]);

        return [
            'districts' => $this->ordered($districts, 'district'),
            'schools' => $this->ordered($schools, 'school'),
        ];
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
