<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SearchesAndPaginates;
use App\Http\Requests\DistrictRequest;
use App\Models\District;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DistrictController extends Controller
{
    use SearchesAndPaginates;

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Searchable, paginated district registry.
     */
    public function index(Request $request): Response
    {
        $search = $this->searchTerm($request);

        $query = District::query()
            ->withCount('schools')
            ->orderBy('name');

        $this->applySearch($query, $search, ['name']);

        return Inertia::render('registry/districts', [
            'districts' => $query->paginate($this->registryPageSize)->withQueryString()
                ->through(fn (District $district): array => [
                    'id' => $district->id,
                    'name' => $district->name,
                    'active' => $district->active,
                    'schools_count' => $district->schools_count,
                ]),
            'filters' => ['search' => $search],
            'canManage' => Gate::allows('manage-meet-data'),
        ]);
    }

    /**
     * Create a district.
     */
    public function store(DistrictRequest $request): RedirectResponse
    {
        $district = District::create($request->validated());

        $this->audit->record('district.created', $district, ['name' => $district->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('District created.')]);

        return back();
    }

    /**
     * Update a district.
     */
    public function update(DistrictRequest $request, District $district): RedirectResponse
    {
        $district->update($request->validated());

        $this->audit->record('district.updated', $district, ['name' => $district->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('District updated.')]);

        return back();
    }

    /**
     * Archive a district instead of deleting it.
     */
    public function archive(District $district): RedirectResponse
    {
        $district->forceFill(['active' => false])->save();

        $this->audit->record('district.archived', $district, ['name' => $district->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('District archived.')]);

        return back();
    }

    /**
     * Restore an archived district.
     */
    public function restore(District $district): RedirectResponse
    {
        $district->forceFill(['active' => true])->save();

        $this->audit->record('district.restored', $district, ['name' => $district->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('District restored.')]);

        return back();
    }

    /**
     * Delete a district that no school references.
     */
    public function destroy(District $district): RedirectResponse
    {
        if ($district->schools()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This district has schools. Archive it instead.'),
            ]);

            return back();
        }

        $district->delete();

        $this->audit->record('district.deleted', $district, ['name' => $district->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('District deleted.')]);

        return back();
    }
}
