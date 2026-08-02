<?php

namespace App\Http\Controllers;

use App\Enums\TransportRequestStatus;
use App\Models\Delegation;
use App\Models\TransportRequest;
use App\Policies\TransportPolicy;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * File/manage a delegation's transport request (WP-REALIGN-11).
 * `store()` is the one manage action a DelegationOfficer gets on this
 * domain — filing a request for their own delegation — via
 * `TransportPolicy::createRequest()`; `updateStatus()`/`destroy()` stay
 * Transport-Team/Admin/Organizer-only. See
 * docs/food-billeting-transport.md.
 */
class TransportRequestController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly TransportPolicy $policy,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'delegation_id' => ['required', 'integer', Rule::exists('delegations', 'id')],
            'pickup_location' => ['required', 'string', 'max:200'],
            'dropoff_location' => ['required', 'string', 'max:200'],
            'requested_at' => ['required', 'date'],
            'passenger_count' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $delegation = Delegation::query()->findOrFail((int) $validated['delegation_id']);
        abort_unless($this->policy->createRequest($request->user(), $delegation), 403);

        $transportRequest = TransportRequest::create([
            ...$validated,
            'meet_id' => $delegation->meet_id,
            'status' => TransportRequestStatus::Pending,
            'requested_by_user_id' => $request->user()->id,
        ]);

        $this->audit->record('transport_request.created', $transportRequest, [
            'delegation' => $delegation->registrantName(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transport requested.')]);

        return back();
    }

    public function updateStatus(Request $request, TransportRequest $transportRequest): RedirectResponse
    {
        abort_unless($this->policy->manage($request->user(), $transportRequest->meet), 403);

        $validated = $request->validate([
            'status' => ['required', Rule::in([TransportRequestStatus::Cancelled->value])],
        ]);

        $transportRequest->forceFill($validated)->save();

        $this->audit->record('transport_request.status_updated', $transportRequest, [
            'delegation' => $transportRequest->delegation->registrantName(),
            'status' => $transportRequest->status->value,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Request updated.')]);

        return back();
    }

    public function destroy(Request $request, TransportRequest $transportRequest): RedirectResponse
    {
        abort_unless($this->policy->manage($request->user(), $transportRequest->meet), 403);

        $context = ['delegation' => $transportRequest->delegation->registrantName()];

        $transportRequest->delete();

        $this->audit->record('transport_request.deleted', $transportRequest, $context);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Request removed.')]);

        return back();
    }
}
