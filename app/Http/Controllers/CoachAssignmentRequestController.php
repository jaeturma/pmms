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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
        abort_unless($canRequest || $reviewableIds->isNotEmpty() || $user->isAdmin(), 403);

        $query = CoachAssignmentRequest::query()->with([
            'user:id,name,email', 'meetSport.meet:id,name', 'meetSport.sport:id,name',
            'event:id,name', 'delegation.school:id,name', 'delegation.district:id,name', 'school:id,name',
        ])->latest();

        if ($canRequest) {
            $query->where('user_id', $user->id);
        } elseif (! $user->isAdmin()) {
            $query->whereIn('meet_sport_id', $reviewableIds);
        }

        return Inertia::render('coach/assignments', [
            'requests' => $query->get()->map(fn (CoachAssignmentRequest $item): array => [
                'id' => $item->id, 'coach' => $item->user->name, 'email' => $item->user->email,
                'meet' => $item->meetSport->meet->name, 'sport' => $item->meetSport->sport->name,
                'event' => $item->event?->name, 'team' => $item->delegation->registrantName(),
                'school' => $item->school->name, 'status' => $item->status, 'review_notes' => $item->review_notes,
            ]),
            'canRequest' => $canRequest,
            'canReview' => $user->isAdmin() || $reviewableIds->isNotEmpty(),
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
        abort_unless($reviewer->isAdmin() || $this->reviewableMeetSportIds($reviewer)->contains($coachAssignmentRequest->meet_sport_id), 403);
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

    /** @return Collection<int, int> */
    private function reviewableMeetSportIds(User $user): Collection
    {
        return $user->meetSportAssignments()->where('status', 'active')->whereIn('role', [
            MeetSportAssignmentRole::TournamentManager->value,
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
