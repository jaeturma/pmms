<?php

namespace App\Services;

use App\Enums\EligibilityStatus;
use App\Enums\EntryStatus;
use App\Enums\MedicalClearanceStatus;
use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\Permission;
use App\Enums\ResultStatus;
use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\CoachAssignmentRequest;
use App\Models\CoachOnboardingRequest;
use App\Models\Delegation;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\EventResult;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\Personnel;
use App\Models\SportRosterMember;
use App\Models\User;

class MeetReadinessService
{
    /** Major-domain weights. Non-applicable domains are removed before normalization. */
    private const WEIGHTS = ['structure' => 10, 'venues' => 15, 'personnel' => 15, 'entries' => 15, 'coaches' => 10, 'eligibility' => 15, 'medical' => 10, 'schedules' => 5, 'medals' => 5];

    public function scopeFor(User $user, Meet $meet): ?array
    {
        if ($user->canViewManagementReports()) {
            return ['label' => 'Meet-wide', 'event_ids' => null, 'delegation_ids' => null];
        }
        if ($user->role === UserRole::Coach) {
            return ['label' => 'My approved coach assignments', 'event_ids' => $user->approvedCoachEventIds()->all(), 'delegation_ids' => $user->approvedCoachDelegationIds()->all()];
        }
        if ($user->hasPermission(Permission::AthleteEligibilityReview, $meet)) {
            return ['label' => 'DSAC meet scope', 'event_ids' => null, 'delegation_ids' => null];
        }
        $eventIds = $user->tournamentEventIds($meet->id);
        if ($eventIds->isNotEmpty()) {
            return [
                'label' => 'Assigned tournament functions',
                'event_ids' => $eventIds->all(),
                'delegation_ids' => null,
                'ict_scope' => $user->meetSportAssignments()
                    ->where('status', MeetSportAssignmentStatus::Active)
                    ->where('role', MeetSportAssignmentRole::TournamentICT->value)
                    ->whereHas('meetSport', fn ($sports) => $sports->where('meet_id', $meet->id))
                    ->exists(),
            ];
        }

        return null;
    }

