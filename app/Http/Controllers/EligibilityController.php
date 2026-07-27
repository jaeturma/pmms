<?php

namespace App\Http\Controllers;

use App\Enums\EligibilityDocumentType;
use App\Enums\EligibilityStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Concerns\SearchesAndPaginates;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\EligibilityDocument;
use App\Models\EligibilityReview;
use App\Models\User;
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
    ) {}

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
        }

        // Counts reflect the officer's whole queue regardless of the
        // status/search filters below — the summary cards' "at a glance"
        // totals shouldn't shift just because the list is filtered.
        $counts = [
            'pending' => (clone $base)->where('status', EligibilityStatus::Pending->value)->count(),
            'approved' => (clone $base)->where('status', EligibilityStatus::Approved->value)->count(),
            'returned' => (clone $base)->where('status', EligibilityStatus::Returned->value)->count(),
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
            'can_delete' => $review->status !== EligibilityStatus::Approved
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
     * Remove a document while the review is not yet approved.
     */
    public function destroyDocument(EligibilityDocument $document): RedirectResponse
    {
        $athlete = $document->athlete;
        $review = $athlete->eligibilityReview;

        Gate::authorize('upload', [EligibilityReview::class, $athlete->delegation]);

        if ($review !== null && $review->status === EligibilityStatus::Approved) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Documents of an approved review cannot be removed.'),
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

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Eligibility returned for correction.')]);

        return back();
    }
}
