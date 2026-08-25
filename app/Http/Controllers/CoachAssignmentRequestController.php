<?php

namespace App\Http\Controllers;

use App\Enums\MeetSportAssignmentRole;
use App\Enums\PersonnelRole;
use App\Enums\UserRole;
use App\Models\Accreditation;
use App\Models\CoachAssignmentRequest;
use App\Models\CoachOnboardingRequest;
use App\Models\Delegation;
use App\Models\Event;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\Personnel;
use App\Models\School;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\FileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class CoachAssignmentRequestController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $reviewableIds = $this->reviewableMeetSportIds($user);
        $visibleIds = $this->visibleMeetSportIds($user);
        $canRequest = $this->isCoachApplicant($user);
        abort_unless($canRequest || $visibleIds->isNotEmpty() || $user->canReviewCoachRegistrations(), 403);

        $query = CoachAssignmentRequest::query()->with([
            'user:id,name,email', 'meetSport.meet:id,name', 'meetSport.sport:id,name',
            'event:id,name', 'delegation.school:id,name', 'delegation.district:id,name', 'school:id,name',
        ])->latest();

        if ($canRequest) {
            $query->where('user_id', $user->id);
        } elseif (! $user->canReviewCoachRegistrations()) {
            $query->whereIn('meet_sport_id', $visibleIds)
                ->whereIn('event_id', $user->tournamentEventIds());
        }

        return Inertia::render('coach/assignments', [
            'registrations' => $this->onboardingRegistrations($user),
            'requests' => $query->get()->map(fn (CoachAssignmentRequest $item): array => [
                'id' => $item->id, 'coach' => $item->user->name, 'email' => $item->user->email,
                'meet' => $item->meetSport->meet->name, 'sport' => $item->meetSport->sport->name,
                'event' => $item->event?->name, 'team' => $item->delegation->registrantName(),
                'school' => $item->school->name, 'status' => $item->status, 'review_notes' => $item->review_notes,
            ]),
            'canRequest' => $canRequest,
            'canReview' => $user->canReviewCoachRegistrations() || $reviewableIds->isNotEmpty(),
            'options' => $canRequest ? $this->requestOptions() : [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->isCoachApplicant($request->user()), 403);
        $data = $request->validate([
            'meet_sport_id' => ['required', Rule::exists('meet_sports', 'id')],
            'event_id' => ['required', Rule::exists('events', 'id')],
            'delegation_id' => ['required', Rule::exists('delegations', 'id')],
            'school_id' => ['required', Rule::exists('schools', 'id')->where('active', true)],
        ]);
        $delegation = Delegation::query()->findOrFail($data['delegation_id']);
        $school = School::query()->findOrFail($data['school_id']);
        $meetSport = MeetSport::query()->findOrFail($data['meet_sport_id']);
        $event = Event::query()->findOrFail($data['event_id']);

        abort_unless($meetSport->meet_id === $delegation->meet_id, 422);
        abort_unless($event->sport_id === $meetSport->sport_id && $event->meets()->whereKey($meetSport->meet_id)->exists(), 422);
        abort_unless(($delegation->district_id !== null && $school->district_id === $delegation->district_id)
            || ($delegation->school_id !== null && $school->id === $delegation->school_id), 403);

        CoachAssignmentRequest::query()->updateOrCreate(
            ['user_id' => $request->user()->id, 'event_id' => $event->id, 'delegation_id' => $delegation->id, 'school_id' => $school->id],
            ['meet_sport_id' => $meetSport->id, 'status' => 'pending', 'reviewed_by' => null, 'reviewed_at' => null, 'review_notes' => null],
        );

        return back()->with('success', __('Coach enrollment submitted for approval.'));
    }

    public function review(Request $request, CoachAssignmentRequest $coachAssignmentRequest, AuditLogger $audit): RedirectResponse
    {
        /** @var User $reviewer */
        $reviewer = $request->user();
        abort_unless($reviewer->canReviewCoachRegistrations() || (
            $this->reviewableMeetSportIds($reviewer)->contains($coachAssignmentRequest->meet_sport_id)
            && $reviewer->tournamentEventIds()->contains($coachAssignmentRequest->event_id)
        ), 403);
        $data = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected', 'inactive'])], 'review_notes' => ['nullable', 'string', 'max:1000']]);

        DB::transaction(function () use ($coachAssignmentRequest, $reviewer, $data, $audit): void {
            $coachAssignmentRequest->forceFill([
                'status' => $data['status'], 'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(), 'review_notes' => $data['review_notes'] ?? null,
                'assigned_by' => $data['status'] === 'approved' ? $reviewer->id : $coachAssignmentRequest->assigned_by,
                'assigned_at' => $data['status'] === 'approved' ? now() : $coachAssignmentRequest->assigned_at,
                'ended_at' => $data['status'] === 'inactive' ? now() : null,
            ])->save();
            if ($data['status'] !== 'inactive') {
                CoachOnboardingRequest::query()->updateOrCreate(
                    ['user_id' => $coachAssignmentRequest->user_id],
                    ['status' => $data['status'], 'reviewed_by' => $reviewer->id, 'reviewed_at' => now(), 'review_notes' => $data['review_notes'] ?? null],
                );
            }

            if ($data['status'] === 'approved') {
                $coachAssignmentRequest->user->forceFill(['role' => UserRole::Coach])->save();
            }
            $audit->record($data['status'] === 'inactive' ? 'coach.assignment_removed' : 'coach.assignment_updated', $coachAssignmentRequest, [
                'coach' => $coachAssignmentRequest->user->name, 'event_id' => $coachAssignmentRequest->event_id,
                'delegation_id' => $coachAssignmentRequest->delegation_id, 'status' => $data['status'],
            ], $reviewer);
        });

        return back()->with('success', __('Coach enrollment reviewed.'));
    }

    public function resetPassword(Request $request, CoachAssignmentRequest $coachAssignmentRequest, AuditLogger $audit): RedirectResponse
    {
        /** @var User $reviewer */
        $reviewer = $request->user();
        abort_unless($reviewer->canReviewCoachRegistrations() || (
            $this->reviewableMeetSportIds($reviewer)->contains($coachAssignmentRequest->meet_sport_id)
            && $reviewer->tournamentEventIds()->contains($coachAssignmentRequest->event_id)
        ), 403);

        $password = config('pmms.accounts.default_reset_password');
        abort_unless(is_string($password) && $password !== '', 503, 'The reset password is not configured.');

        $coachAssignmentRequest->user->forceFill([
            'password' => Hash::make($password),
            'must_change_password' => false,
            'password_changed_at' => null,
        ])->save();

        $audit->record('user.password_reset', $coachAssignmentRequest->user, [
            'coach_assignment_request_id' => $coachAssignmentRequest->id,
            'reset_by' => $reviewer->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Coach password reset to DdOPaa2026!.')]);

        return back();
    }

    public function reviewOnboarding(Request $request, CoachOnboardingRequest $coachOnboardingRequest, AuditLogger $audit): RedirectResponse
    {
        /** @var User $reviewer */
        $reviewer = $request->user();
        abort_unless($this->canReviewOnboarding($reviewer, $coachOnboardingRequest), 403);
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'returned', 'rejected'])],
            'event_ids' => [Rule::requiredIf($request->input('status') === 'approved' && $coachOnboardingRequest->meet_sport_id !== null), 'nullable', 'array', 'min:1'],
            'event_ids.*' => ['integer', 'distinct', Rule::exists('events', 'id')],
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $selectedEvents = collect($data['event_ids'] ?? []);
        if ($coachOnboardingRequest->meet_sport_id !== null && $selectedEvents->isNotEmpty()) {
            $validCount = Event::query()->whereKey($selectedEvents)
                ->where('sport_id', $coachOnboardingRequest->meetSport->sport_id)
                ->whereHas('meets', fn ($meets) => $meets->whereKey($coachOnboardingRequest->meetSport->meet_id))->count();
            if ($validCount !== $selectedEvents->count()) {
                throw ValidationException::withMessages(['event_ids' => __('Every assignment must belong to the applied sport and meet.')]);
            }
        }

        DB::transaction(function () use ($coachOnboardingRequest, $reviewer, $data, $selectedEvents, $audit): void {
            $coachOnboardingRequest->forceFill([
                'status' => $data['status'],
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_notes' => $data['review_notes'] ?? null,
            ])->save();
            $coachOnboardingRequest->user->forceFill([
                'role' => $data['status'] === 'approved' ? UserRole::Coach : UserRole::Viewer,
                'approval_status' => $data['status'],
                'approved_by' => $data['status'] === 'approved' ? $reviewer->id : null,
                'approved_at' => $data['status'] === 'approved' ? now() : null,
            ])->save();

            if ($data['status'] === 'approved') {
                $coachOnboardingRequest->loadMissing(['events', 'meetSport', 'delegation', 'school']);
                $events = $coachOnboardingRequest->meet_sport_id !== null
                    ? Event::query()->whereKey($selectedEvents)->get()
                    : $coachOnboardingRequest->events;
                $personnelByDelegation = [];
                foreach ($events as $event) {
                    $meetSport = $coachOnboardingRequest->meetSport ?? MeetSport::query()
                        ->where('sport_id', $event->sport_id)
                        ->whereHas('meet.events', fn ($events) => $events->whereKey($event->id))
                        ->whereHas('meet.delegations', fn ($delegations) => $delegations
                            ->where('district_id', $coachOnboardingRequest->district_id)
                            ->orWhereHas('school', fn ($school) => $school->where('district_id', $coachOnboardingRequest->district_id)))
                        ->latest('id')->first();
                    $delegation = $coachOnboardingRequest->delegation ?? $meetSport?->meet->delegations()
                        ->where(fn ($query) => $query->where('district_id', $coachOnboardingRequest->district_id)
                            ->orWhereHas('school', fn ($school) => $school->where('district_id', $coachOnboardingRequest->district_id)))
                        ->first();
                    $school = $coachOnboardingRequest->school ?? $delegation?->school ?? $delegation?->district?->schools()->where('active', true)->first();
                    if ($meetSport === null || $delegation === null || $school === null) {
                        continue;
                    }
                    CoachAssignmentRequest::query()->updateOrCreate([
                        'user_id' => $coachOnboardingRequest->user_id,
                        'event_id' => $event->id,
                        'delegation_id' => $delegation->id,
                        'school_id' => $school->id,
                    ], [
                        'meet_sport_id' => $meetSport->id,
                        'status' => 'approved',
                        'reviewed_by' => $reviewer->id,
                        'reviewed_at' => now(),
                        'assigned_by' => $reviewer->id,
                        'assigned_at' => now(),
                        'ended_at' => null,
                        'scope_type' => 'event',
                        'review_notes' => $data['review_notes'] ?? null,
                    ]);

                    $person = $personnelByDelegation[$delegation->id] ??= $this->coachPersonnel(
                        $coachOnboardingRequest,
                        $delegation,
                        $school,
                    );
                    $person->sports()->syncWithoutDetaching([$event->sport_id]);
                    $audit->record('coach.assignment_added', $coachOnboardingRequest, [
                        'coach' => $coachOnboardingRequest->user->name, 'sport' => $meetSport->sport_id,
                        'event_id' => $event->id, 'delegation_id' => $delegation->id,
                    ], $reviewer);
                }

                // Some public registrations select municipality + events
                // before a meet-event assignment row exists. Approval must
                // still establish the coach's roster identity immediately.
                $this->materializeCoachPersonnel($coachOnboardingRequest);
            }

            $audit->record(match ($data['status']) {
                'approved' => 'coach.approved', 'returned' => 'coach.application_returned', default => 'coach.application_rejected',
            }, $coachOnboardingRequest, ['coach' => $coachOnboardingRequest->user->name, 'review_notes' => $data['review_notes'] ?? null], $reviewer);
        });

        return back()->with('success', __('Coach account registration reviewed.'));
    }

    public function accredit(Request $request, CoachOnboardingRequest $coachOnboardingRequest, AuditLogger $audit): RedirectResponse
    {
        $coachOnboardingRequest->loadMissing(['events', 'user']);
        abort_unless($coachOnboardingRequest->status === 'approved', 422, 'Only approved coaches can be accredited.');
        abort_unless($this->canAccreditOnboarding($request->user(), $coachOnboardingRequest), 403);

        if ($coachOnboardingRequest->profile_upload_id === null || $coachOnboardingRequest->certification_upload_id === null) {
            throw ValidationException::withMessages([
                'documents' => __('A coach profile photo and coaching certification are required before accreditation.'),
            ]);
        }

        $personnel = Personnel::query()->where('user_id', $coachOnboardingRequest->user_id)
            ->whereHas('delegation', fn ($query) => $query
                ->where('district_id', $coachOnboardingRequest->district_id)
                ->orWhereHas('school', fn ($school) => $school->where('district_id', $coachOnboardingRequest->district_id)))
            ->with(['delegation.meet', 'sports'])
            ->first();

        // Registrations approved before coach roster synchronization was
        // introduced do not yet have a Personnel row. Materialize it from
        // the approved municipality and event selections on first use.
        if ($personnel === null) {
            $personnel = $this->materializeCoachPersonnel($coachOnboardingRequest);
        }

        abort_if($personnel === null, 422, 'The selected municipality has no active meet delegation for this coach.');
        abort_unless($this->canAccreditCoach($request->user(), $personnel), 403);
        abort_if($personnel->accreditation()->exists(), 422, 'This coach is already accredited.');

        $accreditation = DB::transaction(function () use ($personnel, $request): Accreditation {
            $accreditation = Accreditation::query()->create([
                'delegation_id' => $personnel->delegation_id,
                'personnel_id' => $personnel->id,
                'accredited_at' => now(),
            ]);
            $accreditation->forceFill(['accredited_by' => $request->user()->id])->save();
            $accreditation->assignNumber();

            return $accreditation;
        });

        $audit->record('coach.accreditation_granted', $accreditation, [
            'coach' => $coachOnboardingRequest->user->name,
            'municipality' => $coachOnboardingRequest->district?->name,
            'sports' => $personnel->sports->pluck('name')->all(),
            'number' => $accreditation->number,
        ]);

        return back()->with('success', __('Coach accreditation granted.'));
    }

    public function document(Request $request, CoachOnboardingRequest $coachOnboardingRequest, string $type): HttpResponse
    {
        abort_unless($coachOnboardingRequest->user_id === $request->user()->id
            || $this->canReviewOnboarding($request->user(), $coachOnboardingRequest)
            || $this->canAccreditOnboarding($request->user(), $coachOnboardingRequest), 403);
        abort_unless(in_array($type, ['profile', 'certification'], true), 404);
        $upload = $type === 'profile' ? $coachOnboardingRequest->profile : $coachOnboardingRequest->certification;
        abort_if($upload === null, 404);

        return Storage::disk($upload->disk)->response($upload->path, $upload->original_name);
    }

    public function uploadDocument(
        Request $request,
        CoachOnboardingRequest $coachOnboardingRequest,
        string $type,
        FileUploadService $uploads,
    ): RedirectResponse {
        $user = $request->user();
        $ownsRegistration = $coachOnboardingRequest->user_id === $user->id;
        abort_unless($ownsRegistration
            || $this->canReviewOnboarding($user, $coachOnboardingRequest)
            || $this->canAccreditOnboarding($user, $coachOnboardingRequest), 403);
        abort_unless(in_array($type, ['profile', 'certification'], true), 404);

        $isAccredited = Accreditation::query()
            ->whereHas('personnel', fn ($personnel) => $personnel
                ->where('user_id', $coachOnboardingRequest->user_id))
            ->exists();
        if ($coachOnboardingRequest->status === 'approved' && $isAccredited) {
            throw ValidationException::withMessages([
                'document' => __('Coach attachments can no longer be updated after approval and accreditation.'),
            ]);
        }

        $rules = $type === 'profile'
            ? ['document' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']]
            : ['document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240']];
        $validated = $request->validate($rules);
        $column = $type === 'profile' ? 'profile_upload_id' : 'certification_upload_id';
        $oldUpload = $type === 'profile' ? $coachOnboardingRequest->profile : $coachOnboardingRequest->certification;
        $upload = $uploads->store($validated['document'], $user, 'coach_'.$type);

        $coachOnboardingRequest->forceFill([$column => $upload->id])->save();
        if ($oldUpload !== null) {
            $uploads->delete($oldUpload);
        }

        if ($type === 'profile') {
            Personnel::query()->where('user_id', $coachOnboardingRequest->user_id)
                ->update(['photo_upload_id' => $upload->id]);
        }

        return back()->with('success', __(ucfirst($type).' document uploaded.'));
    }

    public function resetOnboardingPassword(Request $request, CoachOnboardingRequest $coachOnboardingRequest, AuditLogger $audit): RedirectResponse
    {
        /** @var User $reviewer */
        $reviewer = $request->user();
        abort_unless($this->canReviewOnboarding($reviewer, $coachOnboardingRequest), 403);
        $password = config('pmms.accounts.default_reset_password');
        abort_unless(is_string($password) && $password !== '', 503, 'The reset password is not configured.');
        $coachOnboardingRequest->user->forceFill(['password' => Hash::make($password), 'must_change_password' => false, 'password_changed_at' => null])->save();
        $audit->record('user.password_reset', $coachOnboardingRequest->user, ['coach_onboarding_request_id' => $coachOnboardingRequest->id, 'reset_by' => $reviewer->id]);

        return back()->with('success', __('Coach password reset to DdOPaa2026!.'));
    }

    private function canReviewOnboarding(User $user, CoachOnboardingRequest $request): bool
    {
        if ($user->canReviewCoachRegistrations()) {
            return true;
        }

        if ($request->meet_sport_id !== null) {
            return $user->meetSportAssignments()->where('status', 'active')
                ->where('meet_sport_id', $request->meet_sport_id)
                ->whereIn('role', $this->coachReviewerRoles())->exists();
        }

        return $request->events()->whereKey($user->tournamentEventIds())->exists()
            && $user->meetSportAssignments()->where('status', 'active')->whereIn('role', $this->coachReviewerRoles())->exists();
    }

    private function onboardingRegistrations(User $user): array
    {
        if ($this->isCoachApplicant($user)) {
            $query = CoachOnboardingRequest::query()->where('user_id', $user->id);
        } elseif ($user->canReviewCoachRegistrations()) {
            $query = CoachOnboardingRequest::query();
        } else {
            $query = CoachOnboardingRequest::query()
                ->where(function ($scope) use ($user): void {
                    $scope->whereIn('meet_sport_id', $this->visibleMeetSportIds($user))
                        ->orWhere(fn ($legacy) => $legacy->whereNull('meet_sport_id')
                            ->whereHas('events', fn ($events) => $events->whereKey($user->tournamentEventIds())));
                });
        }

        return $query->with(['user:id,name,email,approval_status', 'district:id,name', 'meetSport.meet:id,name', 'meetSport.sport:id,name', 'delegation.school:id,name', 'delegation.district:id,name', 'school:id,name', 'events.sport:id,name', 'profile:id,mime_type', 'certification:id,mime_type'])
            ->latest()->get()->map(function (CoachOnboardingRequest $item) use ($user): array {
                $accreditationNumber = Accreditation::query()
                    ->whereHas('personnel', fn ($personnel) => $personnel->where('user_id', $item->user_id))
                    ->value('number');
                $canManageDocuments = $item->user_id === $user->id
                    || $this->canReviewOnboarding($user, $item)
                    || $this->canAccreditOnboarding($user, $item);

                return [
                    'id' => $item->id,
                    'coach' => $item->user->name,
                    'email' => $item->user->email,
                    'team' => $item->delegation?->registrantName() ?? $item->district?->name,
                    'school' => $item->school?->name,
                    'sport' => $item->meetSport?->sport?->name ?? $item->events->pluck('sport.name')->filter()->unique()->join(', '),
                    'events' => CoachAssignmentRequest::query()->where('user_id', $item->user_id)->where('status', 'approved')->whereNull('ended_at')->with('event:id,name')->get()->pluck('event.name')->filter()->join(', '),
                    'assignment_options' => $item->meetSport === null ? [] : $item->meetSport->meet->events()->where('sport_id', $item->meetSport->sport_id)->orderBy('display_order')->get(['events.id', 'events.name', 'events.gender', 'events.age_division'])->map(fn (Event $event): array => [
                        'id' => $event->id, 'label' => $event->name.' — '.$event->age_division->label().' '.$event->gender->label(),
                    ])->values()->all(),
                    'status' => $item->status,
                    'review_notes' => $item->review_notes,
                    'profile_url' => $item->profile_upload_id ? route('coach.onboarding-documents.show', [$item, 'profile']) : null,
                    'profile_mime_type' => $item->profile?->mime_type,
                    'certification_url' => $item->certification_upload_id ? route('coach.onboarding-documents.show', [$item, 'certification']) : null,
                    'certification_mime_type' => $item->certification?->mime_type,
                    'documents_complete' => $item->profile_upload_id !== null && $item->certification_upload_id !== null,
                    'can_update_attachments' => $canManageDocuments
                        && ! ($item->status === 'approved' && $accreditationNumber !== null),
                    'can_accredit' => $item->status === 'approved'
                        && $item->profile_upload_id !== null
                        && $item->certification_upload_id !== null
                        && $this->canAccreditOnboarding($user, $item),
                    'accreditation_number' => $accreditationNumber,
                ];
            })->all();
    }

    private function coachPersonnel(CoachOnboardingRequest $request, Delegation $delegation, ?School $school): Personnel
    {
        $parts = preg_split('/\s+/', trim($request->user->name)) ?: [];
        $lastName = count($parts) > 1 ? array_pop($parts) : '';

        $person = Personnel::query()->where('delegation_id', $delegation->id)
            ->where('user_id', $request->user_id)->first();

        if ($person === null) {
            $person = new Personnel([
                'delegation_id' => $delegation->id,
                'school_id' => $school?->id,
                'first_name' => implode(' ', $parts) ?: $request->user->name,
                'last_name' => $lastName,
                'role' => PersonnelRole::Coach,
                'email' => $request->user->email,
            ]);
            $person->forceFill([
                'user_id' => $request->user_id,
                'photo_upload_id' => $request->profile_upload_id,
            ])->save();
        }

        return $person;
    }

    private function materializeCoachPersonnel(CoachOnboardingRequest $request): ?Personnel
    {
        $created = null;

        // The approved assignment request is authoritative: it preserves
        // the exact delegation, school, event, and meet selected by the
        // coach. Do not try to infer those choices from municipality alone.
        $assignments = CoachAssignmentRequest::query()
            ->where('user_id', $request->user_id)
            ->orderByRaw("CASE WHEN status = 'approved' THEN 0 ELSE 1 END")
            ->latest('id')
            ->with(['delegation', 'school', 'event'])
            ->get();

        foreach ($assignments as $assignment) {
            if ($assignment->delegation === null || $assignment->school === null || $assignment->event === null) {
                continue;
            }

            $person = $this->coachPersonnel($request, $assignment->delegation, $assignment->school);
            $person->sports()->syncWithoutDetaching([$assignment->event->sport_id]);
            $created ??= $person;
        }

        if ($created !== null) {
            return $created->load(['delegation.meet', 'sports']);
        }

        // Legacy onboarding rows created before exact assignment requests
        // were stored can still be recovered from municipality + event.
        foreach ($request->events as $event) {
            $meetSport = MeetSport::query()
                ->where('sport_id', $event->sport_id)
                ->whereHas('meet.events', fn ($events) => $events->whereKey($event->id))
                ->whereHas('meet.delegations', fn ($delegations) => $delegations
                    ->where('district_id', $request->district_id)
                    ->orWhereHas('school', fn ($school) => $school->where('district_id', $request->district_id)))
                ->latest('id')->first();
            $delegation = $meetSport?->meet->delegations()
                ->where(fn ($query) => $query->where('district_id', $request->district_id)
                    ->orWhereHas('school', fn ($school) => $school->where('district_id', $request->district_id)))
                ->first();
            $school = $delegation?->school ?? $delegation?->district?->schools()->where('active', true)->first();

            if ($meetSport === null || $delegation === null || $school === null) {
                continue;
            }

            $created ??= $this->coachPersonnel($request, $delegation, $school);
            $created->sports()->syncWithoutDetaching([$event->sport_id]);
        }

        if ($created === null) {
            $meet = $event?->meets()->latest('meets.id')->first()
                ?? Meet::query()->where('is_active', true)->latest('id')->first()
                ?? Meet::current();
            $meet->events()->syncWithoutDetaching($request->events->modelKeys());
            foreach ($request->events->pluck('sport_id')->unique() as $sportId) {
                MeetSport::query()->firstOrCreate(
                    ['meet_id' => $meet->id, 'sport_id' => $sportId],
                    ['active' => true],
                );
            }
            $delegation = $meet->delegations()
                ->where(fn ($query) => $query->where('district_id', $request->district_id)
                    ->orWhereHas('school', fn ($school) => $school->where('district_id', $request->district_id)))
                ->first();

            if ($delegation !== null) {
                $school = $delegation->school
                    ?? $delegation->district?->schools()->where('active', true)->first();
                $created = $this->coachPersonnel($request, $delegation, $school);
                $created->sports()->syncWithoutDetaching($request->events->pluck('sport_id')->all());
            }
        }

        return $created?->load(['delegation.meet', 'sports']);
    }

    private function canAccreditOnboarding(User $user, CoachOnboardingRequest $request): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($request->meet_sport_id !== null) {
            return $user->meetSportAssignments()->where('status', 'active')
                ->where('meet_sport_id', $request->meet_sport_id)
                ->whereIn('role', $this->coachAccreditorRoles())->exists();
        }

        return $request->events()->whereKey($user->tournamentEventIds())->exists()
            && $user->meetSportAssignments()->where('status', 'active')->whereIn('role', $this->coachAccreditorRoles())->exists();
    }

    private function canAccreditCoach(User $user, Personnel $personnel): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->meetSportAssignments()->where('status', 'active')
            ->whereIn('role', $this->coachAccreditorRoles())
            ->whereHas('meetSport', fn ($query) => $query
                ->where('meet_id', $personnel->delegation->meet_id)
                ->whereIn('sport_id', $personnel->sports->modelKeys()))->exists();
    }

    private function coachAccreditorRoles(): array
    {
        return [
            MeetSportAssignmentRole::TournamentManager->value,
            MeetSportAssignmentRole::AssistantTournamentManager->value,
            MeetSportAssignmentRole::TournamentSecretary->value,
            MeetSportAssignmentRole::TournamentICT->value,
        ];
    }

    private function coachReviewerRoles(): array
    {
        return [
            MeetSportAssignmentRole::TournamentSecretary->value,
            MeetSportAssignmentRole::TournamentICT->value,
        ];
    }

    private function coachViewerRoles(): array
    {
        return [
            MeetSportAssignmentRole::TournamentManager->value,
            MeetSportAssignmentRole::AssistantTournamentManager->value,
            MeetSportAssignmentRole::TournamentSecretary->value,
            MeetSportAssignmentRole::TournamentICT->value,
            MeetSportAssignmentRole::TechnicalOfficial->value,
        ];
    }

    /** @return Collection<int, int> */
    private function visibleMeetSportIds(User $user): Collection
    {
        return $user->meetSportAssignments()->where('status', 'active')
            ->whereIn('role', $this->coachViewerRoles())
            ->pluck('meet_sport_id');
    }

    /** @return Collection<int, int> */
    private function reviewableMeetSportIds(User $user): Collection
    {
        return $user->meetSportAssignments()->where('status', 'active')
            ->whereIn('role', $this->coachReviewerRoles())
            ->pluck('meet_sport_id');
    }

    /** @return Collection<int, int> */
    private function accreditableMeetSportIds(User $user): Collection
    {
        return $user->meetSportAssignments()->where('status', 'active')
            ->whereIn('role', $this->coachAccreditorRoles())
            ->pluck('meet_sport_id');
    }

    private function isCoachApplicant(User $user): bool
    {
        $onboarding = CoachOnboardingRequest::query()->where('user_id', $user->id)->first();

        return $onboarding === null
            ? $user->role === UserRole::Coach
            : in_array($onboarding->status, ['pending', 'returned', 'rejected'], true);
    }

    private function requestOptions(): array
    {
        return MeetSport::query()->where('active', true)->with([
            'meet:id,name', 'sport:id,name', 'meet.delegations.school:id,name',
            'meet.delegations.district:id,name', 'meet.delegations.district.schools:id,district_id,name',
        ])->get()->flatMap(fn (MeetSport $meetSport) => $meetSport->meet->delegations->flatMap(function (Delegation $delegation) use ($meetSport) {
            $schools = $delegation->school !== null ? collect([$delegation->school]) : $delegation->district->schools;

            return $meetSport->meet->events()->where('sport_id', $meetSport->sport_id)->get()->flatMap(fn (Event $event) => $schools->map(fn (School $school): array => [
                'meet_sport_id' => $meetSport->id, 'event_id' => $event->id,
                'delegation_id' => $delegation->id, 'team' => $delegation->registrantName(),
                'school_id' => $school->id, 'school' => $school->name,
                'label' => "{$meetSport->meet->name} — {$meetSport->sport->name} / {$event->name} — {$delegation->registrantName()} ({$school->name})",
            ]));
        }))->values()->all();
    }
}
