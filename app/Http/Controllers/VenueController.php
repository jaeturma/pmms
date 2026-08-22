<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SearchesAndPaginates;
use App\Http\Requests\VenueRequest;
use App\Models\District;
use App\Models\Venue;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class VenueController extends Controller
{
    use SearchesAndPaginates;

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Searchable, paginated venue registry.
     */
    public function index(Request $request): Response
    {
        $search = $this->searchTerm($request);

        $query = Venue::query()
            ->with(['municipality:id,name', 'competitionAreas:id,venue_id,name',
                'meetSportAssignments.meetSport.sport:id,name',
                'gameCoordinatorAssignments.person:id,full_name',
                'gameCoordinatorAssignments.meetSport.sport:id,name'])
            ->orderBy('name');

        $this->applySearch($query, $search, ['name', 'address']);

        return Inertia::render('registry/venues', [
            'venues' => $query->paginate($this->registryPageSize)->withQueryString()
                ->through(fn (Venue $venue): array => [
                    'id' => $venue->id,
                    'name' => $venue->name,
                    'address' => $venue->address,
                    'short_name' => $venue->short_name,
                    'municipality_id' => $venue->municipality_id,
                    'municipality' => $venue->municipality?->name,
                    'latitude' => $venue->latitude,
                    'longitude' => $venue->longitude,
                    'gps_location' => $venue->latitude !== null && $venue->longitude !== null
                        ? $venue->latitude.', '.$venue->longitude
                        : null,
                    'public_notes' => $venue->public_notes,
                    'internal_notes' => $venue->internal_notes,
                    'readiness_status' => $venue->readiness_status,
                    'sports' => $venue->meetSportAssignments->pluck('meetSport.sport.name')->filter()->unique()->values(),
                    'competition_areas' => $venue->competitionAreas->pluck('name')->values(),
                    'game_coordinators' => $venue->gameCoordinatorAssignments->map(fn ($assignment): array => [
                        'id' => $assignment->id,
                        'name' => $assignment->person->full_name,
                        'contact_number' => $assignment->source_contact_text,
                        'sport' => $assignment->meetSport?->sport?->name,
                        'is_lead' => $assignment->is_lead,
                    ])->values(),
                    'notes' => $venue->notes,
                    'active' => $venue->active,
                ]),
            'filters' => ['search' => $search],
            'municipalityOptions' => District::query()->where('active', true)->orderBy('name')->get(['id', 'name'])
                ->map(fn (District $district): array => ['id' => $district->id, 'label' => $district->name]),
            'canManage' => Gate::allows('manage-meet-data'),
        ]);
    }

    /**
     * Create a venue.
     */
    public function store(VenueRequest $request): RedirectResponse
    {
        $venue = Venue::create($request->venueData());

        $this->audit->record('venue.created', $venue, ['name' => $venue->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Venue created.')]);

        return back();
    }

    /**
     * Update a venue.
     */
    public function update(VenueRequest $request, Venue $venue): RedirectResponse
    {
        $venue->update($request->venueData());

        $this->audit->record('venue.updated', $venue, ['name' => $venue->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Venue updated.')]);

        return back();
    }

    /**
     * Archive a venue instead of deleting it.
     */
    public function archive(Venue $venue): RedirectResponse
    {
        $venue->forceFill(['active' => false])->save();

        $this->audit->record('venue.archived', $venue, ['name' => $venue->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Venue archived.')]);

        return back();
    }

    /**
     * Restore an archived venue.
     */
    public function restore(Venue $venue): RedirectResponse
    {
        $venue->forceFill(['active' => true])->save();

        $this->audit->record('venue.restored', $venue, ['name' => $venue->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Venue restored.')]);

        return back();
    }

    /**
     * Delete a venue that no schedule references.
     */
    public function destroy(Venue $venue): RedirectResponse
    {
        if ($venue->isInUse()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This venue is scheduled for events. Archive it instead.'),
            ]);

            return back();
        }

        $venue->delete();

        $this->audit->record('venue.deleted', $venue, ['name' => $venue->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Venue deleted.')]);

        return back();
    }
}
