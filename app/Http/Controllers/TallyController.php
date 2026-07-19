<?php

namespace App\Http\Controllers;

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
     * Medal tally per school and district — aggregates of validated
     * results only, readable by every authenticated role.
     */
    public function index(Request $request): Response
    {
        $meetId = $request->integer('meet_id');
        $sportId = $request->integer('sport_id');

        $standings = $this->tally->standings(
            $meetId > 0 ? $meetId : null,
            $sportId > 0 ? $sportId : null,
        );

        return Inertia::render('tally/index', [
            'schools' => $standings['schools'],
            'districts' => $standings['districts'],
            'filters' => [
                'meet_id' => $meetId > 0 ? $meetId : null,
                'sport_id' => $sportId > 0 ? $sportId : null,
            ],
            'meetOptions' => Meet::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Meet $meet): array => ['id' => $meet->id, 'label' => $meet->name]),
            'sportOptions' => Sport::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Sport $sport): array => ['id' => $sport->id, 'label' => $sport->name]),
            'generatedAt' => now()->toDayDateTimeString(),
        ]);
    }
}
