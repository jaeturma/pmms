<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SearchesAndPaginates;
use App\Models\Announcement;
use App\Models\Meet;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Internal announcement management — the whole module is manager-gated
 * by the route group. The public portal shows published rows only.
 */
class AnnouncementController extends Controller
{
    use SearchesAndPaginates;

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Searchable, paginated announcement registry.
     */
    public function index(Request $request): Response
    {
        $search = $this->searchTerm($request);

        $query = Announcement::query()
            ->with(['meet:id,name', 'author:id,name'])
            ->orderByDesc('id');

        $this->applySearch($query, $search, ['title']);

        return Inertia::render('announcements/index', [
            'announcements' => $query->paginate($this->registryPageSize)->withQueryString()
                ->through(fn (Announcement $announcement): array => [
                    'id' => $announcement->id,
                    'meet_id' => $announcement->meet_id,
                    'meet' => $announcement->meet?->name,
                    'title' => $announcement->title,
                    'body' => $announcement->body,
                    'is_published' => $announcement->is_published,
                    'published_at' => $announcement->published_at?->toDayDateTimeString(),
                    'author' => $announcement->author?->name,
                ]),
            'filters' => ['search' => $search],
            'meetOptions' => Meet::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Meet $meet): array => ['id' => $meet->id, 'label' => $meet->name]),
        ]);
    }

    /**
     * Create an announcement (starts unpublished).
     */
    public function store(Request $request): RedirectResponse
    {
        $announcement = new Announcement($this->validated($request));

        /** @var User $user */
        $user = $request->user();

        $announcement->forceFill(['created_by' => $user->id])->save();

        $this->audit->record('announcement.created', $announcement, $this->context($announcement));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Announcement created.')]);

        return back();
    }

    /**
     * Update an announcement's content.
     */
    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $announcement->update($this->validated($request));

        $this->audit->record('announcement.updated', $announcement, $this->context($announcement));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Announcement updated.')]);

        return back();
    }

    /**
     * Publish to the public portal.
     */
    public function publish(Announcement $announcement): RedirectResponse
    {
        if ($announcement->is_published) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This announcement is already published.'),
            ]);

            return back();
        }

        $announcement->forceFill([
            'is_published' => true,
            'published_at' => now(),
        ])->save();

        $this->audit->record('announcement.published', $announcement, $this->context($announcement));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Announcement published.')]);

        return back();
    }

    /**
     * Remove from the public portal, effective immediately.
     */
    public function unpublish(Announcement $announcement): RedirectResponse
    {
        if (! $announcement->is_published) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This announcement is not published.'),
            ]);

            return back();
        }

        $announcement->forceFill([
            'is_published' => false,
            'published_at' => null,
        ])->save();

        $this->audit->record('announcement.unpublished', $announcement, $this->context($announcement));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Announcement unpublished.')]);

        return back();
    }

    /**
     * Delete an announcement.
     */
    public function destroy(Announcement $announcement): RedirectResponse
    {
        $context = $this->context($announcement);

        $announcement->delete();

        $this->audit->record('announcement.deleted', $announcement, $context);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Announcement deleted.')]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'meet_id' => ['nullable', 'integer', Rule::exists('meets', 'id')],
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:2000'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function context(Announcement $announcement): array
    {
        $announcement->loadMissing('meet:id,name');

        return [
            'title' => $announcement->title,
            'meet' => $announcement->meet?->name,
        ];
    }
}
