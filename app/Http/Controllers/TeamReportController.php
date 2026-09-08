<?php

namespace App\Http\Controllers;

use App\Models\Delegation;
use App\Models\Meet;
use App\Services\TeamReport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamReportController extends Controller
{
    public function index(Request $request, TeamReport $report): Response
    {
        abort_unless($request->user()->isAdmin(), 403);
        $validated = $request->validate([
            'meet_id' => ['nullable', 'integer', 'exists:meets,id'],
            'delegation_id' => ['nullable', 'integer', 'min:1'],
        ]);
        $meet = isset($validated['meet_id']) ? Meet::findOrFail($validated['meet_id']) : Meet::query()->orderBy('id')->firstOrFail();
        $teams = Delegation::query()->where('meet_id', $meet->id)->with(['school', 'district'])->orderBy('id')->get()
            ->map(fn ($team) => ['id' => $team->id, 'name' => $team->registrantName() ?: 'Delegation #'.$team->id])
            ->sortBy('name')->values();
        $teamId = isset($validated['delegation_id']) ? (int) $validated['delegation_id'] : null;
        $team = $teamId !== null ? $teams->firstWhere('id', $teamId) : null;
        abort_if($teamId !== null && $team === null, 404);

        return Inertia::render('reports/team-report', [
            'meet' => ['id' => $meet->id, 'name' => $meet->name, 'school_year' => $meet->school_year,
                'venue' => $meet->venue, 'starts_at' => $meet->starts_at?->format('M j, Y'), 'ends_at' => $meet->ends_at?->format('M j, Y')],
            'meetOptions' => Meet::query()->orderByDesc('id')->get(['id', 'name']),
            'teamOptions' => $teams, 'team' => $team,
            ...($teamId !== null ? $report->build($meet, $teamId) : ['sports' => [], 'summary' => ['gold' => 0, 'silver' => 0, 'bronze' => 0, 'total' => 0]]),
            'generatedAt' => now()->toDayDateTimeString(),
        ]);
    }
}
