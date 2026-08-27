<?php

namespace App\Http\Controllers;

use App\Models\FaqItem;
use App\Models\Meet;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): Response
    {
        $this->authorizeEditor($request);

        return Inertia::render('content/faq', ['items' => FaqItem::query()->orderBy('display_order')->paginate(30)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeEditor($request);
        $item = new FaqItem($this->validated($request));
        $item->forceFill(['meet_id' => Meet::current()->id, 'created_by' => $request->user()->id])->save();
        $this->audit->record('faq.created', $item, ['question' => $item->question]);

        return back();
    }

    public function update(Request $request, FaqItem $faqItem): RedirectResponse
    {
        $this->authorizeEditor($request);
        $faqItem->update($this->validated($request));
        $this->audit->record('faq.updated', $faqItem, ['question' => $faqItem->question]);

        return back();
    }

    public function status(Request $request, FaqItem $faqItem): RedirectResponse
    {
        $this->authorizeEditor($request);
        $data = $request->validate(['status' => ['required', Rule::in(['draft', 'published', 'archived'])]]);
        $faqItem->forceFill(['status' => $data['status'], 'published_at' => $data['status'] === 'published' ? now() : null])->save();
        $this->audit->record("faq.{$data['status']}", $faqItem, ['question' => $faqItem->question]);

        return back();
    }

    public function destroy(Request $request, FaqItem $faqItem): RedirectResponse
    {
        $this->authorizeEditor($request);
        abort_if($faqItem->status === 'published', 422, 'Published FAQs must be archived before deletion.');
        $faqItem->delete();

        return back();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'question' => ['required', 'string', 'max:255'], 'answer' => ['required', 'string'],
            'category' => ['required', 'string', 'max:80'], 'display_order' => ['integer', 'min:0'], 'is_featured' => ['boolean'],
        ]);
    }

    private function authorizeEditor(Request $request): void
    {
        abort_unless($request->user()->canManageEditorialContent(), 403);
    }
}
