<?php

namespace App\Http\Controllers;

use App\Enums\DivisionType;
use App\Http\Requests\DivisionRequest;
use App\Models\Division;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The division's settings: city or province type, and the label that
 * follows from it ("District" vs "Municipality") — see
 * App\Enums\DivisionType and docs/division.md.
 */
class DivisionController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Show the division settings. Admin-only (`can:administer` route
     * middleware) — the current division is already shared to every page
     * via Inertia for read purposes.
     */
    public function edit(): Response
    {
        $division = Division::current();

        return Inertia::render('division/edit', [
            'division' => [
                'type' => $division->type->value,
                'name' => $division->name,
                'areaLabel' => $division->areaLabel(),
            ],
            'typeLocked' => $division->typeIsLocked(),
        ]);
    }

    /**
     * Update the division settings. The type is refused once any
     * delegation exists — see Division::typeIsLocked().
     */
    public function update(DivisionRequest $request): RedirectResponse
    {
        $division = Division::current();
        $validated = $request->validated();

        $from = $division->type;

        $division->forceFill([
            'name' => $validated['name'],
            'type' => isset($validated['type']) ? DivisionType::from($validated['type']) : $division->type,
        ])->save();

        $this->audit->record('division.updated', $division, [
            'name' => $division->name,
            'type' => ['from' => $from->value, 'to' => $division->type->value],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Division settings updated.')]);

        return back();
    }
}
