<?php

namespace App\Http\Controllers;

use App\Enums\MeetSportAssignmentRole;
use App\Enums\UserRole;
use App\Models\CoachAssignmentRequest;
use App\Models\CoachOnboardingRequest;
use App\Models\Delegation;
use App\Models\Event;
use App\Models\MeetSport;
use App\Models\School;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CoachAssignmentRequestController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $reviewableIds = $this->reviewableMeetSportIds($user);
        $canRequest = $this->isCoachApplicant($user);
        abort_unless($canRequest || $reviewableIds->isNotEmpty() || $user->canReviewCoachRegistrations(), 403);

        $query = CoachAssignmentRequest::query()->with([
            'user:id,name,email', 'meetSport.meet:id,name', 'meetSport.sport:id,name',
            'event:id,name', 'delegation.school:id,name', 'delegation.district:id,name', 'school:id,name',
        ])->latest();

        if ($canRequest) {
            $query->where('user_id', $user->id);
        } elseif (! $user->canReviewCoachRegistrations()) {
            $query->whereIn('meet_sport_id', $reviewableIds);
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

    public function review(Request $request, CoachAssignmentRequest $coachAssignmentRequest): RedirectResponse
    {
        /** @var User $reviewer */
        $reviewer = $request->user();
        abort_unless($reviewer->canReviewCoachRegistrations() || $this->reviewableMeetSportIds($reviewer)->contains($coachAssignmentRequest->meet_sport_id), 403);
        $data = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected'])], 'review_notes' => ['nullable', 'string', 'max:1000']]);

        DB::transaction(function () use ($coachAssignmentRequest, $reviewer, $data): void {
            $coachAssignmentRequest->forceFill([
                'status' => $data['status'], 'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(), 'review_notes' => $data['review_notes'] ?? null,
            ])->save();
            CoachOnboardingRequest::query()->updateOrCreate(
                ['user_id' => $coachAssignmentRequest->user_id],
                ['status' => $data['status'], 'reviewed_by' => $reviewer->id, 'reviewed_at' => now(), 'review_notes' => $data['review_notes'] ?? null],
            );

            if ($data['status'] === 'approved') {
                $coachAssignmentRequest->user->forceFill(['role' => UserRole::Coach])->save();
            }
        });

        return back()->with('success', __('Coach enrollment reviewed.'));
    }

    public function resetPassword(Request $request, CoachAssignmentRequest $coachAssignmentRequest, AuditLogger $audit): RedirectResponse
    {
        /** @var User $reviewer */
        $reviewer = $request->user();
        abort_unless($reviewer->canReviewCoachRegistrations() || $this->reviewableMeetSportIds($reviewer)->contains($coachAssignmentRequest->meet_sport_id), 403);

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

    public function reviewOnboarding(Request $request, CoachOnboardingRequest $coachOnboardingRequest): RedirectResponse
    {
        /** @var User $reviewer */
        $reviewer = $request->user();
        abort_unless($this->canReviewOnboarding($reviewer, $coachOnboardingRequest), 403);
        $data = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected'])], 'review_notes' => ['nullable', 'string', 'max:1000']]);

        DB::transaction(function () use ($coachOnboardingRequest, $reviewer, $data): void {
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
                $coachOnboardingRequest->loadMissing('events');
                foreach ($coachOnboardingRequest->events as $event) {
                    $meetSport = MeetSport::query()
                        ->where('sport_id', $event->sport_id)
                        ->whereHas('meet.events', fn ($events) => $events->whereKey($event->id))
                        ->whereHas('meet.delegations', fn ($delegations) => $delegations
                            ->where('district_id', $coachOnboardingRequest->district_id)
                            ->orWhereHas('school', fn ($school) => $school->where('district_id', $coachOnboardingRequest->district_id)))
                        ->latest('id')->first();
                    $delegation = $meetSport?->meet->delegations()
                        ->where(fn ($query) => $query->where('district_id', $coachOnboardingRequest->district_id)
                            ->orWhereHas('school', fn ($school) => $school->where('district_id', $coachOnboardingRequest->district_id)))
                        ->first();
                    $school = $delegation?->school ?? $delegation?->district?->schools()->where('active', true)->first();
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
                        'review_notes' => $data['review_notes'] ?? null,
                    ]);
                }
            }
        });

        return back()->with('success', __('Coach account registration reviewed.'));
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

        $sportIds = $request->events()->pluck('sport_id');

        return $user->meetSportAssignments()->where('status', 'active')
            ->whereIn('role', [MeetSportAssignmentRole::TournamentICT->value, MeetSportAssignmentRole::TournamentSecretary->value])
            ->whereHas('meetSport', fn ($query) => $query->whereIn('sport_id', $sportIds))->exists();
    }

    private function onboardingRegistrations(User $user): array
    {
        if ($this->isCoachApplicant($user)) {
            $query = CoachOnboardingRequest::query()->where('user_id', $user->id);
        } elseif ($user->canReviewCoachRegistrations()) {
            $query = CoachOnboardingRequest::query();
        } else {
            $sportIds = $user->meetSportAssignments()->where('status', 'active')
                ->whereIn('role', [MeetSportAssignmentRole::TournamentICT->value, MeetSportAssignmentRole::TournamentSecretary->value])
                ->whereHas('meetSport')->with('meetSport:id,sport_id')->get()->pluck('meetSport.sport_id');
            $query = CoachOnboardingRequest::query()->whereHas('events', fn ($events) => $events->whereIn('sport_id', $sportIds));
        }

        return $query->with(['user:id,name,email,approval_status', 'district:id,name', 'events.sport:id,name'])->latest()->get()->map(fn (CoachOnboardingRequest $item) => [
            'id' => $item->id,
            'coach' => $item->user->name,
            'email' => $item->user->email,
            'team' => $item->district?->name,
            'events' => $item->events->map(fn ($event) => $event->sport->name.' / '.$event->name)->join(', '),
            'status' => $item->status,
            'review_notes' => $item->review_notes,
        ])->all();
    }

    /** @return Collection<int, int> */
    private function reviewableMeetSportIds(User $user): Collection
    {
        return $user->meetSportAssignments()->where('status', 'active')->whereIn('role', [
            MeetSportAssignmentRole::TournamentICT->value,
            MeetSportAssignmentRole::TournamentSecretary->value,
        ])->pluck('meet_sport_id');
    }

    private function isCoachApplicant(User $user): bool
    {
        return $user->role === UserRole::Coach || CoachOnboardingRequest::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'rejected'])
            ->exists();
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
