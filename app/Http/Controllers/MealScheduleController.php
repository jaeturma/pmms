<?php

namespace App\Http\Controllers;

use App\Enums\ManagementTeamType;
use App\Enums\MealType;
use App\Http\Controllers\Concerns\ScopesToManagementTeam;
use App\Models\MealAnnouncement;
use App\Models\MealSchedule;
use App\Models\Meet;
use App\Models\Venue;
use App\Policies\FoodPolicy;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Management UI for the Food Team's meal schedule (WP-REALIGN-11) — see
 * docs/food-billeting-transport.md. `MealSchedule` is this page's primary
 * entity; `MealAnnouncement`s are managed inline from here via
 * `MealAnnouncementController`.
 */
class MealScheduleController extends Controller
{
    use ScopesToManagementTeam;

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly FoodPolicy $policy,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        abort_unless($this->policy->viewAny($user), 403);

        $meetId = Meet::current()->id;
        $accessibleMeetIds = $this->accessibleMeetIds($user, ManagementTeamType::Food);

        $schedules = MealSchedule::query()
            ->with(['meet:id,name', 'venue:id,name'])
            ->when($accessibleMeetIds !== null, fn ($q) => $q->whereIn('meet_id', $accessibleMeetIds))
            ->when($meetId > 0, fn ($q) => $q->where('meet_id', $meetId))
            ->orderBy('date')
            ->orderBy('meal_type')
            ->get();

        $announcements = MealAnnouncement::query()
            ->with(['meet:id,name', 'postedBy:id,name'])
            ->when($accessibleMeetIds !== null, fn ($q) => $q->whereIn('meet_id', $accessibleMeetIds))
            ->when($meetId > 0, fn ($q) => $q->where('meet_id', $meetId))
            ->orderByDesc('id')
            ->get();

        $meetOptions = Meet::query()
            ->whereKey($meetId)
            ->when($accessibleMeetIds !== null, fn ($q) => $q->whereIn('id', $accessibleMeetIds))
            ->orderByDesc('id')
            ->get(['id', 'name'])
            ->map(fn (Meet $meet): array => ['id' => $meet->id, 'label' => $meet->name]);

        return Inertia::render('food/index', [
            'schedules' => $schedules->map(fn (MealSchedule $schedule): array => [
                'id' => $schedule->id,
                'meet_id' => $schedule->meet_id,
                'meet' => $schedule->meet->name,
                'meal_type' => $schedule->meal_type->value,
                'meal_type_label' => $schedule->meal_type->label(),
                'date' => $schedule->date->toDateString(),
                'starts_at' => $schedule->starts_at,
                'ends_at' => $schedule->ends_at,
                'enforce_serving_time' => $schedule->enforce_serving_time,
                'venue_id' => $schedule->venue_id,
                'venue' => $schedule->venue?->name,
                'notes' => $schedule->notes,
            ])->values(),
            'announcements' => $announcements->map(fn (MealAnnouncement $announcement): array => [
                'id' => $announcement->id,
                'meet_id' => $announcement->meet_id,
                'meet' => $announcement->meet->name,
                'title' => $announcement->title,
                'message' => $announcement->message,
                'posted_by' => $announcement->postedBy->name,
                'posted_at' => $announcement->created_at?->toDayDateTimeString(),
            ])->values(),
            'filters' => ['meet_id' => $meetId > 0 ? $meetId : null],
            'meetOptions' => $meetOptions,
            'venueOptions' => Venue::query()->where('active', true)->orderBy('name')->get(['id', 'name'])
                ->map(fn (Venue $venue): array => ['id' => $venue->id, 'label' => $venue->name]),
            'mealTypeOptions' => array_map(
                fn (MealType $type): array => ['value' => $type->value, 'label' => $type->label()],
                MealType::cases(),
            ),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'meet_id' => ['required', 'integer', Rule::exists('meets', 'id')],
            'meal_type' => ['required', Rule::enum(MealType::class)],
            'date' => ['required', 'date'],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i', 'after:starts_at'],
            'enforce_serving_time' => ['sometimes', 'boolean'],
            'venue_id' => ['nullable', 'integer', Rule::exists('venues', 'id')],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $meet = Meet::query()->findOrFail((int) $validated['meet_id']);
        abort_unless($this->policy->manage($request->user(), $meet), 403);

        if (MealSchedule::query()
            ->where('meet_id', $validated['meet_id'])
            ->where('meal_type', $validated['meal_type'])
            ->whereDate('date', $validated['date'])
            ->where('starts_at', $validated['starts_at'] ?? null)
            ->exists()) {
            throw ValidationException::withMessages([
                'meal_type' => __('This meet already has that meal period at the same start time.'),
            ]);
        }

        $schedule = MealSchedule::create($validated);

        $this->audit->record('meal_schedule.created', $schedule, [
            'meet' => $meet->name,
            'meal_type' => $schedule->meal_type->value,
            'date' => $schedule->date->toDateString(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Meal schedule added.')]);

        return back();
    }

    public function update(Request $request, MealSchedule $mealSchedule): RedirectResponse
    {
        abort_unless($this->policy->manage($request->user(), $mealSchedule->meet), 403);

        $validated = $request->validate([
            'starts_at' => ['nullable', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i', 'after:starts_at'],
            'enforce_serving_time' => ['sometimes', 'boolean'],
            'venue_id' => ['nullable', 'integer', Rule::exists('venues', 'id')],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $mealSchedule->fill($validated)->save();

        $this->audit->record('meal_schedule.updated', $mealSchedule, [
            'meet' => $mealSchedule->meet->name,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Meal schedule updated.')]);

        return back();
    }

    public function destroy(Request $request, MealSchedule $mealSchedule): RedirectResponse
    {
        abort_unless($this->policy->manage($request->user(), $mealSchedule->meet), 403);

        $context = ['meet' => $mealSchedule->meet->name, 'meal_type' => $mealSchedule->meal_type->value];

        $mealSchedule->delete();

        $this->audit->record('meal_schedule.deleted', $mealSchedule, $context);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Meal schedule removed.')]);

        return back();
    }
}
