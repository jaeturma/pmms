<?php

namespace App\Http\Controllers;

use App\Enums\DrrmCategory;
use App\Models\EmergencyContact;
use App\Models\Meet;
use App\Policies\DrrmPolicy;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * See docs/medical-drrm.md.
 */
class EmergencyContactController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly DrrmPolicy $policy,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'role' => ['nullable', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'category' => ['nullable', Rule::enum(DrrmCategory::class)],
        ]);

        $meet = Meet::current();
        abort_unless($this->policy->manage($request->user(), $meet), 403);

        $contact = EmergencyContact::create([...$validated, 'meet_id' => $meet->id]);

        $this->audit->record('emergency_contact.created', $contact, ['meet' => $meet->name, 'name' => $contact->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Emergency contact added.')]);

        return back();
    }

    public function update(Request $request, EmergencyContact $emergencyContact): RedirectResponse
    {
        abort_unless($this->policy->manage($request->user(), $emergencyContact->meet), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'role' => ['nullable', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'category' => ['nullable', Rule::enum(DrrmCategory::class)],
        ]);

        $emergencyContact->fill($validated)->save();

        $this->audit->record('emergency_contact.updated', $emergencyContact, ['meet' => $emergencyContact->meet->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Emergency contact updated.')]);

        return back();
    }

    public function destroy(Request $request, EmergencyContact $emergencyContact): RedirectResponse
    {
        abort_unless($this->policy->manage($request->user(), $emergencyContact->meet), 403);

        $context = ['meet' => $emergencyContact->meet->name, 'name' => $emergencyContact->name];

        $emergencyContact->delete();

        $this->audit->record('emergency_contact.deleted', $emergencyContact, $context);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Emergency contact removed.')]);

        return back();
    }
}
