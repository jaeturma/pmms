<?php

namespace App\Http\Controllers;

use App\Enums\EntryStatus;
use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\Division;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventResult;
use App\Models\EventSchedule;
use App\Models\Meet;
use App\Models\Personnel;
use App\Models\ResultPlacement;
use App\Models\School;
use App\Models\Sport;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\MedalTallyService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Printable delegation roster: athletes and personnel of one delegation.
     */
    public function delegationRoster(Delegation $delegation): Response
    {
        Gate::authorize('viewRoster', $delegation);

        return Inertia::render('reports/delegation-roster', [
            ...$this->rosterData($delegation),
            'generatedAt' => now()->toDayDateTimeString(),
        ]);
    }

    /**
     * CSV of the delegation roster — a sensitive export, audited.
     */
    public function downloadDelegationRoster(Delegation $delegation): StreamedResponse
    {
        Gate::authorize('viewRoster', $delegation);

        $data = $this->rosterData($delegation);

        $this->audit->record('report.roster_exported', $delegation, [
            'registrant' => $delegation->registrantName(),
            'meet' => $delegation->meet->name,
        ]);

        $rows = [['Type', 'Last Name', 'First Name', 'Sex', 'Birthdate', 'Age', 'LRN', 'Grade', 'Role', 'Sports']];

        foreach ($data['athletes'] as $athlete) {
            $rows[] = [
                'Athlete', $athlete['last_name'], $athlete['first_name'], $athlete['sex_label'],
                $athlete['birthdate'], $athlete['age'], $athlete['lrn'], $athlete['grade_level'], '', '',
            ];
        }

        foreach ($data['personnel'] as $person) {
            $rows[] = [
                'Personnel', $person['last_name'], $person['first_name'], '', '', '', '', '',
                $person['role_label'], $person['sports'],
            ];
        }

        $slug = str_replace(' ', '-', strtolower($delegation->registrantName()));

        return $this->csv("roster-{$slug}.csv", $rows);
    }

    /**
     * Printable entry list for one event, officer-scoped like the entries
     * registry. Withdrawn entries are excluded — this is the start list.
     */
    public function eventEntries(Request $request, Event $event): Response
    {
        Gate::authorize('viewAny', Entry::class);
        $this->authorizeEventScope($request, $event);

        return Inertia::render('reports/event-entries', [
            'event' => $this->eventSummary($event),
            'entries' => $this->eventEntryRows($request, $event),
            'generatedAt' => now()->toDayDateTimeString(),
        ]);
    }

    /**
     * CSV of the per-event entry list — a sensitive export, audited.
     */
    public function downloadEventEntries(Request $request, Event $event): StreamedResponse
    {
        Gate::authorize('viewAny', Entry::class);
        $this->authorizeEventScope($request, $event);

        $entries = $this->eventEntryRows($request, $event);

        $this->audit->record('report.event_entries_exported', $event, [
            'event' => $this->eventSummary($event)['label'],
            'rows' => count($entries),
        ]);

        $rows = [['Last Name', 'First Name', 'Sex', 'Age', 'Grade', 'School', 'Status']];

        foreach ($entries as $entry) {
            $rows[] = [
                $entry['last_name'], $entry['first_name'], $entry['sex_label'], $entry['age'],
                $entry['grade_level'], $entry['school'], $entry['status_label'],
            ];
        }

        return $this->csv("entries-event-{$event->id}.csv", $rows);
    }

    /**
     * School participation summary: per-school counts, optionally for one
     * meet. Aggregates only — open to every authenticated user.
     */
    public function participation(): Response
    {
        return Inertia::render('reports/school-participation', [
            'rows' => $this->participationRows(),
            'generatedAt' => now()->toDayDateTimeString(),
        ]);
    }

    /**
     * CSV of the participation summary, audited like the other exports.
     */
    public function downloadParticipation(): StreamedResponse
    {
        $this->audit->record('report.participation_exported', null, [
            'meet' => Meet::current()->name,
        ]);

        $rows = [['School', Division::current()->areaLabel(), 'Delegations', 'Athletes', 'Personnel', 'Entries']];

        foreach ($this->participationRows() as $row) {
            $rows[] = [
                $row['school'], $row['district'], $row['delegations_count'],
                $row['athletes_count'], $row['personnel_count'], $row['entries_count'],
            ];
        }

        return $this->csv('school-participation.csv', $rows);
    }

    /**
     * Official result sheet for one validated event result — validated
     * results are official meet outcomes, readable by all roles.
     */
    public function resultSheet(Request $request, EventResult $result): Response
    {
        abort_unless($result->isValidated(), 404);
        $this->authorizeEventScope($request, $result->event);

        return Inertia::render('reports/result-sheet', [
            ...$this->resultSheetData($result),
            'generatedAt' => now()->toDayDateTimeString(),
        ]);
    }

    /**
     * CSV of the official result sheet, audited.
     */
    public function downloadResultSheet(Request $request, EventResult $result): StreamedResponse
    {
        abort_unless($result->isValidated(), 404);
        $this->authorizeEventScope($request, $result->event);

        $data = $this->resultSheetData($result);

        $this->audit->record('report.result_sheet_exported', $result, [
            'meet' => $data['result']['meet'],
            'event' => $data['result']['event'],
        ]);

        $rows = [['Rank', 'Athlete', 'School', 'Mark', 'Tie']];

        foreach ($data['placements'] as $placement) {
            $rows[] = [
                $placement['rank'], $placement['athlete'], $placement['school'],
                $placement['mark'], $placement['is_tie'] ? 'Yes' : '',
            ];
        }

        return $this->csv("result-sheet-{$result->id}.csv", $rows);
    }

    /**
     * Printable medal tally — official district/municipality standings plus
     * a school-level reference table — validated results only, readable by
     * all roles.
     */
    public function tallyReport(Request $request, MedalTallyService $tally): Response
    {
        $meet = Meet::current();
        $sportId = $request->integer('sport_id');

        $standings = $tally->standings($meet->id, $sportId > 0 ? $sportId : null);

        return Inertia::render('reports/medal-tally', [
            'schools' => $standings['schools'],
            'districts' => $standings['districts'],
            'meet' => $meet->name,
            'sport' => $sportId > 0 ? Sport::query()->find($sportId)?->name : null,
            'filters' => [
                'sport_id' => $sportId > 0 ? $sportId : null,
            ],
            'generatedAt' => now()->toDayDateTimeString(),
        ]);
    }

    /**
     * CSV of the medal tally, audited.
     */
    public function downloadTallyReport(Request $request, MedalTallyService $tally): StreamedResponse
    {
        $meet = Meet::current();
        $sportId = $request->integer('sport_id');

        $standings = $tally->standings($meet->id, $sportId > 0 ? $sportId : null);

        $this->audit->record('report.tally_exported', null, [
            'meet' => $meet->name,
            'sport' => $sportId > 0 ? Sport::query()->find($sportId)?->name : 'all sports',
        ]);

        $areaLabel = Division::current()->areaLabel();

        $rows = [['Type', 'Position', 'Name', $areaLabel, 'Gold', 'Silver', 'Bronze', 'Total']];

        foreach ($standings['districts'] as $row) {
            $rows[] = [
                $areaLabel, $row['position'], $row['district'], '',
                $row['gold'], $row['silver'], $row['bronze'], $row['total'],
            ];
        }

        foreach ($standings['schools'] as $row) {
            $rows[] = [
                'School', $row['position'], $row['school'], $row['district'],
                $row['gold'], $row['silver'], $row['bronze'], $row['total'],
            ];
        }

        return $this->csv('medal-tally.csv', $rows);
    }

    /**
     * Daily schedule sheet: one day's slots grouped by venue, all roles.
     */
    public function scheduleSheet(Request $request): Response
    {
        $date = $this->sheetDate($request);

        return Inertia::render('reports/schedule-sheet', [
            'date' => $date,
            'venues' => $this->scheduleSheetVenues($date, $request->user()),
            'generatedAt' => now()->toDayDateTimeString(),
        ]);
    }

    /**
     * CSV of the daily schedule sheet, audited.
     */
    public function downloadScheduleSheet(Request $request): StreamedResponse
    {
        $date = $this->sheetDate($request);
        $venues = $this->scheduleSheetVenues($date, $request->user());

        $this->audit->record('report.schedule_exported', null, [
            'date' => $date,
            'venues' => count($venues),
        ]);

        $rows = [['Venue', 'Start', 'End', 'Event', 'Meet', 'Note']];

        foreach ($venues as $venue) {
            foreach ($venue['slots'] as $slot) {
                $rows[] = [
                    $venue['venue'], $slot['starts_at'], $slot['ends_at'],
                    $slot['event'], $slot['meet'], $slot['note'],
                ];
            }
        }

        return $this->csv("schedule-{$date}.csv", $rows);
    }

    /**
     * @return array{result: array<string, mixed>, placements: array<int, array<string, mixed>>}
     */
    private function resultSheetData(EventResult $result): array
    {
        $result->load([
            'meet:id,name,school_year',
            'event.sport:id,name',
            'encodedBy:id,name',
            'validatedBy:id,name',
            'placements.entry.athlete:id,first_name,last_name,school_id',
            'placements.entry.athlete.school:id,name',
        ]);

        return [
            'result' => [
                'id' => $result->id,
                'meet' => $result->meet->name,
                'school_year' => $result->meet->school_year,
                'event' => sprintf(
                    '%s — %s (%s, %s)',
                    $result->event->sport->name,
                    $result->event->name,
                    $result->event->gender->label(),
                    $result->event->age_division->label(),
                ),
                'encoded_by' => $result->encodedBy?->name,
                'validated_by' => $result->validatedBy?->name,
                'validated_at' => $result->validated_at?->toDayDateTimeString(),
            ],
            'placements' => $result->placements
                ->sortBy([['rank', 'asc']])
                ->map(fn (ResultPlacement $placement): array => [
                    'rank' => $placement->rank,
                    'athlete' => $placement->entry->athlete->fullName(),
                    'school' => $placement->entry->athlete->school->name,
                    'mark' => $placement->mark,
                    'is_tie' => $placement->is_tie,
                ])
                ->values()
                ->all(),
        ];
    }

    private function sheetDate(Request $request): string
    {
        $date = $request->string('date')->toString();

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1
            ? $date
            : now()->toDateString();
    }

    /**
     * @return array<int, array{venue: string, slots: array<int, array<string, mixed>>}>
     */
    private function scheduleSheetVenues(string $date, User $user): array
    {
        return EventSchedule::query()
            ->whereDate('scheduled_date', $date)
            ->when($user->tournamentEventIds()->isNotEmpty(), fn ($query) => $query
                ->whereIn('event_id', $user->tournamentEventIds()))
            ->with(['venue:id,name', 'meet:id,name', 'event.sport:id,name'])
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn (EventSchedule $slot): string => $slot->venue->name)
            ->sortKeys()
            ->map(fn ($slots, string $venue): array => [
                'venue' => $venue,
                'slots' => $slots
                    ->map(fn (EventSchedule $slot): array => [
                        'id' => $slot->id,
                        'starts_at' => substr($slot->starts_at, 0, 5),
                        'ends_at' => substr($slot->ends_at, 0, 5),
                        'event' => sprintf(
                            '%s — %s (%s, %s)',
                            $slot->event->sport->name,
                            $slot->event->name,
                            $slot->event->gender->label(),
                            $slot->event->age_division->label(),
                        ),
                        'meet' => $slot->meet->name,
                        'note' => $slot->note,
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    private function authorizeEventScope(Request $request, Event $event): void
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->tournamentEventIds()->isNotEmpty()) {
            abort_unless($user->tournamentEventIds()->contains($event->id), 403);
        }

        if ($user->role === UserRole::Coach) {
            abort_unless($user->approvedCoachEventIds()->contains($event->id), 403);
        }
    }

    /**
     * @return array{delegation: array<string, mixed>, athletes: array<int, array<string, mixed>>, personnel: array<int, array<string, mixed>>}
     */
    private function rosterData(Delegation $delegation): array
    {
        $delegation->load([
            'school:id,name',
            'district:id,name',
            'meet:id,name,school_year',
            'athletes' => fn ($query) => $query->orderBy('last_name')->orderBy('first_name'),
            'personnel' => fn ($query) => $query->with('sports:id,name')->orderBy('last_name')->orderBy('first_name'),
        ]);

        return [
            'delegation' => [
                'id' => $delegation->id,
                'registrant' => $delegation->registrantName(),
                'meet' => $delegation->meet->name,
                'school_year' => $delegation->meet->school_year,
                'head_name' => $delegation->head_name,
                'status_label' => $delegation->status->label(),
            ],
            'athletes' => $delegation->athletes->map(fn (Athlete $athlete): array => [
                'id' => $athlete->id,
                'last_name' => $athlete->last_name,
                'first_name' => $athlete->first_name,
                'sex_label' => $athlete->sex->label(),
                'birthdate' => $athlete->birthdate->toDateString(),
                'age' => $athlete->age(),
                'lrn' => $athlete->lrn,
                'grade_level' => $athlete->grade_level,
            ])->values()->all(),
            'personnel' => $delegation->personnel->map(fn (Personnel $person): array => [
                'id' => $person->id,
                'last_name' => $person->last_name,
                'first_name' => $person->first_name,
                'role_label' => $person->role->label(),
                'sports' => $person->sports->pluck('name')->implode('; '),
            ])->values()->all(),
        ];
    }

    /**
     * @return array{id: int, label: string}
     */
    private function eventSummary(Event $event): array
    {
        $event->loadMissing('sport:id,name');

        return [
            'id' => $event->id,
            'label' => sprintf(
                '%s — %s (%s, %s)',
                $event->sport->name,
                $event->name,
                $event->gender->label(),
                $event->age_division->label(),
            ),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function eventEntryRows(Request $request, Event $event): array
    {
        /** @var User $user */
        $user = $request->user();

        $query = $event->entries()
            ->with([
                'athlete:id,first_name,last_name,sex,birthdate,grade_level,school_id',
                'athlete.school:id,name',
            ])
            ->where('status', '!=', EntryStatus::Withdrawn->value);

        if ($user->role === UserRole::DelegationOfficer) {
            $query->whereHas(
                'delegation.officers',
                fn ($officers) => $officers->whereKey($user->getKey()),
            );
        }

        return $query->get()
            ->map(fn (Entry $entry): array => [
                'id' => $entry->id,
                'last_name' => $entry->athlete->last_name,
                'first_name' => $entry->athlete->first_name,
                'sex_label' => $entry->athlete->sex->label(),
                'age' => $entry->athlete->age(),
                'grade_level' => $entry->athlete->grade_level,
                'school' => $entry->athlete->school->name,
                'status_label' => $entry->status->label(),
            ])
            ->sortBy([['school', 'asc'], ['last_name', 'asc']])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function participationRows(): array
    {
        $meetId = Meet::current()->id;

        // Athletes, personnel, and entries reach their meet through their
        // own delegation (not a direct meet_id column), so the filter has
        // to go through that relation rather than a plain where().
        $byDelegationMeet = fn (Builder $query) => $query->whereHas('delegation', fn (Builder $delegation) => $delegation->where('meet_id', $meetId));

        return School::query()
            ->with('district:id,name')
            ->withCount([
                'delegations' => fn (Builder $query) => $query->where('meet_id', $meetId),
                'athletes' => $byDelegationMeet,
                'personnel' => $byDelegationMeet,
                'entries' => $byDelegationMeet,
            ])
            ->orderBy('name')
            ->get()
            // A school participates either by registering its own
            // delegation (City) or by having athletes/personnel of its
            // own under a municipal delegation (Province) — checking
            // delegations_count alone would silently hide every
            // Province-deployment school.
            ->filter(fn (School $school): bool => $school->delegations_count > 0
                || $school->athletes_count > 0
                || $school->personnel_count > 0)
            ->map(fn (School $school): array => [
                'id' => $school->id,
                'school' => $school->name,
                'district' => $school->district->name,
                'delegations_count' => $school->delegations_count,
                'athletes_count' => $school->athletes_count,
                'personnel_count' => $school->personnel_count,
                'entries_count' => $school->entries_count,
            ])
            ->values()
            ->all();
    }

    /**
     * Stream rows as a CSV download.
     *
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
