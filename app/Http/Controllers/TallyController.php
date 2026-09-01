<?php

namespace App\Http\Controllers;

use App\Enums\AgeDivision;
use App\Models\Meet;
use App\Models\Sport;
use App\Services\MedalTallyService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TallyController extends Controller
{
    public function __construct(private readonly MedalTallyService $tally) {}

    /**
     * Medal tally: official district/municipality standings plus a
     * school-level reference table — aggregates of validated results only,
     * readable by every authenticated role.
     */
    public function index(Request $request): Response
    {
        $meetId = Meet::current()->id;
        $sportId = $request->integer('sport_id');
        $ageDivisionRaw = (string) $request->query('age_division', '');
        $ageDivision = AgeDivision::tryFrom($ageDivisionRaw)?->value;

        $standings = $this->tally->standings(
            $meetId,
            $sportId > 0 ? $sportId : null,
            $ageDivision,
        );

        $districts = collect($standings['districts']);

        return Inertia::render('tally/index', [
            'schools' => $standings['schools'],
            'districts' => $standings['districts'],
            'totals' => [
                'gold' => (int) $districts->sum('gold'),
                'silver' => (int) $districts->sum('silver'),
                'bronze' => (int) $districts->sum('bronze'),
                'total' => (int) $districts->sum('total'),
            ],
            'topByPoints' => $districts
                ->sortByDesc('points')
                ->take(5)
                ->values()
                ->all(),
            'bySport' => $this->tally->medalsBySport(
                $meetId,
                $sportId > 0 ? $sportId : null,
                $ageDivision,
            ),
            'recentMedals' => $this->tally->recentMedals(
                $meetId,
                $sportId > 0 ? $sportId : null,
                $ageDivision,
            ),
            'filters' => [
                'sport_id' => $sportId > 0 ? $sportId : null,
                'age_division' => $ageDivision,
            ],
            'sportOptions' => Sport::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Sport $sport): array => ['id' => $sport->id, 'label' => $sport->name]),
            'ageDivisionOptions' => $this->tally->ageDivisionOptions($meetId, $sportId > 0 ? $sportId : null),
            'generatedAt' => now()->toDayDateTimeString(),
        ]);
    }
}
