<?php

namespace App\Http\Controllers;

use App\Enums\InventoryAdjustmentType;
use App\Models\EquipmentItem;
use App\Models\InventoryAdjustment;
use App\Policies\SupplyPolicy;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Correct an `EquipmentItem.quantity` outside the normal
 * issue/return/transfer flow — damage, loss, recount, found
 * (WP-REALIGN-10). `reason` is required, mirroring
 * `ResultController::correct()`'s reason-required precedent. Unlike
 * issues/returns/transfers, applying an adjustment updates `quantity`
 * directly rather than only affecting the derived available count. See
 * docs/equipment-management.md.
 */
class InventoryAdjustmentController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly SupplyPolicy $policy,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'equipment_item_id' => ['required', 'integer', Rule::exists('equipment_items', 'id')],
            'type' => ['required', Rule::enum(InventoryAdjustmentType::class)],
            'quantity_delta' => ['required', 'integer', 'not_in:0'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $item = EquipmentItem::query()->with('category', 'issues.returns')->findOrFail((int) $validated['equipment_item_id']);
        abort_unless($this->policy->manage($request->user(), $item->category->meet), 403);

        $outstandingIssued = $item->quantity - $item->availableQuantity();
        $newQuantity = $item->quantity + $validated['quantity_delta'];

        if ($newQuantity < $outstandingIssued) {
            throw ValidationException::withMessages([
                'quantity_delta' => __('This would drop below the :outstanding unit(s) currently issued out.', ['outstanding' => $outstandingIssued]),
            ]);
        }

        $adjustment = DB::transaction(function () use ($item, $validated, $request): InventoryAdjustment {
            $item->quantity = $item->quantity + $validated['quantity_delta'];
            $item->save();

            return InventoryAdjustment::create([
                'equipment_item_id' => $item->id,
                'type' => $validated['type'],
                'quantity_delta' => $validated['quantity_delta'],
                'reason' => $validated['reason'],
                'adjusted_by_user_id' => $request->user()->id,
                'adjusted_at' => now(),
            ]);
        });

        $this->audit->record('inventory_adjustment.created', $adjustment, [
            'category' => $item->category->name,
            'type' => $adjustment->type->value,
            'quantity_delta' => $adjustment->quantity_delta,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Inventory adjusted.')]);

        return back();
    }
}
