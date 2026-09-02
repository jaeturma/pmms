<?php

namespace App\Http\Controllers;

use App\Enums\AgeDivision;
use App\Enums\GenderCategory;
use App\Enums\Permission;
use App\Models\DemoScenario;
use App\Models\Meet;
use App\Models\Sport;
use App\Models\Venue;
use App\Services\DemoScenarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DemoDataController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeManage($request);
        $scenarios = DemoScenario::query()->with(['meet:id,name', 'sport:id,name', 'creator:id,name'])
            ->withCount(['events', 'schedules', 'matches', 'results'])->latest()->get();

        return Inertia::render('system/demo-data', [
            'scenarios' => $scenarios->map(fn (DemoScenario $scenario) => [
                'id' => $scenario->id, 'name' => $scenario->name, 'template' => $scenario->template,
                'meet' => $scenario->meet->name, 'sport' => $scenario->sport->name,
                'created_by' => $scenario->creator?->name, 'created_at' => $scenario->created_at?->format('M j, Y g:i A'),
                'events' => $scenario->events_count, 'schedules' => $scenario->schedules_count,
                'matches' => $scenario->matches_count, 'results' => $scenario->results_count,
            ]),
            'meets' => Meet::query()->orderByDesc('starts_at')->get(['id', 'name']),
            'sports' => Sport::query()->where('active', true)->orderBy('name')->get(['id', 'name']),
            'venues' => Venue::query()->where('active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, DemoScenarioService $service): RedirectResponse
    {
        $this->authorizeManage($request);
        $data = $request->validate([
            'request_token' => ['required', 'uuid'], 'meet_id' => ['required', 'integer', 'exists:meets,id'],
            'sport_id' => ['required', 'integer', 'exists:sports,id'], 'venue_id' => ['required', 'integer', 'exists:venues,id'],
            'name' => ['required', 'string', 'max:160'], 'event_name' => ['required', 'string', 'max:130'],
            'template' => ['required', Rule::in(['head_to_head', 'performance'])],
            'gender' => ['required', Rule::enum(GenderCategory::class)], 'age_division' => ['required', Rule::enum(AgeDivision::class)],
            'scheduled_date' => ['required', 'date'], 'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'side_a_label' => ['required', 'string', 'max:80'], 'side_b_label' => ['required', 'string', 'max:80'],
        ]);
        $scenario = $service->generate($data, $request->user());

        return to_route('demo-data.show', $scenario)->with('success', __('Demo scenario generated.'));
    }

    public function show(Request $request, DemoScenario $demoScenario): Response
    {
        $this->authorizeManage($request);
        $demoScenario->load(['meet:id,name', 'sport:id,name', 'events', 'schedules.venue:id,name', 'matches.scoringSessions', 'results']);

        return Inertia::render('system/demo-preview', [
            'scenario' => [
                'id' => $demoScenario->id, 'name' => $demoScenario->name, 'meet' => $demoScenario->meet->name,
                'sport' => $demoScenario->sport->name,
                'events' => $demoScenario->events->map(fn ($event) => ['id' => $event->id, 'name' => $event->name]),
                'schedules' => $demoScenario->schedules->map(fn ($slot) => ['id' => $slot->id, 'date' => $slot->scheduled_date->format('M j, Y'), 'time' => $slot->starts_at, 'venue' => $slot->venue->name]),
                'matches' => $demoScenario->matches->map(fn ($match) => ['id' => $match->id, 'label' => $match->round_label, 'status' => $match->status->value, 'scoreboard_url' => route('scoring.show', $match)]),
                'results' => $demoScenario->results->map(fn ($result) => ['id' => $result->id, 'status' => $result->status->value]),
            ],
        ]);
    }

    public function destroy(Request $request, DemoScenario $demoScenario, DemoScenarioService $service): RedirectResponse
    {
        $this->authorizeManage($request);
        $request->validate(['confirmation' => ['required', Rule::in(['DELETE DEMO DATA'])]]);
        $service->remove($demoScenario, $request->user());

        return to_route('demo-data.index')->with('success', __('Demo scenario removed.'));
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless($request->user()->hasPermission(Permission::DemoManage), 403);
    }
}
