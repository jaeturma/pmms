<?php

namespace App\Http\Controllers;

use App\Enums\DrrmCategory;
use App\Models\Meet;
use App\Models\ReadinessChecklist;
use App\Policies\DrrmPolicy;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * See docs/medical-drrm.md.
 */
class ReadinessChecklistController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly DrrmPolicy $policy,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'meet_id' => ['required', 'integer', Rule::exists('meets', 'id')],
            'category' => ['required', Rule::enum(DrrmCategory::class)],
            'item' => ['required', 'string', 'max:200'],
        ]);

        $meet = Meet::query()->findOrFail((int) $validated['meet_id']);
        abort_unless($this->policy->manage($request->user(), $meet), 403);

        $checklist = ReadinessChecklist::create($validated);

        $this->audit->record('readiness_checklist.created', $checklist, ['meet' => $meet->name, 'item' => $checklist->item]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Checklist item added.')]);

        return back();
    }

    /**
     * Toggle a checklist item's completion.
     */
    public function updateStatus(Request $request, ReadinessChecklist $readinessChecklist): RedirectResponse
    {
        abort_unless($this->policy->manage($request->user(), $readinessChecklist->meet), 403);

        $validated = $request->validate([
            'is_complete' => ['required', 'boolean'],
        ]);

        $readinessChecklist->fill([
            'is_complete' => $validated['is_complete'],
            'completed_by_user_id' => $validated['is_complete'] ? $request->user()->id : null,
            'completed_at' => $validated['is_complete'] ? now() : null,
        ])->save();

        $this->audit->record('readiness_checklist.completed', $readinessChecklist, [
            'meet' => $readinessChecklist->meet->name,
            'item' => $readinessChecklist->item,
            'is_complete' => $readinessChecklist->is_complete,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Checklist item updated.')]);

        return back();
    }

    public function destroy(Request $request, ReadinessChecklist $readinessChecklist): RedirectResponse
    {
        abort_unless($this->policy->manage($request->user(), $readinessChecklist->meet), 403);

        $context = ['meet' => $readinessChecklist->meet->name, 'item' => $readinessChecklist->item];

        $readinessChecklist->delete();

        $this->audit->record('readiness_checklist.deleted', $readinessChecklist, $context);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Checklist item removed.')]);

        return back();
    }
}
