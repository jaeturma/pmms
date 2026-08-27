<?php

namespace App\Http\Controllers;

use App\Models\Meet;
use App\Models\NewsItem;
use App\Services\AuditLogger;
use App\Services\FileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class NewsController extends Controller
{
    public function __construct(private readonly AuditLogger $audit, private readonly FileUploadService $uploads) {}

    public function index(Request $request): Response
    {
        $this->authorizeEditor($request);

        return Inertia::render('content/news', [
            'items' => NewsItem::query()->with('author:id,name')->latest()->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeEditor($request);
        $data = $this->validated($request);
        $item = new NewsItem($data);
        $item->forceFill(['author_id' => $request->user()->id, 'meet_id' => Meet::current()->id])->save();
        $this->storeFeaturedImage($request, $item);
        $this->audit->record('news.created', $item, ['title' => $item->title]);

        return back();
    }

    public function update(Request $request, NewsItem $newsItem): RedirectResponse
    {
        $this->authorizeEditor($request);
        $newsItem->update($this->validated($request, $newsItem));
        $this->storeFeaturedImage($request, $newsItem);
        $this->audit->record('news.updated', $newsItem, ['title' => $newsItem->title]);

        return back();
    }

    public function status(Request $request, NewsItem $newsItem): RedirectResponse
    {
        $this->authorizeEditor($request);
        $data = $request->validate(['status' => ['required', Rule::in(['draft', 'scheduled', 'published', 'archived'])], 'published_at' => ['nullable', 'date']]);
        $status = $data['status'];
        $newsItem->forceFill([
            'status' => $status,
            'published_at' => $status === 'published' ? ($data['published_at'] ?? now()) : ($status === 'scheduled' ? $data['published_at'] : null),
        ])->save();
        $action = match ($status) {
            'draft' => 'news.unpublished',
            default => "news.{$status}",
        };
        $this->audit->record($action, $newsItem, ['title' => $newsItem->title]);

        return back();
    }

    public function destroy(Request $request, NewsItem $newsItem): RedirectResponse
    {
        $this->authorizeEditor($request);
        abort_if($newsItem->status === 'published', 422, 'Published news must be archived before deletion.');
        $this->audit->record('news.deleted', $newsItem, ['title' => $newsItem->title]);
        $newsItem->delete();

        return back();
    }

    private function validated(Request $request, ?NewsItem $item = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'], 'slug' => ['nullable', 'string', 'max:200', Rule::unique('news_items')->ignore($item)],
            'summary' => ['nullable', 'string', 'max:500'], 'body' => ['required', 'string'], 'is_featured' => ['boolean'],
            'featured_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);
        $data['slug'] = $data['slug'] ?: Str::slug($data['title']).'-'.Str::lower(Str::random(5));

        return $data;
    }

    public function publicImage(NewsItem $newsItem)
    {
        abort_unless($newsItem->status === 'published' && $newsItem->published_at?->isPast() && $newsItem->featuredImage, 404);
        $file = $newsItem->featuredImage;

        return Storage::disk($file->disk)->response($file->path, $file->original_name, ['Cache-Control' => 'public, max-age=86400']);
    }

    private function storeFeaturedImage(Request $request, NewsItem $item): void
    {
        if (! $request->hasFile('featured_image')) {
            return;
        }
        $upload = $this->uploads->store($request->file('featured_image'), $request->user(), 'featured_image');
        $item->forceFill(['featured_image_upload_id' => $upload->id])->save();
    }

    private function authorizeEditor(Request $request): void
    {
        abort_unless($request->user()->canManageEditorialContent(), 403);
    }
}
