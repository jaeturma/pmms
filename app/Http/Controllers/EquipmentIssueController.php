<?php

namespace App\Http\Controllers;

use App\Enums\EquipmentIssueStatus;
use App\Models\EquipmentIssue;
use App\Models\EquipmentItem;
use App\Policies\SupplyPolicy;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Issue equipment out of a stock line to a venue (WP-REALIGN-10). Create
 * only — an issue is never edited or deleted, it's superseded by
 * `EquipmentReturn` rows against it. See docs/equipment-management.md.
 */
class EquipmentIssueController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly SupplyPolicy $policy,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'equipment_item_id' => ['required', 'integer', Rule::exists('equipment_items', 'id')],
            'venue_id' => ['required', 'integer', Rule::exists('venues', 'id')],
            'quantity' => ['required', 'integer', 'min:1'],
            'custodian_name' => ['nullable', 'string', 'max:120'],
            'purpose' => ['nullable', 'string', 'max:1000'],
        ]);

        $item = EquipmentItem::query()->with('category')->findOrFail((int) $validated['equipment_item_id']);
        abort_unless($this->policy->manage($request->user(), $item->category->meet), 403);

        if ($validated['quantity'] > $item->availableQuantity()) {
            throw ValidationException::withMessages([
                'quantity' => __('Only :available available.', ['available' => $item->availableQuantity()]),
            ]);
        }

        $issue = EquipmentIssue::create([
            'equipment_item_id' => $item->id,
            'venue_id' => $validated['venue_id'],
            'quantity' => $validated['quantity'],
            'custodian_name' => $validated['custodian_name'] ?? null,
            'issued_by_user_id' => $request->user()->id,
            'purpose' => $validated['purpose'] ?? null,
            'status' => EquipmentIssueStatus::Issued,
            'issued_at' => now(),
        ]);

        $this->audit->record('equipment_issue.created', $issue, [
            'category' => $item->category->name,
            'quantity' => $issue->quantity,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Equipment issued.')]);

        return back();
    }
}
