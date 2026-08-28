<?php

namespace App\Http\Controllers;

use App\Enums\EligibilityDocumentType;
use App\Enums\Permission;
use App\Enums\RequirementStatus;
use App\Enums\ResultStatus;
use App\Enums\UserRole;
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
use App\Models\ResultPlacement;
use App\Models\School;
use App\Models\SchoolDistrict;
use App\Models\Sport;
use App\Models\User;
use App\Services\AthletePhotoService;
use App\Services\AuditLogger;
use App\Services\CompetitionAccessService;
use App\Services\FileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
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

        $search = $this->searchTerm($request);
        $municipalityId = $request->integer('municipality_id') ?: null;
        $schoolDistrictId = $request->integer('school_district_id') ?: null;
        $schoolId = $request->integer('school_id') ?: null;
        $sportId = $request->integer('sport_id') ?: null;
        $sex = $request->string('sex')->value();
        $accreditation = $request->string('accreditation')->value();

        $query = Athlete::query()
            ->with([
                'school:id,district_id,school_district_id,name',
                'school.district:id,name',
                'school.schoolDistrict:id,name',
                'delegation.meet:id,name',
                'delegation.school:id,name',
                'delegation.district:id,name',
                'eligibilityReview:id,athlete_id,status',
                'accreditation:id,athlete_id',
                'sportRosterMemberships.meetSport.sport:id,name',
                'registrar:id,name,role',
            ])
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($user->role === UserRole::DelegationOfficer) {
            $query->whereHas(
                'delegation.officers',
                fn ($officers) => $officers->whereKey($user->getKey()),
            );
        } elseif ($user->role === UserRole::Coach) {
            $assignedEventIds = $user->approvedCoachEventIds();
            $query->whereHas('delegation.personnel', fn ($personnel) => $personnel->where('user_id', $user->id))
                ->ownedBy($user)
                ->where(fn ($athletes) => $athletes
                    ->whereDoesntHave('entries')
                    ->orWhereHas('entries', fn ($entries) => $entries->whereIn('event_id', $assignedEventIds)));
        } elseif (! $user->hasRole(UserRole::Admin, UserRole::Organizer)
            && $user->hasPermission(Permission::AthleteEligibilityReview, Meet::current())) {
            $query->whereHas('delegation', fn ($delegation) => $delegation->where('meet_id', Meet::current()->id));
        } elseif (! $user->canManageProductionAccounts() && $user->tournamentMeetIds()->isNotEmpty()) {
            app(CompetitionAccessService::class)->scopeAthletes($query, $user);
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
            $query->whereHas('eligibilityReview', fn ($reviews) => $reviews->where('status', \App\Enums\EligibilityStatus::Approved->value))
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
                    'school' => $athlete->school->name,
                    'district' => $athlete->school->schoolDistrict?->name
                        ?? $athlete->school->district?->name
                        ?? __('Not assigned'),
                    'delegation' => $athlete->delegation->registrantName(),
                    'coach' => $athlete->registrar?->hasRole(UserRole::Coach)
                        ? $athlete->registrar->name
                        : null,
                    'photo_url' => $athlete->photo_upload_id === null ? null : route('athletes.photo', $athlete),
                    'sports' => $athlete->rosterSportNames()->join(', '),
                    'accreditation_status' => $athlete->accreditation !== null
                        ? __('Accredited')
                        : __('Not accredited'),
                    'eligibility_status' => $athlete->eligibilityReview?->status->label()
                        ?? __('Documents not submitted'),
                    'can_update' => $user->can('update', $athlete),
                    'can_delete' => $user->can('delete', $athlete),
                ]),
            'filters' => [
                'search' => $search, 'municipality_id' => $municipalityId,
                'school_district_id' => $schoolDistrictId, 'school_id' => $schoolId,
                'sport_id' => $sportId, 'sex' => $sex, 'accreditation' => $accreditation,
            ],
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
                        ->with('sport:id,name')
                        ->whereIn('id', $user->approvedCoachEventIdsForDelegation($delegation))
                        ->where('is_team_event', false)
                        ->orderBy('name')
                        ->get()
                        ->map(fn (Event $event): array => [
                            'id' => $event->id,
                            'label' => $event->sport->name.' — '.$event->name,
                            'gender' => $event->gender->value,
                            'age_division' => $event->age_division->value,
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
            'registrar:id,name,role',
        ]);

        $documentOrder = collect(EligibilityDocumentType::qualificationRequirements())
            ->mapWithKeys(fn (EligibilityDocumentType $type, int $index): array => [$type->value => $index]);

        $documents = $athlete->eligibilityDocuments
            ->sortByDesc('id')
            ->unique(fn (EligibilityDocument $document): string => $document->document_type->value)
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
                'coach' => $athlete->registrar?->hasRole(UserRole::Coach)
                    ? $athlete->registrar->name
                    : null,
                'photo_url' => $athlete->photo_upload_id === null
                    ? null
                    : route('athletes.photo', $athlete),
                'sports_photo_url' => $athlete->sportsPhotoUrl(),
                'sports' => $athlete->rosterSportNames()->join(', '),
                'accreditation_status' => $athlete->accreditation !== null
                    ? __('Accredited')
                    : ($athlete->eligibilityReview?->status->label() ?? __('Documents not submitted')),
                'can_update' => Gate::allows('update', $athlete),
                'documents' => $documents->map(fn (EligibilityDocument $document): array => [
                    'id' => $document->id,
                    'document' => $document->document_type === EligibilityDocumentType::AthleteHistory
                        ? __('Profile / History')
                        : $document->document_type->label(),
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
        $delegation = Delegation::query()->findOrFail($request->integer('delegation_id'));

        Gate::authorize('create', [Athlete::class, $delegation]);

        $fileFields = ['photo', 'sports_photo', 'athlete_history', 'form_10', 'school_id_document', 'birth_certificate', 'report_card', 'parental_consent', 'medical_certificate', 'event_id', 'district_id', 'school_district_id'];
        $athlete = new Athlete($request->safe()->except($fileFields));

        /** @var User $user */
        $user = $request->user();
        $athlete->registered_by = $user->id;

        if ($request->hasFile('photo')) {
            $athlete->photo_upload_id = $this->athletePhotos->store($request->file('photo'), $user, 'passport')->id;
        }

        if ($request->hasFile('sports_photo')) {
            $athlete->sports_photo_upload_id = $this->athletePhotos->store($request->file('sports_photo'), $user, 'sports')->id;
        }

        DB::transaction(function () use ($request, $athlete, $user): void {
            if ($user->role === UserRole::Coach && $request->filled(['district_id', 'school_district_id'])) {
                $athlete->school()->getRelated()->newQuery()->whereKey($athlete->school_id)->update([
                    'district_id' => $request->integer('district_id'),
                    'school_district_id' => $request->integer('school_district_id'),
                ]);
            }
            $athlete->save();

            $documents = [
                'athlete_history' => EligibilityDocumentType::AthleteHistory,
                'form_10' => EligibilityDocumentType::Form10,
                'school_id_document' => EligibilityDocumentType::SchoolId,
                'birth_certificate' => EligibilityDocumentType::BirthCertificate,
                'report_card' => EligibilityDocumentType::ReportCard,
                'parental_consent' => EligibilityDocumentType::ParentalConsent,
                'medical_certificate' => EligibilityDocumentType::MedicalCertificate,
            ];

            foreach ($documents as $field => $type) {
                if (! $request->hasFile($field)) {
                    continue;
                }

                $upload = $this->athletePhotos->storeDocument($request->file($field), $user, $field);
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

            if ($user->role === UserRole::Coach && $request->filled('event_id')) {
                Entry::query()->create([
                    'delegation_id' => $athlete->delegation_id,
                    'athlete_id' => $athlete->id,
                    'event_id' => $request->integer('event_id'),
                ]);
            }
        });

        $this->audit->record('athlete.created', $athlete, [
            'name' => $athlete->fullName(),
            'school' => $athlete->school->name,
            'registrant' => $delegation->registrantName(),
        ]);
        if ($user->role === UserRole::Coach && $request->filled('event_id')) {
            $entry = $athlete->entries()->with('event')->sole();
            $this->audit->record('entry.auto_assigned', $entry, [
                'athlete' => $athlete->fullName(),
                'event' => $entry->event->name,
                'source' => 'coach_approved_assignment',
            ]);
        }
        if ($athlete->photo_upload_id !== null) {
            $this->audit->record('athlete.passport_photo_uploaded', $athlete);
        }
        if ($athlete->sports_photo_upload_id !== null) {
            $this->audit->record('athlete.sports_photo_uploaded', $athlete);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Athlete registered.')]);

        return back();
    }

    /**
     * Update an athlete, optionally replacing the photo.
     */
    public function update(AthleteRequest $request, Athlete $athlete): RedirectResponse
    {
        Gate::authorize('update', $athlete);

        $fileFields = ['photo', 'sports_photo', 'athlete_history', 'form_10', 'school_id_document', 'birth_certificate', 'report_card', 'parental_consent', 'medical_certificate'];
        $athlete->fill($request->safe()->except([...$fileFields, 'delegation_id']));

        /** @var User $user */
        $user = $request->user();

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

        $documents = [
            'athlete_history' => EligibilityDocumentType::AthleteHistory,
            'form_10' => EligibilityDocumentType::Form10,
            'school_id_document' => EligibilityDocumentType::SchoolId,
            'birth_certificate' => EligibilityDocumentType::BirthCertificate,
            'report_card' => EligibilityDocumentType::ReportCard,
            'parental_consent' => EligibilityDocumentType::ParentalConsent,
            'medical_certificate' => EligibilityDocumentType::MedicalCertificate,
        ];

        foreach ($documents as $field => $type) {
            if (! $request->hasFile($field)) {
                continue;
            }

            $previousDocuments = $athlete->eligibilityDocuments()
                ->where('document_type', $type->value)
                ->with('fileUpload')
                ->get();
            $upload = $this->athletePhotos->storeDocument($request->file($field), $user, $field);
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
        }

        if ($athlete->eligibilityDocuments()->exists()) {
            EligibilityReview::query()->firstOrCreate([
                'athlete_id' => $athlete->id,
                'meet_id' => $athlete->delegation->meet_id,
            ]);
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

        return Storage::disk($upload->disk)->response($path, basename($path), ['Content-Type' => 'image/jpeg']);
    }

    /** Permanently remove an athlete and release their LRN for re-registration. */
    public function destroy(Request $request, Athlete $athlete): RedirectResponse
    {
        Gate::authorize('delete', $athlete);
        abort_unless($request->user()->isAdmin(), 403);

        $name = $athlete->fullName();
        $uploads = collect([$athlete->photo, $athlete->sportsPhoto])
            ->merge($athlete->eligibilityDocuments()->with('fileUpload')->get()->pluck('fileUpload'))
            ->filter()
            ->unique('id')
            ->values();

        DB::transaction(function () use ($athlete): void {
            $athlete->teamMemberships()->delete();
            $athlete->sportRosterMemberships()->delete();
            $athlete->forceDelete();
        });

        foreach ($uploads as $upload) {
            if ($upload->id === $athlete->photo_upload_id || $upload->id === $athlete->sports_photo_upload_id) {
                $this->athletePhotos->delete($upload);
            } else {
                $this->uploads->delete($upload);
            }
        }

        $this->audit->record('athlete.permanently_deleted', $athlete, ['name' => $name]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Athlete permanently removed. The LRN can now be registered again.'),
        ]);

        return redirect()->route('athletes.index');
    }
}
