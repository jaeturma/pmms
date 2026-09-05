<?php

namespace App\Http\Controllers;

use App\Models\Delegation;
use App\Models\Event;
use App\Models\EventResult;
use App\Models\Meet;
use App\Models\ResultPlacement;
use App\Models\TeamEntry;
use App\Services\ResultAttributionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ResultAttributionController extends Controller
{
    public static function rules(): array
    {
        return [
            'athlete_id' => ['nullable', 'integer'],
            'team_entry_id' => ['nullable', 'integer'],
            'athlete_ids' => ['sometimes', 'array', 'max:200'],
            'athlete_ids.*' => ['integer', 'distinct'],
            'coaches' => ['sometimes', 'array', 'max:20'],
            'coaches.*.user_id' => ['required', 'integer', 'distinct'],
            'coaches.*.role' => ['required', Rule::in(['primary', 'assistant'])],
        ];
    }

    public function options(Request $request, ResultAttributionService $service)
    {
        $event = Event::findOrFail($request->integer('event_id'));
        $delegation = Delegation::findOrFail($request->integer('delegation_id'));
        abort_unless($delegation->meet_id === Meet::current()->id && $event->meets()->whereKey($delegation->meet_id)->exists(), 403);
        abort_unless($service->canManage($request->user(), $event, $delegation), 403);

        return response()->json([
            'athletes' => $service->athletes($event, $delegation)->orderBy('last_name')->orderBy('first_name')->get()
                ->map(fn ($a) => ['id' => $a->id, 'label' => $a->fullName()]),
            'teams' => $event->is_team_event ? TeamEntry::where('event_id', $event->id)->where('delegation_id', $delegation->id)->with('members')->get()
                ->map(fn ($t) => ['id' => $t->id, 'label' => 'Team Entry #'.$t->id.' ('.$t->status->value.')', 'athlete_ids' => $t->members->pluck('athlete_id')]) : [],
            'coaches' => $event->is_team_event ? $service->coaches($event, $delegation)->map(fn ($c) => ['id' => $c->id, 'label' => $c->name]) : [],
        ]);
    }

    public function update(Request $request, EventResult $result, ResultPlacement $placement, ResultAttributionService $service)
    {
        abort_unless($placement->event_result_id === $result->id && $result->result_source === 'direct', 404);
        abort_unless($placement->delegation !== null && $result->meet_id === Meet::current()->id && $service->canManage($request->user(), $result->event, $placement->delegation), 403);
        $data = $request->validate([...self::rules(),
            'rank' => ['prohibited'], 'delegation_id' => ['prohibited'], 'mark' => ['prohibited'],
            'tally_quantity' => ['prohibited'], 'status' => ['prohibited'], 'medal_type' => ['prohibited'],
        ]);
        DB::transaction(function () use ($result, $placement, $service, $data) {
            EventResult::whereKey($result->id)->lockForUpdate()->firstOrFail();
            $placement = ResultPlacement::whereKey($placement->id)->lockForUpdate()->firstOrFail();
            $payload = array_replace($service->report($placement), $data);
            if (array_key_exists('team_entry_id', $data) && $data['team_entry_id'] !== $placement->team_entry_id && ! array_key_exists('athlete_ids', $data)) {
                unset($payload['athlete_ids']);
            }
            $service->save($placement, $service->validate($result->event, $placement->delegation, $payload));
        });

        return back()->with('success', 'Reporting attribution saved.');
    }
}
