<?php

namespace App\Http\Controllers;

use App\Enums\MeetSportAssignmentRole;
use App\Models\GalleryItem;
use App\Models\MeetSport;
use App\Services\AuditLogger;
use App\Services\FileUploadService;
use App\Services\GalleryImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class GalleryController extends Controller
{
    public function __construct(private readonly FileUploadService $uploads, private readonly GalleryImageService $images, private readonly AuditLogger $audit) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->canAccessContentManagement(), 403);
        $editor = $request->user()->canManageEditorialContent();
        $sportIds = $this->assignedMeetSportIds($request);
        $query = GalleryItem::query()->with(['meetSport.sport:id,name', 'event:id,name', 'uploader:id,name', 'file:id'])
            ->when(! $editor, fn ($scope) => $scope->whereIn('meet_sport_id', $sportIds)->where('uploaded_by', $request->user()->id))
            ->when($request->filled('status'), fn ($scope) => $scope->where('status', $request->string('status')))
            ->when($request->filled('date'), fn ($scope) => $scope->whereDate('capture_date', $request->date('date')))
            ->latest();

        return Inertia::render('content/gallery', [
            'items' => $query->paginate(24)->withQueryString()->through(fn (GalleryItem $item) => [
                'id' => $item->id, 'title' => $item->title, 'caption' => $item->caption, 'capture_date' => $item->capture_date->toDateString(),
                'status' => $item->status, 'is_featured' => $item->is_featured, 'sport' => $item->meetSport?->sport?->name,
                'event' => $item->event?->name, 'uploader' => $item->uploader?->name, 'image_url' => route('content.gallery.image', $item),
                'rejection_reason' => $item->rejection_reason,
            ]),
            'meetSports' => MeetSport::query()->with('sport:id,name')->whereIn('id', $editor ? MeetSport::query()->pluck('id') : $sportIds)->get()->map(fn (MeetSport $scope) => ['id' => $scope->id, 'name' => $scope->sport->name]),
            'canReview' => $editor,
            'limits' => config('pmms.gallery'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->canUploadGalleryCandidates(), 403);
        $data = $request->validate([
            'meet_sport_id' => ['required', 'integer', 'exists:meet_sports,id'], 'sport_event_id' => ['nullable', 'integer', 'exists:events,id'],
            'capture_date' => ['required', 'date'], 'caption' => ['nullable', 'string', 'max:1000'],
            'photos' => ['required', 'array', 'min:1', 'max:20'], 'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('pmms.gallery.max_upload_kb')],
        ]);
        $meetSport = MeetSport::query()->with('meet')->findOrFail($data['meet_sport_id']);
        $this->authorizeScope($request, $meetSport);
        if (isset($data['sport_event_id'])) {
            abort_unless($meetSport->sport->events()->whereKey($data['sport_event_id'])->exists(), 403);
        }

        $existing = GalleryItem::query()->where('meet_sport_id', $meetSport->id)->whereDate('capture_date', $data['capture_date'])->count();
        if (config('pmms.gallery.strict_candidate_limit') && $existing + count($request->file('photos')) > config('pmms.gallery.daily_candidate_max')) {
            throw ValidationException::withMessages(['photos' => 'The configured daily candidate limit would be exceeded.']);
        }

        foreach ($request->file('photos') as $photo) {
            $hash = hash_file('sha256', $photo->getPathname());
            if (GalleryItem::query()->where('meet_id', $meetSport->meet_id)->where('file_hash', $hash)->exists()) {
                throw ValidationException::withMessages(['photos' => 'One of these photos has already been uploaded for this meet.']);
            }
            $optimized = $this->images->optimize($photo);
            try {
                $upload = $this->uploads->store($optimized, $request->user(), 'photos');
            } finally {
                if ($optimized !== $photo) {
                    @unlink($optimized->getPathname());
                }
            }
            $item = GalleryItem::query()->create([
                'meet_id' => $meetSport->meet_id, 'meet_sport_id' => $meetSport->id, 'sport_event_id' => $data['sport_event_id'] ?? null,
                'file_upload_id' => $upload->id, 'uploaded_by' => $request->user()->id, 'caption' => $data['caption'] ?? null,
                'capture_date' => $data['capture_date'], 'status' => 'submitted', 'submitted_at' => now(), 'file_hash' => $hash,
            ]);
            $this->audit->record('gallery.candidate_uploaded', $item, ['meet_sport_id' => $meetSport->id]);
            $this->audit->record('gallery.submitted', $item, ['meet_sport_id' => $meetSport->id]);
        }

        return back();
    }

    public function review(Request $request, GalleryItem $galleryItem): RedirectResponse
    {
        abort_unless($request->user()->canManageEditorialContent(), 403);
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected', 'archived'])], 'title' => ['nullable', 'string', 'max:180'],
            'caption' => ['nullable', 'string', 'max:1000'], 'description' => ['nullable', 'string'], 'rejection_reason' => ['nullable', 'string', 'max:1000'],
            'is_featured' => ['boolean'], 'display_order' => ['nullable', 'integer', 'min:0'],
        ]);
        $galleryItem->forceFill([
            ...$data, 'reviewed_by' => $request->user()->id,
            'rejected_at' => $data['status'] === 'rejected' ? now() : null,
        ])->save();
        $this->audit->record("gallery.{$data['status']}", $galleryItem, ['meet_sport_id' => $galleryItem->meet_sport_id]);

        return back();
    }

    public function publish(Request $request): RedirectResponse
    {
        abort_unless($request->user()->canManageEditorialContent(), 403);
        $ids = $request->validate(['ids' => ['required', 'array', 'min:1'], 'ids.*' => ['integer', 'exists:gallery_items,id']])['ids'];

        DB::transaction(function () use ($ids, $request): void {
            $items = GalleryItem::query()->whereIn('id', $ids)->lockForUpdate()->get();
            foreach ($items->groupBy(fn (GalleryItem $item) => $item->meet_sport_id.'|'.$item->capture_date->toDateString()) as $group) {
                $first = $group->first();
                $published = GalleryItem::query()->where('meet_id', $first->meet_id)->whereDate('capture_date', $first->capture_date)
                    ->when(config('pmms.gallery.public_limit_scope') === 'sport', fn ($scope) => $scope->where('meet_sport_id', $first->meet_sport_id))
                    ->where('status', 'published')->lockForUpdate()->count();
                if ($published + $group->count() > config('pmms.gallery.daily_public_max')) {
                    throw ValidationException::withMessages(['ids' => 'The configured public gallery limit for this sport and day would be exceeded.']);
                }
            }
            foreach ($items as $item) {
                abort_unless(in_array($item->status, ['submitted', 'approved'], true), 422);
                $item->forceFill(['status' => 'published', 'published_at' => now(), 'reviewed_by' => $request->user()->id])->save();
                $this->audit->record('gallery.published', $item, ['meet_sport_id' => $item->meet_sport_id]);
            }
        });

        return back();
    }

    public function unpublish(Request $request, GalleryItem $galleryItem): RedirectResponse
    {
        abort_unless($request->user()->canManageEditorialContent(), 403);
        abort_unless($galleryItem->status === 'published', 422);
        $galleryItem->forceFill(['status' => 'approved', 'published_at' => null, 'reviewed_by' => $request->user()->id])->save();
        $this->audit->record('gallery.unpublished', $galleryItem, ['meet_sport_id' => $galleryItem->meet_sport_id]);

        return back();
    }

    public function image(Request $request, GalleryItem $galleryItem)
    {
        abort_unless($galleryItem->status === 'published' || ($request->user()?->canAccessContentManagement() ?? false), 404);
        $file = $galleryItem->file;

        return Storage::disk($file->disk)->response($file->path, $file->original_name, ['Cache-Control' => 'private, max-age=3600']);
    }

    public function publicImage(GalleryItem $galleryItem)
    {
        abort_unless($galleryItem->status === 'published', 404);
        $file = $galleryItem->file;

        return Storage::disk($file->disk)->response($file->path, $file->original_name, ['Cache-Control' => 'public, max-age=86400']);
    }

    private function assignedMeetSportIds(Request $request): array
    {
        return $request->user()->meetSportAssignments()->where('status', 'active')->whereIn('role', [MeetSportAssignmentRole::TournamentICT->value, MeetSportAssignmentRole::TournamentSecretary->value])->pluck('meet_sport_id')->all();
    }

    private function authorizeScope(Request $request, MeetSport $meetSport): void
    {
        if ($request->user()->canManageEditorialContent()) {
            return;
        }
        abort_unless(in_array($meetSport->id, $this->assignedMeetSportIds($request), true), 403);
    }
}
