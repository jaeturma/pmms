<?php

namespace App\Http\Controllers;

use App\Models\EquipmentItem;
use App\Models\EquipmentTransfer;
use App\Policies\SupplyPolicy;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Move a stock line between venues within a meet (WP-REALIGN-10). A
 * full-quantity transfer moves the item's own `venue_id`; a
 * partial-quantity transfer splits the stock line — the original item's
 * quantity is reduced and a new item row is created at the destination
 * venue, since this WP tracks one `venue_id` per item, not a per-venue
 * ledger on one row. See docs/equipment-management.md.
 */
class EquipmentTransferController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly SupplyPolicy $policy,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'equipment_item_id' => ['required', 'integer', Rule::exists('equipment_items', 'id')],
            'to_venue_id' => ['required', 'integer', Rule::exists('venues', 'id')],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $item = EquipmentItem::query()->with('category', 'issues.returns')->findOrFail((int) $validated['equipment_item_id']);
        abort_unless($this->policy->manage($request->user(), $item->category->meet), 403);

        // Capped by *available* quantity, not the item's raw quantity —
        // `venue_id` represents where the un-issued stock sits; the
        // portion currently out on an EquipmentIssue is tracked at that
        // issue's own venue independently and isn't this item's to move.
        $available = $item->availableQuantity();

        if ($validated['quantity'] > $available) {
            throw ValidationException::withMessages([
                'quantity' => __('Only :available available to transfer.', ['available' => $available]),
            ]);
        }

        if ((int) $validated['to_venue_id'] === $item->venue_id) {
            throw ValidationException::withMessages([
                'to_venue_id' => __('This item is already at that venue.'),
            ]);
        }

        $fromVenueId = $item->venue_id;

        $transfer = DB::transaction(function () use ($item, $validated, $fromVenueId, $request): EquipmentTransfer {
            if ($validated['quantity'] === $item->quantity) {
                $item->venue_id = $validated['to_venue_id'];
                $item->save();
            } else {
                EquipmentItem::create([
                    'equipment_category_id' => $item->equipment_category_id,
                    'venue_id' => $validated['to_venue_id'],
                    'quantity' => $validated['quantity'],
                    'condition' => $item->condition,
                    'notes' => $item->notes,
                ]);

                $item->quantity -= $validated['quantity'];
                $item->save();
            }

            return EquipmentTransfer::create([
                'equipment_item_id' => $item->id,
                'from_venue_id' => $fromVenueId,
                'to_venue_id' => $validated['to_venue_id'],
                'quantity' => $validated['quantity'],
                'transferred_by_user_id' => $request->user()->id,
                'reason' => $validated['reason'] ?? null,
                'transferred_at' => now(),
            ]);
        });

        $this->audit->record('equipment_transfer.created', $transfer, [
            'category' => $item->category->name,
            'quantity' => $transfer->quantity,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Equipment transferred.')]);

        return back();
    }
}
