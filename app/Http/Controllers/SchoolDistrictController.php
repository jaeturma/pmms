<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SearchesAndPaginates;
use App\Http\Requests\SchoolDistrictRequest;
use App\Models\District;
use App\Models\SchoolDistrict;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Registry for the real DepEd school district — the sub-unit a school
 * belongs to within its municipality (see SchoolDistrict's docblock).
 * Mirrors DistrictController's CRUD pattern exactly.
 */
class SchoolDistrictController extends Controller
{
    use SearchesAndPaginates;

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Searchable, paginated school district registry.
     */
    public function index(Request $request): Response
    {
        $search = $this->searchTerm($request);

        $query = SchoolDistrict::query()
            ->with('municipality:id,name')
            ->withCount('schools')
            ->orderBy('name');

        $this->applySearch($query, $search, ['name', 'municipality.name']);

        return Inertia::render('registry/school-districts', [
            'schoolDistricts' => $query->paginate($this->registryPageSize)->withQueryString()
                ->through(fn (SchoolDistrict $schoolDistrict): array => [
                    'id' => $schoolDistrict->id,
                    'district_id' => $schoolDistrict->district_id,
                    'name' => $schoolDistrict->name,
                    'nickname' => $schoolDistrict->nickname,
                    'active' => $schoolDistrict->active,
                    'schools_count' => $schoolDistrict->schools_count,
                    'municipality' => [
                        'id' => $schoolDistrict->municipality->id,
                        'name' => $schoolDistrict->municipality->name,
                    ],
                ]),
            'filters' => ['search' => $search],
            'municipalities' => District::query()
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'canManage' => Gate::allows('manage-meet-data'),
        ]);
    }

    /**
     * Create a school district.
     */
    public function store(SchoolDistrictRequest $request): RedirectResponse
    {
        $schoolDistrict = SchoolDistrict::create($request->validated());

        $this->audit->record('school_district.created', $schoolDistrict, ['name' => $schoolDistrict->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('District created.')]);

        return back();
    }

    /**
     * Update a school district.
     */
    public function update(SchoolDistrictRequest $request, SchoolDistrict $schoolDistrict): RedirectResponse
    {
        $schoolDistrict->update($request->validated());

        $this->audit->record('school_district.updated', $schoolDistrict, ['name' => $schoolDistrict->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('District updated.')]);

        return back();
    }

    /**
     * Archive a school district instead of deleting it.
     */
    public function archive(SchoolDistrict $schoolDistrict): RedirectResponse
    {
        $schoolDistrict->forceFill(['active' => false])->save();

        $this->audit->record('school_district.archived', $schoolDistrict, ['name' => $schoolDistrict->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('District archived.')]);

        return back();
    }

    /**
     * Restore an archived school district.
     */
    public function restore(SchoolDistrict $schoolDistrict): RedirectResponse
    {
        $schoolDistrict->forceFill(['active' => true])->save();

        $this->audit->record('school_district.restored', $schoolDistrict, ['name' => $schoolDistrict->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('District restored.')]);

        return back();
    }

    /**
     * Delete a school district that no school references.
     */
    public function destroy(SchoolDistrict $schoolDistrict): RedirectResponse
    {
        if ($schoolDistrict->schools()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This district has schools. Archive it instead.'),
            ]);

            return back();
        }

        $schoolDistrict->delete();

        $this->audit->record('school_district.deleted', $schoolDistrict, ['name' => $schoolDistrict->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('District deleted.')]);

        return back();
    }
}
