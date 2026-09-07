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
     * Medal tally: official district/municipality standings plus a
     * school-level reference table — aggregates of validated results only,
     * readable by every authenticated role.
     *
     * Uses the exact same `MedalTallyService::categoryBreakdown()` the
     * public `/tally` board consumes, so the internal page, the dashboard
     * widget and the reports can never show a different medal count from
     * the public tally. `category` is the same Overall / Elementary /
     * Secondary / Paragames selector (default Overall = Elementary +
     * Secondary, no Paragames, no Kickboxing).
     */
    public function index(Request $request): Response
    {
        $meetId = Meet::current()->id;
        $sportId = $request->integer('sport_id') > 0 ? $request->integer('sport_id') : null;
        $category = $this->resolveCategory($request->query('category'));

        return Inertia::render('tally/index', [
            ...$this->tally->categoryBreakdown($meetId, $category, $sportId),
            'filters' => [
                'sport_id' => $sportId,
                'category' => $category,
            ],
            'sportOptions' => Sport::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Sport $sport): array => ['id' => $sport->id, 'label' => $sport->name]),
            'categoryOptions' => MedalTallyService::categoryOptions(),
            'generatedAt' => now()->toDayDateTimeString(),
        ]);
    }

    private function resolveCategory(mixed $value): string
    {
        return in_array($value, MedalTallyService::CATEGORIES, true) ? $value : 'overall';
    }
}
