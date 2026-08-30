<?php

namespace App\Http\Controllers;

use App\Models\Meet;
use App\Services\MeetReadinessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class MeetReadinessController extends Controller
{
    public function __construct(private readonly MeetReadinessService $readiness) {}

    public function __invoke(Request $request): Response
    {
        $meet = Meet::query()->find($request->integer('meet_id')) ?? Meet::current();
        $scope = $this->readiness->scopeFor($request->user(), $meet);
        abort_if($scope === null, 403);
        $filters = [...$request->only(['sport_id', 'event_id', 'delegation_id', 'status', 'issue_type']), ...$scope, 'scope_label' => $scope['label']];
        $key = 'meet-readiness:'.$meet->id.':'.md5(json_encode($filters));
        if ($request->boolean('refresh')) Cache::forget($key);
        $data = Cache::remember($key, now()->addSeconds(45), fn () => $this->readiness->calculate($meet, $filters));

        return Inertia::render('meet-readiness/index', [...$data, 'print' => $request->boolean('print')]);
    }
}
