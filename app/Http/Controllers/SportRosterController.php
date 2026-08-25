<?php

namespace App\Http\Controllers;

use App\Models\Athlete;
use App\Models\Entry;
use App\Models\Event;
use App\Models\MeetSport;
use App\Models\SportRosterMember;
use App\Services\AuditLogger;
use App\Services\SportRosterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SportRosterController extends Controller
{
    public function __construct(private readonly SportRosterService $rosters, private readonly AuditLogger $audit) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'meet_sport_id' => ['required', 'integer', Rule::exists('meet_sports', 'id')],
            'athlete_id' => ['required', 'integer', Rule::exists('athletes', 'id')],
        ]);
        $meetSport = MeetSport::query()->with('sport')->findOrFail($validated['meet_sport_id']);
        $athlete = Athlete::query()->with('delegation.meet')->findOrFail($validated['athlete_id']);
        $scopeEvent = Event::query()->where('sport_id', $meetSport->sport_id)
            ->where('age_division', $athlete->ageDivision()->value)
            ->where('gender', $athlete->sex->value === 'male' ? 'boys' : 'girls')
            ->when($request->user()?->role === \App\Enums\UserRole::Coach, fn ($events) => $events->whereIn('id', $request->user()->approvedCoachEventIdsForDelegation($athlete->delegation)))
            ->firstOrFail();
        Gate::authorize('create', [Entry::class, $athlete->delegation, $scopeEvent]);
        $member = $this->rosters->add($meetSport, $athlete);
        $this->audit->record('sport_roster.member_added', $member, ['sport' => $meetSport->sport->name, 'athlete' => $athlete->fullName()]);

        return back()->with('success', __('Swimmer added to roster.'));
    }

    public function destroy(Request $request, SportRosterMember $sportRosterMember): RedirectResponse
    {
        $sportRosterMember->load(['athlete.delegation', 'meetSport']);
        $scopeEvent = Event::query()->where('sport_id', $sportRosterMember->meetSport->sport_id)
            ->where('age_division', $sportRosterMember->level->value)
            ->where('gender', $sportRosterMember->gender->value)
            ->when($request->user()?->role === \App\Enums\UserRole::Coach, fn ($events) => $events->whereIn('id', $request->user()->approvedCoachEventIdsForDelegation($sportRosterMember->athlete->delegation)))
            ->firstOrFail();
        Gate::authorize('create', [Entry::class, $sportRosterMember->athlete->delegation, $scopeEvent]);
        if ($sportRosterMember->athlete->entries()->whereHas('event', fn ($events) => $events->where('sport_id', $sportRosterMember->meetSport->sport_id))->where('status', 'confirmed')->exists()) {
            return back()->withErrors(['roster' => __('A swimmer with confirmed competition entries cannot be removed.')]);
        }
        $this->audit->record('sport_roster.member_removed', $sportRosterMember, ['athlete' => $sportRosterMember->athlete->fullName()]);
        $sportRosterMember->delete();

        return back()->with('success', __('Swimmer removed from roster.'));
    }
}
