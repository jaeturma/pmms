<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Controllers\Concerns\SearchesAndPaginates;
use App\Http\Requests\SportRequest;
use App\Models\Sport;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

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
            ->with(['technicalOfficials:id,name,email', 'tournamentManager:id,name,email'])
            ->orderBy('name');

        $this->applySearch($query, $search, ['name']);

        return Inertia::render('catalog/sports', [
            'sports' => $query->paginate($this->registryPageSize)->withQueryString()
                ->through(fn (Sport $sport): array => [
                    'id' => $sport->id,
                    'name' => $sport->name,
                    'active' => $sport->active,
                    'events_count' => $sport->events_count,
                    'technical_officials' => $sport->technicalOfficials
                        ->map(fn (User $official): array => [
                            'id' => $official->id,
                            'name' => $official->name,
                        ])
                        ->values(),
                    'tournament_manager' => $sport->tournamentManager === null ? null : [
                        'id' => $sport->tournamentManager->id,
                        'name' => $sport->tournamentManager->name,
                    ],
                ]),
            'filters' => ['search' => $search],
            'technicalOfficialOptions' => User::query()
                ->where('role', UserRole::TechnicalOfficial->value)
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'tournamentManagerOptions' => User::query()
                ->where('role', UserRole::TournamentManager->value)
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
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
     * Replace the Technical Officials assigned to operate live scoring for
     * this sport. Same full-replace pattern as
     * DelegationController::syncOfficers.
     */
    public function syncTechnicalOfficials(Request $request, Sport $sport): RedirectResponse
    {
        Gate::authorize('manage-meet-data');

        $validated = $request->validate([
            'user_ids' => ['array'],
            'user_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where('role', UserRole::TechnicalOfficial->value),
            ],
        ]);

        /** @var array<int, int> $userIds */
        $userIds = $validated['user_ids'] ?? [];

        $sport->technicalOfficials()->sync($userIds);

        $this->audit->record('sport.technical_officials_updated', $sport, [
            'name' => $sport->name,
            'official_count' => count($userIds),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Technical officials updated.')]);

        return back();
    }

    /**
     * Assign (or clear) this sport's single Tournament Manager — a
     * nullable 1:1, unlike `syncTechnicalOfficials()`'s full-replace
     * many-to-many, since exactly one TM per sport is the real constraint
     * (see the migration's own docblock).
     */
    public function syncTournamentManager(Request $request, Sport $sport): RedirectResponse
    {
        Gate::authorize('manage-meet-data');

        $validated = $request->validate([
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('role', UserRole::TournamentManager->value),
            ],
        ]);

        $sport->forceFill(['tournament_manager_id' => $validated['user_id'] ?? null])->save();

        $this->audit->record('sport.tournament_manager_assigned', $sport, [
            'name' => $sport->name,
            'tournament_manager_id' => $sport->tournament_manager_id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tournament manager updated.')]);

        return back();
    }

    /**
     * Serve a sport's public photo — same guest-accessible, policy-free
     * "just stream the file" shape as `DistrictController::logo()`. No
     * upload path exists yet to actually set `photo_upload_id` (out of
     * this WP's scope — admin-side, not the public portal), so this
     * currently 404s for every sport; it's here so the public mini
     * portal's `photoUrl()` has somewhere real to point once one does.
     */
    public function photo(Sport $sport): HttpResponse
    {
        $upload = $sport->photo;

        abort_if($upload === null, 404);

        return Storage::disk($upload->disk)->response($upload->path, $upload->original_name);
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
