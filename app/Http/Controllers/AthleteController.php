<?php

namespace App\Http\Controllers;

use App\Enums\EligibilityDocumentType;
use App\Enums\UserRole;
use App\Http\Controllers\Concerns\BuildsSchoolOptionsByDelegation;
use App\Http\Controllers\Concerns\SearchesAndPaginates;
use App\Http\Requests\AthleteRequest;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\EligibilityDocument;
use App\Models\EligibilityReview;
use App\Models\Entry;
use App\Models\FileUpload;
use App\Models\User;
use App\Services\AthletePhotoService;
use App\Services\AuditLogger;
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
                'entries.event.sport:id,name',
            ])
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($user->role === UserRole::DelegationOfficer) {
            $query->whereHas(
                'delegation.officers',
                fn ($officers) => $officers->whereKey($user->getKey()),
            );
        } elseif ($user->role === UserRole::Coach) {
            $approved = $user->coachAssignmentRequests()->where('status', 'approved');
            $query->whereHas('entries', fn ($entries) => $entries
                ->whereIn('delegation_id', (clone $approved)->select('delegation_id'))
                ->whereIn('event_id', (clone $approved)->select('event_id')));
        } elseif ($user->tournamentAthleteSportIds()->isNotEmpty()) {
            $query->whereHas('entries.event', fn ($event) => $event->whereIn('sport_id', $user->tournamentAthleteSportIds()));
        } elseif (! $user->hasRole(UserRole::Admin, UserRole::Organizer)) {
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

        $this->applySearch($query, $search, ['first_name', 'last_name', 'lrn']);

        $delegations = Delegation::query()
            ->with(['school:id,name', 'district:id,name', 'meet:id,name']);

        if ($user->role === UserRole::DelegationOfficer) {
            $delegations->whereHas(
                'officers',
                fn ($officers) => $officers->whereKey($user->getKey()),
            );
        } elseif ($user->role === UserRole::Coach) {
            $delegations->whereIn('id', $user->coachAssignmentRequests()->where('status', 'approved')->select('delegation_id'));
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
                    'district' => $athlete->school->schoolDistrict?->name ?? $athlete->school->district->name,
                    'delegation' => $athlete->delegation->registrantName(),
                    'sports' => $athlete->entries->pluck('event.sport.name')->filter()->unique()->values()->join(', '),
                    'accreditation_status' => $athlete->accreditation !== null
                        ? __('Accredited')
                        : ($athlete->eligibilityReview?->status->label() ?? __('Documents not submitted')),
                    'can_update' => $user->can('update', $athlete),
                    'can_delete' => $user->can('delete', $athlete),
                ]),
            'filters' => ['search' => $search],
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
        ]);
    }

    /**
     * Full athlete profile. Every view of minor data is audited.
     */
    public function show(Athlete $athlete): Response
    {
        Gate::authorize('view', $athlete);

        $athlete->loadMissing(['eligibilityReview:id,athlete_id,status', 'accreditation:id,athlete_id', 'entries.event.sport:id,name']);

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
                'photo_url' => $athlete->photo_upload_id === null
                    ? null
                    : route('athletes.photo', $athlete),
                'sports_photo_url' => $athlete->sportsPhotoUrl(),
                'sports' => $athlete->entries->pluck('event.sport.name')->filter()->unique()->values()->join(', '),
                'accreditation_status' => $athlete->accreditation !== null
                    ? __('Accredited')
                    : ($athlete->eligibilityReview?->status->label() ?? __('Documents not submitted')),
                'can_update' => Gate::allows('update', $athlete),
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

        $fileFields = ['photo', 'sports_photo', 'athlete_history', 'form_10', 'school_id_document', 'birth_certificate', 'report_card', 'parental_consent', 'medical_certificate', 'event_id'];
        $athlete = new Athlete($request->safe()->except($fileFields));

        /** @var User $user */
        $user = $request->user();

        if ($request->hasFile('photo')) {
            $athlete->photo_upload_id = $this->athletePhotos->store($request->file('photo'), $user, 'passport')->id;
        }

        if ($request->hasFile('sports_photo')) {
            $athlete->sports_photo_upload_id = $this->athletePhotos->store($request->file('sports_photo'), $user, 'sports')->id;
        }

        DB::transaction(function () use ($request, $athlete, $user): void {
            $athlete->save();

            if ($request->user()?->role === UserRole::Coach && $request->filled('event_id')) {
                Entry::query()->create([
                    'delegation_id' => $athlete->delegation_id,
                    'athlete_id' => $athlete->id,
                    'event_id' => $request->integer('event_id'),
                ]);
            }

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

                $upload = $this->uploads->store($request->file($field), $user, $field);
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
        });

        $this->audit->record('athlete.created', $athlete, [
            'name' => $athlete->fullName(),
            'school' => $athlete->school->name,
            'registrant' => $delegation->registrantName(),
        ]);
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

            $upload = $this->uploads->store($request->file($field), $user, $field);
            EligibilityDocument::query()->create([
                'athlete_id' => $athlete->id,
                'file_upload_id' => $upload->id,
                'document_type' => $type->value,
            ]);
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

    /** Soft-delete an athlete while retaining accreditation evidence. */
    public function destroy(Athlete $athlete): RedirectResponse
    {
        Gate::authorize('delete', $athlete);

        $name = $athlete->fullName();
        $athlete->delete();

        $this->audit->record('athlete.deleted', $athlete, ['name' => $name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Athlete removed.')]);

        return redirect()->route('athletes.index');
    }
}
