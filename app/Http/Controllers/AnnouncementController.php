<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SearchesAndPaginates;
use App\Models\Announcement;
use App\Models\Meet;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        abort_unless($request->user()->canViewAnnouncements(), 403);
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
            'canManage' => $request->user()->canManageAnnouncements(),
        ]);
    }

    /**
     * Create an announcement (starts unpublished).
     */
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->canManageAnnouncements(), 403);
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
        abort_unless($request->user()->canManageAnnouncements(), 403);
        $announcement->update($this->validated($request));

        $this->audit->record('announcement.updated', $announcement, $this->context($announcement));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Announcement updated.')]);

        return back();
    }

    /**
     * Publish to the public portal.
     */
    public function publish(Request $request, Announcement $announcement): RedirectResponse
    {
        abort_unless($request->user()->canManageAnnouncements(), 403);
        if ($announcement->is_published) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This announcement is already published.'),
            ]);

            return back();
        }

        $announcement->forceFill([
            'is_published' => true,
            'status' => 'published',
            'published_at' => now(),
        ])->save();

        $this->audit->record('announcement.published', $announcement, $this->context($announcement));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Announcement published.')]);

        return back();
    }

    /**
     * Remove from the public portal, effective immediately.
     */
    public function unpublish(Request $request, Announcement $announcement): RedirectResponse
    {
        abort_unless($request->user()->canManageAnnouncements(), 403);
        if (! $announcement->is_published) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This announcement is not published.'),
            ]);

            return back();
        }

        $announcement->forceFill([
            'is_published' => false,
            'status' => 'draft',
            'published_at' => null,
        ])->save();

        $this->audit->record('announcement.unpublished', $announcement, $this->context($announcement));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Announcement unpublished.')]);

        return back();
    }

    /**
     * Delete an announcement.
     */
    public function destroy(Request $request, Announcement $announcement): RedirectResponse
    {
        abort_unless($request->user()->canManageAnnouncements(), 403);
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
        $validated = $request->validate([
            'tied_to_meet' => ['boolean'],
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:2000'],
            'priority' => ['nullable', 'in:normal,important,urgent'],
            'audience' => ['nullable', 'in:public,all_users,coaches,tournament_personnel,delegation,internal'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        return [
            'meet_id' => $request->boolean('tied_to_meet') ? Meet::current()->id : null,
            'title' => $validated['title'],
            'body' => $validated['body'],
            'priority' => $validated['priority'] ?? 'normal',
            'audience' => $validated['audience'] ?? 'public',
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
        ];
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
