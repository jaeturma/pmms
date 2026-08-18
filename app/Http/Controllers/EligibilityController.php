<?php

namespace App\Http\Controllers;

use App\Enums\EligibilityDocumentType;
use App\Enums\EligibilityStatus;
use App\Enums\RequirementStatus;
use App\Enums\Permission;
use App\Enums\UserRole;
use App\Http\Controllers\Concerns\SearchesAndPaginates;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\EligibilityDocument;
use App\Models\EligibilityReview;
use App\Models\User;
use App\Models\EligibilityCheck;
use App\Models\Event;
use App\Models\Meet;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Services\Eligibility\AthleteEligibilityChecker;
use App\Services\Eligibility\TechnicalOfficialEligibilityChecker;
use App\Services\AuditLogger;
use App\Services\FileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class EligibilityController extends Controller
{
    use SearchesAndPaginates;

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly FileUploadService $uploads,
        private readonly AthleteEligibilityChecker $athleteChecker,
        private readonly TechnicalOfficialEligibilityChecker $officialChecker,
    ) {}

    public function athleteChecker(Request $request): Response
    {
        Gate::authorize('viewAny', EligibilityReview::class);
        $meet = Meet::query()->find($request->integer('meet_id')) ?? Meet::query()->active()->first();
        $athlete = $request->filled('athlete_id') ? Athlete::query()->findOrFail($request->integer('athlete_id')) : null;
        $event = $request->filled('event_id') ? Event::query()->findOrFail($request->integer('event_id')) : null;
        $category = $request->filled('sport_category_id') ? SportCategory::query()->findOrFail($request->integer('sport_category_id')) : $event?->sportCategory;
        $evaluation = $athlete !== null && $meet !== null ? $this->athleteChecker->evaluate($athlete, $meet, $event, $category) : null;

        if ($evaluation !== null) {
            $previousResult = EligibilityCheck::query()->where('subject_type', 'athlete')->where('subject_id', $athlete->id)
                ->where('meet_id', $meet->id)->latest('checked_at')->value('result');
            EligibilityCheck::query()->create(['meet_id' => $meet->id, 'subject_type' => 'athlete', 'subject_id' => $athlete->id,
                'sport_id' => $event?->sport_id, 'sport_category_id' => $category?->id, 'event_id' => $event?->id,
                'result' => $evaluation->result(), 'checked_by' => $request->user()?->id, 'checked_at' => now(), 'snapshot' => $evaluation->toArray()]);
            $this->audit->record('athlete.eligibility.checked', $athlete, ['result' => $evaluation->result()->value]);
            $this->audit->record('athlete.eligibility.recalculated', $athlete, ['result' => $evaluation->result()->value, 'previous_result' => $previousResult]);
            if ($previousResult !== $evaluation->result()->value && in_array($evaluation->result()->value, ['eligible', 'ineligible'], true)) {
                $this->audit->record('athlete.became_'.$evaluation->result()->value, $athlete, ['previous_result' => $previousResult]);
            }
        }

        return Inertia::render('athletes/eligibility', [
            'athletes' => Athlete::query()->with('school:id,name')->orderBy('last_name')->get()->map(fn (Athlete $item) => ['id' => $item->id, 'label' => $item->fullName().' — '.$item->school->name]),
            'events' => Event::query()->with(['sport:id,name', 'sportCategory:id,display_name'])->orderBy('name')->get()->map(fn (Event $item) => ['id' => $item->id, 'label' => $item->sport->name.' — '.$item->name, 'category' => $item->sportCategory?->display_name]),
            'selectedAthlete' => $athlete?->load(['school', 'delegation.district', 'eligibilityDocuments']),
            'evaluation' => $evaluation?->toArray(), 'meet' => $meet?->only(['id', 'name']),
        ]);
    }

    public function officialChecker(Request $request, User $official): Response
    {
        abort_unless($request->user()?->hasRole(UserRole::Admin, UserRole::Organizer), 403);
        $meet = Meet::query()->find($request->integer('meet_id')) ?? Meet::query()->active()->firstOrFail();
        $sport = $request->filled('sport_id') ? Sport::query()->findOrFail($request->integer('sport_id')) : null;
        $evaluation = $sport === null ? null : $this->officialChecker->evaluate($official, $meet, $sport);
        if ($evaluation !== null) {
            EligibilityCheck::query()->create(['meet_id' => $meet->id, 'subject_type' => 'technical_official', 'subject_id' => $official->id,
                'sport_id' => $sport->id, 'result' => $evaluation->result(), 'checked_by' => $request->user()?->id,
                'checked_at' => now(), 'snapshot' => $evaluation->toArray()]);
            $this->audit->record('technical_official.eligibility.checked', $official, ['result' => $evaluation->result()->value]);
        }
        $credential = $sport === null ? null : \App\Models\TechnicalOfficialAccreditation::query()
            ->where('user_id', $official->id)->where('sport_id', $sport->id)->latest()->first();

        return Inertia::render('technical-officials/eligibility', [
            'official' => $official->only(['id', 'name', 'email']),
            'sports' => Sport::query()->where('active', true)->orderBy('name')->get(['id', 'name']),
            'selectedSport' => $sport?->only(['id', 'name']),
            'accreditation' => $credential === null ? null : [
                'type' => $credential->accreditation_type,
                'certificate_number' => $credential->certificate_number,
                'issuer' => $credential->issuing_organization,
                'issued_at' => $credential->issued_at?->toDateString(),
                'expires_at' => $credential->expires_at?->toDateString(),
                'status' => $credential->status->value,
                'attachment_url' => route('technical-official-accreditations.download', $credential),
            ],
            'evaluation' => $evaluation?->toArray(),
            'meet' => $meet->only(['id', 'name']),
        ]);
    }

    /**
     * The eligibility review queue, searchable by athlete name and
     * filterable by status, officer-scoped.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', EligibilityReview::class);

        /** @var User $user */
        $user = $request->user();

        $search = $this->searchTerm($request);
        $status = (string) $request->query('status', '');

        $base = EligibilityReview::query()
            ->with([
                'athlete.school:id,name',
                'athlete.delegation.meet:id,name',
                'athlete.eligibilityDocuments.fileUpload:id,original_name',
                'reviewer:id,name',
            ]);

        if ($user->role === UserRole::DelegationOfficer) {
            $base->whereHas(
                'athlete.delegation.officers',
                fn ($officers) => $officers->whereKey($user->getKey()),
            );
        } elseif ($user->role === UserRole::Coach) {
            $base->whereHas('athlete.delegation.personnel', fn ($personnel) => $personnel->where('user_id', $user->id));
        } elseif (! $user->hasRole(UserRole::Admin, UserRole::Organizer) && ! $user->hasPermission(Permission::AthleteEligibilityReview)) {
            $assignments = $user->athleteOversightAssignments()->where('active', true)->get();
            $base->whereHas('athlete.school', function ($school) use ($assignments): void {
                $school->where(function ($scope) use ($assignments): void {
                    foreach ($assignments as $assignment) {
                        $scope->orWhere(fn ($item) => $assignment->school_district_id !== null
                            ? $item->where('school_district_id', $assignment->school_district_id)
                            : $item->where('district_id', $assignment->district_id));
                    }
                });
            });
        }

        // Counts reflect the officer's whole queue regardless of the
        // status/search filters below — the summary cards' "at a glance"
        // totals shouldn't shift just because the list is filtered.
        $counts = [
            'pending' => (clone $base)->where('status', EligibilityStatus::Pending->value)->count(),
            'approved' => (clone $base)->where('status', EligibilityStatus::Approved->value)->count(),
            'returned' => (clone $base)->where('status', EligibilityStatus::Returned->value)->count(),
            'rejected' => (clone $base)->where('status', EligibilityStatus::Rejected->value)->count(),
        ];

        $query = (clone $base)
            ->orderByRaw("case status when 'pending' then 0 when 'returned' then 1 else 2 end")
            ->orderByDesc('id');

        if (EligibilityStatus::tryFrom($status) !== null) {
            $query->where('status', $status);
        }

        $this->applySearch($query, $search, ['athlete.first_name', 'athlete.last_name']);

        $delegationScope = Delegation::query()->with(['school:id,name', 'district:id,name', 'meet:id,name']);

        if ($user->role === UserRole::DelegationOfficer) {
            $delegationScope->whereHas(
                'officers',
                fn ($officers) => $officers->whereKey($user->getKey()),
            );
        }

        $uploadableDelegations = $delegationScope->get()
            ->filter(fn (Delegation $delegation): bool => $user->can('upload', [EligibilityReview::class, $delegation]));

        return Inertia::render('eligibility/index', [
            'reviews' => $query->paginate($this->registryPageSize)->withQueryString()
                ->through(fn (EligibilityReview $review): array => $this->reviewRow($review, $user)),
            'counts' => $counts,
            'filters' => [
                'status' => EligibilityStatus::tryFrom($status)?->value,
                'search' => $search !== '' ? $search : null,
            ],
            'athleteOptions' => Athlete::query()
                ->with('school:id,name')
                ->whereIn('delegation_id', $uploadableDelegations->pluck('id'))
                ->orderBy('last_name')
                ->get()
                ->map(fn (Athlete $athlete): array => [
                    'id' => $athlete->id,
                    'label' => "{$athlete->fullName()} — {$athlete->school->name}",
                ])
                ->values(),
            'documentTypeOptions' => array_map(
                fn (EligibilityDocumentType $type): array => [
                    'value' => $type->value,
                    'label' => $type->label(),
                ],
                EligibilityDocumentType::cases(),
            ),
        ]);
    }

    /**
     * @return array{id: int, athlete: string, school: string, meet: string, status: string, status_label: string, remarks: string|null, reviewer: string|null, decided_at: string|null, documents: array<int, array{id: int, label: string, file_name: string, uploaded_at: string|null, url: string, can_delete: bool}>, can_decide: bool}
     */
    private function reviewRow(EligibilityReview $review, User $user): array
    {
        return [
            'id' => $review->id,
            'athlete' => $review->athlete->fullName(),
            'school' => $review->athlete->school->name,
            'meet' => $review->athlete->delegation->meet->name,
            'status' => $review->status->value,
            'status_label' => $review->status->label(),
            'remarks' => $review->remarks,
            'reviewer' => $review->reviewer?->name,
            'decided_at' => $review->decided_at?->diffForHumans(),
            'documents' => $review->athlete->eligibilityDocuments
                ->map(fn (EligibilityDocument $document): array => $this->documentRow($document, $review, $user))
                ->values()
                ->all(),
            'can_decide' => $review->status === EligibilityStatus::Pending
                && $user->can('decide', $review),
        ];
    }

    /**
     * @return array{id: int, label: string, file_name: string, uploaded_at: string|null, url: string, can_delete: bool}
     */
    private function documentRow(EligibilityDocument $document, EligibilityReview $review, User $user): array
    {
        return [
            'id' => $document->id,
            'label' => $document->document_type->label(),
            'file_name' => $document->fileUpload->original_name,
            'uploaded_at' => $document->created_at !== null ? $document->created_at->format('M j, Y') : null,
            'url' => route('eligibility.documents.download', $document),
            'can_delete' => ! in_array($review->status, [EligibilityStatus::Approved, EligibilityStatus::Rejected], true)
                && $user->can('upload', [EligibilityReview::class, $review->athlete->delegation]),
        ];
    }

    /**
     * Upload an eligibility document; ensures a pending review exists and
     * re-opens returned reviews (resubmission).
     */
    public function storeDocument(Request $request): RedirectResponse
    {
        $request->validate([
            'athlete_id' => ['required', 'integer', Rule::exists('athletes', 'id')],
            'document_type' => ['required', Rule::enum(EligibilityDocumentType::class)],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $athlete = Athlete::query()
            ->with('delegation.meet')
            ->findOrFail($request->integer('athlete_id'));

        Gate::authorize('upload', [EligibilityReview::class, $athlete->delegation]);

        $review = EligibilityReview::query()->firstOrCreate([
            'athlete_id' => $athlete->id,
            'meet_id' => $athlete->delegation->meet_id,
        ]);

        if ($review->status === EligibilityStatus::Approved) {
            throw ValidationException::withMessages([
                'athlete_id' => __('This athlete\'s eligibility is already approved.'),
            ]);
        }

        // Unlike Returned below, Rejected is terminal — a fresh upload
        // never reopens it automatically (see EligibilityStatus::Rejected's
        // own docblock).
        if ($review->status === EligibilityStatus::Rejected) {
            throw ValidationException::withMessages([
                'athlete_id' => __('This athlete\'s eligibility was rejected.'),
            ]);
        }

        /** @var UploadedFile $file */
        $file = $request->file('file');

        /** @var User $user */
        $user = $request->user();

        $upload = $this->uploads->store($file, $user);

        $document = EligibilityDocument::create([
            'athlete_id' => $athlete->id,
            'file_upload_id' => $upload->id,
            'document_type' => $request->string('document_type')->value(),
        ]);

        $this->audit->record('athlete.document.uploaded', $document, [
            'athlete' => $athlete->fullName(),
            'type' => $document->document_type->value,
        ]);
        // Retained for compatibility with existing audit reports.
        $this->audit->record('eligibility.document_uploaded', $document, [
            'athlete' => $athlete->fullName(),
            'type' => $document->document_type->value,
        ]);

        if ($review->status === EligibilityStatus::Returned) {
            $review->forceFill([
                'status' => EligibilityStatus::Pending,
                'reviewer_id' => null,
                'remarks' => null,
                'decided_at' => null,
            ])->save();

            $this->audit->record('eligibility.resubmitted', $review, [
                'athlete' => $athlete->fullName(),
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Document uploaded.')]);

        return back();
    }

    /**
     * Serve a document; every view of minor eligibility data is audited.
     */
    public function downloadDocument(EligibilityDocument $document): HttpResponse
    {
        $review = $document->athlete->eligibilityReview;

        abort_if($review === null, 404);

        Gate::authorize('view', $review);

        $this->audit->record('eligibility.document_viewed', $document, [
            'athlete' => $document->athlete->fullName(),
            'type' => $document->document_type->value,
        ]);

        $upload = $document->fileUpload;

        return Storage::disk($upload->disk)->response($upload->path, $upload->original_name);
    }

    /**
     * Remove a document while the review is not yet decided in a way that
     * locks it (approved or rejected — both terminal, see `documentRow()`'s
     * matching `can_delete` computation).
     */
    public function destroyDocument(EligibilityDocument $document): RedirectResponse
    {
        $athlete = $document->athlete;
        $review = $athlete->eligibilityReview;

        Gate::authorize('upload', [EligibilityReview::class, $athlete->delegation]);

        if ($review !== null && in_array($review->status, [EligibilityStatus::Approved, EligibilityStatus::Rejected], true)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Documents of a decided review cannot be removed.'),
            ]);

            return back();
        }

        $upload = $document->fileUpload;

        $this->audit->record('eligibility.document_deleted', $document, [
            'athlete' => $athlete->fullName(),
            'type' => $document->document_type->value,
        ]);

        $document->delete();
        $this->uploads->delete($upload);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Document removed.')]);

        return back();
    }

    public function verifyDocument(Request $request, EligibilityDocument $document): RedirectResponse
    {
        $review = $document->athlete->eligibilityReview;
        abort_if($review === null, 404);
        $permission = $document->document_type === EligibilityDocumentType::MedicalCertificate
            ? Permission::MedicalClearanceEvaluate
            : Permission::AthleteDocumentsVerify;
        abort_unless($request->user()?->hasPermission($permission, $review->meet), 403);
        $data = $request->validate(['status' => ['required', Rule::enum(RequirementStatus::class)], 'remarks' => ['nullable', 'string', 'max:1000']]);
        abort_unless(in_array($data['status'], [RequirementStatus::Verified->value, RequirementStatus::Rejected->value, RequirementStatus::UnderReview->value], true), 422);
        $document->forceFill(['status' => $data['status'], 'verified_by' => $request->user()?->id,
            'verified_at' => $data['status'] === RequirementStatus::UnderReview->value ? null : now(), 'remarks' => $data['remarks'] ?? null])->save();
        $action = $data['status'] === RequirementStatus::Verified->value ? 'athlete.document.verified' : ($data['status'] === RequirementStatus::Rejected->value ? 'athlete.document.rejected' : 'athlete.document.under_review');
        $this->audit->record($action, $document, ['athlete' => $document->athlete->fullName(), 'type' => $document->document_type->value]);
        return back();
    }

    /**
     * Approve a pending review (human decision, with optional remarks).
     */
    public function approve(Request $request, EligibilityReview $review): RedirectResponse
    {
        Gate::authorize('decide', $review);

        $request->validate(['remarks' => ['nullable', 'string', 'max:500']]);

        if ($review->status !== EligibilityStatus::Pending) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Only pending reviews can be decided.'),
            ]);

            return back();
        }

        $review->forceFill([
            'status' => EligibilityStatus::Approved,
            'reviewer_id' => $request->user()?->getAuthIdentifier(),
            'remarks' => $request->string('remarks')->value() ?: null,
            'decided_at' => now(),
        ])->save();

        $this->audit->record('eligibility.approved', $review, [
            'athlete' => $review->athlete->fullName(),
        ]);
        $this->audit->record('athlete.dsac.approved', $review, ['athlete' => $review->athlete->fullName()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Eligibility approved.')]);

        return back();
    }

    /**
     * Return a pending review for correction (remarks required).
     */
    public function returnReview(Request $request, EligibilityReview $review): RedirectResponse
    {
        Gate::authorize('decide', $review);

        $request->validate(['remarks' => ['required', 'string', 'max:500']]);

        if ($review->status !== EligibilityStatus::Pending) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Only pending reviews can be decided.'),
            ]);

            return back();
        }

        $review->forceFill([
            'status' => EligibilityStatus::Returned,
            'reviewer_id' => $request->user()?->getAuthIdentifier(),
            'remarks' => $request->string('remarks')->value(),
            'decided_at' => now(),
        ])->save();

        $this->audit->record('eligibility.returned', $review, [
            'athlete' => $review->athlete->fullName(),
        ]);
        $this->audit->record('athlete.dsac.returned', $review, ['athlete' => $review->athlete->fullName()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Eligibility returned for correction.')]);

        return back();
    }

    /**
     * Reject a pending review outright (reason required) — terminal,
     * unlike `returnReview()`: a fresh document upload does not reopen a
     * rejected review the way it reopens a returned one (see
     * `storeDocument()` and `EligibilityStatus::Rejected`'s docblock).
     * Added WP-REALIGN-06.
     */
    public function reject(Request $request, EligibilityReview $review): RedirectResponse
    {
        Gate::authorize('decide', $review);

        $request->validate(['remarks' => ['required', 'string', 'max:500']]);

        if ($review->status !== EligibilityStatus::Pending) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Only pending reviews can be decided.'),
            ]);

            return back();
        }

        $review->forceFill([
            'status' => EligibilityStatus::Rejected,
            'reviewer_id' => $request->user()?->getAuthIdentifier(),
            'remarks' => $request->string('remarks')->value(),
            'decided_at' => now(),
        ])->save();

        $this->audit->record('eligibility.rejected', $review, [
            'athlete' => $review->athlete->fullName(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Eligibility rejected.')]);

        return back();
    }
}
