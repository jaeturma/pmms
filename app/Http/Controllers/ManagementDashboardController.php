<?php

namespace App\Http\Controllers;

use App\Enums\DelegationStatus;
use App\Enums\EligibilityStatus;
use App\Enums\IncidentStatus;
use App\Enums\MeetStatus;
use App\Enums\ProtestStatus;
use App\Enums\ResultStatus;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\Division;
use App\Models\EligibilityReview;
use App\Models\Entry;
use App\Models\EventResult;
use App\Models\EventSchedule;
use App\Models\Incident;
use App\Models\Meet;
use App\Models\Personnel;
use App\Models\Protest;
use App\Services\AuditLogger;
use App\Services\MedalTallyService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Phase 5 — cross-meet/historical oversight for Admin and Organizer, on top
 * of (never duplicating) Phase 3's single-Active-meet operations block.
 * Every widget here reads meets already filtered by meetsInScope().
 */
class ManagementDashboardController extends Controller
{
    /**
     * An Active meet with an encoded (not yet validated) result older than
     * this is flagged as stalled — a plain age check against existing
     * timestamps, not a predictive score.
     */
    private const STALLED_RESULT_HOURS = 24;

    public function __construct(private readonly AuditLogger $audit) {}

    public function index(MedalTallyService $tally): Response
    {
        $meets = collect([Meet::current()]);

        return Inertia::render('management/index', [
            ...$this->widgetData($meets, $tally),
            'generatedAt' => now()->toDayDateTimeString(),
        ]);
    }

    /**
     * Printable version of the management dashboard — same widgets,
     * print-styled page instead of the interactive one.
     */
    public function report(MedalTallyService $tally): Response
    {
        $meets = collect([Meet::current()]);

        return Inertia::render('reports/management', [
            ...$this->widgetData($meets, $tally),
            'generatedAt' => now()->toDayDateTimeString(),
        ]);
    }

    /**
     * CSV of the management dashboard, audited — one block per widget,
     * each with its own header row, since the widgets have unrelated
     * shapes (unlike the tally report, which can share one header across
     * district/school rows).
     */
    public function downloadReport(MedalTallyService $tally): StreamedResponse
    {
        $meets = collect([Meet::current()]);
        $data = $this->widgetData($meets, $tally);

        $this->audit->record('report.management_exported', null, [
            'meet' => Meet::current()->name,
        ]);

        $rows = [];

        $rows[] = ['Participation - Delegations by status'];
        $rows[] = ['Meet', 'Draft', 'Submitted', 'Approved', 'Total'];
        foreach ($data['participation']['rows'] as $row) {
            $rows[] = [$row['meet'], $row['delegations']['draft'], $row['delegations']['submitted'], $row['delegations']['approved'], $row['delegations']['total']];
        }
        $rows[] = [];

        $rows[] = ['Participation - Individuals & Entries'];
        $rows[] = ['Meet', 'Athletes', 'Personnel', 'Entries'];
        foreach ($data['participation']['rows'] as $row) {
            $rows[] = [$row['meet'], $row['athletes'], $row['personnel'], $row['entries']];
        }
        $rows[] = [];

        $rows[] = ['Operations Progress & Risk'];
        $rows[] = ['Meet', 'Encoded', 'Validated', 'Eligibility Pending', 'Eligibility Returned', 'Protests Open', 'Protests Decided', 'Incidents Open', 'Stalled'];
        foreach ($data['operations'] as $row) {
            $rows[] = [
                $row['meet'], $row['results']['encoded'], $row['results']['validated'],
                $row['eligibility']['pending'], $row['eligibility']['returned'],
                $row['protests']['filed'] + $row['protests']['under_review'],
                $row['protests']['upheld'] + $row['protests']['dismissed'],
                $row['incidents']['open'], $row['is_stalled'] ? 'Yes' : 'No',
            ];
        }
        $rows[] = [];

        $areaLabel = Division::current()->areaLabel();

        $rows[] = ["{$areaLabel} Performance History"];
        $rows[] = ['Position', $areaLabel, 'Gold', 'Silver', 'Bronze', 'Total'];
        foreach ($data['performance']['districts'] as $row) {
            $rows[] = [$row['position'], $row['district'], $row['gold'], $row['silver'], $row['bronze'], $row['total']];
        }
        $rows[] = [];

        $rows[] = ['School Performance History (reference)'];
        $rows[] = ['Position', 'School', $areaLabel, 'Gold', 'Silver', 'Bronze', 'Total'];
        foreach ($data['performance']['schools'] as $row) {
            $rows[] = [$row['position'], $row['school'], $row['district'], $row['gold'], $row['silver'], $row['bronze'], $row['total']];
        }
        $rows[] = [];

        $rows[] = ['Venue Utilization'];
        $rows[] = ['Venue', 'Slots', 'Hours', 'Meets', 'Events'];
        foreach ($data['venues'] as $row) {
            $rows[] = [$row['venue'], $row['slots'], $row['hours'], $row['meets'], $row['events']];
        }

        return $this->csv('management-dashboard.csv', $rows);
    }

