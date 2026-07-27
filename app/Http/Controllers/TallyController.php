<?php

namespace App\Http\Controllers;

use App\Enums\AgeDivision;
use App\Models\Meet;
use App\Models\Sport;
use App\Services\MedalTallyService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        $meetId = $request->integer('meet_id');
        $sportId = $request->integer('sport_id');
        $ageDivisionRaw = (string) $request->query('age_division', '');
        $ageDivision = AgeDivision::tryFrom($ageDivisionRaw)?->value;

        $standings = $this->tally->standings(
            $meetId > 0 ? $meetId : null,
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
                $meetId > 0 ? $meetId : null,
                $sportId > 0 ? $sportId : null,
                $ageDivision,
            ),
            'recentMedals' => $this->tally->recentMedals(
                $meetId > 0 ? $meetId : null,
                $sportId > 0 ? $sportId : null,
                $ageDivision,
            ),
            'filters' => [
                'meet_id' => $meetId > 0 ? $meetId : null,
                'sport_id' => $sportId > 0 ? $sportId : null,
                'age_division' => $ageDivision,
            ],
            'meetOptions' => Meet::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Meet $meet): array => ['id' => $meet->id, 'label' => $meet->name]),
            'sportOptions' => Sport::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Sport $sport): array => ['id' => $sport->id, 'label' => $sport->name]),
            'ageDivisionOptions' => Collection::make(AgeDivision::cases())
                ->map(fn (AgeDivision $division): array => ['id' => $division->value, 'label' => $division->label()])
                ->all(),
            'generatedAt' => now()->toDayDateTimeString(),
        ]);
    }
}
