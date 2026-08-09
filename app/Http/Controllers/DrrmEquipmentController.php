<?php

namespace App\Http\Controllers;

use App\Models\DrrmEquipment;
use App\Models\Meet;
use App\Policies\DrrmPolicy;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * A flat inventory list — see `DrrmEquipment`'s own docblock for why
 * this is deliberately not Supply's issue/return/transfer machinery.
 * See docs/medical-drrm.md.
 */
class DrrmEquipmentController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly DrrmPolicy $policy,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'quantity' => ['required', 'integer', 'min:1'],
            'venue_id' => ['nullable', 'integer', Rule::exists('venues', 'id')],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $meet = Meet::current();
        abort_unless($this->policy->manage($request->user(), $meet), 403);

        $equipment = DrrmEquipment::create([...$validated, 'meet_id' => $meet->id]);

        $this->audit->record('drrm_equipment.created', $equipment, ['meet' => $meet->name, 'name' => $equipment->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Equipment added.')]);

        return back();
    }

    public function update(Request $request, DrrmEquipment $drrmEquipment): RedirectResponse
    {
        abort_unless($this->policy->manage($request->user(), $drrmEquipment->meet), 403);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'venue_id' => ['nullable', 'integer', Rule::exists('venues', 'id')],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $drrmEquipment->fill($validated)->save();

        $this->audit->record('drrm_equipment.updated', $drrmEquipment, ['meet' => $drrmEquipment->meet->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Equipment updated.')]);

        return back();
    }

    public function destroy(Request $request, DrrmEquipment $drrmEquipment): RedirectResponse
    {
        abort_unless($this->policy->manage($request->user(), $drrmEquipment->meet), 403);

        $context = ['meet' => $drrmEquipment->meet->name, 'name' => $drrmEquipment->name];

        $drrmEquipment->delete();

        $this->audit->record('drrm_equipment.deleted', $drrmEquipment, $context);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Equipment removed.')]);

        return back();
    }
}