    /**
     * The four Phase 5 widgets for one set of meets in scope — shared by
     * the interactive dashboard, the printable report, and the CSV export
     * so they can never disagree.
     *
     * @param  Collection<int, Meet>  $meets
     * @return array<string, mixed>
     */
    private function widgetData(Collection $meets, MedalTallyService $tally): array
    {
        return [
            'meets' => $meets
                ->map(fn (Meet $meet): array => [
                    'id' => $meet->id,
                    'name' => $meet->name,
                    'school_year' => $meet->school_year,
                    'starts_at' => $meet->starts_at->format('M j, Y'),
                    'ends_at' => $meet->ends_at->format('M j, Y'),
                    'status_label' => $meet->status->label(),
                ])
                ->values(),
            'participation' => $this->participation($meets),
            'operations' => $this->operationsProgress($meets),
            'performance' => $this->performanceHistory($meets, $tally),
            'venues' => $this->venueUtilization($meets),
        ];
    }

    /**
     * Per-meet participation and registration counts, plus totals across
     * the meets in scope. Delegations (the registering unit) and
     * individuals (their own school, via the delegation they registered
     * under) are always counted separately — never conflated, per
     * DESIGN-NOTES.
     *
     * @param  Collection<int, Meet>  $meets
     * @return array{rows: array<int, array<string, mixed>>, totals: array<string, int>}
     */
    private function participation(Collection $meets): array
    {
        $rows = $meets
            ->map(function (Meet $meet): array {
                $delegationCounts = Delegation::query()
                    ->where('meet_id', $meet->id)
                    ->selectRaw('status, count(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status');

                return [
                    'meet_id' => $meet->id,
                    'meet' => $meet->name,
                    'school_year' => $meet->school_year,
                    'delegations' => [
                        'draft' => (int) ($delegationCounts[DelegationStatus::Draft->value] ?? 0),
                        'submitted' => (int) ($delegationCounts[DelegationStatus::Submitted->value] ?? 0),
                        'approved' => (int) ($delegationCounts[DelegationStatus::Approved->value] ?? 0),
                        'total' => (int) $delegationCounts->sum(),
                    ],
                    'athletes' => Athlete::query()
                        ->whereHas('delegation', fn ($query) => $query->where('meet_id', $meet->id))
                        ->count(),
                    'personnel' => Personnel::query()
                        ->whereHas('delegation', fn ($query) => $query->where('meet_id', $meet->id))
                        ->count(),
                    'entries' => Entry::query()
                        ->whereHas('delegation', fn ($query) => $query->where('meet_id', $meet->id))
                        ->count(),
                ];
            })
            ->values()
            ->all();

        return [
            'rows' => $rows,
            'totals' => [
                'delegations' => (int) collect($rows)->sum('delegations.total'),
                'athletes' => (int) collect($rows)->sum('athletes'),
                'personnel' => (int) collect($rows)->sum('personnel'),
                'entries' => (int) collect($rows)->sum('entries'),
            ],
        ];
    }

