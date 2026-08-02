<?php

namespace App\Http\Controllers;

use App\Models\MealAnnouncement;
use App\Models\Meet;
use App\Policies\FoodPolicy;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Internal Food Team notice CRUD (WP-REALIGN-11). See
 * docs/food-billeting-transport.md.
 */
class MealAnnouncementController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly FoodPolicy $policy,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'meet_id' => ['required', 'integer', Rule::exists('meets', 'id')],
            'title' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $meet = Meet::query()->findOrFail((int) $validated['meet_id']);
        abort_unless($this->policy->manage($request->user(), $meet), 403);

        $announcement = MealAnnouncement::create([
            ...$validated,
            'posted_by_user_id' => $request->user()->id,
        ]);

        $this->audit->record('meal_announcement.created', $announcement, [
            'meet' => $meet->name,
            'title' => $announcement->title,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Announcement posted.')]);

        return back();
    }

    public function update(Request $request, MealAnnouncement $mealAnnouncement): RedirectResponse
    {
        abort_unless($this->policy->manage($request->user(), $mealAnnouncement->meet), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $mealAnnouncement->fill($validated)->save();

        $this->audit->record('meal_announcement.updated', $mealAnnouncement, [
            'meet' => $mealAnnouncement->meet->name,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Announcement updated.')]);

        return back();
    }

    public function destroy(Request $request, MealAnnouncement $mealAnnouncement): RedirectResponse
    {
        abort_unless($this->policy->manage($request->user(), $mealAnnouncement->meet), 403);

        $context = ['meet' => $mealAnnouncement->meet->name, 'title' => $mealAnnouncement->title];

        $mealAnnouncement->delete();

        $this->audit->record('meal_announcement.deleted', $mealAnnouncement, $context);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Announcement removed.')]);

        return back();
    }
}
