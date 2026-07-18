<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SearchesAndPaginates;
use App\Http\Requests\SchoolRequest;
use App\Models\District;
use App\Models\School;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SchoolController extends Controller
{
    use SearchesAndPaginates;

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Searchable, paginated school registry.
     */
    public function index(Request $request): Response
    {
        $search = $this->searchTerm($request);

        $query = School::query()
            ->with('district:id,name')
            ->orderBy('name');

        $this->applySearch($query, $search, ['name', 'school_id_code', 'district.name']);

        return Inertia::render('registry/schools', [
            'schools' => $query->paginate($this->registryPageSize)->withQueryString()
                ->through(fn (School $school): array => [
                    'id' => $school->id,
                    'district_id' => $school->district_id,
                    'name' => $school->name,
                    'school_id_code' => $school->school_id_code,
                    'level' => $school->level->value,
                    'address' => $school->address,
                    'active' => $school->active,
                    'district' => ['id' => $school->district->id, 'name' => $school->district->name],
                ]),
            'filters' => ['search' => $search],
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
