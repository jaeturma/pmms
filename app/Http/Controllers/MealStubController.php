<?php

namespace App\Http\Controllers;

use App\Models\MealEntitlement;
use App\Models\MealSchedule;
use App\Models\Meet;
use App\Policies\FoodPolicy;
use App\Services\AuditLogger;
use App\Services\MealEntitlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MealStubController extends Controller
{
    public function __construct(
        private readonly MealEntitlementService $entitlements,
        private readonly FoodPolicy $foodPolicy,
        private readonly AuditLogger $audit,
    ) {}

    public function show(Request $request): Response
    {
        $meet = Meet::current();
        abort_unless($this->entitlements->isEligible($request->user(), $meet), 403);
        $this->entitlements->sync($meet);
        $identity = $this->entitlements->identity($request->user(), $meet);

        $meals = MealEntitlement::query()->where('user_id', $request->user()->id)
            ->whereHas('schedule', fn ($schedules) => $schedules->where('meet_id', $meet->id))
            ->with('schedule.venue:id,name')->get()
            ->sortBy(fn (MealEntitlement $meal): string => $meal->schedule->date->toDateString().' '.($meal->schedule->starts_at ?? ''))
            ->values();

        return Inertia::render('meal-stub/show', [
            'person' => ['name' => $request->user()->name, ...$identity, 'meet' => $meet->name],
            'today' => now()->toDateString(),
            'todayLabel' => now()->translatedFormat('l, F j, Y'),
            'meals' => $meals->map(fn (MealEntitlement $item) => $this->row($item))->all(),
        ]);
    }

    public function consumeOwn(Request $request, MealEntitlement $mealEntitlement): RedirectResponse
    {
        $meet = Meet::current();
        abort_unless($mealEntitlement->user_id === $request->user()->id, 403);
        abort_unless($this->entitlements->isEligible($request->user(), $meet), 403);

        return $this->consume($request, $mealEntitlement, 'self', false);
    }

    public function distribution(Request $request): Response
    {
        $meet = Meet::current();
        abort_unless($this->foodPolicy->viewAny($request->user()), 403);
        $this->entitlements->sync($meet);
        $search = trim($request->string('search')->toString());
        $scheduleId = $request->integer('meal_schedule_id') ?: null;
        $hasPersonnelFilter = $request->integer('sport_id') > 0
            || $request->integer('twg_group_id') > 0;
        $personnel = $hasPersonnelFilter
            ? $this->filteredPersonnel($request, $meet)
            : collect();
        $personnelPage = max(1, $request->integer('personnel_page', 1));
        $personnelPerPage = 20;
        $personnelPaginator = new LengthAwarePaginator(
            $personnel->forPage($personnelPage, $personnelPerPage)->values(),
            $personnel->count(),
            $personnelPerPage,
            $personnelPage,
            [
                'path' => route('food.distribution'),
                'pageName' => 'personnel_page',
                'query' => $request->query(),
            ],
        );

        $query = MealEntitlement::query()->whereHas('schedule', fn ($schedules) => $schedules
            ->where('meet_id', $meet->id)->whereDate('date', now()->toDateString()))
            ->with(['schedule.venue:id,name', 'user:id,name,username,email'])
            ->when($scheduleId !== null, fn ($items) => $items->where('meal_schedule_id', $scheduleId))
            ->when($search !== '', fn ($items) => $items->whereHas('user', fn ($users) => $users
                ->where('name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")));

        $items = $query->orderBy('user_id')->paginate(20)->withQueryString()
            ->through(fn (MealEntitlement $item): array => [
                ...$this->row($item),
                'person' => $item->user->name,
                'code' => $item->user->username ?? $item->user->email,
                ...$this->entitlements->identity($item->user, $meet),
            ]);
        $summaryQuery = MealEntitlement::query()->whereHas('schedule', fn ($schedules) => $schedules
            ->where('meet_id', $meet->id)->whereDate('date', now()->toDateString()))
            ->when($scheduleId !== null, fn ($summary) => $summary->where('meal_schedule_id', $scheduleId));
        $expected = (clone $summaryQuery)->count();
        $consumed = (clone $summaryQuery)->where('status', 'consumed')->count();

        $report = MealEntitlement::query()
            ->whereHas('schedule', fn ($schedules) => $schedules->where('meet_id', $meet->id))
            ->with(['schedule', 'user'])
            ->get()
            ->map(function (MealEntitlement $item) use ($meet): array {
                $identity = $this->entitlements->identity($item->user, $meet);

                return [
                    'date' => $item->schedule->date->toDateString(),
                    'meal' => $item->schedule->meal_type->label(),
                    'role' => $identity['role'],
                    'sport' => $identity['sport'] ?: 'Meet Management / TWG',
                    'consumed' => $item->status === 'consumed' ? 1 : 0,
                ];
            })
            ->groupBy(fn (array $row) => implode('|', [$row['date'], $row['meal'], $row['role'], $row['sport']]))
            ->map(function ($rows): array {
                $first = $rows->first();
                $expected = $rows->count();
                $consumed = $rows->sum('consumed');

                return [...$first, 'expected' => $expected, 'consumed' => $consumed, 'not_claimed' => $expected - $consumed];
            })->sortBy(fn (array $row) => $row['date'].'|'.$row['meal'].'|'.$row['role'].'|'.$row['sport'])->values();

        return Inertia::render('food/distribution', [
            'entitlements' => $items,
            'summary' => ['expected' => $expected, 'consumed' => $consumed, 'remaining' => $expected - $consumed],
            'filters' => ['search' => $search, 'meal_schedule_id' => $scheduleId],
            'personnel' => $personnelPaginator,
            'personnelFilters' => [
                'search' => trim($request->string('personnel_search')->toString()),
                'sport_id' => $request->integer('sport_id') ?: null,
                'twg_group_id' => $request->integer('twg_group_id') ?: null,
                'has_group_filter' => $hasPersonnelFilter,
            ],
            'sportOptions' => $meet->meetSports()->with('sport:id,name')->get()
                ->sortBy('sport.name')->values()
                ->map(fn ($meetSport): array => ['id' => $meetSport->sport_id, 'label' => $meetSport->sport->name]),
            'twgGroupOptions' => $meet->managementTeams()->orderBy('name')->get(['id', 'name'])
                ->map(fn ($team): array => ['id' => $team->id, 'label' => $team->name]),
            'schedules' => MealSchedule::query()->where('meet_id', $meet->id)
                ->whereDate('date', now()->toDateString())->with('venue:id,name')->get()
                ->map(fn ($schedule) => ['id' => $schedule->id, 'label' => $schedule->meal_type->label().' · '.($schedule->starts_at ?: '')]),
            'can_override' => $request->user()->isAdmin(),
            'report' => $report,
        ]);
    }

    public function batchPrint(Request $request): Response
    {
        $meet = Meet::current();
        abort_unless($this->foodPolicy->viewAny($request->user()), 403);
        abort_unless(
            $request->integer('sport_id') > 0
                || $request->integer('twg_group_id') > 0
                || $request->integer('personnel_id') > 0,
            422,
            'Select a sport, TWG group, or personnel before loading meal stubs.',
        );
        $this->entitlements->sync($meet);

        $personnel = $this->filteredPersonnel($request, $meet);
        $entitlements = MealEntitlement::query()
            ->whereIn('user_id', $personnel->pluck('id'))
            ->whereHas('schedule', fn ($schedules) => $schedules
                ->where('meet_id', $meet->id)
                ->whereBetween('date', ['2026-09-03', '2026-09-08']))
            ->with('schedule:id,meet_id,meal_type,date,starts_at,ends_at,venue_id')
            ->get()
            ->groupBy('user_id');

        return Inertia::render('food/batch-print', [
            'meet' => $meet->name,
            'personnel' => $personnel->map(function (array $person) use ($entitlements): array {
                $meals = $entitlements->get($person['id'], collect())
                    ->sortBy(fn (MealEntitlement $meal): string => $meal->schedule->date->toDateString().' '.($meal->schedule->starts_at ?? ''))
                    ->values()
                    ->map(fn (MealEntitlement $meal): array => $this->row($meal));

                return [...$person, 'meals' => $meals];
            })->values(),
        ]);
    }

    public function templatePrint(Request $request): Response
    {
        $meet = Meet::current();
        abort_unless($this->foodPolicy->viewAny($request->user()), 403);

        $meals = MealSchedule::query()
            ->where('meet_id', $meet->id)
            ->whereBetween('date', ['2026-09-03', '2026-09-08'])
            ->orderBy('date')
            ->orderBy('starts_at')
            ->get()
            ->map(fn (MealSchedule $schedule): array => [
                'id' => $schedule->id,
                'date' => $schedule->date->toDateString(),
                'meal' => $schedule->meal_type->label(),
                'starts_at' => $schedule->starts_at,
                'ends_at' => $schedule->ends_at,
            ]);

        return Inertia::render('food/template-print', [
            'meet' => $meet->name,
            'meals' => $meals,
        ]);
    }

    public function consumeStaff(Request $request, MealEntitlement $mealEntitlement): RedirectResponse
    {
        abort_unless($this->foodPolicy->manage($request->user(), $mealEntitlement->schedule->meet), 403);
        $data = $request->validate(['override' => ['sometimes', 'boolean'], 'reason' => ['nullable', 'string', 'max:500']]);
        $override = $request->boolean('override');
        if ($override) {
            abort_unless($request->user()->isAdmin(), 403);
            if (blank($data['reason'] ?? null)) {
                throw ValidationException::withMessages(['reason' => __('An override reason is required.')]);
            }
        }

        return $this->consume($request, $mealEntitlement, $override ? 'admin_override' : 'staff', $override, $data['reason'] ?? null);
    }

    private function consume(Request $request, MealEntitlement $entitlement, string $method, bool $override, ?string $notes = null): RedirectResponse
    {
        $consumedAt = DB::transaction(function () use ($request, $entitlement, $method, $override, $notes) {
            $locked = MealEntitlement::query()->with('schedule')->lockForUpdate()->findOrFail($entitlement->id);
            if ($locked->status === 'consumed') {
                throw ValidationException::withMessages(['meal' => __('Meal already marked as consumed at :time.', ['time' => $locked->consumed_at->format('g:i A')])]);
            }
            if ($locked->status !== 'available') {
                throw ValidationException::withMessages(['meal' => __('This meal entitlement is not available.')]);
            }
            if (! $override && $this->entitlements->effectiveStatus($locked) !== 'available') {
                throw ValidationException::withMessages(['meal' => __('This meal can only be consumed during its configured serving period.')]);
            }
            $locked->forceFill([
                'status' => 'consumed', 'consumed_at' => now(), 'consumed_by_user_id' => $request->user()->id,
                'consumption_method' => $method, 'consumption_notes' => $notes,
            ])->save();

            $this->audit->record('meal.consumed', $locked, [
                'person' => $locked->user->name, 'meal' => $locked->schedule->meal_type->label(),
                'consumed_at' => $locked->consumed_at->toIso8601String(), 'consumption_method' => $method,
                'consumed_by_user_id' => $request->user()->id,
            ]);

            return $locked->consumed_at;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Meal marked as consumed at :time.', ['time' => $consumedAt->format('g:i A')])]);

        return back();
    }

    private function row(MealEntitlement $item): array
    {
        return [
            'id' => $item->id, 'date' => $item->schedule->date->toDateString(),
            'meal' => $item->schedule->meal_type->label(), 'starts_at' => $item->schedule->starts_at,
            'ends_at' => $item->schedule->ends_at, 'venue' => $item->schedule->venue?->name,
            'status' => $item->status, 'display_status' => $this->entitlements->effectiveStatus($item),
            'consumed_at' => $item->consumed_at?->format('g:i A'),
            'enforce_serving_time' => $item->schedule->enforce_serving_time,
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    private function filteredPersonnel(Request $request, Meet $meet): Collection
    {
        $search = mb_strtolower(trim($request->string('personnel_search')->toString()));
        $sportId = $request->integer('sport_id') ?: null;
        $twgGroupId = $request->integer('twg_group_id') ?: null;
        $personnelId = $request->integer('personnel_id') ?: null;

        return $this->entitlements->eligibleUsers($meet)
            ->map(function ($user) use ($meet): array {
                $assignments = $user->meetSportAssignments()
                    ->where('status', 'active')
                    ->whereHas('meetSport', fn ($sports) => $sports->where('meet_id', $meet->id))
                    ->with('meetSport.sport:id,name')
                    ->get();
                $memberships = $user->managementTeamMemberships()
                    ->where('status', 'active')
                    ->whereHas('managementTeam', fn ($teams) => $teams->where('meet_id', $meet->id))
                    ->with('managementTeam:id,name')
                    ->get();
                $identity = $this->entitlements->identity($user, $meet);

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'code' => $user->username ?? $user->email,
                    'role' => $identity['role'],
                    'sport' => $assignments->pluck('meetSport.sport.name')->filter()->unique()->join(', ') ?: null,
                    'twg_group' => $memberships->pluck('managementTeam.name')->filter()->unique()->join(', ') ?: null,
                    'sport_ids' => $assignments->pluck('meetSport.sport_id')->unique()->values()->all(),
                    'twg_group_ids' => $memberships->pluck('management_team_id')->unique()->values()->all(),
                ];
            })
            ->filter(fn (array $person): bool => $search === ''
                || str_contains(mb_strtolower($person['name'].' '.$person['code']), $search))
            ->filter(fn (array $person): bool => $sportId === null || in_array($sportId, $person['sport_ids'], true))
            ->filter(fn (array $person): bool => $twgGroupId === null || in_array($twgGroupId, $person['twg_group_ids'], true))
            ->filter(fn (array $person): bool => $personnelId === null || $person['id'] === $personnelId)
            ->map(function (array $person): array {
                unset($person['sport_ids'], $person['twg_group_ids']);

                return $person;
            })
            ->sortBy('name')
            ->values();
    }
}
