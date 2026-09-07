<?php

namespace App\Http\Controllers;

use App\Enums\DavraaReportDesignation;
use App\Enums\DavraaReportLevel;
use App\Enums\DavraaReportStatus;
use App\Enums\GenderCategory;
use App\Enums\UserRole;
use App\Http\Requests\DavraaReportGroupRequest;
use App\Models\Athlete;
use App\Models\DavraaReportGroup;
use App\Models\DavraaReportMember;
use App\Models\Event;
use App\Models\Meet;
use App\Models\SportRosterMember;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\DavraaReportAccess;
use App\Services\DavraaReportBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * DAVRAA Report — the Tournament ICT's "List of Recommended Qualifiers to
 * DAVRAA". Grouping is entirely ICT-driven (never derived from Match /
 * Result / Medal / Schedule / confirmed Entry); the module only reuses
 * existing Athlete / Coach / Sport / Event / School / District records.
 */
class DavraaReportController extends Controller
{
    public function __construct(
        private readonly DavraaReportAccess $access,
        private readonly DavraaReportBuilder $builder,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): Response
    {
        $meetId = Meet::current()->id;
        abort_unless($this->access->canBrowse($request->user(), $meetId), 403);

        $sportIds = $this->access->sportIds($request->user(), $meetId);
        $status = DavraaReportStatus::tryFrom($request->string('status')->toString());

        $groups = DavraaReportGroup::query()
            ->where('meet_id', $meetId)
            ->when($sportIds !== null, fn ($query) => $query->whereIn('sport_id', $sportIds ?? []))
            ->when($status !== null, fn ($query) => $query->where('status', $status->value))
            ->when($status === null, fn ($query) => $query->where('status', '!=', DavraaReportStatus::Archived->value))
            ->with(['sport:id,name', 'creator:id,name', 'events:id', 'members:id,davraa_report_group_id,designation'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (DavraaReportGroup $group): array => [
                'id' => $group->id,
                'name' => $group->name,
                'sport' => $group->sport?->name ?? 'Sport unavailable',
                'division' => $group->division->label(),
                'level' => $group->level->label(),
                'status' => $group->status->value,
                'status_label' => $group->status->label(),
                'events_count' => $group->events->count(),
                'athletes_count' => $group->members->where('designation', DavraaReportDesignation::Athlete)->count(),
                'staff_count' => $group->members->where('designation', '!=', DavraaReportDesignation::Athlete)->count(),
                'created_by' => $group->creator?->name,
                'can_manage' => $this->access->canManage($request->user(), $meetId, $group->sport_id),
            ]);

        return Inertia::render('davraa-reports/index', [
            'groups' => $groups,
            'filters' => ['status' => $status?->value],
            'canCreate' => $this->access->sportIds($request->user(), $meetId) !== [],
            'meet' => Meet::current()->name,
        ]);
    }

    public function create(Request $request): Response
    {
        $meetId = Meet::current()->id;
        abort_unless($this->access->sportIds($request->user(), $meetId) !== [], 403);

        return Inertia::render('davraa-reports/form', [
            'group' => null,
            ...$this->formOptions($request),
        ]);
    }

    public function store(DavraaReportGroupRequest $request): RedirectResponse
    {
        $group = DB::transaction(function () use ($request): DavraaReportGroup {
            $data = $request->payload();
            $group = DavraaReportGroup::create([
                'meet_id' => Meet::current()->id,
                'sport_id' => $data['sport_id'],
                'name' => $data['name'],
                'division' => $data['division'],
                'level' => $data['level'],
                'notes' => $data['notes'],
                'status' => $data['status'],
                'created_by' => $request->user()->id,
            ]);
            $this->syncGroup($group, $data);

            return $group;
        });

        $this->audit->record('davraa_report.created', $group, $this->context($group));

        return to_route('davraa-reports.edit', $group)->with('success', 'DAVRAA Report Group created.');
    }

    public function edit(Request $request, DavraaReportGroup $davraaReportGroup): Response
    {
        $this->authorizeManage($request, $davraaReportGroup);

        return Inertia::render('davraa-reports/form', [
            'group' => $this->groupPayload($davraaReportGroup),
            ...$this->formOptions($request),
        ]);
    }

    public function update(DavraaReportGroupRequest $request, DavraaReportGroup $davraaReportGroup): RedirectResponse
    {
        abort_unless($davraaReportGroup->isEditable(), 422, 'Archived reports are read-only. Restore it first.');

        DB::transaction(function () use ($request, $davraaReportGroup): void {
            $data = $request->payload();
            $davraaReportGroup->update([
                'sport_id' => $data['sport_id'],
                'name' => $data['name'],
                'division' => $data['division'],
                'level' => $data['level'],
                'notes' => $data['notes'],
                'status' => $data['status'],
            ]);
            $this->syncGroup($davraaReportGroup, $data);
        });

        $this->audit->record('davraa_report.updated', $davraaReportGroup, $this->context($davraaReportGroup));

        return back()->with('success', 'DAVRAA Report Group saved.');
    }

    public function duplicate(Request $request, DavraaReportGroup $davraaReportGroup): RedirectResponse
    {
        $this->authorizeManage($request, $davraaReportGroup);

        $copy = DB::transaction(function () use ($request, $davraaReportGroup): DavraaReportGroup {
            $copy = $davraaReportGroup->replicate(['status']);
            $copy->name = mb_substr($davraaReportGroup->name.' (Copy)', 0, 160);
            $copy->status = DavraaReportStatus::Draft;
            $copy->created_by = $request->user()->id;
            $copy->save();

            $copy->events()->sync($davraaReportGroup->events()->pluck('events.id'));
            foreach ($davraaReportGroup->members()->get() as $member) {
                $clone = $member->replicate();
                $clone->davraa_report_group_id = $copy->id;
                $clone->save();
            }

            return $copy;
        });

        $this->audit->record('davraa_report.duplicated', $copy, [
            ...$this->context($copy), 'source_id' => $davraaReportGroup->id,
        ]);

        return to_route('davraa-reports.edit', $copy)->with('success', 'DAVRAA Report Group duplicated.');
    }

    public function updateStatus(Request $request, DavraaReportGroup $davraaReportGroup): RedirectResponse
    {
        $this->authorizeManage($request, $davraaReportGroup);
        $validated = $request->validate([
            'status' => ['required', Rule::enum(DavraaReportStatus::class)],
        ]);

        $davraaReportGroup->update(['status' => $validated['status']]);
        $this->audit->record('davraa_report.status_changed', $davraaReportGroup, [
            ...$this->context($davraaReportGroup), 'status' => $validated['status'],
        ]);

        return back()->with('success', 'DAVRAA Report Group '.$davraaReportGroup->status->label().'.');
    }

    public function print(Request $request, DavraaReportGroup $davraaReportGroup): Response
    {
        $this->authorizeView($request, $davraaReportGroup);
        $this->audit->record('davraa_report.printed', $davraaReportGroup, $this->context($davraaReportGroup));

        return Inertia::render('davraa-reports/print', [
            'report' => $this->reportPayload($davraaReportGroup),
        ]);
    }

    public function export(Request $request, DavraaReportGroup $davraaReportGroup): StreamedResponse
    {
        $this->authorizeView($request, $davraaReportGroup);
        $payload = $this->reportPayload($davraaReportGroup);
        $rows = [
            ['LIST OF RECOMMENDED QUALIFIERS TO DAVRAA'],
            ['Event', $payload['event']],
            ['Division', $payload['division']],
            ['Level', $payload['level']],
            [],
            ['No.', 'Designation', 'LRN', 'Last Name', 'Given Name(s)', 'M.I.', 'Name of School', 'Name of District'],
        ];
        foreach ($payload['rows'] as $row) {
            $rows[] = [
                $row['no'], $row['designation'], $row['lrn'], $row['last_name'],
                $row['given_names'], $row['middle_initial'], $row['school_name'], $row['district_name'],
            ];
        }
        $rows[] = [];
        $rows[] = ['Prepared By:', '', 'Recommended By:', ''];
        $rows[] = ['Tournament Secretary', '', 'Tournament Manager', ''];

        $this->audit->record('davraa_report.exported', $davraaReportGroup, $this->context($davraaReportGroup));

        $filename = 'davraa-'.Str::slug($davraaReportGroup->name).'.csv';

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

    /** Athlete / Coach picker data for the selected Sport. */
    public function options(Request $request): JsonResponse
    {
        $meetId = Meet::current()->id;
        $sportId = $request->integer('sport_id');
        abort_unless($sportId > 0 && $this->access->canManage($request->user(), $meetId, $sportId), 403);

        $level = DavraaReportLevel::tryFrom($request->string('level')->toString());
        $division = GenderCategory::tryFrom($request->string('division')->toString());
        $eventIds = collect($request->input('event_ids', []))->filter()->map(fn ($id): int => (int) $id);
        $search = trim($request->string('search')->toString());

        $rosterAthleteIds = SportRosterMember::query()
            ->whereHas('meetSport', fn ($ms) => $ms->where('meet_id', $meetId)->where('sport_id', $sportId))
            ->when($level !== null, fn ($q) => $q->where('level', $this->rosterLevel($level)))
            ->when($division !== null && $division !== GenderCategory::Mixed, fn ($q) => $q->where('gender', $division->value))
            ->pluck('athlete_id')
            ->filter()
            ->unique();

        $athletes = Athlete::query()
            ->where(function ($query) use ($rosterAthleteIds, $search, $meetId): void {
                $query->whereKey($rosterAthleteIds);
                if ($search !== '') {
                    $query->orWhere(fn ($q) => $q
                        ->where(fn ($name) => $name->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('lrn', 'like', "%{$search}%"))
                        ->whereHas('delegation', fn ($d) => $d->where('meet_id', $meetId)));
                }
            })
            ->with(['school.district', 'school.schoolDistrict', 'delegation.district'])
            ->when($eventIds->isNotEmpty(), fn ($q) => $q->with(['entries' => fn ($e) => $e->whereIn('event_id', $eventIds)->with('event:id,name')]))
            ->orderBy('last_name')->orderBy('first_name')
            ->limit(400)
            ->get()
            ->map(function (Athlete $athlete) use ($rosterAthleteIds, $eventIds): array {
                $snapshot = $this->builder->athleteSnapshot($athlete);

                return [
                    'id' => $athlete->id,
                    'name' => $athlete->fullName(),
                    'lrn' => $athlete->lrn,
                    'school' => $snapshot['school_name'],
                    'district' => $snapshot['district_name'],
                    'on_roster' => $rosterAthleteIds->contains($athlete->id),
                    'events' => $eventIds->isEmpty() ? [] : $athlete->entries->map(fn ($entry) => $entry->event?->name)->filter()->unique()->values(),
                ];
            });

        $associatedCoachIds = User::query()
            ->where('role', UserRole::Coach->value)
            ->whereHas('coachAssignmentRequests', fn ($q) => $q
                ->where('status', 'approved')->whereNull('ended_at')
                ->whereHas('meetSport', fn ($ms) => $ms->where('meet_id', $meetId)->where('sport_id', $sportId)))
            ->pluck('id');

        $coaches = User::query()
            ->whereIn('role', [UserRole::Coach->value])
            ->where(function ($query) use ($associatedCoachIds, $meetId): void {
                $query->whereKey($associatedCoachIds)
                    ->orWhereHas('coachAssignmentRequests', fn ($q) => $q
                        ->where('status', 'approved')->whereNull('ended_at')
                        ->whereHas('meetSport', fn ($ms) => $ms->where('meet_id', $meetId)));
            })
            ->with('personnel.school')
            ->orderBy('name')
            ->get()
            ->map(fn (User $coach): array => [
                'id' => $coach->id,
                'name' => $coach->name,
                'associated' => $associatedCoachIds->contains($coach->id),
                'school' => $coach->personnel?->school?->name,
            ]);

        return response()->json(['athletes' => $athletes, 'coaches' => $coaches]);
    }

    private function syncGroup(DavraaReportGroup $group, array $data): void
    {
        $group->events()->sync($data['event_ids']);

        $athleteSnaps = Athlete::query()
            ->whereKey(collect($data['members'])->pluck('athlete_id')->filter())
            ->with(['school.district', 'school.schoolDistrict', 'delegation.district'])
            ->get()->keyBy('id');
        $coachSnaps = User::query()
            ->whereKey(collect($data['members'])->pluck('coach_user_id')->filter())
            ->with('personnel.school.district', 'personnel.school.schoolDistrict')
            ->get()->keyBy('id');

        $group->members()->delete();
        foreach ($data['members'] as $member) {
            $designation = DavraaReportDesignation::from($member['designation']);
            $snapshot = ['last_name' => null, 'given_names' => null, 'middle_initial' => null, 'lrn' => null, 'school_name' => null, 'district_name' => null];

            if ($member['athlete_id'] !== null && $athleteSnaps->has($member['athlete_id'])) {
                $snapshot = $this->builder->athleteSnapshot($athleteSnaps->get($member['athlete_id']));
            } elseif ($member['coach_user_id'] !== null && $coachSnaps->has($member['coach_user_id'])) {
                $snapshot = $this->builder->coachSnapshot($coachSnaps->get($member['coach_user_id']));
            }

            // A typed CHAPERONE name wins over an (absent) profile snapshot.
            if (filled($member['last_name'])) {
                $snapshot['last_name'] = trim($member['last_name']);
                $snapshot['given_names'] = filled($member['given_names']) ? trim($member['given_names']) : $snapshot['given_names'];
                $snapshot['middle_initial'] = filled($member['middle_initial']) ? trim($member['middle_initial']) : $snapshot['middle_initial'];
            }

            $group->members()->create([
                'designation' => $designation,
                'athlete_id' => $designation === DavraaReportDesignation::Athlete ? $member['athlete_id'] : null,
                'coach_user_id' => $designation === DavraaReportDesignation::Athlete ? null : $member['coach_user_id'],
                'sort_order' => $member['sort_order'],
                ...$snapshot,
            ]);
        }
    }

    private function groupPayload(DavraaReportGroup $group): array
    {
        $group->loadMissing([
            'events:id,name,gender,age_division,sport_id',
            'members.athlete:id,first_name,middle_name,last_name,name_extension',
            'members.coach:id,name',
        ]);

        return [
            'id' => $group->id,
            'name' => $group->name,
            'sport_id' => $group->sport_id,
            'division' => $group->division->value,
            'level' => $group->level->value,
            'notes' => $group->notes,
            'status' => $group->status->value,
            'status_label' => $group->status->label(),
            'editable' => $group->isEditable(),
            'event_ids' => $group->events->pluck('id'),
            'members' => $group->members->map(fn ($member): array => [
                'designation' => $member->designation->value,
                'athlete_id' => $member->athlete_id,
                'coach_user_id' => $member->coach_user_id,
                'sort_order' => $member->sort_order,
                'name' => $this->memberName($member),
                'last_name' => $member->last_name,
                'given_names' => $member->given_names,
                'middle_initial' => $member->middle_initial,
                'lrn' => $member->lrn,
                'school' => $member->school_name,
                'district' => $member->district_name,
                'incomplete' => $member->missingFields(),
            ]),
        ];
    }

    private function reportPayload(DavraaReportGroup $group): array
    {
        $group->loadMissing(['sport:id,name', 'events:id,name,gender,age_division,sport_id', 'members']);
        $eventLabel = $group->events->isEmpty()
            ? $group->sport?->name ?? '—'
            : $group->events->map(fn (Event $event) => $event->name)->join(', ');

        return [
            'id' => $group->id,
            'name' => $group->name,
            'sport' => $group->sport?->name,
            'event' => $eventLabel,
            'division' => strtoupper($group->division->label()),
            'level' => $group->level->reportLabel(),
            'status' => $group->status->value,
            'status_label' => $group->status->label(),
            'notes' => $group->notes,
            'rows' => $this->builder->rows($group),
        ];
    }

    private function formOptions(Request $request): array
    {
        $meetId = Meet::current()->id;
        $sports = $this->access->sportOptions($request->user(), $meetId);

        return [
            'sportOptions' => $sports->map(fn ($sport): array => [
                'id' => $sport->id, 'name' => $sport->name, 'is_team_sport' => (bool) $sport->is_team_sport,
            ]),
            'eventOptions' => Event::query()
                ->whereIn('sport_id', $sports->pluck('id'))
                ->whereHas('meets', fn ($meets) => $meets->whereKey($meetId))
                ->orderBy('sport_id')->orderBy('name')
                ->get(['id', 'sport_id', 'name', 'gender', 'age_division', 'is_team_event'])
                ->map(fn (Event $event): array => [
                    'id' => $event->id, 'sport_id' => $event->sport_id,
                    'label' => sprintf('%s (%s, %s)', $event->name, $event->gender->label(), $event->age_division->label()),
                    'is_team_event' => $event->is_team_event,
                ]),
            'divisionOptions' => collect(GenderCategory::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'levelOptions' => collect(DavraaReportLevel::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'designationOptions' => collect(DavraaReportDesignation::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
        ];
    }

    private function memberName(DavraaReportMember $member): string
    {
        if ($member->athlete_id !== null) {
            return $member->athlete?->fullName() ?? trim(($member->given_names ?? '').' '.($member->last_name ?? '')) ?: 'Archived athlete';
        }

        return $member->coach?->name ?? trim(($member->given_names ?? '').' '.($member->last_name ?? '')) ?: 'Unnamed';
    }

    private function rosterLevel(DavraaReportLevel $level): string
    {
        return match ($level) {
            DavraaReportLevel::Elementary => 'elementary',
            DavraaReportLevel::Secondary => 'secondary',
            DavraaReportLevel::Sned => 'secondary',
        };
    }

    private function authorizeManage(Request $request, DavraaReportGroup $group): void
    {
        $meetId = Meet::current()->id;
        abort_unless($group->meet_id === $meetId, 404);
        abort_unless($this->access->canManage($request->user(), $meetId, $group->sport_id), 403);
    }

    private function authorizeView(Request $request, DavraaReportGroup $group): void
    {
        $meetId = Meet::current()->id;
        abort_unless($group->meet_id === $meetId, 404);
        abort_unless($this->access->canView($request->user(), $meetId, $group->sport_id), 403);
    }

    private function context(DavraaReportGroup $group): array
    {
        return [
            'group_id' => $group->id,
            'name' => $group->name,
            'sport_id' => $group->sport_id,
            'status' => $group->status->value,
        ];
    }
}
