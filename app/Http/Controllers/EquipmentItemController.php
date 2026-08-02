<?php

namespace App\Http\Controllers;

use App\Enums\EquipmentCondition;
use App\Models\EquipmentCategory;
use App\Models\EquipmentItem;
use App\Policies\SupplyPolicy;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Stock-line CRUD for `EquipmentItem` (WP-REALIGN-10). `quantity` is
 * only ever set here at creation — after that, it changes only via
 * `InventoryAdjustmentController` (a reasoned correction) and `venue_id`
 * only via `EquipmentTransferController` (a tracked move); `update()`
 * here is deliberately limited to condition/notes, the two fields that
 * don't need their own transactional record. See
 * docs/equipment-management.md.
 */
class EquipmentItemController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly SupplyPolicy $policy,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'equipment_category_id' => ['required', 'integer', Rule::exists('equipment_categories', 'id')],
            'venue_id' => ['nullable', 'integer', Rule::exists('venues', 'id')],
            'quantity' => ['required', 'integer', 'min:1'],
            'condition' => ['nullable', Rule::enum(EquipmentCondition::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $category = EquipmentCategory::query()->findOrFail((int) $validated['equipment_category_id']);
        abort_unless($this->policy->manage($request->user(), $category->meet), 403);

        $item = EquipmentItem::create($validated);

        $this->audit->record('equipment_item.created', $item, [
            'category' => $category->name,
            'quantity' => $item->quantity,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Item added.')]);

        return back();
    }

    public function update(Request $request, EquipmentItem $equipmentItem): RedirectResponse
    {
        abort_unless($this->policy->manage($request->user(), $equipmentItem->category->meet), 403);

        $validated = $request->validate([
            'condition' => ['nullable', Rule::enum(EquipmentCondition::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $equipmentItem->fill($validated)->save();

        $this->audit->record('equipment_item.updated', $equipmentItem, [
            'category' => $equipmentItem->category->name,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Item updated.')]);

        return back();
    }

    public function destroy(Request $request, EquipmentItem $equipmentItem): RedirectResponse
    {
        abort_unless($this->policy->manage($request->user(), $equipmentItem->category->meet), 403);

        $context = [
            'category' => $equipmentItem->category->name,
            'quantity' => $equipmentItem->quantity,
        ];

        $equipmentItem->delete();

        $this->audit->record('equipment_item.deleted', $equipmentItem, $context);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Item removed.')]);

        return back();
    }
}