    /**
     * Per-meet encoding/validation, eligibility, protest, and incident
     * progress, plus a plain age-based "stalled" flag — reuses the
     * existing status enums, never recomputes new state.
     *
     * @param  Collection<int, Meet>  $meets
     * @return array<int, array<string, mixed>>
     */
    private function operationsProgress(Collection $meets): array
    {
        return $meets
            ->map(function (Meet $meet): array {
                $resultCounts = EventResult::query()
                    ->where('meet_id', $meet->id)
                    ->selectRaw('status, count(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status');

                $eligibilityCounts = EligibilityReview::query()
                    ->where('meet_id', $meet->id)
                    ->selectRaw('status, count(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status');

                $protestCounts = Protest::query()
                    ->whereHas('delegation', fn ($query) => $query->where('meet_id', $meet->id))
                    ->selectRaw('status, count(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status');

                $incidentCounts = Incident::query()
                    ->where('meet_id', $meet->id)
                    ->selectRaw('status, count(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status');

                $isStalled = $meet->status === MeetStatus::Active
                    && EventResult::query()
                        ->where('meet_id', $meet->id)
                        ->where('status', ResultStatus::Encoded->value)
                        ->where('encoded_at', '<', now()->subHours(self::STALLED_RESULT_HOURS))
                        ->exists();

                return [
                    'meet_id' => $meet->id,
                    'meet' => $meet->name,
                    'results' => [
                        'encoded' => (int) ($resultCounts[ResultStatus::Encoded->value] ?? 0),
                        'validated' => (int) ($resultCounts[ResultStatus::Validated->value] ?? 0),
                    ],
                    'eligibility' => [
                        'pending' => (int) ($eligibilityCounts[EligibilityStatus::Pending->value] ?? 0),
                        'approved' => (int) ($eligibilityCounts[EligibilityStatus::Approved->value] ?? 0),
                        'returned' => (int) ($eligibilityCounts[EligibilityStatus::Returned->value] ?? 0),
                    ],
                    'protests' => [
                        'filed' => (int) ($protestCounts[ProtestStatus::Filed->value] ?? 0),
                        'under_review' => (int) ($protestCounts[ProtestStatus::UnderReview->value] ?? 0),
                        'upheld' => (int) ($protestCounts[ProtestStatus::Upheld->value] ?? 0),
                        'dismissed' => (int) ($protestCounts[ProtestStatus::Dismissed->value] ?? 0),
                    ],
                    'incidents' => [
                        'open' => (int) ($incidentCounts[IncidentStatus::Open->value] ?? 0),
                        'resolved' => (int) ($incidentCounts[IncidentStatus::Resolved->value] ?? 0),
                    ],
                    'is_stalled' => $isStalled,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Medal-tally standings aggregated across the meets in scope — calls
     * MedalTallyService::standings() once per meet and sums the results; it
     * does not reimplement tally derivation. District/municipality
     * standings are the official aggregate, listed first, matching the
     * live tally's district-first convention (docs/medal-tally.md);
     * school-level history is the reference table below it.
     *
     * @param  Collection<int, Meet>  $meets
     * @return array{districts: array<int, array<string, mixed>>, schools: array<int, array<string, mixed>>}
     */
    private function performanceHistory(Collection $meets, MedalTallyService $tally): array
    {
        $districtTotals = [];
        $schoolTotals = [];

        foreach ($meets as $meet) {
            $standings = $tally->standings($meet->id);

            foreach ($standings['districts'] as $row) {
                $key = $row['district'];
                $districtTotals[$key] ??= ['district' => $row['district'], 'gold' => 0, 'silver' => 0, 'bronze' => 0, 'total' => 0];
                $districtTotals[$key]['gold'] += $row['gold'];
                $districtTotals[$key]['silver'] += $row['silver'];
                $districtTotals[$key]['bronze'] += $row['bronze'];
                $districtTotals[$key]['total'] += $row['total'];
            }

            foreach ($standings['schools'] as $row) {
                $key = $row['school'].'|'.$row['district'];
                $schoolTotals[$key] ??= ['school' => $row['school'], 'district' => $row['district'], 'gold' => 0, 'silver' => 0, 'bronze' => 0, 'total' => 0];
                $schoolTotals[$key]['gold'] += $row['gold'];
                $schoolTotals[$key]['silver'] += $row['silver'];
                $schoolTotals[$key]['bronze'] += $row['bronze'];
                $schoolTotals[$key]['total'] += $row['total'];
            }
        }

        return [
            'districts' => $this->orderedStandings(array_values($districtTotals), 'district'),
            'schools' => $this->orderedStandings(array_values($schoolTotals), 'school'),
        ];
    }

    /**
     * Conventional medal ordering: gold, then silver, then bronze, then
     * name — the same convention MedalTallyService::ordered() uses, applied
     * here to the across-meets totals.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function orderedStandings(array $rows, string $nameKey): array
    {
        return collect($rows)
            ->sort(fn (array $a, array $b): int => [$b['gold'], $b['silver'], $b['bronze'], $a[$nameKey]]
                <=> [$a['gold'], $a['silver'], $a['bronze'], $b[$nameKey]])
            ->values()
            ->map(fn (array $row, int $i): array => ['position' => $i + 1, ...$row])
            ->all();
    }

    /**
     * Per-venue scheduled slot count, total scheduled hours, and distinct
     * meets/events, across the meets in scope — derived from
     * `EventSchedule`, no new tables. Only venues with at least one slot
     * in scope are returned (an unused venue has nothing to report).
     *
     * @param  Collection<int, Meet>  $meets
     * @return array<int, array<string, mixed>>
     */
    private function venueUtilization(Collection $meets): array
    {
        return EventSchedule::query()
            ->whereIn('meet_id', $meets->pluck('id'))
            ->with('venue:id,name')
            ->get()
            ->groupBy('venue_id')
            ->map(function (Collection $slots): array {
                /** @var EventSchedule $first */
                $first = $slots->first();

                $minutes = $slots->sum(fn (EventSchedule $slot): int => (int) abs(
                    Carbon::parse($slot->ends_at)->diffInMinutes(Carbon::parse($slot->starts_at)),
                ));

                return [
                    'venue_id' => $first->venue_id,
                    'venue' => $first->venue->name,
                    'slots' => $slots->count(),
                    'hours' => round($minutes / 60, 1),
                    'meets' => $slots->pluck('meet_id')->unique()->count(),
                    'events' => $slots->pluck('event_id')->unique()->count(),
                ];
            })
            ->sortBy('venue')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function csv(string $filename, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                return;
            }

            foreach ($rows as $row) {
                fputcsv($out, $row);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
