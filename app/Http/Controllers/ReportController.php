<?php

namespace App\Http\Controllers;

use App\Enums\EntryStatus;
use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\Entry;
use App\Models\Event;
use App\Models\Meet;
use App\Models\Personnel;
use App\Models\School;
use App\Models\User;
use App\Services\AuditLogger;
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
            'school' => $delegation->school->name,
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

        $school = str_replace(' ', '-', strtolower($delegation->school->name));

        return $this->csv("roster-{$school}.csv", $rows);
    }

    /**
     * Printable entry list for one event, officer-scoped like the entries
     * registry. Withdrawn entries are excluded — this is the start list.
     */
    public function eventEntries(Request $request, Event $event): Response
    {
        Gate::authorize('viewAny', Entry::class);

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
    public function participation(Request $request): Response
    {
        return Inertia::render('reports/school-participation', [
            'rows' => $this->participationRows($request),
            'filters' => ['meet_id' => $request->integer('meet_id') ?: null],
            'meetOptions' => Meet::query()
                ->orderByDesc('starts_at')
                ->get(['id', 'name']),
            'generatedAt' => now()->toDayDateTimeString(),
        ]);
    }

    /**
     * CSV of the participation summary, audited like the other exports.
     */
    public function downloadParticipation(Request $request): StreamedResponse
    {
        $meetId = $request->integer('meet_id');

        $this->audit->record('report.participation_exported', null, [
            'meet' => $meetId > 0 ? Meet::query()->find($meetId)?->name : 'all meets',
        ]);

        $rows = [['School', 'District', 'Delegations', 'Athletes', 'Personnel', 'Entries']];

        foreach ($this->participationRows($request) as $row) {
            $rows[] = [
                $row['school'], $row['district'], $row['delegations_count'],
                $row['athletes_count'], $row['personnel_count'], $row['entries_count'],
            ];
        }

        return $this->csv('school-participation.csv', $rows);
    }

    /**
     * @return array{delegation: array<string, mixed>, athletes: array<int, array<string, mixed>>, personnel: array<int, array<string, mixed>>}
     */
    private function rosterData(Delegation $delegation): array
    {
        $delegation->load([
            'school:id,name',
            'meet:id,name,school_year',
            'athletes' => fn ($query) => $query->orderBy('last_name')->orderBy('first_name'),
            'personnel' => fn ($query) => $query->with('sports:id,name')->orderBy('last_name')->orderBy('first_name'),
        ]);

        return [
            'delegation' => [
                'id' => $delegation->id,
                'school' => $delegation->school->name,
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
            ->with(['athlete:id,first_name,last_name,sex,birthdate,grade_level', 'delegation.school:id,name'])
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
                'school' => $entry->delegation->school->name,
                'status_label' => $entry->status->label(),
            ])
            ->sortBy([['school', 'asc'], ['last_name', 'asc']])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function participationRows(Request $request): array
    {
        $meetId = $request->integer('meet_id');

        $byMeet = fn (Builder $query) => $meetId > 0
            ? $query->where('delegations.meet_id', $meetId)
            : $query;

        return School::query()
            ->with('district:id,name')
            ->withCount([
                'delegations' => fn (Builder $query) => $meetId > 0
                    ? $query->where('meet_id', $meetId)
                    : $query,
                'athletes' => $byMeet,
                'personnel' => $byMeet,
                'entries' => $byMeet,
            ])
            ->orderBy('name')
            ->get()
            ->filter(fn (School $school): bool => $school->delegations_count > 0)
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