    public function calculate(Meet $meet, array $filters = []): array
    {
        $scopedEventIds = array_key_exists('event_ids', $filters) ? $filters['event_ids'] : null;
        $scopedDelegationIds = array_key_exists('delegation_ids', $filters) ? $filters['delegation_ids'] : null;
        $events = Event::query()->real()->where('active', true)->whereHas('meets', fn ($q) => $q->whereKey($meet->id))
            ->when($scopedEventIds !== null, fn ($q) => $q->whereIn('events.id', $scopedEventIds))
            ->with(['sport:id,name', 'sportCategory:id,display_name', 'venueAssignments.venue:id,name', 'medalConfig'])->get();
        $eventIds = $events->modelKeys();
        $meetSports = MeetSport::query()->where('meet_id', $meet->id)->where('active', true)->whereIn('sport_id', $events->pluck('sport_id'))->with('sport:id,name')->get();
        $delegations = Delegation::query()->where('meet_id', $meet->id)->when($scopedDelegationIds !== null, fn ($q) => $q->whereIn('id', $scopedDelegationIds))->with(['school:id,name', 'district:id,name'])->get(['id', 'school_id', 'district_id']);
        $entries = Entry::query()->whereIn('event_id', $eventIds)->whereIn('delegation_id', $delegations->modelKeys())
            ->where('status', '!=', EntryStatus::Withdrawn->value)
            ->with(['athlete:id', 'athlete.eligibilityReview:id,athlete_id,status', 'athlete.medicalClearance:id,athlete_id,status', 'athlete.accreditation:id,athlete_id'])
            ->get(['id', 'event_id', 'delegation_id', 'athlete_id']);
        $schedules = EventSchedule::query()->real()->where('meet_id', $meet->id)->whereIn('event_id', $eventIds)->get(['id', 'event_id']);
        $results = EventResult::query()->real()->where('meet_id', $meet->id)->whereIn('event_id', $eventIds)
            ->where('result_scope', 'event')->latest('id')->get(['id', 'event_id', 'status'])->unique('event_id')->keyBy('event_id');
        $assignments = MeetSportAssignment::query()->whereIn('meet_sport_id', $meetSports->modelKeys())
            ->where('status', MeetSportAssignmentStatus::Active->value)->get(['id', 'meet_sport_id', 'role']);
        $coachAssignments = CoachAssignmentRequest::query()->whereIn('delegation_id', $delegations->modelKeys())
            ->whereHas('user')
            ->where('status', 'approved')->whereNull('ended_at')
            ->where(fn ($query) => $query->whereIn('event_id', $eventIds)
                ->orWhere(fn ($scope) => $scope->whereNull('event_id')->whereIn('meet_sport_id', $meetSports->modelKeys())))
            ->get(['id', 'delegation_id', 'event_id', 'meet_sport_id', 'sport_category_id']);
        $rosterMembers = SportRosterMember::query()->whereIn('meet_sport_id', $meetSports->modelKeys())
            ->whereIn('delegation_id', $delegations->modelKeys())
            ->with(['athlete:id', 'athlete.eligibilityReview:id,athlete_id,status', 'athlete.medicalClearance:id,athlete_id,status', 'athlete.accreditation:id,athlete_id'])
            ->get(['id', 'meet_sport_id', 'delegation_id', 'athlete_id', 'gender', 'level']);
        $coachRequests = CoachOnboardingRequest::query()->where(function ($q) use ($meetSports, $eventIds): void {
            $q->whereIn('meet_sport_id', $meetSports->modelKeys())->orWhereIn('event_id', $eventIds)->orWhereHas('events', fn ($events) => $events->whereIn('events.id', $eventIds));
        })->whereHas('user')->get(['id', 'user_id']);
        $accreditedCoachUserIds = Personnel::query()->whereIn('user_id', $coachRequests->pluck('user_id'))->whereHas('accreditation')->pluck('user_id')->filter()->unique();

        // Index the production-sized collections once. Repeated Collection::where()
        // calls inside the event loop made readiness O(events x rows), which was
        // expensive enough to time out the authenticated dashboard.
        $entriesByEvent = $entries->groupBy('event_id');
        $schedulesByEvent = $schedules->keyBy('event_id');
        $assignmentsByMeetSport = $assignments->groupBy('meet_sport_id');
        $rosterByMeetSport = $rosterMembers->groupBy('meet_sport_id');
        $rosterByDelegation = $rosterMembers->groupBy('delegation_id');
        $meetSportsBySport = $meetSports->keyBy('sport_id');
        $directCoachAssignmentsByEvent = $coachAssignments->whereNotNull('event_id')->groupBy('event_id');
        $sportCoachAssignmentsByMeetSport = $coachAssignments->whereNull('event_id')->groupBy('meet_sport_id');

        $eventRows = $events->map(function (Event $event) use ($entriesByEvent, $schedulesByEvent, $results, $assignmentsByMeetSport, $rosterByMeetSport, $meetSportsBySport, $directCoachAssignmentsByEvent, $sportCoachAssignmentsByMeetSport, $delegations, $meet): array {
            $eventEntries = $entriesByEvent->get($event->id, collect());
            $athletes = $eventEntries->pluck('athlete')->filter()->unique('id');
            $meetSportId = $meetSportsBySport->get($event->sport_id)?->id;
            $eventRoster = $rosterByMeetSport->get($meetSportId, collect())
                ->filter(fn (SportRosterMember $member): bool => $event->age_division->accepts($member->level)
                    && ($event->gender->value === 'mixed' || $member->gender === $event->gender));
            $rosterAthletes = $eventRoster->pluck('athlete')->filter()->unique('id');
            $roles = $assignmentsByMeetSport->get($meetSportId, collect())->pluck('role')->map(fn ($role) => $role->value)->unique();
            $requiredRoles = [MeetSportAssignmentRole::TournamentManager->value, MeetSportAssignmentRole::TournamentSecretary->value, MeetSportAssignmentRole::TournamentICT->value];
            $missingRoles = collect($requiredRoles)->diff($roles)->values();
            $venueReady = $event->venueAssignments->isNotEmpty();
            $entryReady = $eventEntries->isNotEmpty();
            $scheduleReady = $schedulesByEvent->has($event->id);
            $eligible = $athletes->filter(fn (Athlete $a) => $a->eligibilityReview?->status === EligibilityStatus::Approved)->count();
            $medical = $athletes->filter(fn (Athlete $a) => ! $meet->medical_clearance_required || $a->medicalClearance?->status === MedicalClearanceStatus::Cleared)->count();
            $accredited = $athletes->filter(fn (Athlete $a) => $a->accreditation !== null)->count();
            $medalReady = ! $event->is_medal_event || ($event->medalConfig !== null && $event->medalConfig->isComplete());
            $entryDelegations = $eventEntries->pluck('delegation_id')->unique();
            $eventCoachAssignments = $directCoachAssignmentsByEvent->get($event->id, collect())
                ->concat($sportCoachAssignmentsByMeetSport->get($meetSportId, collect())->filter(
                    fn (CoachAssignmentRequest $assignment): bool => $assignment->sport_category_id === null
                        || $assignment->sport_category_id === $event->sport_category_id,
                ));
            $coachDelegations = $eventCoachAssignments->pluck('delegation_id')->unique();
            $missingCoachDelegations = $entryDelegations->diff($coachDelegations);
            $critical = collect();
            $attention = collect();
            if (! $venueReady) {
                $critical->push('No venue assigned');
            }
            if (! $entryReady) {
                $critical->push('No submitted entry');
            }
            if ($eventCoachAssignments->isEmpty()) {
                $critical->push('No approved Coach assigned to this Event');
            }
            if ($missingRoles->isNotEmpty()) {
                $critical->push('Missing required tournament personnel: '.$missingRoles->map(fn ($r) => str($r)->replace('_', ' ')->title())->join(', '));
            }
            if (! $scheduleReady) {
                $critical->push('No schedule');
            }
            if (! $medalReady) {
                $critical->push('Medal configuration incomplete');
            }
            if ($athletes->isNotEmpty() && $eligible === 0) {
                $critical->push('No eligible athlete among submitted entries');
            } elseif ($eligible < $athletes->count()) {
                $attention->push(($athletes->count() - $eligible).' athlete(s) pending/not eligible');
            }
            if ($meet->medical_clearance_required && $medical < $athletes->count()) {
                $attention->push(($athletes->count() - $medical).' athlete(s) pending/not medically cleared');
            }
            if ($accredited < $athletes->count()) {
                $attention->push(($athletes->count() - $accredited).' athlete(s) not accredited');
            }
            if ($missingCoachDelegations->isNotEmpty()) {
                $attention->push($missingCoachDelegations->count().' participating delegation(s) without an approved coach assignment');
            }
            $resultStatus = $results->get($event->id)?->status;
            if ($resultStatus === null) {
                $attention->push('Result not yet encoded');
            } elseif ($resultStatus === ResultStatus::Submitted) {
                $attention->push('Submitted result awaiting review');
            } elseif ($resultStatus === ResultStatus::Returned) {
                $attention->push('Returned result awaiting correction');
            } elseif ($resultStatus === ResultStatus::Validated) {
                $attention->push('Validated result ready for officialization');
            }
            $status = $critical->isNotEmpty() ? 'not_ready' : ($attention->isNotEmpty() ? 'needs_attention' : 'ready');

            return ['id' => $event->id, 'sport_id' => $event->sport_id, 'sport' => $event->sport->name, 'event' => $event->name,
                'event_classification' => trim(($event->age_division->label().' '.$event->gender->label())), 'venue' => $venueReady, 'entries' => $eventEntries->count(),
                'delegations_with_entry' => $entryDelegations->count(), 'delegations_total' => $delegations->count(), 'missing_delegations' => $delegations->whereNotIn('id', $entryDelegations)->map(fn ($d) => $d->registrantName())->values()->all(),
                'coaches' => $eventCoachAssignments->count(), 'registered_athletes' => $rosterAthletes->count(),
                'eligible_roster_athletes' => $rosterAthletes->filter(fn (Athlete $a) => $a->eligibilityReview?->status === EligibilityStatus::Approved)->count(),
                'pending_eligibility' => $rosterAthletes->filter(fn (Athlete $a) => $a->eligibilityReview?->status !== EligibilityStatus::Approved)->count(),
                'athletes_with_entries' => $athletes->count(), 'athletes' => $athletes->count(), 'eligible' => $eligible,
                'medical_cleared' => $medical, 'accredited' => $accredited, 'schedule' => $scheduleReady, 'medal' => $medalReady,
                'personnel_count' => $roles->count(), 'personnel_ready' => $missingRoles->isEmpty(), 'technical_officials' => $roles->filter(fn ($r) => $r === MeetSportAssignmentRole::TechnicalOfficial->value)->count(),
                'status' => $status, 'reasons' => $critical->merge($attention)->values()->all()];
        });

        $eventRowsBySport = $eventRows->groupBy('sport_id');
        $sportRows = $meetSports->map(function (MeetSport $meetSport) use ($eventRowsBySport, $rosterByMeetSport): array {
            $rows = $eventRowsBySport->get($meetSport->sport_id, collect());
            $status = $rows->contains('status', 'not_ready') ? 'not_ready' : ($rows->contains('status', 'needs_attention') ? 'needs_attention' : 'ready');

            return ['id' => $meetSport->sport_id, 'sport' => $meetSport->sport->name, 'events' => $rows->count(),
                'ready_events' => $rows->where('status', 'ready')->count(), 'attention_events' => $rows->where('status', 'needs_attention')->count(),
                'not_ready_events' => $rows->where('status', 'not_ready')->count(), 'venues' => $rows->where('venue', true)->count(),
                'entries' => $rows->where('entries', '>', 0)->count(), 'coaches' => $rows->sum('coaches'),
                'athletes' => $rosterByMeetSport->get($meetSport->id, collect())->pluck('athlete_id')->unique()->count(),
                'schedules' => $rows->where('schedule', true)->count(), 'status' => $status, 'issues' => $rows->sum(fn ($r) => count($r['reasons']))];
        });

        $athletes = $entries->pluck('athlete')->filter()->unique('id');
        $rosterAthletes = $rosterMembers->pluck('athlete')->filter()->unique('id');
        $domains = collect([
            'structure' => $this->domain('Competition structure', $events->count(), $events->filter(fn ($e) => $e->sport_id && $e->gender && $e->age_division)->count()),
            'venues' => $this->domain('Venues', $events->count(), $eventRows->where('venue', true)->count()),
            'personnel' => $this->domain('Tournament personnel', $events->count(), $eventRows->where('personnel_ready', true)->count()),
            'entries' => $this->domain('Entries', $events->count(), $eventRows->where('entries', '>', 0)->count()),
            'coaches' => $this->domain('Coaches', $coachRequests->count(), $accreditedCoachUserIds->count()),
            'eligibility' => $this->domain('Athlete eligibility', $rosterAthletes->count(), $rosterAthletes->filter(fn ($a) => $a->eligibilityReview?->status === EligibilityStatus::Approved)->count()),
            'medical' => $meet->medical_clearance_required ? $this->domain('Medical clearance', $athletes->count(), $athletes->filter(fn ($a) => $a->medicalClearance?->status === MedicalClearanceStatus::Cleared)->count()) : null,
            'schedules' => $this->domain('Schedules', $events->count(), $eventRows->where('schedule', true)->count()),
            'medals' => $this->domain('Medal configuration', $events->where('is_medal_event', true)->count(), $eventRows->filter(fn ($r) => $r['medal'] && $events->firstWhere('id', $r['id'])?->is_medal_event)->count()),
        ])->filter();
        $weightTotal = $domains->filter(fn ($d) => $d['applicable'])->keys()->sum(fn ($key) => self::WEIGHTS[$key]);
        $overall = $weightTotal === 0 ? 0 : (int) round($domains->filter(fn ($d) => $d['applicable'])->sum(fn ($d, $key) => $d['score'] * self::WEIGHTS[$key]) / $weightTotal);
        $ictScope = (bool) ($filters['ict_scope'] ?? false);
        $issues = $eventRows->flatMap(fn ($row) => collect($row['reasons'])->map(function ($reason) use ($row, $ictScope, $meet): array {
            [$domain, $responsible, $action, $target, $ictCanAct] = match (true) {
                $reason === 'No schedule' => ['Schedule', 'Tournament ICT', 'Manage Schedule', '/schedule?event_id='.$row['id'], true],
                $reason === 'No submitted entry' => ['Entries', 'Tournament ICT / Secretariat', 'Review Entries', '/entries?event_id='.$row['id'], true],
                str_starts_with($reason, 'No approved Coach'), str_contains($reason, 'without an approved coach') => ['Coach coverage', 'Tournament ICT', 'Manage Coach Assignment', '/coach/assignment-requests?sport_id='.$row['sport_id'], true],
                $reason === 'No venue assigned' => ['Venue', 'Meet Management', 'Manage Venue', '/events?event_id='.$row['id'], false],
                str_contains($reason, 'medically cleared') => ['Medical', 'Medical Team', 'View Athletes', '/medical?event_id='.$row['id'], false],
                str_contains($reason, 'eligible') => ['Eligibility', 'DSAC', 'View Concern', '/eligibility?event_id='.$row['id'], false],
                str_contains($reason, 'tournament personnel') => ['Personnel', 'Meet Management', 'Manage Personnel', '/management', false],
                str_contains($reason, 'result') || str_contains($reason, 'Result') => ['Results', 'Tournament ICT / Secretariat', 'Manage Results', '/results?event_id='.$row['id'], true],
                default => ['Configuration', 'Meet Management', 'View Event', '/events?event_id='.$row['id'], false],
            };

            return [
                'severity' => $row['status'] === 'not_ready' ? 'critical' : 'attention',
                'domain' => $domain,
                'sport_id' => $row['sport_id'],
                'sport' => $row['sport'],
                'event' => $row['event'],
                'message' => $reason,
                'event_id' => $row['id'],
                'responsible' => $responsible,
                'can_act' => $ictScope && $ictCanAct,
                'action' => $action,
                'target' => $target,
                'last_updated' => $meet->updated_at?->toDayDateTimeString(),
            ];
        }))->values();

        $filteredEvents = $eventRows->when($filters['sport_id'] ?? null, fn ($rows, $id) => $rows->where('sport_id', (int) $id))
            ->when($filters['event_id'] ?? null, fn ($rows, $id) => $rows->where('id', (int) $id))
            ->when($filters['status'] ?? null, fn ($rows, $status) => $rows->where('status', $status))
            ->when($filters['search'] ?? null, fn ($rows, $search) => $rows->filter(fn ($row) => str($row['sport'].' '.$row['event'].' '.$row['event_classification'])->contains((string) $search, true)))->values();
        $filteredIssues = $issues->when($filters['sport_id'] ?? null, fn ($rows, $id) => $rows->where('sport_id', (int) $id))
            ->when($filters['event_id'] ?? null, fn ($rows, $id) => $rows->where('event_id', (int) $id))
            ->when($filters['issue_type'] ?? null, fn ($rows, $type) => $rows->where('severity', $type))
            ->when($filters['search'] ?? null, fn ($rows, $search) => $rows->filter(fn ($row) => str($row['sport'].' '.$row['event'].' '.$row['message'])->contains((string) $search, true)))->values();
        $teamAthletes = $delegations->map(function (Delegation $delegation) use ($rosterByDelegation): array {
            return [
                'id' => $delegation->id,
                'team' => $delegation->registrantName(),
                'athletes' => $rosterByDelegation->get($delegation->id, collect())->pluck('athlete_id')->unique()->count(),
            ];
        })->filter(fn (array $row): bool => $row['athletes'] > 0)->sortByDesc('athletes')->values();

        return ['meet' => ['id' => $meet->id, 'name' => $meet->name, 'starts_at' => $meet->starts_at?->toDateString(), 'ends_at' => $meet->ends_at?->toDateString(), 'days_until_start' => now()->startOfDay()->diffInDays($meet->starts_at, false)],
            'calculated_at' => now()->toDayDateTimeString(), 'overall' => $overall, 'overall_status' => $sportRows->contains('status', 'not_ready') ? 'not_ready' : ($issues->isNotEmpty() ? 'needs_attention' : 'ready'),
            'domains' => $domains->values(), 'sports' => $sportRows->values(), 'teams' => $teamAthletes,
            'events' => $this->paginate($filteredEvents, max(1, (int) ($filters['events_page'] ?? 1))),
            'issues' => $this->paginate($filteredIssues, max(1, (int) ($filters['issues_page'] ?? 1))),
            'summary' => ['sports_total' => $sportRows->count(), 'sports_ready' => $sportRows->where('status', 'ready')->count(), 'events_total' => $events->count(), 'events_ready' => $eventRows->where('status', 'ready')->count(),
                'venues_assigned' => $eventRows->where('venue', true)->count(), 'events_with_entries' => $eventRows->where('entries', '>', 0)->count(), 'coaches_registered' => $coachRequests->count(), 'coaches_accredited' => $accreditedCoachUserIds->count(),
                'athletes_total' => $rosterAthletes->count(), 'athletes_eligible' => $rosterAthletes->filter(fn ($a) => $a->eligibilityReview?->status === EligibilityStatus::Approved)->count(),
                'athletes_with_entries' => $athletes->count(), 'athletes_pending_eligibility' => $rosterAthletes->filter(fn ($a) => $a->eligibilityReview?->status !== EligibilityStatus::Approved)->count(),
                'medical_cleared' => $athletes->filter(fn ($a) => $a->medicalClearance?->status === MedicalClearanceStatus::Cleared)->count(), 'schedules_ready' => $eventRows->where('schedule', true)->count(), 'open_issues' => $issues->count()],
            'scope_label' => $filters['scope_label'] ?? 'Meet-wide', 'options' => ['sports' => $meetSports->map(fn ($s) => ['id' => $s->sport_id, 'name' => $s->sport->name])->values(), 'events' => $events->map(fn ($e) => ['id' => $e->id, 'name' => $e->sport->name.' — '.$e->name])->values()], 'filters' => collect($filters)->except(['event_ids', 'delegation_ids', 'scope_label', 'ict_scope'])->all()];
    }

    private function domain(string $label, int $total, int $ready): array
    {
        return ['label' => $label, 'total' => $total, 'ready' => $ready, 'applicable' => $total > 0, 'score' => $total > 0 ? round($ready / $total * 100, 1) : 100];
    }

    private function paginate($rows, int $page, int $perPage = 10): array
    {
        $total = $rows->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);

        return ['data' => $rows->forPage($page, $perPage)->values(), 'current_page' => $page, 'last_page' => $lastPage, 'per_page' => $perPage, 'total' => $total];
    }
}
