<?php

namespace App\Http\Controllers;

use App\Enums\DelegationStatus;
use App\Enums\EntryStatus;
use App\Enums\ManagementTeamMemberStatus;
use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\MeetStatus;
use App\Enums\Permission;
use App\Enums\ResultStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Concerns\ScopesToAssignedSport;
use App\Http\Controllers\Concerns\SearchesAndPaginates;
use App\Models\Delegation;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\EventResult;
use App\Models\EventSchedule;
use App\Models\FileUpload;
use App\Models\Meet;
use App\Models\ResultAttachment;
use App\Models\ResultPlacement;
use App\Models\TeamEntry;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\CompetitionAccessService;
use App\Services\CompetitionResultService;
use App\Services\FileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ResultController extends Controller
{
    use ScopesToAssignedSport, SearchesAndPaginates;

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly CompetitionResultService $competitionResults,
        private readonly FileUploadService $uploads,
    ) {}

    /**
     * Results per event. Validated results are official and readable by all
     * roles; encoded results are working data, visible to managers, plus
     * (Phase 16) a Technical Official for their own sport's events only —
     * they encoded it, they should be able to see and revise it before a
     * manager (or, since Phase 13, their sport's Tournament Manager)
     * validates it.
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $canManage = Gate::allows('manage-meet-data');
        $access = app(CompetitionAccessService::class);
        $isScopedTechnicalOfficial = $user->role === UserRole::TechnicalOfficial;
        $isScopedResultEncoder = $access->hasAssignmentRole(
            $user,
            $access->resultEncoderRoles(),
            Meet::current()->id,
        );
        $isCentralSecretariat = $this->isCentralEventSecretariat($user, Meet::current()->id);
        $assignedEventIds = $user->tournamentEventIds();
        $coachDelegationIds = collect();
        if ($user->role === UserRole::Coach) {
            $assignedEventIds = $user->approvedCoachEventIds();
            $coachDelegationIds = $user->approvedCoachDelegationIds();
        }
        $isTournamentScoped = $user->role === UserRole::Coach
            || (! $user->hasRole(UserRole::Admin, UserRole::Organizer)
                && $assignedEventIds->isNotEmpty());
        $managedSportIds = $this->userManagedSportIds($user);
        $managedSportId = $managedSportIds->first();
        $canOfficialize = $user->hasPermission(Permission::ResultsOfficialize, Meet::current());

        // `canEncode` is a strict superset of `canManage`: it governs
        // "sees encoded results and the encode form at all" (Admin/
        // Organizer, or a scoped TO), while `canManage` stays the
        // narrower "can also validate/correct/delete" gate the frontend
        // already used for those three actions. A Tournament Manager gains
        // neither — encoding stays a TO job, "can manage" is expressed
        // per-row below instead of this global flag, since a TM's own
        // encoded results share this list with every other sport's
        // already-visible validated results.
        $canEncode = $canManage || $isScopedResultEncoder || $isCentralSecretariat;
        $canDirectResult = $user->isAdmin() || $isCentralSecretariat
            || $access->hasAssignmentRole($user, [MeetSportAssignmentRole::TournamentICT->value], Meet::current()->id);

        $meetId = Meet::current()->id;
        $eventId = $request->integer('event_id');

        $query = EventResult::query()->real()
            ->with([
                'meet:id,name',
                'event.sport:id,code,name',
                'event.medalConfig',
                'medalAwards',
                'encodedBy:id,name',
                'submittedBy:id,name',
                'validatedBy:id,name',
                'returnedBy:id,name',
                'officialBy:id,name',
                'cancellationRequestedBy:id,name',
                'schedule.venue:id,name',
                'schedule.competitionArea:id,name',
                'attachments.file:id,original_name,mime_type,size',
                'placements.entry.athlete' => fn ($athletes) => $athletes
                    ->withTrashed()
                    ->select(['id', 'first_name', 'last_name', 'school_id']),
                'placements.entry.athlete.school:id,name',
                'placements.entry.delegation.school:id,name',
                'placements.teamEntry.delegation.school:id,name',
                'placements.teamEntry.delegation.district:id,name',
                'placements.delegation.school:id,name',
                'placements.delegation.district:id,name',
                'placements.teamEntry.members:id,team_entry_id,athlete_id',
            ])
            ->orderByDesc('id');

        if ($user->role === UserRole::Coach) {
            $query->whereIn('event_id', $assignedEventIds)
                ->where(fn ($results) => $results
                    ->whereHas('placements.entry', fn ($entries) => $entries
                        ->whereIn('delegation_id', $coachDelegationIds))
                    ->orWhereHas('placements.teamEntry', fn ($teams) => $teams
                        ->whereIn('delegation_id', $coachDelegationIds)));
        }

        if (! $canManage) {
            $query->where(function ($visible) use ($user, $isScopedResultEncoder, $isScopedTechnicalOfficial, $assignedEventIds, $managedSportId, $canOfficialize) {
                $visible->where('status', ResultStatus::Official->value)
                    ->orWhere(function ($secretariatResults) use ($user) {
                        $secretariatResults->whereIn('status', [
                            ResultStatus::Encoded->value,
                            ResultStatus::Submitted->value,
                            ResultStatus::Validated->value,
                            ResultStatus::Returned->value,
                            ResultStatus::Reopened->value,
                        ])->whereHas('meet.managementTeams', fn ($team) => $team
                            ->where('source_code', 'EVENT_SECRETARIAT')
                            ->whereHas('members', fn ($member) => $member
                                ->where('user_id', $user->id)
                                ->where('status', ManagementTeamMemberStatus::Active)));
                    })
                    ->orWhere(function ($assignedResults) use ($user) {
                        $assignedResults->whereIn('status', [
                            ResultStatus::Encoded->value,
                            ResultStatus::Submitted->value,
                            ResultStatus::Returned->value,
                            ResultStatus::Reopened->value,
                        ])->whereHas('event.sport.meetSports', fn ($meetSport) => $meetSport
                            ->whereColumn('meet_sports.meet_id', 'event_results.meet_id')
                            ->whereHas('assignments', fn ($assignment) => $assignment
                                ->where('user_id', $user->id)
                                ->where('status', MeetSportAssignmentStatus::Active)));
                    });

                // Transitional compatibility for installations that have
                // not yet backfilled meet-scoped assignments.
                if (($isScopedResultEncoder || $isScopedTechnicalOfficial) && $assignedEventIds->isNotEmpty()) {
                    $visible->orWhere(fn ($legacy) => $legacy
                        ->where('status', ResultStatus::Encoded->value)
                        ->whereIn('event_id', $assignedEventIds));
                }

                if ($managedSportId !== null) {
                    $visible->orWhere(fn ($legacy) => $legacy
                        ->where('status', ResultStatus::Encoded->value)
                        ->whereHas('event', fn ($event) => $event->where('sport_id', $managedSportId)));
                }

                if ($canOfficialize) {
                    $visible->orWhereIn('status', [ResultStatus::Validated->value, ResultStatus::Official->value]);
                }
            });
        }

        if ($meetId > 0) {
            $query->where('meet_id', $meetId);
        }

        if ($isTournamentScoped) {
            $query->whereIn('event_id', $assignedEventIds);
        }

        if ($eventId > 0) {
            $query->where('event_id', $eventId);
        }

        return Inertia::render('results/index', [
            'results' => $query->paginate($this->registryPageSize)->withQueryString()
                ->through(function (EventResult $result) use ($user, $canManage): array {
                    $event = $result->event;
                    $sportId = $event?->sport_id;
                    $canForm = $user->isAdmin() || $this->isCentralEventSecretariat($user, $result->meet_id) || $user->meetSportAssignments()
                        ->where('status', MeetSportAssignmentStatus::Active)
                        ->whereIn('role', [
                            MeetSportAssignmentRole::TournamentManager,
                            MeetSportAssignmentRole::AssistantTournamentManager,
                            MeetSportAssignmentRole::TournamentSecretary,
                            MeetSportAssignmentRole::TournamentICT,
                        ])
                        ->whereHas('meetSport', fn ($scope) => $scope
                            ->where('meet_id', $result->meet_id)
                            ->where('sport_id', $sportId))
                        ->exists();
                    $isEventSecretariat = $user->isAdmin() || $user->managementTeamMemberships()
                        ->where('status', ManagementTeamMemberStatus::Active)
                        ->whereHas('managementTeam', fn ($team) => $team
                            ->where('meet_id', $result->meet_id)
                            ->where('source_code', 'EVENT_SECRETARIAT'))
                        ->exists() || $this->isAssignedTournamentSecretary($user, $result);
                    $attachment = $result->attachments
                        ->where('attachment_type', ResultAttachment::SIGNED_RESULT_FORM)
                        ->where('result_version', $result->version)
                        ->where('is_current', true)
                        ->sortByDesc('id')
                        ->first();
                    $attachment ??= $result->attachments
                        ->where('attachment_type', ResultAttachment::DIRECT_RESULT_EVIDENCE)
                        ->where('is_current', true)->sortByDesc('id')->first();
                    $resultPhoto = $result->attachments
                        ->where('attachment_type', ResultAttachment::RESULT_PHOTO)
                        ->where('result_version', $result->version)
                        ->where('is_current', true)
                        ->sortByDesc('id')
                        ->first();

                    return [
                        'id' => $result->id,
                        'meet_id' => $result->meet_id,
                        'event_id' => $result->event_id,
                        'match_id' => $result->match_id,
                        'result_scope' => $result->result_scope,
                        'meet' => $result->meet?->name ?? 'Unavailable meet',
                        'event' => $event === null ? 'Unavailable event' : $this->eventLabel($event),
                        'status' => $result->status->value,
                        'status_label' => $result->result_source === 'direct' && $result->status === ResultStatus::Official ? 'Accepted' : $result->status->label(),
                        'result_source' => $result->result_source,
                        'can_reopen' => $result->status === ResultStatus::Official && $user->hasPermission(Permission::ResultsReopen, $result->meet),
                        'encoded_by' => $result->encodedBy?->name,
                        'encoded_at' => $result->encoded_at?->toDayDateTimeString(),
                        'submitted_by' => $result->submittedBy?->name,
                        'submitted_at' => $result->submitted_at?->toDayDateTimeString(),
                        'validated_by' => $result->validatedBy?->name,
                        'validated_at' => $result->validated_at?->toDayDateTimeString(),
                        'returned_by' => $result->returnedBy?->name,
                        'returned_at' => $result->returned_at?->toDayDateTimeString(),
                        'return_reason' => $result->return_reason,
                        'operational_remarks' => $result->operational_remarks,
                        'data_issues' => $this->resultDataIssues($result),
                        'can_defer_issues' => $user->isAdmin() || $user->role === UserRole::TournamentICT,
                        'official_by' => $result->officialBy?->name,
                        'official_at' => $result->official_at?->toDayDateTimeString(),
                        'competition_context' => $result->schedule === null
                            ? 'Unscheduled result'
                            : sprintf('%s · %s–%s · %s%s',
                                $result->schedule->scheduled_date?->format('M j, Y') ?? 'Date unavailable',
                                substr((string) $result->schedule->starts_at, 0, 5),
                                substr((string) $result->schedule->ends_at, 0, 5),
                                $result->schedule->venue?->name ?? 'Venue unavailable',
                                $result->schedule->competitionArea ? ' / '.$result->schedule->competitionArea->name : ''),
                        'version' => $result->version,
                        'reference' => $result->referenceNumber(),
                        'can_form' => $canForm,
                        'can_upload_photo' => $canForm,
                        'can_review' => $isEventSecretariat,
                        'can_cancel' => $isEventSecretariat && (in_array($result->status, [ResultStatus::Submitted, ResultStatus::Returned, ResultStatus::Validated], true)
                            || ($result->result_source === 'direct' && $result->status === ResultStatus::Official)),
                        'can_request_cancellation' => $result->status === ResultStatus::Submitted
                            && $result->cancellation_requested_at === null
                            && $user->meetSportAssignments()
                                ->where('status', MeetSportAssignmentStatus::Active)
                                ->where('role', MeetSportAssignmentRole::TournamentICT->value)
                                ->whereHas('meetSport', fn ($scope) => $scope
                                    ->where('meet_id', $result->meet_id)
                                    ->where('sport_id', $sportId))
                                ->exists()
                            && $event !== null
                            && app(CompetitionAccessService::class)->canAccessEvent($user, $event, $result->meet_id),
                        'cancellation_request' => $result->cancellation_requested_at === null ? null : [
                            'reason' => $result->cancellation_request_reason,
                            'requested_by' => $result->cancellationRequestedBy?->name,
                            'requested_at' => $result->cancellation_requested_at->toDayDateTimeString(),
                        ],
                        'can_officialize' => ($isEventSecretariat || $user->hasPermission(Permission::ResultsOfficialize, $result->meet))
                            && $result->isFinalEventResult()
                            && ($result->status === ResultStatus::Validated || ($result->result_source === 'direct' && $result->status === ResultStatus::Submitted))
                            && ($result->result_source === 'direct' || $attachment !== null || ($result->result_source === 'manual' && $result->event_schedule_id === null)),
                        'form_generated' => $result->form_generated_version === $result->version,
                        'tm_confirmed' => $result->tm_confirmed_at !== null,
                        'can_tm_confirm' => $sportId !== null && $this->userManagedSportIds($user)->contains($sportId)
                            && in_array($result->status, [ResultStatus::Encoded, ResultStatus::Returned, ResultStatus::Reopened], true),
                        'signed_form' => $attachment === null ? null : [
                            'id' => $attachment->id,
                            'name' => $attachment->file->original_name,
                            'type' => $attachment->attachment_type,
                        ],
                        'result_photo' => $resultPhoto === null ? null : [
                            'id' => $resultPhoto->id,
                            'name' => $resultPhoto->file?->original_name ?? 'Written result photo',
                            'url' => route('results.photo.show', [$result, $resultPhoto]),
                        ],
                        // Superset of the page-level `canManage` prop: also true
                        // for a Tournament Manager on their own sport's results
                        // (Phase 13). Validated results from every sport are
                        // visible on this shared list, but a TM may only
                        // validate/correct/delete their own sport's — a global
                        // boolean can't express that, so it's computed per row.
                        'can_manage' => $canManage,
                        'awards_medals' => $event?->resolvedMedalConfig()->awards_medals ?? false,
                        'medal_tally' => [
                            'gold' => $result->medalAwards->where('medal_type', 'gold')->sum('tally_quantity'),
                            'silver' => $result->medalAwards->where('medal_type', 'silver')->sum('tally_quantity'),
                            'bronze' => $result->medalAwards->where('medal_type', 'bronze')->sum('tally_quantity'),
                            'total' => $result->medalAwards->sum('tally_quantity'),
                        ],
                        'placements' => $result->placements
                            ->sortBy([['rank', 'asc']])
                            ->map(fn (ResultPlacement $placement): array => [
                                'entry_id' => $placement->entry_id,
                                'team_entry_id' => $placement->team_entry_id,
                                'delegation_id' => $placement->delegation_id,
                                'rank' => $placement->rank,
                                'athlete' => $placement->teamEntry?->delegation?->registrantName()
                                    ?? $placement->delegation?->registrantName()
                                    ?? $placement->entry?->athlete?->fullName()
                                    ?? $placement->entry?->delegation?->school?->name
                                    ?? 'Archived participant',
                                'school' => $placement->teamEntry?->delegation?->registrantName()
                                    ?? $placement->delegation?->registrantName()
                                    ?? $placement->entry?->athlete?->school?->name
                                    ?? $placement->entry?->delegation?->school?->name
                                    ?? 'School unavailable',
                                'mark' => $placement->mark,
                                'tally_quantity' => $placement->tally_quantity,
                                'is_tie' => $placement->is_tie,
                            ])
                            ->values()
                            ->all(),
                    ];
                }),
            'filters' => [
                'meet_id' => $meetId > 0 ? $meetId : null,
                'event_id' => $eventId > 0 ? $eventId : null,
            ],
            'meetOptions' => Meet::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Meet $meet): array => ['id' => $meet->id, 'label' => $meet->name]),
            'eventOptionsByMeet' => Event::query()->real()
                ->whereHas('meets')
                ->when($isTournamentScoped, fn ($events) => $events->whereKey($assignedEventIds))
                ->with(['sport:id,name', 'meets:id'])
                ->get(['id', 'sport_id', 'name', 'gender', 'age_division'])
                ->flatMap(fn (Event $event) => $event->meets->map(fn (Meet $meet): array => [
                    'id' => $event->id,
                    'meet_id' => $meet->id,
                    'label' => $this->eventLabel($event),
                ]))
                ->values(),
            'scheduleOptions' => EventSchedule::query()->real()
                ->when($isTournamentScoped, fn ($schedules) => $schedules->whereIn('event_id', $assignedEventIds))
                ->with(['venue:id,name', 'competitionArea:id,name'])
                ->orderBy('scheduled_date')->orderBy('starts_at')
                ->get(['id', 'meet_id', 'event_id', 'venue_id', 'competition_area_id', 'scheduled_date', 'starts_at', 'ends_at'])
                ->map(fn (EventSchedule $schedule): array => [
                    'id' => $schedule->id,
                    'meet_id' => $schedule->meet_id,
                    'event_id' => $schedule->event_id,
                    'label' => sprintf('%s · %s–%s · %s%s',
                        $schedule->scheduled_date?->format('M j, Y') ?? 'Date unavailable',
                        substr($schedule->starts_at, 0, 5), substr($schedule->ends_at, 0, 5),
                        $schedule->venue?->name ?? 'Venue unavailable',
                        $schedule->competitionArea ? ' / '.$schedule->competitionArea->name : ''),
                ])->values(),
            'activeMeets' => Meet::query()
                ->where('status', MeetStatus::Active->value)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Meet $meet): array => ['id' => $meet->id, 'label' => $meet->name]),
            'encodedEventKeys' => $canEncode
                ? EventResult::query()->real()
                    ->when(
                        ! $canManage,
                        fn ($query) => $query->whereIn('event_id', $assignedEventIds),
                    )
                    ->get(['meet_id', 'event_id'])
                    ->map(fn (EventResult $result): string => "{$result->meet_id}-{$result->event_id}")
                    ->values()
                : [],
            'entryOptions' => $canEncode
                ? Entry::query()
                    ->whereIn('status', $user->isAdmin() || $user->role === UserRole::TournamentICT
                        ? [EntryStatus::Submitted->value, EntryStatus::Confirmed->value]
                        : [EntryStatus::Confirmed->value])
                    ->whereHas('athlete')
                    ->whereHas('event', fn ($events) => $events->where('is_team_event', false))
                    ->when(
                        ! $canManage,
                        fn ($query) => $query->whereIn('event_id', $assignedEventIds),
                    )
                    ->when($user->role === UserRole::Coach, fn ($query) => $query
                        ->whereIn('delegation_id', $coachDelegationIds))
                    ->with([
                        'athlete:id,first_name,last_name,school_id',
                        'athlete.school:id,name',
                        'delegation:id,meet_id',
                        'delegation.school:id,name',
                        'delegation.district:id,name',
                        'event:id,is_team_event',
                    ])
                    ->get()
                    ->map(fn (Entry $entry): array => [
                        'id' => $entry->id,
                        'event_id' => $entry->event_id,
                        'meet_id' => $entry->delegation->meet_id,
                        'is_team_event' => $entry->event->is_team_event,
                        'delegation_id' => $entry->delegation_id,
                        'label' => $entry->event->is_team_event
                            ? $entry->delegation->registrantName()
                            : "{$entry->delegation->registrantName()} — {$entry->athlete?->fullName()}",
                    ])
                    ->unique(fn (array $option): string => $option['is_team_event']
                        ? 'team-'.$option['event_id'].'-'.$option['delegation_id']
                        : 'entry-'.$option['id'])
                    ->sortBy('label')
                    ->values()
                : [],
            'teamEntryOptions' => $canEncode
                ? TeamEntry::query()
                    ->whereIn('status', [EntryStatus::Submitted->value, EntryStatus::Confirmed->value])
                    ->when(! $canManage, fn ($query) => $query->whereIn('event_id', $assignedEventIds))
                    ->with(['delegation:id,meet_id,school_id,district_id', 'delegation.school:id,name', 'delegation.district:id,name'])
                    ->get()
                    ->map(fn (TeamEntry $team): array => [
                        'id' => $team->id,
                        'event_id' => $team->event_id,
                        'meet_id' => $team->delegation->meet_id,
                        'label' => $team->delegation->registrantName(),
                    ])->sortBy('label')->values()
                : [],
            'competitionOptions' => $canEncode
                ? EventMatch::query()->real()
                    ->whereIn('status', ['completed', 'walkover'])
                    ->whereDoesntHave('result')
                    ->when(! $canManage, fn ($query) => $query->whereIn('event_id', $assignedEventIds))
                    ->with(['event.sport:id,name', 'schedule.venue:id,name', 'entries.athlete.school:id,name', 'entries.delegation.school:id,name', 'entries.delegation.district:id,name', 'teamEntries.delegation.school:id,name', 'teamEntries.delegation.district:id,name'])
                    ->get()
                    ->map(fn (EventMatch $match): array => [
                        'id' => $match->id,
                        'meet_id' => $match->meet_id,
                        'event_id' => $match->event_id,
                        'label' => sprintf('%s · %s · %s', $this->eventLabel($match->event), $match->round_label, $match->schedule?->venue?->name ?? __('No schedule')),
                        'context' => $match->schedule === null
                            ? __('Non-scheduled match')
                            : sprintf('%s %s–%s%s', $match->schedule->scheduled_date->format('M j'), substr($match->schedule->starts_at, 0, 5), substr($match->schedule->ends_at, 0, 5), $match->competition_area ? " · {$match->competition_area}" : ''),
                        'entries' => ($match->event->is_team_event ? $match->teamEntries : $match->entries)->map(fn (Entry|TeamEntry $entry): array => [
                            'id' => $entry->id,
                            'delegation_id' => $entry->delegation_id,
                            'participant_type' => $match->event->is_team_event ? 'team' : 'entry',
                            'label' => $match->event->is_team_event
                                ? $entry->delegation->registrantName()
                                : "{$entry->delegation->registrantName()} — ".($entry->athlete?->fullName() ?? __('Archived participant')),
                        ])->unique(fn (array $option): string => $match->event->is_team_event
                            ? 'team-'.$option['delegation_id']
                            : 'entry-'.$option['id'])->values(),
                    ])->values()
                : [],
            'canManage' => $canManage,
            'canEncode' => $canEncode,
            'canDirectResult' => $canDirectResult,
            'delegationOptions' => Delegation::query()
                ->where('meet_id', $meetId)
                ->whereIn('status', [DelegationStatus::Submitted->value, DelegationStatus::Approved->value])
                ->with(['school:id,name', 'district:id,name'])
                ->get()->map(fn (Delegation $delegation): array => [
                    'id' => $delegation->id, 'label' => $delegation->registrantName(),
                ])->sortBy('label')->values(),
        ]);
    }

    /**
     * Encode an event's final standing (first decision — a manager, or a
     * Technical Official encoding a result directly for their own sport
     * without having run live scoring for it, per Phase 16).
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'meet_id' => ['exclude_with:match_id', 'required_without:match_id', 'integer', Rule::exists('meets', 'id')],
            'event_id' => ['exclude_with:match_id', 'required_without:match_id', 'integer', Rule::exists('events', 'id')],
            'event_schedule_id' => ['exclude_with:match_id', 'nullable', 'integer', Rule::exists('event_schedules', 'id')],
            'match_id' => ['nullable', 'integer', Rule::exists('matches', 'id')],
            'placements' => ['required', 'array', 'min:1'],
            'placements.*.entry_id' => ['nullable', 'integer', 'distinct', 'required_without:placements.*.team_entry_id', Rule::exists('entries', 'id')],
            'placements.*.team_entry_id' => ['nullable', 'integer', 'distinct', 'required_without:placements.*.entry_id', Rule::exists('team_entries', 'id')],
            'placements.*.rank' => ['required', 'integer', 'min:1', 'max:999'],
            'placements.*.mark' => ['nullable', 'string', 'max:60'],
            'placements.*.is_tie' => ['boolean'],
        ]);
        /** @var User $user */
        $user = $request->user();
        $match = ! empty($data['match_id'])
            ? EventMatch::query()->with(['meet', 'event', 'entries', 'teamEntries'])->findOrFail((int) $data['match_id'])
            : null;
        $meet = $match?->meet ?? Meet::query()->findOrFail((int) $data['meet_id']);
        $event = $match?->event ?? Event::query()->findOrFail((int) $data['event_id']);
        $this->authorizeEncode($request, $event->id);
        $this->assertEncodable($meet, $event->id);

        if (! empty($data['match_id'])) {
            $this->assertPlacementsValid($data['placements'], $meet->id, $event->id, $match, $this->canDeferDataIssues($user));
            $this->competitionResults->createManual($match, $data['placements'], $user);
        } else {
            $schedule = isset($data['event_schedule_id'])
                ? EventSchedule::query()->real()->findOrFail((int) $data['event_schedule_id'])
                : null;
            if ($schedule !== null && ($schedule->meet_id !== $meet->id || $schedule->event_id !== $event->id)) {
                throw ValidationException::withMessages([
                    'event_schedule_id' => __('The selected schedule must belong to the selected Meet and Sports Event.'),
                ]);
            }
            $this->assertPlacementsValid($data['placements'], $meet->id, $event->id, null, $this->canDeferDataIssues($user));
            $this->competitionResults->createFinalEventResult($meet, $event, $schedule, $data['placements'], $user);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Result encoded.')]);

        return back();
    }

    /**
     * Re-encode placements while the result is still unvalidated.
     */
    public function update(Request $request, EventResult $result): RedirectResponse
    {
        $this->authorizeEncode($request, $result->event_id);

        if ($result->isLocked() || in_array($result->status, [ResultStatus::Submitted, ResultStatus::Validated], true)) {
            abort_unless($request->user()->isAdmin(), 403);

            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Submitted, validated, and official results are locked.'),
            ]);

            return back();
        }

        $data = $this->validatePayload($request);

        $this->assertEncodable($result->meet, $result->event_id);
        $this->assertPlacementsValid($data['placements'], $result->meet_id, $result->event_id, null, $this->canDeferDataIssues($request->user()));

        DB::transaction(function () use ($result, $data): void {
            $result->placements()->delete();
            $this->writePlacements($result, $data['placements']);
            $result->forceFill([
                'version' => $result->version + 1,
                'status' => ResultStatus::Encoded,
                'tm_confirmed_by' => null,
                'tm_confirmed_at' => null,
            ])->save();
        });

        $this->audit->record('result.encoded', $result, [
            ...$this->context($result),
            'revision' => true,
            'placements' => $this->placementSnapshot($result->refresh()),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Result updated.')]);

        return back();
    }

    /**
     * Validate a result (second explicit manager decision). Validated
     * results are official, locked, and feed the medal tally.
     */
    public function validateResult(Request $request, EventResult $result): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->managementTeamMemberships()
            ->where('status', ManagementTeamMemberStatus::Active)
            ->whereHas('managementTeam', fn ($team) => $team
                ->where('meet_id', $result->meet_id)
                ->where('source_code', 'EVENT_SECRETARIAT'))
            ->exists() || $this->isAssignedTournamentSecretary($user, $result), 403);

        abort_unless($result->status === ResultStatus::Submitted, 422, 'Only a submitted result may be validated.');

        if ($result->isValidated()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This result is already validated.'),
            ]);

            return back();
        }

        $result->forceFill([
            'status' => ResultStatus::Validated,
            'validated_by' => $user->id,
            'validated_at' => now(),
        ])->save();

        $this->audit->record('result.validated', $result, $this->context($result));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Result validated. It remains internal until accepted.')]);

        return back();
    }

    /**
     * Correct a validated result: never a silent edit. Requires a reason,
     * reopens the result to encoded, and preserves the superseded
     * placements in the audit record.
     */
    public function correct(Request $request, EventResult $result): RedirectResponse
    {
        $this->authorizeManage($request, $result);

        if ($result->isLocked()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Official results must be reopened through the audited Event Secretariat workflow.'),
            ]);

            return back();
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        if (! $result->isValidated()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Only validated results need a correction — encoded results can be edited directly.'),
            ]);

            return back();
        }

        $superseded = $this->placementSnapshot($result);

        $result->forceFill([
            'status' => ResultStatus::Encoded,
            'validated_by' => null,
            'validated_at' => null,
        ])->save();

        $this->audit->record('result.corrected', $result, [
            ...$this->context($result),
            'reason' => $validated['reason'],
            'superseded_placements' => $superseded,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Result reopened for correction. Re-encode and validate it again.'),
        ]);

        return back();
    }

    /** Delete a result and any result-owned match while retaining competition setup. */
    public function destroy(Request $request, EventResult $result): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_unless($result->status === ResultStatus::Encoded, 422, 'Only an unsubmitted draft result may be permanently deleted. Use the audited result lifecycle for reviewed results.');

        $result->loadMissing('match', 'schedule');
        $context = [...$this->context($result),
            'match_id' => $result->match_id,
            'event_schedule_id' => $result->event_schedule_id,
        ];
        $attachmentUploadIds = $result->attachments()->pluck('file_upload_id');

        DB::transaction(function () use ($result): void {
            $match = $result->match;
            $result->medalAwards()->delete();
            $result->attachments()->delete();
            $result->placements()->delete();
            $result->delete();
            $match?->scoringSessions()->delete();
            $match?->delete();
        }, 3);

        FileUpload::query()->whereIn('id', $attachmentUploadIds)->get()
            ->each(fn (FileUpload $upload) => $this->uploads->delete($upload));

        $this->audit->record('result.deleted', $result, $context);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Result, related match, and tally contribution deleted. Schedule and competition setup were retained.')]);

        return back();
    }

    /**
     * @return array{event_id: mixed, placements: array<int, array{entry_id: int, rank: int, mark?: string|null, is_tie?: bool}>}
     */
    private function validatePayload(Request $request): array
    {
        /** @var array{event_id: mixed, placements: array<int, array{entry_id: int, rank: int, mark?: string|null, is_tie?: bool}>} */
        return $request->validate([
            'event_id' => ['required', 'integer', Rule::exists('events', 'id')],
            'placements' => ['required', 'array', 'min:1'],
            'placements.*.entry_id' => ['nullable', 'integer', 'distinct', 'required_without:placements.*.team_entry_id', Rule::exists('entries', 'id')],
            'placements.*.team_entry_id' => ['nullable', 'integer', 'distinct', 'required_without:placements.*.entry_id', Rule::exists('team_entries', 'id')],
            'placements.*.rank' => ['required', 'integer', 'min:1', 'max:999'],
            'placements.*.mark' => ['nullable', 'string', 'max:60'],
            'placements.*.is_tie' => ['boolean'],
        ]);
    }

    /**
     * Admin/Organizer may encode any result; a Technical Official only
     * for an event whose sport is one they're assigned to (`User::sports()`)
     * — the same scoping `ScoringSessionController::canManage()` already
     * uses for live scoring, applied here to the separate encode-a-result
     * path (Phase 16). Validating/correcting/deleting a result stays a
     * manager-only decision — this method is only called from `store()`/
     * `update()`, never `validateResult()`/`correct()`/`destroy()`.
     */
    private function authorizeEncode(Request $request, int $eventId): void
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->isAdmin()) {
            return;
        }

        $event = Event::query()->findOrFail($eventId);
        $access = app(CompetitionAccessService::class);

        abort_unless(
            $this->isCentralEventSecretariat($user, Meet::current()->id)
                || ($access->hasAssignmentRole($user, $access->resultEncoderRoles(), Meet::current()->id)
                    && $access->canAccessEvent($user, $event, Meet::current()->id)),
            403,
        );
    }

    /**
     * Admin/Organizer may validate/correct/delete any result; a Tournament
     * Manager only a result whose sport is one they operate
     * (`ScopesToAssignedSport::userOperatesSport()`) — the counterpart to
     * `authorizeEncode()`'s TO scoping, for the separate manager-decision
     * trio (Phase 13). A Technical Official has no access to this trio at
     * all, same as before.
     */
    private function authorizeManage(Request $request, EventResult $result): void
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->isAdmin()) {
            return;
        }

        $result->loadMissing('event');

        abort_unless($user->isAdmin(), 403);
    }

    /**
     * Results are encoded only for events of an active meet.
     */
    private function assertEncodable(Meet $meet, int $eventId): void
    {
        if ($meet->status !== MeetStatus::Active) {
            throw ValidationException::withMessages([
                'meet_id' => __('Results can only be encoded while the meet is active.'),
            ]);
        }

        if (! $meet->events()->whereKey($eventId)->exists()) {
            throw ValidationException::withMessages([
                'event_id' => __('That event is not part of the selected meet.'),
            ]);
        }
    }

    /**
     * Placement integrity: confirmed entries of this meet+event only, and
     * duplicate ranks only when every entry sharing the rank is tied.
     *
     * @param  array<int, array{entry_id: int, rank: int, mark?: string|null, is_tie?: bool}>  $placements
     */
    private function assertPlacementsValid(array $placements, int $meetId, int $eventId, ?EventMatch $match = null, bool $deferDataIssues = false): void
    {
        $event = Event::query()->findOrFail($eventId);
        $entries = Entry::query()
            ->with(['athlete:id,first_name,last_name', 'delegation:id,meet_id'])
            ->whereIn('id', array_filter(array_column($placements, 'entry_id')))
            ->get()
            ->keyBy('id');
        $teamEntries = TeamEntry::query()
            ->with(['delegation:id,meet_id,school_id,district_id', 'delegation.school:id,name', 'delegation.district:id,name', 'members'])
            ->whereIn('id', array_filter(array_column($placements, 'team_entry_id')))
            ->get()
            ->keyBy('id');
        $placedTeamIds = [];

        foreach ($placements as $placement) {
            if ($event->is_team_event && ! empty($placement['team_entry_id'])) {
                $team = $teamEntries->get($placement['team_entry_id']);
                if ($team === null || $team->event_id !== $eventId || $team->delegation->meet_id !== $meetId) {
                    throw ValidationException::withMessages([
                        'placements' => __('Every team placement must belong to this meet event.'),
                    ]);
                }
                // The delegation is the authoritative participant for a team
                // event. Its athlete roster may be completed after encoding.
                if ($match !== null && ! $match->teamEntries->contains('id', $team->id)) {
                    throw ValidationException::withMessages([
                        'placements' => __('Every placement must be a participant in this competition.'),
                    ]);
                }
                if (in_array($team->id, $placedTeamIds, true)) {
                    throw ValidationException::withMessages(['placements' => __('Select each team only once in the result.')]);
                }
                $placedTeamIds[] = $team->id;

                continue;
            }

            /** @var Entry|null $entry */
            $entry = $entries->get($placement['entry_id'] ?? null);

            if ($entry === null
                || $entry->event_id !== $eventId
                || $entry->delegation->meet_id !== $meetId) {
                throw ValidationException::withMessages([
                    'placements' => __('Every placement must be an entry of this meet event.'),
                ]);
            }

            if (! $deferDataIssues && $entry->status !== EntryStatus::Confirmed) {
                throw ValidationException::withMessages([
                    'placements' => __('Only confirmed entries can be placed (:name is :status).', [
                        'name' => $entry->athlete->fullName(),
                        'status' => $entry->status->label(),
                    ]),
                ]);
            }

            if ($event->is_team_event) {
                $teamEntryId = $entry->teamMemberships()
                    ->whereHas('teamEntry', fn ($team) => $team->where('status', EntryStatus::Confirmed->value))
                    ->value('team_entry_id');
                if (! $deferDataIssues && $teamEntryId === null) {
                    throw ValidationException::withMessages([
                        'placements' => __('Team-event placements require a finalized team roster.'),
                    ]);
                }
                if (in_array($teamEntryId, $placedTeamIds, true)) {
                    throw ValidationException::withMessages([
                        'placements' => __('Select each team only once in the result.'),
                    ]);
                }
                $placedTeamIds[] = $teamEntryId;
            }

            if (! $deferDataIssues && $match !== null && ! $match->entries->contains('id', $entry->id)) {
                throw ValidationException::withMessages([
                    'placements' => __('Every placement must be a scheduled participant in this competition.'),
                ]);
            }
        }

        $byRank = collect($placements)->groupBy('rank');

        foreach ($byRank as $rank => $group) {
            if ($group->count() > 1 && $group->contains(fn (array $placement): bool => ! ($placement['is_tie'] ?? false))) {
                throw ValidationException::withMessages([
                    'placements' => __('Rank :rank appears more than once — mark those placements as a tie.', [
                        'rank' => $rank,
                    ]),
                ]);
            }
        }
    }

    /**
     * @param  array<int, array{entry_id: int, rank: int, mark?: string|null, is_tie?: bool}>  $placements
     */
    private function writePlacements(EventResult $result, array $placements): void
    {
        foreach ($placements as $placement) {
            $team = ! empty($placement['team_entry_id'])
                ? TeamEntry::query()->with('members')->find($placement['team_entry_id'])
                : null;
            $result->placements()->create([
                'entry_id' => $placement['entry_id'] ?? $team?->members->first()?->entry_id,
                'team_entry_id' => $placement['team_entry_id'] ?? Entry::query()->find($placement['entry_id'] ?? null)?->teamMemberships()
                    ->whereHas('teamEntry', fn ($team) => $team->where('status', EntryStatus::Confirmed->value))
                    ->value('team_entry_id'),
                'rank' => $placement['rank'],
                'mark' => $placement['mark'] ?? null,
                'is_tie' => $placement['is_tie'] ?? false,
            ]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function placementSnapshot(EventResult $result): array
    {
        return $result->placements()
            ->with([
                'entry.athlete:id,first_name,last_name,school_id',
                'entry.athlete.school:id,name',
                'teamEntry.delegation.school:id,name',
                'teamEntry.delegation.district:id,name',
            ])
            ->orderBy('rank')
            ->get()
            ->map(fn (ResultPlacement $placement): array => [
                'rank' => $placement->rank,
                'athlete' => $placement->teamEntry?->delegation?->registrantName()
                    ?? $placement->entry?->athlete?->fullName()
                    ?? __('Archived participant'),
                'school' => $placement->teamEntry?->delegation?->registrantName()
                    ?? $placement->entry?->athlete?->school?->name
                    ?? __('School unavailable'),
                'mark' => $placement->mark,
                'is_tie' => $placement->is_tie,
            ])
            ->all();
    }

    private function eventLabel(Event $event): string
    {
        $event->loadMissing('sport:id,name');

        return sprintf(
            '%s — %s (%s, %s)',
            $event->sport->name,
            $event->name,
            $event->gender->label(),
            $event->age_division->label(),
        );
    }

    private function canDeferDataIssues(User $user): bool
    {
        return $user->isAdmin() || $user->role === UserRole::TournamentICT;
    }

    /** @return array<int, string> */
    private function resultDataIssues(EventResult $result): array
    {
        $issues = [];
        if ($result->match_id !== null && $result->event_schedule_id === null) {
            $issues[] = __('Schedule is not linked.');
        }
        if ($result->match_id !== null && $result->tm_confirmed_at === null) {
            $issues[] = __('Tournament Manager confirmation is pending.');
        }
        foreach ($result->placements as $placement) {
            if ($placement->entry !== null && $placement->entry->status !== EntryStatus::Confirmed) {
                $issues[] = __('A participant entry is not confirmed.');
            }
            if ($placement->teamEntry !== null && $placement->teamEntry->members->isEmpty()) {
                $issues[] = __('A team has no assigned athletes.');
            }
            if ($placement->delegation_id !== null && $placement->entry_id === null && $placement->team_entry_id === null) {
                $issues[] = __('Athlete or team association is pending for a delegation-only placement.');
            }
        }

        return array_values(array_unique($issues));
    }

    private function isAssignedTournamentSecretary(User $user, EventResult $result): bool
    {
        return $user->meetSportAssignments()
            ->where('status', MeetSportAssignmentStatus::Active)
            ->where('role', MeetSportAssignmentRole::TournamentSecretary->value)
            ->whereHas('meetSport', fn ($meetSport) => $meetSport
                ->where('meet_id', $result->meet_id)
                ->where('sport_id', $result->event->sport_id))
            ->exists()
            && app(CompetitionAccessService::class)->canAccessEvent($user, $result->event, $result->meet_id);
    }

    private function isCentralEventSecretariat(User $user, int $meetId): bool
    {
        return $user->managementTeamMemberships()
            ->where('status', ManagementTeamMemberStatus::Active)
            ->whereHas('managementTeam', fn ($team) => $team
                ->where('meet_id', $meetId)
                ->where('source_code', 'EVENT_SECRETARIAT'))
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    private function context(EventResult $result): array
    {
        $result->loadMissing(['meet:id,name', 'event:id,sport_id,name']);

        return [
            'meet' => $result->meet->name,
            'event' => $result->event->name,
        ];
    }
}
