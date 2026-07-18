<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SearchesAndPaginates;
use App\Http\Requests\SportRequest;
use App\Models\Sport;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SportController extends Controller
{
    use SearchesAndPaginates;

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Searchable, paginated sports catalog.
     */
    public function index(Request $request): Response
    {
        $search = $this->searchTerm($request);

        $query = Sport::query()
            ->withCount('events')
            ->orderBy('name');

        $this->applySearch($query, $search, ['name']);

        return Inertia::render('catalog/sports', [
            'sports' => $query->paginate($this->registryPageSize)->withQueryString()
                ->through(fn (Sport $sport): array => [
                    'id' => $sport->id,
                    'name' => $sport->name,
                    'active' => $sport->active,
                    'events_count' => $sport->events_count,
                ]),
            'filters' => ['search' => $search],
            'canManage' => Gate::allows('manage-meet-data'),
        ]);
    }

    /**
     * Create a sport.
     */
    public function store(SportRequest $request): RedirectResponse
    {
        $sport = Sport::create($request->validated());

        $this->audit->record('sport.created', $sport, ['name' => $sport->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sport created.')]);

        return back();
    }

    /**
     * Update a sport.
     */
    public function update(SportRequest $request, Sport $sport): RedirectResponse
    {
        $sport->update($request->validated());

        $this->audit->record('sport.updated', $sport, ['name' => $sport->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sport updated.')]);

        return back();
    }

    /**
     * Archive a sport instead of deleting it.
     */
    public function archive(Sport $sport): RedirectResponse
    {
        $sport->forceFill(['active' => false])->save();

        $this->audit->record('sport.archived', $sport, ['name' => $sport->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sport archived.')]);

        return back();
    }

    /**
     * Restore an archived sport.
     */
    public function restore(Sport $sport): RedirectResponse
    {
        $sport->forceFill(['active' => true])->save();

        $this->audit->record('sport.restored', $sport, ['name' => $sport->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sport restored.')]);

        return back();
    }

    /**
     * Delete a sport that no event references.
     */
    public function destroy(Sport $sport): RedirectResponse
    {
        if ($sport->events()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This sport has events. Archive it instead.'),
            ]);

            return back();
        }

        $sport->delete();

        $this->audit->record('sport.deleted', $sport, ['name' => $sport->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sport deleted.')]);

        return back();
    }
}
