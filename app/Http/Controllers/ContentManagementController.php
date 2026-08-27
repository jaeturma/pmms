<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\FaqItem;
use App\Models\GalleryItem;
use App\Models\NewsItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContentManagementController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->canAccessContentManagement(), 403);

        return Inertia::render('content/index', [
            'canManageEditorial' => $request->user()->canManageEditorialContent(),
            'counts' => [
                'news_draft' => NewsItem::query()->where('status', 'draft')->count(),
                'news_published' => NewsItem::query()->where('status', 'published')->count(),
                'announcements_active' => Announcement::query()->published()->count(),
                'gallery_pending' => GalleryItem::query()->where('status', 'submitted')->count(),
                'gallery_published_today' => GalleryItem::query()->where('status', 'published')->whereDate('published_at', today())->count(),
                'faq_published' => FaqItem::query()->where('status', 'published')->count(),
                'faq_draft' => FaqItem::query()->where('status', 'draft')->count(),
            ],
        ]);
    }
}
