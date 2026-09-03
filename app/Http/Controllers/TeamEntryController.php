<?php

namespace App\Http\Controllers;

use App\Enums\AgeDivision;
use App\Enums\EligibilityStatus;
use App\Enums\MeetSportAssignmentRole;
use App\Enums\EntryStatus;
use App\Enums\MedicalClearanceStatus;
use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\Entry;
use App\Models\Event;
use App\Models\MeetSport;
use App\Models\SportRosterMember;
use App\Models\TeamEntry;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\CompetitionAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TeamEntryController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'event_id' => ['required', 'integer', Rule::exists('events', 'id')],
            'athlete_ids' => ['required', 'array', 'min:1'],
            'athlete_ids.*' => ['required', 'integer', 'distinct', Rule::exists('athletes', 'id')],
        ]);
        $event = Event::query()->with(['sportCategory', 'meets'])->findOrFail($validated['event_id']);
        $athletesById = Athlete::query()->with(['delegation.meet', 'eligibilityReview', 'medicalClearance'])
            ->whereKey($validated['athlete_ids'])->get()->keyBy('id');
        $athletes = collect($validated['athlete_ids'])->map(fn (int $id) => $athletesById->get($id))->filter()->values();
        $this->assertRosterValid($request->user(), $event, $athletes, count($validated['athlete_ids']), false);
        $delegation = $athletes->first()->delegation;
        Gate::authorize('create', [Entry::class, $delegation, $event]);

        $team = DB::transaction(function () use ($event, $athletes, $delegation): TeamEntry {
            $team = TeamEntry::query()->firstOrCreate([
                'delegation_id' => $delegation->id,
                'event_id' => $event->id,
            ], ['status' => EntryStatus::Submitted]);
            if ($team->isRosterLocked()) {
                throw ValidationException::withMessages(['athlete_ids' => __('This team roster is locked.')]);
            }
            $members = [];
            foreach ($athletes as $index => $athlete) {
                $entry = Entry::query()->firstOrCreate(
                    ['athlete_id' => $athlete->id, 'event_id' => $event->id],
                    ['delegation_id' => $delegation->id],
                );
                $members[] = ['athlete_id' => $athlete->id, 'entry_id' => $entry->id, 'member_order' => $index + 1];
            }
            $team->members()->delete();
            $team->members()->createMany($members);

            return $team->refresh();
        });
        $this->audit->record('team_entry.submitted', $team, ['event' => $event->name, 'members' => $athletes->pluck('id')->all()]);

        return back()->with('success', __('Team entry saved.'));
    }

    public function confirm(Request $request, TeamEntry $teamEntry): RedirectResponse
    {
        $teamEntry->load(['delegation', 'event.sportCategory', 'event.meets', 'members.athlete.delegation.meet', 'members.athlete.eligibilityReview', 'members.athlete.medicalClearance']);
        Gate::authorize('create', [Entry::class, $teamEntry->delegation, $teamEntry->event]);
        $athletes = $teamEntry->members->pluck('athlete');
        $this->assertRosterValid($request->user(), $teamEntry->event, $athletes, $athletes->count(), true);
        DB::transaction(function () use ($teamEntry): void {
            $teamEntry->members()->with('entry')->get()->each(
                fn ($member) => $member->entry->forceFill(['status' => EntryStatus::Confirmed])->save(),
            );
            $teamEntry->forceFill(['status' => EntryStatus::Confirmed])->save();
        });
        $this->audit->record('team_entry.confirmed', $teamEntry, ['members' => $teamEntry->members->pluck('athlete_id')->all()]);

        return back()->with('success', __('Team entry finalized and roster locked.'));
    }

    /** @param Collection<int, Athlete> $athletes */
    private function assertRosterValid(User $user, Event $event, Collection $athletes, int $requestedCount, bool $finalizing): void
    {
        if (! $event->is_team_event) {
            throw ValidationException::withMessages(['event_id' => __('Select a team, pair, doubles, or relay event.')]);
        }
        if ($athletes->count() !== $requestedCount || $athletes->isEmpty()) {
            throw ValidationException::withMessages(['athlete_ids' => __('Every selected athlete must exist.')]);
        }
        if ($athletes->pluck('delegation_id')->unique()->count() !== 1) {
            throw ValidationException::withMessages(['athlete_ids' => __('All team members must belong to the same delegation.')]);
        }
        $delegation = $athletes->first()->delegation;
        $isAssignedIct = app(CompetitionAccessService::class)->hasAssignmentRole(
            $user,
            [MeetSportAssignmentRole::TournamentICT->value],
            $delegation->meet_id,
        ) && app(CompetitionAccessService::class)->canAccessEvent($user, $event, $delegation->meet_id);
        $meetSportId = MeetSport::query()->where('meet_id', $delegation->meet_id)
            ->where('sport_id', $event->sport_id)->value('id');
        $rosterCount = $meetSportId === null ? 0 : SportRosterMember::query()
            ->where('meet_sport_id', $meetSportId)
            ->where('delegation_id', $delegation->id)
            ->whereIn('athlete_id', $athletes->pluck('id'))->count();
        if ($rosterCount !== $athletes->count()) {
            throw ValidationException::withMessages(['athlete_ids' => __('Every team member must belong to the applicable Sport roster.')]);
        }
        if ($event->event_type === 'RELAY') {
            $required = $event->relay_legs ?? 4;
            if ($athletes->count() !== $required) {
                throw ValidationException::withMessages(['athlete_ids' => __('This relay requires exactly :count swimmers.', ['count' => $required])]);
            }
            $matchingRosterCount = SportRosterMember::query()->where('meet_sport_id', $meetSportId)
                ->where('delegation_id', $delegation->id)
                ->whereIn('level', $event->age_division === AgeDivision::ElementaryAndSecondary
                    ? [AgeDivision::Elementary->value, AgeDivision::Secondary->value]
                    : [$event->age_division->value])
                ->where('gender', $event->gender->value)->whereIn('athlete_id', $athletes->pluck('id'))->count();
            if ($matchingRosterCount !== $required) {
                throw ValidationException::withMessages(['athlete_ids' => __('Every relay swimmer must belong to the matching delegation Swimming roster.')]);
            }
        }
        if ($user->role === UserRole::Coach && $athletes->contains(
            fn (Athlete $athlete): bool => $athlete->accreditation()->doesntExist(),
        )) {
            throw ValidationException::withMessages(['athlete_ids' => __('A coach may select only accredited athletes for an entry.')]);
        }
        foreach ($athletes as $athlete) {
            if (! $isAssignedIct && $athlete->eligibilityReview?->status !== EligibilityStatus::Approved) {
                throw ValidationException::withMessages(['athlete_ids' => __('Every team member must be DSAC approved.')]);
            }
            if (! $event->meets->contains('id', $athlete->delegation->meet_id)) {
                throw ValidationException::withMessages(['event_id' => __('The team event is not part of the athletes’ meet.')]);
            }
            if (! $event->gender->accepts($athlete->sex) || ! $event->age_division->accepts($athlete->ageDivision())) {
                throw ValidationException::withMessages(['athlete_ids' => __('Every member must match the Event requirements.')]);
            }
            if ($athlete->delegation->meet->medical_clearance_required && $athlete->medicalClearance?->status !== MedicalClearanceStatus::Cleared) {
                throw ValidationException::withMessages(['athlete_ids' => __('Every team member must be medically cleared.')]);
            }
        }
        $minimum = $event->sportCategory?->min_players ?? $event->team_size;
        $maximum = $event->sportCategory?->max_players ?? $event->team_size;
        if ($maximum !== null && $athletes->count() > $maximum) {
            throw ValidationException::withMessages(['athlete_ids' => __('This team allows at most :count members.', ['count' => $maximum])]);
        }
        if ($finalizing && $minimum !== null && $athletes->count() < $minimum) {
            throw ValidationException::withMessages(['athlete_ids' => __('This team requires at least :count members.', ['count' => $minimum])]);
        }
    }
}
