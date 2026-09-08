<?php

namespace App\Http\Controllers;

use App\Models\Meet;
use App\Services\DavraaReportAccess;
use App\Services\SportsMedalAwardsReport;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SportsMedalAwardsReportController extends Controller
{
    public function index(Request $request, DavraaReportAccess $access, SportsMedalAwardsReport $report)
    {
        $validated = $request->validate(['meet_id' => ['nullable', 'integer', 'exists:meets,id'], 'sport_id' => ['nullable', 'integer', 'min:1']]);
        $meet = isset($validated['meet_id']) ? Meet::findOrFail($validated['meet_id']) : Meet::current();
        $user = $request->user();
        abort_unless($access->canManage($user, $meet->id), 403);
        $sports = $access->sportOptions($user, $meet->id);
        $sportId = isset($validated['sport_id']) ? (int) $validated['sport_id'] : null;
        if ($sportId === null && ! $user->isAdmin()) {
            abort_if($request->has('sport_id'), 403);
            $sportId = $sports->first()?->id;
            abort_if($sportId === null, 403);
        }
        abort_if($sportId !== null && ! $sports->contains('id', $sportId), 403);

        return Inertia::render('reports/sports-medal-awards', [
            'meet' => ['id' => $meet->id, 'name' => $meet->name, 'school_year' => $meet->school_year,
                'venue' => $meet->venue, 'starts_at' => $meet->starts_at?->format('M j, Y'), 'ends_at' => $meet->ends_at?->format('M j, Y')],
            'meetOptions' => Meet::query()->orderByDesc('id')->get(['id', 'name'])
                ->filter(fn ($option) => $access->canManage($user, $option->id))->values(),
            'sportOptions' => $sports, 'sportId' => $sportId, 'canSelectAll' => $user->isAdmin(),
            'sportLabel' => $sportId === null ? 'ALL SPORTS' : $sports->firstWhere('id', $sportId)?->name,
            'sports' => $report->build($meet, $sportId), 'generatedAt' => now()->toDayDateTimeString(),
        ]);
    }
}
