<?php

namespace App\Http\Controllers;

use App\Enums\EntryStatus;
use App\Enums\MeetStatus;
use App\Enums\Permission;
use App\Enums\ProtestStatus;
use App\Enums\ResultStatus;
use App\Enums\UserRole;
use App\Models\Accreditation;
use App\Models\Announcement;
use App\Models\Athlete;
use App\Models\CoachAssignmentRequest;
use App\Models\Delegation;
use App\Models\EligibilityReview;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventResult;
use App\Models\EventSchedule;
use App\Models\Incident;
use App\Models\Meet;
use App\Models\Personnel;
use App\Models\Protest;
use App\Models\User;
use App\Services\MedalTallyService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the dashboard with its widget data.
     */
    public function index(Request $request, MedalTallyService $tally): Response
    {
        $currentMeet = Meet::current()->loadCount('events');
        /** @var User $user */
        $user = $request->user();

        $athletes = Athlete::query();
        $entries = Entry::query();
        if ($user->role === UserRole::Coach) {
            $delegationIds = $user->approvedCoachDelegationIds();
            $eventIds = $user->approvedCoachEventIds();
            $athletes->whereIn('delegation_id', $delegationIds)
                ->ownedBy($user)
                ->where(fn ($ownedAthletes) => $ownedAthletes
                    ->whereDoesntHave('entries')
                    ->orWhereHas('entries', fn ($ownedEntries) => $ownedEntries->whereIn('event_id', $eventIds)));
            $entries->whereIn('delegation_id', $delegationIds)->whereIn('event_id', $eventIds);
        } elseif (! $user->hasRole(UserRole::Admin, UserRole::Organizer)
            && $user->hasPermission(Permission::AthleteEligibilityReview, $currentMeet)) {
            $athletes->whereHas('delegation', fn ($query) => $query->where('meet_id', $currentMeet->id));
            $entries->whereHas('delegation', fn ($query) => $query->where('meet_id', $currentMeet->id));
        } elseif (! $user->hasRole(UserRole::Admin, UserRole::Organizer)
            && ! $user->canManageProductionAccounts()
            && $user->tournamentEventIds($currentMeet->id)->isNotEmpty()) {
            $eventIds = $user->tournamentEventIds($currentMeet->id);
            $athletes->whereHas('entries', fn ($query) => $query->whereIn('event_id', $eventIds));
            $entries->whereIn('event_id', $eventIds);
        }

        return Inertia::render('dashboard', [
            'currentMeet' => [
                'name' => $currentMeet->name,
                'school_year' => $currentMeet->school_year,
                'status' => $currentMeet->status->value,
                'status_label' => $currentMeet->status->label(),
                'starts_at' => $currentMeet->starts_at->toDateString(),
                'ends_at' => $currentMeet->ends_at->toDateString(),
                'venue' => $currentMeet->venue,
                'events_count' => $currentMeet->events_count,
            ],
            'operations' => $this->operations($request, $tally),
            'coachAccreditation' => $this->coachAccreditation($request, $currentMeet),
            'coachDashboard' => $this->coachDashboard($request, $currentMeet),
            'sportsEventReport' => $this->sportsEventReport($user, $currentMeet),
            'stats' => [
                [
                    'key' => 'athletes',
                    'label' => 'Athletes',
                    'value' => $athletes->count(),
                ],
                [
                    'key' => 'entries',
                    'label' => 'Entries',
                    'value' => $entries->count(),
                ],
            ],
            'announcements' => Announcement::query()
                ->published()
                ->with('meet:id,name')
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->limit(3)
                ->get()
                ->map(fn (Announcement $announcement): array => [
                    'id' => $announcement->id,
                    'title' => $announcement->title,
                    'body' => $announcement->body,
                    'meet' => $announcement->meet?->name,
                    'published_at' => $announcement->published_at?->format('M j, Y g:i A'),
                ])
                ->values(),
        ]);
    }

    /** @return array{sports_count: int, events_count: int, rows: array<int, array<string, mixed>>} */
    private function sportsEventReport(User $user, Meet $meet): array
    {
        $eventQuery = Event::query()
            ->whereHas('meets', fn ($meets) => $meets->whereKey($meet->id))
            ->with('sport:id,name')
            ->orderBy('sport_id')->orderBy('display_order')->orderBy('name');

        $delegationIds = null;
        if ($user->role === UserRole::Coach) {
            $eventQuery->whereKey($user->approvedCoachEventIds());
            $delegationIds = $user->approvedCoachDelegationIds();
        } elseif ($user->role === UserRole::DelegationOfficer) {
            $delegationIds = Delegation::query()->where('meet_id', $meet->id)
                ->whereHas('officers', fn ($officers) => $officers->whereKey($user->id))
                ->pluck('id');
        } elseif (! $user->hasRole(UserRole::Admin, UserRole::Organizer)) {
            // Tournament managers, assistants, secretaries, ICT, and
            // technical officials see only events granted by active scopes.
            $eventQuery->whereKey($user->tournamentEventIds($meet->id));
        }

        $events = $eventQuery->get();
        $eventIds = $events->modelKeys();
        $athleteCounts = Entry::query()->whereIn('event_id', $eventIds)
            ->when($delegationIds !== null, fn ($entries) => $entries->whereIn('delegation_id', $delegationIds))
            ->selectRaw('event_id, COUNT(DISTINCT athlete_id) as aggregate')
            ->groupBy('event_id')->pluck('aggregate', 'event_id');
        $coachCounts = CoachAssignmentRequest::query()->whereIn('event_id', $eventIds)
            ->where('status', 'approved')->whereNull('ended_at')
            ->when($delegationIds !== null, fn ($coaches) => $coaches->whereIn('delegation_id', $delegationIds))
            ->selectRaw('event_id, COUNT(DISTINCT user_id) as aggregate')
            ->groupBy('event_id')->pluck('aggregate', 'event_id');

        return [
            'sports_count' => $events->pluck('sport_id')->unique()->count(),
            'events_count' => $events->count(),
            'rows' => $events->map(fn (Event $event): array => [
                'id' => $event->id, 'sport' => $event->sport->name, 'event' => $event->name,
                'division' => $event->age_division->label(), 'gender' => $event->gender->label(),
                'type' => $event->is_team_event ? __('Team') : __('Individual'),
                'athletes_count' => (int) ($athleteCounts[$event->id] ?? 0),
                'coaches_count' => (int) ($coachCounts[$event->id] ?? 0),
            ])->values()->all(),
        ];
    }

    /**
     * Show a coach their accreditation outcome directly on the dashboard.
     * The assignment request remains the useful fallback until approval has
     * produced and linked the coach's personnel roster row.
     *
     * @return array<string, mixed>|null
     */
    private function coachAccreditation(Request $request, Meet $meet): ?array
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->role !== UserRole::Coach) {
            return null;
        }

        $personnel = Personnel::query()
            ->where('user_id', $user->id)
            ->whereHas('delegation', fn ($query) => $query->where('meet_id', $meet->id))
            ->with(['accreditation', 'delegation.school:id,name', 'delegation.district:id,name', 'school:id,name', 'sports:id,name'])
            ->latest('id')
            ->first();

        $requestRecord = CoachAssignmentRequest::query()
            ->where('user_id', $user->id)
            ->whereHas('meetSport', fn ($query) => $query->where('meet_id', $meet->id))
            ->with(['event:id,name', 'meetSport.sport:id,name', 'delegation.school:id,name', 'delegation.district:id,name', 'school:id,name'])
            ->latest('id')
            ->first();

        $accreditation = $personnel?->accreditation;
        $requestStatus = $requestRecord?->status;
        $status = $accreditation !== null
            ? 'Accredited'
            : match ($requestStatus) {
                'approved' => 'Pending accreditation',
                'rejected' => 'Enrollment rejected',
                'pending' => 'Enrollment under review',
                default => 'Not yet accredited',
            };

        return [
            'status' => $status,
            'accredited' => $accreditation !== null,
            'number' => $accreditation?->number,
            'accredited_on' => $accreditation?->accredited_at?->format('M j, Y'),
            'team' => $personnel?->delegation->registrantName() ?? $requestRecord?->delegation->registrantName(),
            'school' => $personnel?->school?->name ?? $requestRecord?->school?->name,
            'sport' => $personnel?->sports->pluck('name')->join(', ') ?: $requestRecord?->meetSport?->sport?->name,
            'event' => $requestRecord?->event?->name,
            'review_notes' => $requestRecord?->review_notes,
        ];
    }

    /** @return array<string, mixed>|null */
    private function coachDashboard(Request $request, Meet $meet): ?array
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->role !== UserRole::Coach) {
            return null;
        }

        $eventIds = $user->approvedCoachEventIds();
        $delegationIds = $user->approvedCoachDelegationIds();

        return [
            'eligibility_reviews' => EligibilityReview::query()
                ->where('meet_id', $meet->id)
                ->whereHas('athlete', fn ($athlete) => $athlete
                    ->whereIn('delegation_id', $delegationIds)
                    ->ownedBy($user))
                ->with(['athlete:id,school_id,first_name,last_name', 'athlete.school:id,name'])
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(fn (EligibilityReview $review): array => [
                    'id' => $review->id,
                    'athlete' => $review->athlete->fullName(),
                    'school' => $review->athlete->school->name,
                    'status' => $review->status->value,
                    'status_label' => $review->status->label(),
                ])
                ->values(),
            'submitted_entries' => Entry::query()
                ->whereIn('event_id', $eventIds)
                ->whereIn('delegation_id', $delegationIds)
                ->where('status', EntryStatus::Submitted->value)
                ->whereHas('delegation', fn ($delegation) => $delegation->where('meet_id', $meet->id))
                ->with([
                    'athlete:id,first_name,last_name',
                    'event:id,sport_id,name',
                    'event.sport:id,name',
                    'delegation.school:id,name',
                    'delegation.district:id,name',
                ])
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(fn (Entry $entry): array => [
                    'id' => $entry->id,
                    'athlete' => $entry->athlete->fullName(),
                    'event' => "{$entry->event->sport->name} — {$entry->event->name}",
                    'delegation' => $entry->delegation->registrantName(),
                    'own_team' => $delegationIds->contains($entry->delegation_id),
                ])
                ->values(),
        ];
    }

    /**
     * Meet-day operations widgets for the active meet (read-side only).
     * Managers get the operational queues, officers their own delegation's
     * protests, viewers the schedule and tally summaries.
     *
     * @return array<string, mixed>|null
     */
    private function operations(Request $request, MedalTallyService $tally): ?array
    {
        $meet = Meet::current();

        if ($meet->status !== MeetStatus::Active) {
            return null;
        }

        /** @var User $user */
        $user = $request->user();

        $canManage = Gate::allows('manage-meet-data');
        $scopedEventIds = $user->tournamentEventIds($meet->id);
        $isTournamentScoped = ! $user->hasRole(UserRole::Admin, UserRole::Organizer)
            && $scopedEventIds->isNotEmpty();

        return [
            'meet' => ['id' => $meet->id, 'name' => $meet->name],
            'todaySlots' => EventSchedule::query()
                ->where('meet_id', $meet->id)
                ->when($isTournamentScoped, fn ($query) => $query->whereIn('event_id', $scopedEventIds))
                ->whereDate('scheduled_date', today())
                ->with(['venue:id,name', 'event.sport:id,name'])
                ->orderBy('starts_at')
                ->get()
                ->map(fn (EventSchedule $slot): array => [
                    'id' => $slot->id,
                    'starts_at' => substr($slot->starts_at, 0, 5),
                    'ends_at' => substr($slot->ends_at, 0, 5),
                    'event' => sprintf(
                        '%s — %s (%s, %s)',
                        $slot->event->sport->name,
                        $slot->event->name,
                        $slot->event->gender->label(),
                        $slot->event->age_division->label(),
                    ),
                    'venue' => $slot->venue->name,
                ])
                ->values()
                ->all(),
            'tallyTop' => $isTournamentScoped ? [] : array_slice($tally->standings($meet->id)['districts'], 0, 5),
            'eventsOverview' => $this->eventsOverview($meet, $isTournamentScoped ? $scopedEventIds : null),
            'queues' => $canManage ? [
                'pending_results' => EventResult::query()
                    ->where('meet_id', $meet->id)
                    ->where('status', ResultStatus::Encoded->value)
                    ->whereNull('tm_confirmed_at')
                    ->count(),
                'open_protests' => Protest::query()
                    ->whereIn('status', [ProtestStatus::Filed->value, ProtestStatus::UnderReview->value])
                    ->whereHas('delegation', fn ($delegation) => $delegation->where('meet_id', $meet->id))
                    ->count(),
                'open_incidents' => Incident::query()
                    ->where('meet_id', $meet->id)
                    ->where('status', 'open')
                    ->count(),
                'accredited' => Accreditation::query()
                    ->whereHas('delegation', fn ($delegation) => $delegation->where('meet_id', $meet->id))
                    ->count(),
                'accreditable' => Athlete::query()
                    ->whereHas('delegation', fn ($delegation) => $delegation->where('meet_id', $meet->id))
                    ->count()
                    + Personnel::query()
                        ->whereHas('delegation', fn ($delegation) => $delegation->where('meet_id', $meet->id))
                        ->count(),
            ] : null,
            'myProtests' => $user->role === UserRole::DelegationOfficer
                ? Protest::query()
                    ->whereHas('delegation', fn ($delegation) => $delegation
                        ->where('meet_id', $meet->id)
                        ->whereHas('officers', fn ($officers) => $officers->whereKey($user->getKey())))
                    ->latest('id')
                    ->limit(5)
                    ->get()
                    ->map(fn (Protest $protest): array => [
                        'id' => $protest->id,
                        'grounds' => $protest->grounds,
                        'status_label' => $protest->status->label(),
                    ])
                    ->values()
                    ->all()
                : null,
        ];
    }

    /**
     * A 3-way event-status breakdown for the "events overview" dashboard
     * widget (WP-08-04) — deliberately 3 categories, not the 4
     * ("Cancelled" included) the visual reference shows: this catalog has
     * no cancelled/void concept for an `Event` or `EventSchedule` at all,
     * so a "Cancelled" count would have to be invented rather than
     * computed. Completed = has at least one validated result; Ongoing =
     * has a today's `EventSchedule` slot whose time window contains right
     * now (and isn't already counted as completed); Upcoming = everything
     * else attached to the meet. A deliberate approximation, not a
     * tracked event-lifecycle status — documented here so nobody mistakes
     * it for one later.
     *
     * @return array{completed: int, ongoing: int, upcoming: int, total: int}
     */
    private function eventsOverview(Meet $meet, ?Collection $eventIds = null): array
    {
        $total = $meet->events()->when($eventIds !== null, fn ($query) => $query->whereKey($eventIds))->count();

        $completedEventIds = EventResult::query()
            ->where('meet_id', $meet->id)
            ->when($eventIds !== null, fn ($query) => $query->whereIn('event_id', $eventIds))
            ->where('status', ResultStatus::Validated->value)
            ->pluck('event_id')
            ->unique();

        $ongoing = EventSchedule::query()
            ->where('meet_id', $meet->id)
            ->when($eventIds !== null, fn ($query) => $query->whereIn('event_id', $eventIds))
            ->whereDate('scheduled_date', today())
            ->where('starts_at', '<=', now()->format('H:i:s'))
            ->where('ends_at', '>=', now()->format('H:i:s'))
            ->whereNotIn('event_id', $completedEventIds)
            ->distinct('event_id')
            ->count('event_id');

        $completed = $completedEventIds->count();
        $upcoming = max(0, $total - $completed - $ongoing);

        return [
            'completed' => $completed,
            'ongoing' => $ongoing,
            'upcoming' => $upcoming,
            'total' => $total,
        ];
    }
}
