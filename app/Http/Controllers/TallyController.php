<?php

namespace App\Http\Controllers;

use App\Models\Meet;
use App\Models\Setting;
use App\Models\Sport;
use App\Services\MedalTallyService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TallyController extends Controller
{
    public function __construct(private readonly MedalTallyService $tally) {}

    /**
     * Admin and ICT standings include submitted Results. Other viewers use
     * official results. All scopes share the same category and medal rules.
     */
    public function index(Request $request): Response
    {
        $meetId = Meet::current()->id;
        $sportId = $request->integer('sport_id') > 0 ? $request->integer('sport_id') : null;
        $category = $this->resolveCategory($request->query('category'));

        return Inertia::render('tally/index', [
            'medalTallyOfficial' => Setting::current()->medalTallyIsOfficial(),
            ...$this->tally->forInternalViewer($request->user())->categoryBreakdown($meetId, $category, $sportId),
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
