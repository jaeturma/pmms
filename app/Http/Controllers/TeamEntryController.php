<?php

namespace App\Http\Controllers;

use App\Enums\EligibilityStatus;
use App\Enums\EntryStatus;
use App\Enums\MedicalClearanceStatus;
use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\Entry;
use App\Models\Event;
use App\Models\TeamEntry;
use App\Models\User;
use App\Services\AuditLogger;
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
        $athletes = Athlete::query()->with(['delegation.meet', 'eligibilityReview', 'medicalClearance'])
            ->whereKey($validated['athlete_ids'])->get();
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
            $keep = [];
            foreach ($athletes as $athlete) {
                $entry = Entry::query()->firstOrCreate(
                    ['athlete_id' => $athlete->id, 'event_id' => $event->id],
                    ['delegation_id' => $delegation->id],
                );
                $keep[] = $entry->id;
                $team->members()->updateOrCreate(['athlete_id' => $athlete->id], ['entry_id' => $entry->id]);
            }
            $team->members()->whereNotIn('entry_id', $keep)->delete();

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
        if ($user->role === UserRole::Coach && $athletes->contains(fn (Athlete $athlete): bool => ! $athlete->isOwnedBy($user))) {
            throw ValidationException::withMessages(['athlete_ids' => __('A coach may add only athletes registered under their account.')]);
        }
        foreach ($athletes as $athlete) {
            if ($athlete->eligibilityReview?->status !== EligibilityStatus::Approved) {
                throw ValidationException::withMessages(['athlete_ids' => __('Every team member must be DSAC approved.')]);
            }
            if (! $event->meets->contains('id', $athlete->delegation->meet_id)) {
                throw ValidationException::withMessages(['event_id' => __('The team event is not part of the athletes’ meet.')]);
            }
            if (! $event->gender->accepts($athlete->sex) || $event->age_division !== $athlete->ageDivision()) {
                throw ValidationException::withMessages(['athlete_ids' => __('Every member must match the event category.')]);
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
