<?php

namespace App\Http\Controllers;

use App\Enums\EligibilityDocumentType;
use App\Enums\EligibilityStatus;
use App\Enums\Permission;
use App\Enums\ResultStatus;
use App\Enums\UserRole;
use App\Enums\MeetSportAssignmentRole;
use App\Http\Controllers\Concerns\BuildsSchoolOptionsByDelegation;
use App\Http\Controllers\Concerns\SearchesAndPaginates;
use App\Http\Requests\AthleteRequest;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\District;
use App\Models\EligibilityDocument;
use App\Models\EligibilityReview;
use App\Models\Entry;
use App\Models\Event;
use App\Models\FileUpload;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\ResultPlacement;
use App\Models\School;
use App\Models\SchoolDistrict;
use App\Models\Sport;
use App\Models\SportRosterMember;
use App\Models\User;
use App\Services\AthletePhotoService;
use App\Services\AthleteEligibilityService;
use App\Services\AthleteMedicalClearanceService;
use App\Services\AthleteRegistrationScope;
use App\Services\AthleteDeletionService;
use App\Services\AuditLogger;
use App\Services\CompetitionAccessService;
use App\Services\FileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AthleteController extends Controller
{
    use BuildsSchoolOptionsByDelegation;
    use SearchesAndPaginates;

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly FileUploadService $uploads,
        private readonly AthletePhotoService $athletePhotos,
        private readonly AthleteDeletionService $athleteDeletion,
        private readonly AthleteRegistrationScope $registrationScope,
        private readonly AthleteEligibilityService $eligibility,
        private readonly AthleteMedicalClearanceService $medicalClearance,
    ) {}

    /**
     * Searchable, paginated athlete registry. Officers see only their own
     * delegation's athletes; viewers have no access (minor data).
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Athlete::class);

        /** @var User $user */
        $user = $request->user();
        $isIct = $user->role === UserRole::TournamentICT
            || (! $user->isAdmin() && $user->canManageProductionAccounts());

        $search = $this->searchTerm($request);
        $municipalityId = $request->integer('municipality_id') ?: null;
        $schoolDistrictId = $request->integer('school_district_id') ?: null;
        $schoolId = $request->integer('school_id') ?: null;
        $sportId = $request->integer('sport_id') ?: null;
        $sex = $request->string('sex')->value();
        $accreditation = $request->string('accreditation')->value();
        $deleted = $user->isAdmin() && $request->boolean('deleted');
        $ictMeetIds = $user->meetSportAssignments()
            ->where('status', 'active')
            ->where('role', MeetSportAssignmentRole::TournamentICT->value)
            ->with('meetSport:id,meet_id')
            ->get()->pluck('meetSport.meet_id')->filter()->unique()->values();
        $canViewUnassigned = $ictMeetIds->isNotEmpty();
        $unassigned = $canViewUnassigned && $request->boolean('unassigned');

        $query = Athlete::query()
            ->with([
                'school:id,district_id,school_district_id,name',
                'school.district:id,name',
                'school.schoolDistrict:id,name',
                'delegation.meet:id,name',
                'delegation.school:id,name',
                'delegation.district:id,name',
                'eligibilityReview:id,athlete_id,status',
                'eligibilityDocuments:id,athlete_id,document_type',
                'accreditation:id,athlete_id',
                'sportRosterMemberships.meetSport.sport:id,name',
                'entries.event.sport:id,name',
                'registrar:id,name,role',
                'coaches:id,name',
            ])
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($deleted) {
            $query->onlyTrashed();
        }

        if ($user->role === UserRole::DelegationOfficer) {
            $query->whereHas(
                'delegation.officers',
                fn ($officers) => $officers->whereKey($user->getKey()),
            );
        } elseif ($user->role === UserRole::Coach) {
            $query->whereHas('delegation.personnel', fn ($personnel) => $personnel->where('user_id', $user->id))
                ->ownedBy($user);
        } elseif ($isIct) {
            // ICT registration totals and rows must describe the same meet-wide
            // athlete population, including athletes not assigned to a sport yet.
            $query->whereHas('delegation', fn ($delegation) => $delegation->where('meet_id', Meet::current()->id));
        } elseif (! $user->hasRole(UserRole::Admin, UserRole::Organizer)
            && $user->hasPermission(Permission::AthleteEligibilityReview, Meet::current())) {
            $query->whereHas('delegation', fn ($delegation) => $delegation->where('meet_id', Meet::current()->id));
        } elseif (! $user->canManageProductionAccounts() && $user->tournamentMeetIds()->isNotEmpty()) {
            if ($unassigned && $ictMeetIds->isNotEmpty()) {
                $query->whereHas('delegation', fn ($delegations) => $delegations->whereIn('meet_id', $ictMeetIds));
            } else {
                app(CompetitionAccessService::class)->scopeAthletes($query, $user);
            }
        } elseif (! $user->hasRole(UserRole::Admin, UserRole::Organizer) && ! $user->canManageProductionAccounts()) {
            $assignments = $user->athleteOversightAssignments()->where('active', true)->get();
            $query->whereHas('school', function ($school) use ($assignments): void {
                $school->where(function ($scope) use ($assignments): void {
                    foreach ($assignments as $assignment) {
                        $scope->orWhere(fn ($item) => $assignment->school_district_id !== null
                            ? $item->where('school_district_id', $assignment->school_district_id)
                            : $item->where('district_id', $assignment->district_id));
                    }
                });
            });
        }

        if ($unassigned) {
            $query->whereDoesntHave('sportRosterMemberships');
        }

        $query
            ->when($municipalityId !== null, fn ($athletes) => $athletes->whereHas('school', fn ($school) => $school->where('district_id', $municipalityId)))
            ->when($schoolDistrictId !== null, fn ($athletes) => $athletes->whereHas('school', fn ($school) => $school->where('school_district_id', $schoolDistrictId)))
            ->when($schoolId !== null, fn ($athletes) => $athletes->where('school_id', $schoolId))
            ->when(in_array($sex, ['male', 'female'], true), fn ($athletes) => $athletes->where('sex', $sex))
            ->when($sportId !== null, fn ($athletes) => $athletes->whereHas(
                'sportRosterMemberships.meetSport',
                fn ($meetSports) => $meetSports->where('sport_id', $sportId),
            ))
            ->when($accreditation === 'accredited', fn ($athletes) => $athletes->whereHas('accreditation'))
            ->when($accreditation === 'not_accredited', fn ($athletes) => $athletes->whereDoesntHave('accreditation'));

        if ($accreditation === 'eligible') {
            $query->whereHas('eligibilityReview', fn ($reviews) => $reviews->where('status', EligibilityStatus::Approved->value))
                ->whereHas('eligibilityDocuments', fn ($documents) => $documents
                    ->where('document_type', EligibilityDocumentType::MedicalCertificate->value));
        }

        $this->applySearch($query, $search, ['first_name', 'last_name', 'lrn']);

        $delegations = Delegation::query()
            ->with(['school:id,name', 'district:id,name', 'meet:id,name']);

        if ($user->role === UserRole::DelegationOfficer) {
            $delegations->whereHas(
                'officers',
                fn ($officers) => $officers->whereKey($user->getKey()),
            );
        } elseif ($user->role === UserRole::Coach) {
            $delegations->whereHas('personnel', fn ($personnel) => $personnel->where('user_id', $user->id));
        } elseif ($isIct) {
            $delegations->where('meet_id', Meet::current()->id);
        }

        $availableDelegations = $delegations->get()
            ->filter(fn (Delegation $delegation): bool => $user->can('create', [Athlete::class, $delegation]))
            ->values();

        return Inertia::render('athletes/index', [
            'athletes' => $query->paginate($this->registryPageSize)->withQueryString()
                ->through(fn (Athlete $athlete): array => [
                    'id' => $athlete->id,
                    'name' => $athlete->fullName(),
                    'first_name' => $athlete->first_name,
                    'middle_name' => $athlete->middle_name,
                    'last_name' => $athlete->last_name,
                    'name_extension' => $athlete->name_extension,
                    'sex' => $athlete->sex->value,
                    'birthdate' => $athlete->birthdate->toDateString(),
                    'lrn' => $athlete->lrn,
                    'sex_label' => $athlete->sex->label(),
                    'age' => $athlete->age(),
                    'grade_level' => $athlete->grade_level,
                    'age_division' => $athlete->ageDivision()->value,
                    'school' => $athlete->school->name,
                    'district' => $athlete->school->schoolDistrict?->name
                        ?? $athlete->school->district?->name
                        ?? __('Not assigned'),
                    'delegation' => $athlete->delegation->registrantName(),
                    'coach' => $athlete->coaches->pluck('name')->whenEmpty(fn () => $athlete->registrar?->hasRole(UserRole::Coach)
                        ? collect([$athlete->registrar->name]) : collect())->join(', ') ?: null,
                    'photo_url' => $athlete->trashed() ? null : $athlete->photoUrl(),
                    'sports' => $athlete->rosterSportNames()->join(', '),
                    'events' => $athlete->entries->pluck('event.name')->filter()->unique()->sort()->join(', '),
                    'accreditation_status' => $athlete->accreditation !== null
                        ? __('Accredited')
                        : __('Not accredited'),
                    'eligibility_status' => match ($athlete->eligibilityReview?->status?->value) {
                        'approved' => __('Eligible'),
                        'pending', 'returned' => __('Under Review'),
                        default => __('Not Eligible'),
                    },
                    'eligibility_state' => match ($athlete->eligibilityReview?->status?->value) {
                        'approved' => 'eligible',
                        'pending', 'returned' => 'under_review',
                        default => 'not_eligible',
                    },
                    'deleted' => $athlete->trashed(),
                    'deleted_at' => $athlete->deleted_at?->toDateTimeString(),
                    'can_update' => ! $athlete->trashed()
                        && ($user->can('update', $athlete) || $user->can('updateAssets', $athlete)),
                    'can_delete' => ! $athlete->trashed() && $user->can('delete', $athlete),
                    'deletion_pending' => $athlete->deletion_requested_at !== null,
                    'can_confirm_deletion' => $athlete->deletion_requested_at !== null && $this->isTournamentIct($user, $athlete),
                    'can_cancel_deletion' => $athlete->deletion_requested_at !== null
                        && ($user->isAdmin() || $this->isTournamentIct($user, $athlete)),
                ]),
            'filters' => [
                'search' => $search, 'municipality_id' => $municipalityId,
                'school_district_id' => $schoolDistrictId, 'school_id' => $schoolId,
                'sport_id' => $sportId, 'sex' => $sex, 'accreditation' => $accreditation,
                'deleted' => $deleted,
                'unassigned' => $unassigned,
            ],
            'canViewDeleted' => $user->isAdmin(),
            'canViewUnassigned' => $canViewUnassigned,
            'delegationOptions' => $availableDelegations
                ->map(fn (Delegation $delegation): array => [
                    'id' => $delegation->id,
                    'label' => $delegation->registrantName(),
                ])
                ->values(),
            'schoolOptionsByDelegation' => $this->schoolOptionsByDelegation($availableDelegations),
            'fixedDelegationId' => $user->role === UserRole::Coach
                ? $availableDelegations->first()?->id
                : null,
            'fixedMunicipalityId' => $user->role === UserRole::Coach
                ? ($availableDelegations->first()?->district_id ?? $availableDelegations->first()?->school?->district_id)
                : null,
            'coachEventOptionsByDelegation' => $user->role === UserRole::Coach
                ? $availableDelegations->mapWithKeys(function (Delegation $delegation) use ($user): array {
                    $events = Event::query()
                        ->with(['sport:id,name', 'sportCategory:id,display_name'])
                        ->whereIn('id', $user->approvedCoachEventIdsForDelegation($delegation))
                        ->where('is_team_event', false)
                        ->orderBy('name')
                        ->get()
                        ->map(fn (Event $event): array => [
                            'id' => $event->id,
                            'label' => $event->sport->name.' — '.$event->name,
                            'category' => $event->sportCategory?->display_name ?? $event->age_division->label(),
                            'gender' => $event->gender->label(),
                            'grade_level' => match ($event->age_division->value) {
                                'elementary' => __('Grades 1–6'),
                                'secondary' => __('Grades 7–12'),
                                'elementary_secondary', 'mixed' => __('Grades 1–12'),
                                default => $event->age_division->label(),
                            },
                        ])
                        ->values();

                    return [$delegation->id => $events];
                })->all()
                : [],
            'municipalities' => District::query()->where('active', true)->orderBy('name')->get(['id', 'name']),
            'schoolDistricts' => SchoolDistrict::query()->where('active', true)->orderBy('name')->get(['id', 'district_id', 'name']),
            'filterSchools' => School::query()->where('active', true)->orderBy('name')->get(['id', 'district_id', 'school_district_id', 'name']),
            'sports' => Sport::query()->where('active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function edit(Request $request, Athlete $athlete): Response
    {
        $canUpdateIdentity = Gate::allows('update', $athlete);
        $canUpdateAssignments = Gate::allows('updateAssignments', $athlete);
        if (! $canUpdateIdentity && ! $canUpdateAssignments) {
            Gate::authorize('updateAssets', $athlete);
        }

        $athlete->loadMissing(['entries:id,athlete_id,event_id', 'sportRosterMemberships:id,athlete_id,meet_sport_id', 'coaches:id,name']);
        $delegations = Delegation::query()->where('meet_id', $athlete->delegation->meet_id)
            ->with(['school:id,name,district_id', 'district:id,name', 'meet:id,name'])->orderBy('id')->get();
        $schools = School::query()->where('active', true)->with(['district:id,name', 'schoolDistrict:id,name'])->orderBy('name')->get();
        $isTournamentIct = $this->isTournamentIct($request->user(), $athlete);
        $isCoach = $request->user()->role === UserRole::Coach;
        $canReassignCoach = $isTournamentIct || $request->user()->canManageProductionAccounts();
        $allowedEventIds = $isTournamentIct
            ? app(CompetitionAccessService::class)->eventIds($request->user(), $athlete->delegation->meet_id)
            : ($isCoach ? $request->user()->approvedCoachEventIdsForDelegation($athlete->delegation) : null);
        $meetSports = MeetSport::query()->where('meet_id', $athlete->delegation->meet_id)
            ->when($allowedEventIds !== null, fn ($query) => $query->whereIn('sport_id', Event::query()->whereKey($allowedEventIds)->select('sport_id')))
            ->with('sport:id,name')->orderBy('display_order')->get();
        $events = $athlete->delegation->meet->events()
            ->when($allowedEventIds !== null, fn ($query) => $query->whereIn('events.id', $allowedEventIds))
            ->with(['sport:id,name', 'sportCategory:id,display_name'])
            ->orderBy('display_order')->orderBy('name')->get();
        $allowedSportIds = $allowedEventIds === null
            ? collect()
            : Event::query()->whereKey($allowedEventIds)->pluck('sport_id')->unique();
        $coachOptions = $canReassignCoach
            ? User::query()->where('role', UserRole::Coach->value)
                ->whereHas('coachAssignmentRequests', fn ($query) => $query
                    ->where('status', 'approved')->whereNull('ended_at')
                    ->whereIn('delegation_id', $delegations->modelKeys())
                    ->when($isTournamentIct, fn ($query) => $query->where(fn ($scope) => $scope
                        ->whereIn('event_id', $allowedEventIds)
                        ->orWhereHas('meetSport', fn ($meetSport) => $meetSport->whereIn('sport_id', $allowedSportIds)))))
                ->with(['coachAssignmentRequests' => fn ($query) => $query
                    ->where('status', 'approved')->whereNull('ended_at')
                    ->whereIn('delegation_id', $delegations->modelKeys())])
                ->orderBy('name')->get(['id', 'name'])
            : collect();

        return Inertia::render('athletes/edit', [
            'athlete' => [
                'id' => $athlete->id, 'delegation_id' => $athlete->delegation_id, 'school_id' => $athlete->school_id,
                'first_name' => $athlete->first_name, 'middle_name' => $athlete->middle_name, 'last_name' => $athlete->last_name,
                'name_extension' => $athlete->name_extension, 'sex' => $athlete->sex->value,
                'birthdate' => $athlete->birthdate->toDateString(), 'lrn' => $athlete->lrn, 'grade_level' => $athlete->grade_level,
                'age_division' => $athlete->ageDivision()->value,
                'meet_sport_ids' => $athlete->sportRosterMemberships->pluck('meet_sport_id')->all(),
                'event_ids' => $athlete->entries->pluck('event_id')->all(),
                'photo_url' => $athlete->photoUrl(),
                'sports_photo_url' => $athlete->sportsPhotoUrl(),
                'registered_by' => $athlete->registered_by,
                'coach_ids' => $athlete->coaches->modelKeys() ?: ($athlete->registrar?->hasRole(UserRole::Coach) ? [$athlete->registered_by] : []),
            ],
            'delegations' => $delegations->map(fn (Delegation $item) => [
                'id' => $item->id, 'meet_id' => $item->meet_id, 'school_id' => $item->school_id,
                'district_id' => $item->district_id ?? $item->school?->district_id,
                'label' => $item->registrantName().' — '.$item->meet->name,
            ]),
            'schools' => $schools->map(fn (School $item) => ['id' => $item->id, 'name' => $item->name, 'district' => $item->district?->name ?? 'Not assigned', 'district_id' => $item->district_id]),
            'sports' => $meetSports->map(fn (MeetSport $item) => ['id' => $item->id, 'name' => $item->sport->name]),
            'events' => $events->map(fn (Event $item) => [
                'id' => $item->id,
                'sport_id' => $item->sport_id,
                'sport' => $item->sport->name,
                'name' => $item->name,
                'category' => $item->sportCategory?->display_name ?? $item->age_division->label(),
                'gender' => $item->gender->label(),
                'grade_level' => match ($item->age_division->value) {
                    'elementary' => __('Grades 1–6'),
                    'secondary' => __('Grades 7–12'),
                    'elementary_secondary', 'mixed' => __('Grades 1–12'),
                    default => $item->age_division->label(),
                },
            ]),
            'assignmentsOnly' => ! $canUpdateIdentity && $canUpdateAssignments,
            'assetsOnly' => ! $canUpdateIdentity && ! $canUpdateAssignments,
            'canReassignCoach' => $canReassignCoach,
            'coachOptions' => $coachOptions->map(fn (User $coach) => [
                'id' => $coach->id, 'name' => $coach->name,
                'delegation_ids' => $coach->coachAssignmentRequests->pluck('delegation_id')->filter()->unique()->values(),
            ]),
        ]);
    }

    /**
     * Full athlete profile. Every view of minor data is audited.
     */
    public function show(Athlete $athlete): Response
    {
        Gate::authorize('view', $athlete);

        $athlete->loadMissing([
            'eligibilityReview:id,athlete_id,status',
            'eligibilityDocuments.fileUpload:id,original_name',
            'accreditation:id,athlete_id',
            'sportRosterMemberships.meetSport.sport:id,name',
            'entries.event.sport:id,name',
            'registrar:id,name,role',
        ]);

        $documentOrder = collect(EligibilityDocumentType::qualificationRequirements())
            ->mapWithKeys(fn (EligibilityDocumentType $type, int $index): array => [$type->value => $index]);

        $documents = $athlete->eligibilityDocuments
            ->sortByDesc('id')
            ->sortBy(fn (EligibilityDocument $document): int => $documentOrder->get(
                $document->document_type->value,
                PHP_INT_MAX,
            ))
            ->values();

        $achievements = ResultPlacement::query()
            ->with('result.event.sport')
            ->whereHas('result', fn ($result) => $result->where('status', ResultStatus::Official->value))
            ->where(fn ($placements) => $placements
                ->whereHas('entry', fn ($entry) => $entry->where('athlete_id', $athlete->id))
                ->orWhereHas('teamEntry.members', fn ($members) => $members->where('athlete_id', $athlete->id)))
            ->whereIn('rank', [1, 2, 3])
            ->get()
            ->unique(fn (ResultPlacement $placement): string => $placement->event_result_id.'-'.$placement->rank)
            ->sortBy(fn (ResultPlacement $placement): array => [$placement->rank, $placement->result->event->name]);

        $this->audit->record('athlete.viewed', $athlete, ['name' => $athlete->fullName()]);

        return Inertia::render('athletes/show', [
            'athlete' => [
                'id' => $athlete->id,
                'first_name' => $athlete->first_name,
                'middle_name' => $athlete->middle_name,
                'last_name' => $athlete->last_name,
                'name_extension' => $athlete->name_extension,
                'sex' => $athlete->sex->value,
                'sex_label' => $athlete->sex->label(),
                'birthdate' => $athlete->birthdate->toDateString(),
                'age' => $athlete->age(),
                'lrn' => $athlete->lrn,
                'grade_level' => $athlete->grade_level,
                'school' => $athlete->school->name,
                'meet' => $athlete->delegation->meet->name,
                'delegation' => $athlete->delegation->registrantName(),
                'coach' => $athlete->coaches()->pluck('name')->whenEmpty(fn () => $athlete->registrar?->hasRole(UserRole::Coach)
                    ? collect([$athlete->registrar->name]) : collect())->join(', ') ?: null,
                'photo_url' => $athlete->photoUrl(),
                'sports_photo_url' => $athlete->sportsPhotoUrl(),
                'sports' => $athlete->rosterSportNames()
                    ->whenEmpty(fn () => $athlete->entries
                        ->pluck('event.sport.name')
                        ->filter()
                        ->unique()
                        ->sort()
                        ->values())
                    ->join(', '),
                'events' => $athlete->entries
                    ->map(fn ($entry): string => $entry->event->sport->name.' — '.$entry->event->name)
                    ->unique()
                    ->sort()
                    ->values()
                    ->join(', '),
                'accreditation_status' => $athlete->accreditation !== null
                    ? __('Accredited')
                    : ($athlete->eligibilityReview?->status->label() ?? __('Documents not submitted')),
                'eligibility_status' => $athlete->eligibilityReview?->status === EligibilityStatus::Approved
                    ? ($this->hasCompleteEligibilityDocuments($athlete)
                        ? __('Eligible')
                        : __('Eligible — Incomplete documents'))
                    : ($athlete->eligibilityReview?->status->label() ?? __('Documents not submitted')),
                'eligibility_documents_incomplete' => ! $this->hasCompleteEligibilityDocuments($athlete),
                'can_update' => Gate::allows('update', $athlete),
                'can_mark_eligible' => Gate::allows('markEligible', $athlete),
                'documents' => $documents->map(fn (EligibilityDocument $document): array => [
                    'id' => $document->id,
                    'document' => $document->document_type->label(),
                    'file_name' => $document->fileUpload->original_name,
                    'view_url' => route('eligibility.documents.download', $document),
                    'status' => $document->status->value,
                    'status_label' => match ($document->status->value) {
                        'under_review' => __('Review'),
                        'verified' => __('Approved'),
                        'rejected' => __('Rejected'),
                        default => __('Pending'),
                    },
                ])->all(),
                'achievements' => $achievements->map(fn (ResultPlacement $placement): array => [
                    'medal' => match ($placement->rank) {
                        1 => __('Gold'),
                        2 => __('Silver'),
                        3 => __('Bronze'),
                    },
                    'sport' => $placement->result->event->sport->name,
                    'event' => $placement->result->event->name,
                    'team' => $placement->result->event->is_team_event,
                ])->values()->all(),
            ],
        ]);
    }

    /** Manually mark one athlete Eligible when the batch scan missed them. */
    public function markEligible(Request $request, Athlete $athlete): RedirectResponse
    {
        Gate::authorize('markEligible', $athlete);

        $this->eligibility->markEligibleManually($athlete, $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Athlete marked Eligible.'),
        ]);

        return back();
    }

    private function hasCompleteEligibilityDocuments(Athlete $athlete): bool
    {
        $athlete->loadMissing('eligibilityDocuments:id,athlete_id,document_type');
        $uploadedTypes = $athlete->eligibilityDocuments
            ->pluck('document_type')
            ->map(fn (EligibilityDocumentType|string $type): string => $type instanceof EligibilityDocumentType
                ? $type->value
                : $type)
            ->unique();

        return collect(EligibilityDocumentType::qualificationRequirements())
            ->every(fn (EligibilityDocumentType $type): bool => $uploadedTypes->contains($type->value));
    }

    /**
     * Serve the athlete's photo, authorized by athlete visibility rather
     * than upload ownership.
     */
    public function photo(Request $request, Athlete $athlete): HttpResponse
    {
        Gate::authorize('view', $athlete);

        $upload = $athlete->photo;

        abort_if($upload === null, 404);

        return $this->photoResponse($request, $upload);
    }

    /**
     * Serve the athlete's sports/action photo — same authorization as the
     * registry photo above.
     */
    public function sportsPhoto(Request $request, Athlete $athlete): HttpResponse
    {
        Gate::authorize('view', $athlete);

        $upload = $athlete->sportsPhoto;

        abort_if($upload === null, 404);

        return $this->photoResponse($request, $upload);
    }

    /**
     * Register an athlete under a delegation.
     */
    public function store(AthleteRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $delegation = $user->role === UserRole::Coach
            ? $this->registrationScope->resolveDelegation($user)
            : Delegation::query()->findOrFail($request->integer('delegation_id'));
        $selectedMeetSportId = $request->filled('meet_sport_id') ? $request->integer('meet_sport_id') : null;
        if ($user->role === UserRole::Coach && $selectedMeetSportId === null && $request->filled('event_id')) {
            $selectedEvent = Event::query()
                ->whereIn('id', $user->approvedCoachEventIdsForDelegation($delegation))
                ->find($request->integer('event_id'));
            $selectedMeetSportId = $selectedEvent === null ? null : MeetSport::query()
                ->where('meet_id', $delegation->meet_id)
                ->where('sport_id', $selectedEvent->sport_id)
                ->value('id');
        }
        $meetSport = $user->role === UserRole::Coach
            ? $this->registrationScope->resolveMeetSport(
                $user,
                $delegation,
                $selectedMeetSportId,
            )
            : null;

        Gate::authorize('create', [Athlete::class, $delegation]);

        $fileFields = ['photo', 'sports_photo', 'athlete_history', 'form_10', 'form_10_page_2', 'birth_certificate', 'birth_certificate_page_2', 'parental_consent', 'medical_certificate', 'event_id', 'meet_sport_id', 'district_id', 'school_district_id', 'registered_by', 'coach_ids'];
        $recalled = Athlete::onlyTrashed()->where('lrn', $request->string('lrn')->toString())->first();
        if ($recalled !== null && $recalled->delegation->meet_id !== $delegation->meet_id) {
            throw ValidationException::withMessages([
                'lrn' => __('This deleted athlete belongs to a different meet and cannot be recalled here.'),
            ]);
        }
        $athlete = $recalled ?? new Athlete;
        $athlete->fill($request->safe()->except($fileFields));

        $athlete->forceFill([
            'registered_by' => $user->id,
            'delegation_id' => $delegation->id,
            'deletion_requested_by' => null,
            'deletion_requested_at' => null,
        ]);

        DB::transaction(function () use ($request, $athlete, $user, $recalled, $meetSport): void {
            if ($request->hasFile('photo')) {
                $athlete->photo_upload_id = $this->athletePhotos->store($request->file('photo'), $user, 'passport')->id;
            }
            if ($request->hasFile('sports_photo')) {
                $athlete->sports_photo_upload_id = $this->athletePhotos->store($request->file('sports_photo'), $user, 'sports')->id;
            }
            if ($recalled !== null) {
                $athlete->restore();
            }

            if ($user->role === UserRole::Coach && $request->filled(['district_id', 'school_district_id'])) {
                $athlete->school()->getRelated()->newQuery()->whereKey($athlete->school_id)->update([
                    'district_id' => $request->integer('district_id'),
                    'school_district_id' => $request->integer('school_district_id'),
                ]);
            }
            $athlete->save();
            if ($user->role === UserRole::Coach) {
                $athlete->coaches()->syncWithoutDetaching([$user->id]);
            }
            if ($recalled !== null) {
                $athlete->entries()->update(['delegation_id' => $athlete->delegation_id]);
                $athlete->sportRosterMemberships()->update(['delegation_id' => $athlete->delegation_id]);
            }

            $documents = [
                'athlete_history' => EligibilityDocumentType::AthleteRecord,
                'form_10' => EligibilityDocumentType::Form10,
                'form_10_page_2' => EligibilityDocumentType::Form10,
                'birth_certificate' => EligibilityDocumentType::BirthCertificate,
                'birth_certificate_page_2' => EligibilityDocumentType::BirthCertificate,
                'parental_consent' => EligibilityDocumentType::ParentalConsent,
                'medical_certificate' => EligibilityDocumentType::MedicalCertificate,
            ];

            foreach ($documents as $field => $type) {
                if (! $request->hasFile($field)) {
                    continue;
                }

                $file = $request->file($field);
                $upload = $file->getMimeType() === 'application/pdf'
                    ? $this->uploads->store($file, $user, $field)
                    : $this->athletePhotos->storeDocument($file, $user, $field);
                EligibilityDocument::query()->create([
                    'athlete_id' => $athlete->id,
                    'file_upload_id' => $upload->id,
                    'document_type' => $type->value,
                ]);
            }

            EligibilityReview::query()->firstOrCreate([
                'athlete_id' => $athlete->id,
                'meet_id' => $athlete->delegation->meet_id,
            ]);

            $this->eligibility->markEligibleWhenComplete($athlete, $user);
            $this->medicalClearance->clearWhenCertificateAttached($athlete, $user);

            if ($meetSport !== null) {
                SportRosterMember::query()->firstOrCreate([
                    'meet_sport_id' => $meetSport->id,
                    'athlete_id' => $athlete->id,
                ], [
                    'delegation_id' => $athlete->delegation_id,
                    'level' => $athlete->ageDivision(),
                    'gender' => $athlete->sex->value === 'male' ? 'boys' : 'girls',
                ]);
            }
        });

        $this->audit->record($recalled === null ? 'athlete.created' : 'athlete.recalled', $athlete, [
            'name' => $athlete->fullName(),
            'school' => $athlete->school->name,
            'registrant' => $delegation->registrantName(),
        ]);
        if ($meetSport !== null) {
            $this->audit->record('athlete.sport_roster_assigned', $athlete, [
                'athlete' => $athlete->fullName(),
                'sport' => $meetSport->sport->name,
                'source' => 'coach_approved_assignment',
            ]);
        }
        if ($athlete->photo_upload_id !== null) {
            $this->audit->record('athlete.passport_photo_uploaded', $athlete);
        }
        if ($athlete->sports_photo_upload_id !== null) {
            $this->audit->record('athlete.sports_photo_uploaded', $athlete);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $recalled === null
                ? __('Athlete registered.')
                : __('Deleted athlete recalled by LRN and assigned to the current coach.'),
        ]);

        return back();
    }

    /**
     * Update an athlete, optionally replacing the photo.
     */
    public function update(AthleteRequest $request, Athlete $athlete): RedirectResponse
    {
        $canUpdateIdentity = Gate::allows('update', $athlete);
        $canUpdateAssignments = Gate::allows('updateAssignments', $athlete);
        if (! $canUpdateIdentity && ! $canUpdateAssignments) {
            Gate::authorize('updateAssets', $athlete);
        }
        $wasUnassigned = $athlete->sportRosterMemberships()->doesntExist();

        /** @var User $user */
        $user = $request->user();
        $originalMeetId = $athlete->delegation->meet_id;
        $isTournamentIct = $this->isTournamentIct($user, $athlete);
        $isCoach = $user->role === UserRole::Coach;
        $canReassignCoach = $isTournamentIct || $user->canManageProductionAccounts();
        $canManageAssignments = $canUpdateAssignments || $isTournamentIct
            || ($canUpdateIdentity && ($user->isAdmin() || $user->canManageProductionAccounts() || $isCoach));
        $fileFields = ['photo', 'sports_photo', 'athlete_history', 'form_10', 'form_10_page_2', 'birth_certificate', 'birth_certificate_page_2', 'parental_consent', 'medical_certificate', 'meet_sport_ids', 'event_ids', 'registered_by', 'coach_ids'];
        if ($canUpdateIdentity && (! $isTournamentIct || $user->isAdmin() || $user->canManageProductionAccounts())) {
            $athlete->fill($request->safe()->except($fileFields));
        }

        if ($canManageAssignments) {
            $delegation = $request->filled('delegation_id')
                ? Delegation::query()->findOrFail($request->integer('delegation_id'))
                : $athlete->delegation;
            $eventIds = collect($request->has('event_ids')
                ? $request->input('event_ids', [])
                : $athlete->entries()->pluck('event_id'))
                ->map(fn ($id) => (int) $id)->unique();
            $meetSportIds = collect($request->has('meet_sport_ids')
                ? $request->input('meet_sport_ids', [])
                : $athlete->sportRosterMemberships()->pluck('meet_sport_id'))
                ->map(fn ($id) => (int) $id)->unique();

            if ($canReassignCoach && ($request->has('coach_ids') || $request->filled('registered_by'))) {
                $coachIds = collect($request->input('coach_ids', [$request->integer('registered_by')]))->filter()->map(fn ($id) => (int) $id)->unique();
                $coaches = User::query()->where('role', UserRole::Coach->value)->whereKey($coachIds)->get();
                if ($coaches->count() !== $coachIds->count()) {
                    throw ValidationException::withMessages(['coach_ids' => __('Select one or two valid Coaches.')]);
                }
                $selectedSportIds = MeetSport::query()->whereKey($meetSportIds)->pluck('sport_id')->unique();
                foreach ($coaches as $coach) {
                    $coachEventIds = $coach->approvedCoachEventIdsForDelegation($delegation);
                    $coachSportIds = Event::query()->whereKey($coachEventIds)->pluck('sport_id')->unique();
                    if (! $coach->coachAssignmentRequests()->where('delegation_id', $delegation->id)
                        ->where('status', 'approved')->whereNull('ended_at')->exists()
                        || $eventIds->diff($coachEventIds)->isNotEmpty()
                        || $selectedSportIds->diff($coachSportIds)->isNotEmpty()) {
                        throw ValidationException::withMessages([
                            'coach_ids' => __('Every selected Coach must be approved for this athlete’s delegation, sports, and events.'),
                        ]);
                    }
                }
                $athlete->registered_by = $coachIds->first();
                $athlete->setRelation('pendingCoaches', $coaches);
            }

            if ($isTournamentIct) {
                $allowedEventIds = app(CompetitionAccessService::class)->eventIds($user, $athlete->delegation->meet_id);
                $allowedSportIds = Event::query()->whereKey($allowedEventIds)->pluck('sport_id')->unique();
                if ($eventIds->diff($allowedEventIds)->isNotEmpty()
                    || MeetSport::query()->whereKey($meetSportIds)->whereNotIn('sport_id', $allowedSportIds)->exists()) {
                    throw ValidationException::withMessages(['event_ids' => __('Tournament ICT may only assign sports and events within their active scope.')]);
                }
                $athlete->delegation_id = $delegation->id;
                if ($request->filled('school_id')) {
                    $athlete->school_id = $request->integer('school_id');
                }
            }
            if ($isCoach) {
                $allowedEventIds = $user->approvedCoachEventIdsForDelegation($athlete->delegation);
                $allowedSportIds = Event::query()->whereKey($allowedEventIds)->pluck('sport_id')->unique();
                if ($eventIds->diff($allowedEventIds)->isNotEmpty()
                    || MeetSport::query()->whereKey($meetSportIds)->whereNotIn('sport_id', $allowedSportIds)->exists()) {
                    throw ValidationException::withMessages(['event_ids' => __('Coach may only assign approved sports and events.')]);
                }
            }
            if ($delegation->meet_id !== $originalMeetId
                || Event::query()->whereIn('id', $eventIds)->whereDoesntHave('meets', fn ($query) => $query->whereKey($delegation->meet_id))->exists()
                || MeetSport::query()->whereIn('id', $meetSportIds)->where('meet_id', '!=', $delegation->meet_id)->exists()) {
                throw ValidationException::withMessages(['delegation_id' => __('Delegation, sports, and events must belong to the athlete’s meet.')]);
            }
            if ($athlete->entries()->whereNotIn('event_id', $eventIds)->whereHas('placements')->exists()) {
                throw ValidationException::withMessages(['event_ids' => __('An event with recorded results cannot be removed from the athlete.')]);
            }
        }

        $oldPhoto = null;
        $oldSportsPhoto = null;

        if ($request->hasFile('photo')) {
            $oldPhoto = $athlete->photo;
            $athlete->photo_upload_id = $this->athletePhotos->store($request->file('photo'), $user, 'passport')->id;
        }

        if ($request->hasFile('sports_photo')) {
            $oldSportsPhoto = $athlete->sportsPhoto;
            $athlete->sports_photo_upload_id = $this->athletePhotos->store($request->file('sports_photo'), $user, 'sports')->id;
        }

        $athlete->save();

        if ($canReassignCoach && $athlete->relationLoaded('pendingCoaches')) {
            $athlete->coaches()->sync($athlete->getRelation('pendingCoaches')->modelKeys());
        }

        if ($canManageAssignments) {
            DB::transaction(function () use ($request, $athlete): void {
                $eventIds = collect($request->input('event_ids', []))->map(fn ($id) => (int) $id)->unique();
                $athlete->entries()->whereNotIn('event_id', $eventIds)->whereDoesntHave('placements')->delete();
                foreach ($eventIds as $eventId) {
                    Entry::query()->firstOrCreate(['athlete_id' => $athlete->id, 'event_id' => $eventId], ['delegation_id' => $athlete->delegation_id]);
                }
                $athlete->entries()->update(['delegation_id' => $athlete->delegation_id]);
                $sportIds = collect($request->input('meet_sport_ids', []))->map(fn ($id) => (int) $id)->unique();
                $athlete->sportRosterMemberships()->whereNotIn('meet_sport_id', $sportIds)->delete();
                foreach ($sportIds as $meetSportId) {
                    $athlete->sportRosterMemberships()->firstOrCreate(['meet_sport_id' => $meetSportId], [
                        'delegation_id' => $athlete->delegation_id, 'level' => $athlete->ageDivision(),
                        'gender' => $athlete->sex->value === 'male' ? 'boys' : 'girls',
                    ]);
                }
                $athlete->sportRosterMemberships()->update([
                    'delegation_id' => $athlete->delegation_id,
                    'level' => $athlete->ageDivision(),
                    'gender' => $athlete->sex->value === 'male' ? 'boys' : 'girls',
                ]);
            });
        }

        $documents = [
            'athlete_history' => EligibilityDocumentType::AthleteRecord,
            'form_10' => EligibilityDocumentType::Form10,
            'form_10_page_2' => EligibilityDocumentType::Form10,
            'birth_certificate' => EligibilityDocumentType::BirthCertificate,
            'birth_certificate_page_2' => EligibilityDocumentType::BirthCertificate,
            'parental_consent' => EligibilityDocumentType::ParentalConsent,
            'medical_certificate' => EligibilityDocumentType::MedicalCertificate,
        ];

        $replacedDocumentTypes = collect();
        foreach ($documents as $field => $type) {
            if (! $request->hasFile($field)) {
                continue;
            }

            $previousDocuments = collect();
            if (! $replacedDocumentTypes->contains($type->value)) {
                $previousDocuments = $athlete->eligibilityDocuments()
                    ->where('document_type', $type->value)
                    ->with('fileUpload')
                    ->get();
            }
            $file = $request->file($field);
            $upload = $file->getMimeType() === 'application/pdf'
                ? $this->uploads->store($file, $user, $field)
                : $this->athletePhotos->storeDocument($file, $user, $field);
            EligibilityDocument::query()->create([
                'athlete_id' => $athlete->id,
                'file_upload_id' => $upload->id,
                'document_type' => $type->value,
            ]);

            foreach ($previousDocuments as $previousDocument) {
                $previousUpload = $previousDocument->fileUpload;
                $previousDocument->delete();
                $this->uploads->delete($previousUpload);
            }
            $replacedDocumentTypes->push($type->value);
        }

        if ($athlete->eligibilityDocuments()->exists()) {
            EligibilityReview::query()->firstOrCreate([
                'athlete_id' => $athlete->id,
                'meet_id' => $athlete->delegation->meet_id,
            ]);

            $this->eligibility->markEligibleWhenComplete($athlete, $user);
            $this->medicalClearance->clearWhenCertificateAttached($athlete, $user);
        }

        if ($oldPhoto !== null) {
            $this->athletePhotos->delete($oldPhoto);
            $this->audit->record('athlete.passport_photo_replaced', $athlete);
        } elseif ($request->hasFile('photo')) {
            $this->audit->record('athlete.passport_photo_uploaded', $athlete);
        }

        if ($oldSportsPhoto !== null) {
            $this->athletePhotos->delete($oldSportsPhoto);
            $this->audit->record('athlete.sports_photo_replaced', $athlete);
        } elseif ($request->hasFile('sports_photo')) {
            $this->audit->record('athlete.sports_photo_uploaded', $athlete);
        }

        $this->audit->record('athlete.updated', $athlete, ['name' => $athlete->fullName()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Athlete updated.')]);

        if ($wasUnassigned && $athlete->sportRosterMemberships()->exists()) {
            return redirect()->route('athletes.index');
        }

        return back();
    }

    private function photoResponse(Request $request, FileUpload $upload): HttpResponse
    {
        $variant = $request->string('variant')->toString();
        $path = in_array($variant, ['thumb', 'card'], true)
            ? $this->athletePhotos->variantPath($upload, $variant)
            : $upload->path;
        if (! Storage::disk($upload->disk)->exists($path)) {
            $path = $upload->path;
        }

        abort_unless(Storage::disk($upload->disk)->exists($path), 404);

        return Storage::disk($upload->disk)->response($path, null, [
            'Content-Type' => $upload->mime_type ?: 'image/jpeg',
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
        ]);
    }

    /** Archive an athlete while retaining the record for administrator review. */
    public function destroy(Request $request, Athlete $athlete): RedirectResponse
    {
        Gate::authorize('delete', $athlete);
        $user = $request->user();
        if ($user->role === UserRole::Coach) {
            $athlete->forceFill(['deletion_requested_by' => $user->id, 'deletion_requested_at' => now()])->save();
            $this->audit->record('athlete.deletion_requested', $athlete, ['name' => $athlete->fullName()]);
            Inertia::flash('toast', ['type' => 'success', 'message' => __('Deletion request sent to Tournament ICT for confirmation.')]);

            return back();
        }

        if (! $user->isAdmin() && $athlete->deletion_requested_at === null) {
            $athlete->forceFill(['deletion_requested_by' => $user->id, 'deletion_requested_at' => now()])->save();
            $this->audit->record('athlete.deletion_requested', $athlete, ['name' => $athlete->fullName()]);
            Inertia::flash('toast', ['type' => 'success', 'message' => __('Deletion request recorded. Repeat the action to confirm deletion as Tournament ICT.')]);

            return back();
        }

        $name = $athlete->fullName();
        $athlete->delete();

        $this->audit->record('athlete.deleted', $athlete, ['name' => $name]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Athlete moved to deleted athletes.'),
        ]);

        return redirect()->route('athletes.index');
    }

    /** Cancel a pending Coach deletion request without altering the Athlete. */
    public function cancelDeletionRequest(Request $request, Athlete $athlete): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $this->isTournamentIct($user, $athlete), 403);

        if ($athlete->deletion_requested_at === null) {
            throw ValidationException::withMessages([
                'deletion_request' => __('This Athlete has no pending deletion request.'),
            ]);
        }

        $requestedBy = $athlete->deletion_requested_by;
        $athlete->forceFill([
            'deletion_requested_by' => null,
            'deletion_requested_at' => null,
        ])->save();

        $this->audit->record('athlete.deletion_request_cancelled', $athlete, [
            'name' => $athlete->fullName(),
            'requested_by' => $requestedBy,
        ]);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Athlete deletion request cancelled.')]);

        return back();
    }

    private function isTournamentIct(User $user, Athlete $athlete): bool
    {
        return app(CompetitionAccessService::class)->hasAssignmentRole(
            $user,
            [MeetSportAssignmentRole::TournamentICT->value],
            $athlete->delegation->meet_id,
        );
    }

    /** Permanently remove an archived athlete after explicit system-admin confirmation. */
    public function forceDestroy(Request $request, int $athlete): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $request->validate(['confirm' => ['required', 'accepted']]);
        $record = Athlete::onlyTrashed()->findOrFail($athlete);
        $name = $record->fullName();
        $this->athleteDeletion->permanentlyDelete($record);
        $this->audit->record('athlete.permanently_deleted', null, ['athlete_id' => $athlete, 'name' => $name]);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Athlete permanently deleted and may now be encoded again.')]);

        return redirect()->route('athletes.index', ['deleted' => 1]);
    }
}
