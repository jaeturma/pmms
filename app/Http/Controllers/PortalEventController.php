<?php

namespace App\Http\Controllers;

use App\Enums\ResultStatus;
use App\Enums\SportPortalSlug;
use App\Models\Event;
use App\Models\EventResult;
use App\Models\Meet;
use App\Models\ResultAttachment;
use App\Services\PublicEventResults;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortalEventController extends Controller
{
    public function show(Request $request, Event $event, PublicEventResults $publicResults): Response
    {
        $meet = $request->filled('meet_id')
            ? Meet::query()->published()->findOrFail($request->integer('meet_id'))
            : Meet::query()->published()->active()->first();
        $results = $meet === null ? collect() : $publicResults->visible(EventResult::query())
            ->where('status', ResultStatus::Official->value)
            ->where('meet_id', $meet->id)->where('event_id', $event->id)
            ->whereHas('event.meets', fn ($query) => $query->whereKey($meet->id))
            ->with(['event.sport', 'placements.medalAward', 'attachments.file'])
            ->orderByDesc('id')->get()->map(fn (EventResult $result) => $publicResults->row($result));
        // Keep the recorded places and marks. Different sports' scores and
        // times cannot be added together or ranked with a generic formula.
        $medalResults = $results->filter(fn (array $result) => collect($result['placements'])->contains(fn (array $placement) => $placement['medal'] !== null));
        $standings = $results->reject(fn (array $result) => $medalResults->contains('id', $result['id']))
            ->flatMap(fn (array $result) => collect($result['placements'])->map(fn (array $placement) => [
                ...$placement, 'result_id' => $result['id'], 'status_label' => $result['status_label'],
            ]))->sortBy('rank')->values();
        $slug = SportPortalSlug::fromSportName($event->sport->name);

        return Inertia::render('portal/sport-event', [
            'event' => ['id' => $event->id, 'name' => $event->name, 'sport' => $event->sport->name,
                'category' => $event->gender->label().' · '.$event->age_division->label(),
                'sport_url' => $slug === null ? '/sports-directory' : route('public.sport-portal', $slug->value)],
            'meet' => $meet === null ? null : ['id' => $meet->id, 'name' => $meet->name],
            'standings' => $standings, 'results' => $medalResults->values(),
        ]);
    }

    public function document(EventResult $result, ResultAttachment $attachment, PublicEventResults $publicResults): StreamedResponse
    {
        abort_unless($result->status === ResultStatus::Official, 404);
        abort_unless($publicResults->withMedals(EventResult::query())->whereKey($result->id)
            ->whereHas('meet', fn ($query) => $query->published())
            ->whereHas('event.meets', fn ($query) => $query->whereKey($result->meet_id))->exists(), 404);
        abort_unless($attachment->event_result_id === $result->id && $attachment->is_current
            && ($attachment->result_version === $result->version || $attachment->attachment_type === ResultAttachment::DIRECT_RESULT_EVIDENCE), 404);
        $file = $attachment->file;
        abort_unless($file !== null && in_array($file->mime_type, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true), 404);

        return Storage::disk($file->disk)->response($file->path, $file->original_name, [
            'Content-Type' => $file->mime_type, 'Cache-Control' => 'no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
