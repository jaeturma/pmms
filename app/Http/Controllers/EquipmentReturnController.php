<?php

namespace App\Http\Controllers;

use App\Enums\EquipmentCondition;
use App\Enums\EquipmentIssueStatus;
use App\Models\EquipmentIssue;
use App\Models\EquipmentReturn;
use App\Policies\SupplyPolicy;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Return equipment against an `EquipmentIssue` (WP-REALIGN-10). Blocked
 * for issues against a consumable category — a consumable is used up,
 * never returned. Supports partial returns across multiple return
 * events, updating the issue's own status as they come in. See
 * docs/equipment-management.md.
 */
class EquipmentReturnController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly SupplyPolicy $policy,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'equipment_issue_id' => ['required', 'integer', Rule::exists('equipment_issues', 'id')],
            'quantity' => ['required', 'integer', 'min:1'],
            'condition_on_return' => ['nullable', Rule::enum(EquipmentCondition::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $issue = EquipmentIssue::query()->with('item.category', 'returns')->findOrFail((int) $validated['equipment_issue_id']);
        abort_unless($this->policy->manage($request->user(), $issue->item->category->meet), 403);

        if ($issue->item->category->is_consumable) {
            throw ValidationException::withMessages([
                'equipment_issue_id' => __('Consumable equipment cannot be returned.'),
            ]);
        }

        $outstanding = $issue->outstandingQuantity();

        if ($validated['quantity'] > $outstanding) {
            throw ValidationException::withMessages([
                'quantity' => __('Only :outstanding still outstanding on this issue.', ['outstanding' => $outstanding]),
            ]);
        }

        $return = EquipmentReturn::create([
            'equipment_issue_id' => $issue->id,
            'quantity' => $validated['quantity'],
            'condition_on_return' => $validated['condition_on_return'] ?? null,
            'received_by_user_id' => $request->user()->id,
            'notes' => $validated['notes'] ?? null,
            'returned_at' => now(),
        ]);

        $issue->refresh();
        $issue->status = $issue->outstandingQuantity() === 0
            ? EquipmentIssueStatus::Returned
            : EquipmentIssueStatus::PartiallyReturned;
        $issue->save();

        $this->audit->record('equipment_return.created', $return, [
            'category' => $issue->item->category->name,
            'quantity' => $return->quantity,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Equipment returned.')]);

        return back();
    }
}
