<?php

namespace App\Http\Controllers;

use App\Http\Requests\IntegrityAthleteRequest;
use App\Models\Athlete;
use App\Models\AuditLog;
use App\Models\Meet;
use App\Models\School;
use App\Models\SportRosterMember;
use App\Services\DataIntegrityAccess;
use App\Services\DataIntegrityService;
use App\Services\DataLinkRepairService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DataIntegrityController extends Controller
{
    public function index(Request $request, DataIntegrityAccess $access, DataIntegrityService $service)
    {
        $meetId = Meet::current()->id;
        abort_unless($access->allows($request->user(), $meetId), 403);
        $sports = $access->sportIds($request->user(), $meetId);
        $issues = $service->scan($meetId)->filter(fn ($issue) => $sports === null || in_array($issue['sport_id'], $sports, true));
        $type = $request->string('type')->toString();
        $filtered = $issues->when($type !== '', fn ($rows) => $rows->where('type', $type))->values();
        $page = max(1, $request->integer('page', 1));

        return Inertia::render('administration/data-integrity/index', [
            'issues' => $filtered->forPage($page, 50)->values(),
            'counts' => $issues->countBy('type'), 'total' => $filtered->count(), 'page' => $page,
            'filters' => ['type' => $type], 'meet' => Meet::current()->name,
        ]);
    }

    private function authorizeRoster(Request $request, SportRosterMember $roster): void
    {
        abort_unless($roster->meetSport !== null && app(DataIntegrityAccess::class)
            ->allows($request->user(), $roster->meetSport->meet_id, $roster->meetSport->sport_id), 403);
    }

    public function show(Request $request, SportRosterMember $roster)
    {
        $this->authorizeRoster($request, $roster);

        return Inertia::render('administration/data-integrity/roster', [
            'roster' => [
                'id' => $roster->id, 'athlete_id' => $roster->athlete_id,
                'delegation' => $roster->delegation?->registrantName() ?? 'Not linked',
                'sport' => $roster->meetSport?->sport?->name ?? 'Not linked',
                'level' => $roster->level->value, 'gender' => $roster->gender->value,
                'available' => $roster->athlete !== null,
            ],
            'schools' => School::where('active', true)->orderBy('name')->get(['id', 'name']),
            'history' => AuditLog::where('auditable_type', $roster->getMorphClass())->where('auditable_id', $roster->id)
                ->where('action', 'data_integrity.roster_linked')->with('user')->latest()->limit(25)->get()
                ->map(fn ($log) => ['id' => $log->id, 'who' => $log->user?->name ?? 'Missing account', 'at' => $log->created_at->toIso8601String(), 'context' => $log->context]),
        ]);
    }

    public function candidates(Request $request, SportRosterMember $roster)
    {
        $this->authorizeRoster($request, $roster);
        $data = $request->validate(['search' => ['required', 'string', 'min:2', 'max:100'], 'school_id' => ['nullable', 'integer'], 'sport_only' => ['sometimes', 'boolean']]);
        // Delegation is fixed by the broken record; candidates are never selected automatically.
        $query = Athlete::where('delegation_id', $roster->delegation_id)->with('school')
            ->where(fn ($names) => $names->where('first_name', 'like', '%'.$data['search'].'%')
                ->orWhere('last_name', 'like', '%'.$data['search'].'%')->orWhere('lrn', $data['search']))
            ->when($request->filled('school_id'), fn ($q) => $q->where('school_id', $request->integer('school_id')))
            ->when($request->boolean('sport_only'), fn ($q) => $q->whereHas('sportRosterMemberships', fn ($members) => $members->where('meet_sport_id', $roster->meet_sport_id)));
        $request->session()->put('integrity_search.'.$roster->id, now()->timestamp);

        return response()->json(['athletes' => $query->orderBy('last_name')->limit(50)->get()->map(fn ($athlete) => [
            'id' => $athlete->id, 'name' => $athlete->fullName(), 'school' => $athlete->school?->name ?? 'Not linked',
            'level' => $athlete->ageDivision()->value, 'sex' => $athlete->sex->value,
        ])]);
    }

    public function link(Request $request, SportRosterMember $roster, DataLinkRepairService $repair)
    {
        $this->authorizeRoster($request, $roster);
        $data = $request->validate(['athlete_id' => ['required', 'integer'], 'expected_athlete_id' => ['required', 'integer'], 'reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $repair->link($request->user(), $roster, $data['athlete_id'], $data['expected_athlete_id'], $data['reason']);

        return to_route('data-integrity.index')->with('success', 'Athlete link repaired and audit history recorded.');
    }

    public function create(IntegrityAthleteRequest $request, SportRosterMember $roster, DataLinkRepairService $repair)
    {
        abort_unless($request->session()->get('integrity_search.'.$roster->id, 0) >= now()->subMinutes(30)->timestamp, 422, 'Search existing athletes before creating a new identity.');
        $repair->createAndLink($request->user(), $roster, $request->validated());
        $request->session()->forget('integrity_search.'.$roster->id);

        return to_route('data-integrity.index')->with('success', 'Athlete created, roster linked, and both changes audited.');
    }
}
