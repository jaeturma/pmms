<?php

namespace App\Http\Controllers;

use App\Enums\EligibilityStatus;
use App\Enums\MedicalClearanceStatus;
use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\Entry;
use App\Models\Event;
use App\Models\MeetSport;
use App\Models\SportRosterLimit;
use App\Models\SportRosterMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SwimmingRosterController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Entry::class);
        /** @var User $user */
        $user = $request->user();
        $meetSports = MeetSport::query()->with(['meet', 'sport'])
            ->whereHas('sport', fn ($sports) => $sports->where('code', 'SWIMMING'))
            ->when($user->role === UserRole::Coach, fn ($query) => $query->whereIn('meet_id', Delegation::query()->whereIn('id', $user->approvedCoachDelegationIds())->select('meet_id')))
            ->when(! $user->isAdmin() && $user->role !== UserRole::Coach && $user->role !== UserRole::DelegationOfficer, function ($query) use ($user): void {
                $assigned = $user->meetSportAssignments()->where('status', 'active')->pluck('meet_sport_id');
                $query->whereIn('id', $assigned);
            })->get();
        $delegations = Delegation::query()->where('status', 'approved')->whereIn('meet_id', $meetSports->pluck('meet_id'))
            ->when($user->role === UserRole::Coach, fn ($query) => $query->whereIn('id', $user->approvedCoachDelegationIds()))
            ->when($user->role === UserRole::DelegationOfficer, fn ($query) => $query->whereHas('officers', fn ($officers) => $officers->whereKey($user->id)))
            ->with(['school', 'district'])->get();

        $rosters = SportRosterLimit::query()->whereIn('meet_sport_id', $meetSports->pluck('id'))->get()
            ->flatMap(fn (SportRosterLimit $limit) => $delegations->where('meet_id', $meetSports->firstWhere('id', $limit->meet_sport_id)?->meet_id)
                ->map(function (Delegation $delegation) use ($limit, $meetSports, $user): array {
                    $meetSport = $meetSports->firstWhere('id', $limit->meet_sport_id);
                    $event = Event::query()->where('sport_id', $meetSport->sport_id)->where('age_division', $limit->level->value)
                        ->where('gender', $limit->gender->value)
                        ->when($user->role === UserRole::Coach, fn ($events) => $events->whereIn('id', $user->approvedCoachEventIdsForDelegation($delegation)))
                        ->first();
                    $members = SportRosterMember::query()->where('meet_sport_id', $limit->meet_sport_id)
                        ->where('delegation_id', $delegation->id)->where('level', $limit->level->value)->where('gender', $limit->gender->value)
                        ->with(['athlete.delegation.meet', 'athlete.eligibilityReview', 'athlete.medicalClearance', 'athlete.entries.event'])->get();
                    $candidates = Athlete::query()->where('delegation_id', $delegation->id)
                        ->where('sex', $limit->gender->value === 'boys' ? 'male' : 'female')
                        ->whereNotIn('id', $members->pluck('athlete_id'))
                        ->when($user->role === UserRole::Coach, fn ($query) => $query->ownedBy($user))
                        ->orderBy('last_name')->get()->filter(fn (Athlete $athlete) => $athlete->ageDivision() === $limit->level);

                    return [
                        'meet_sport_id' => $limit->meet_sport_id, 'delegation_id' => $delegation->id,
                        'meet' => $meetSport->meet->name, 'delegation' => $delegation->registrantName(),
                        'level' => $limit->level->label(), 'gender' => $limit->gender->label(),
                        'maximum' => $limit->max_athletes, 'can_manage' => $event !== null && $user->can('create', [Entry::class, $delegation, $event]),
                        'members' => $members->map(fn (SportRosterMember $member): array => [
                            'id' => $member->id, 'name' => $member->athlete->fullName(),
                            'eligible' => $member->athlete->eligibilityReview?->status === EligibilityStatus::Approved,
                            'medically_cleared' => ! $member->athlete->delegation->meet->medical_clearance_required || $member->athlete->medicalClearance?->status === MedicalClearanceStatus::Cleared,
                            'entry_count' => $member->athlete->entries->filter(fn (Entry $entry) => $entry->event->sport_id === $meetSport->sport_id)->count(),
                        ])->values(),
                        'candidates' => $candidates->map(fn (Athlete $athlete): array => ['id' => $athlete->id, 'name' => $athlete->fullName()])->values(),
                    ];
                }))->values();

        return Inertia::render('entries/swimming-rosters', ['rosters' => $rosters]);
    }
}
