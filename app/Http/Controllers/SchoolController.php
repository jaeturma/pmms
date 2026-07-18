<?php

namespace App\Http\Controllers;

use App\Http\Requests\SchoolRequest;
use App\Models\District;
use App\Models\School;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SchoolController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * List all schools.
     */
    public function index(): Response
    {
        return Inertia::render('registry/schools', [
            'schools' => School::query()
                ->with('district:id,name')
                ->orderBy('name')
                ->get(['id', 'district_id', 'name', 'school_id_code', 'level', 'address', 'active']),
            'districts' => District::query()
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'canManage' => Gate::allows('manage-meet-data'),
        ]);
    }

    /**
     * Create a school.
     */
    public function store(SchoolRequest $request): RedirectResponse
    {
        $school = School::create($request->validated());

        $this->audit->record('school.created', $school, ['name' => $school->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('School created.')]);

        return back();
    }

    /**
     * Update a school.
     */
    public function update(SchoolRequest $request, School $school): RedirectResponse
    {
        $school->update($request->validated());

        $this->audit->record('school.updated', $school, ['name' => $school->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('School updated.')]);

        return back();
    }

    /**
     * Archive a school instead of deleting it.
     */
    public function archive(School $school): RedirectResponse
    {
        $school->forceFill(['active' => false])->save();

        $this->audit->record('school.archived', $school, ['name' => $school->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('School archived.')]);

        return back();
    }

    /**
     * Restore an archived school.
     */
    public function restore(School $school): RedirectResponse
    {
        $school->forceFill(['active' => true])->save();

        $this->audit->record('school.restored', $school, ['name' => $school->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('School restored.')]);

        return back();
    }

    /**
     * Delete a school that no delegation references.
     */
    public function destroy(School $school): RedirectResponse
    {
        if ($school->delegations()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This school has delegations. Archive it instead.'),
            ]);

            return back();
        }

        $school->delete();

        $this->audit->record('school.deleted', $school, ['name' => $school->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('School deleted.')]);

        return back();
    }
}
