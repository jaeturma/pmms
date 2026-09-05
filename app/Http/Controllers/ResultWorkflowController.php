<?php

namespace App\Http\Controllers;

use App\Enums\DelegationStatus;
use App\Enums\ManagementTeamMemberStatus;
use App\Enums\MatchStatus;
use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\Permission;
use App\Enums\ResultStatus;
use App\Models\Delegation;
use App\Models\Event;
use App\Models\EventResult;
use App\Models\Meet;
use App\Models\ResultAttachment;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\CompetitionAccessService;
use App\Services\FileUploadService;
use App\Services\MedalAwardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResultWorkflowController extends Controller
{
    public function __construct(
        private readonly FileUploadService $uploads,
        private readonly AuditLogger $audit,
        private readonly MedalAwardService $medalAwards,
    ) {}

    public function storeDirect(Request $request, ?EventResult $result = null): RedirectResponse
    {
        $data = $request->validate([
            'event_id' => ['required', 'integer', Rule::exists('events', 'id')],
            'gold_delegation_id' => ['required', 'integer', Rule::exists('delegations', 'id')],
            'silver_delegation_id' => ['nullable', 'integer', Rule::exists('delegations', 'id')],
            'bronze_delegation_id' => ['nullable', 'integer', Rule::exists('delegations', 'id')],
            ...collect(['gold', 'silver', 'bronze'])->flatMap(fn ($medal) => [
                $medal.'_mark' => ['nullable', 'string', 'max:60'],
                $medal.'_count' => ['sometimes', 'required', 'integer', 'min:0', 'max:65535'],
            ])->all(),
            'evidence' => [$result === null ? 'required' : 'nullable', File::types(['pdf', 'jpg', 'jpeg', 'png', 'webp'])->max((int) config('uploads.max_kb'))],
        ]);
        $meet = Meet::current();
        $event = Event::query()->findOrFail((int) $data['event_id']);
        $user = $request->user();
        $access = app(CompetitionAccessService::class);
        $authorized = $user->isAdmin() || $this->isCentralSecretariatForMeet($user, $meet->id)
            || ($this->isAssignedIctForEvent($user, $event, $meet->id) && $access->canAccessEvent($user, $event, $meet->id));
        abort_unless($authorized, 403);
        abort_unless($event->meets()->whereKey($meet->id)->exists(), 422, 'The selected Sports Event is not part of the current Meet.');

        if ($result !== null) {
            abort_unless($result->result_source === 'direct' && $result->meet_id === $meet->id && $result->event_id === $event->id, 422);
            abort_unless(in_array($result->status, [ResultStatus::Encoded, ResultStatus::Submitted, ResultStatus::Returned, ResultStatus::Reopened], true), 422, 'Reopen an accepted Result before editing it.');
        }

        $delegationIds = collect([$data['gold_delegation_id'], $data['silver_delegation_id'] ?? null, $data['bronze_delegation_id'] ?? null])->filter()->map(fn ($id) => (int) $id);
        abort_unless(Delegation::query()->where('meet_id', $meet->id)->whereIn('status', [DelegationStatus::Submitted->value, DelegationStatus::Approved->value])->whereKey($delegationIds)->count() === $delegationIds->unique()->count(), 422, 'Every medal Delegation must be active in the current Meet.');
        abort_if(EventResult::query()->real()->where('meet_id', $meet->id)->where('event_id', $event->id)
            ->when($result !== null, fn ($query) => $query->whereKeyNot($result->id))
            ->whereNotIn('status', [ResultStatus::Cancelled->value])->exists(), 422, 'An active Result already exists for this Sports Event. Return, cancel, or correct it instead.');

        $upload = null;
        $checksum = null;
        if ($request->hasFile('evidence')) {
            $upload = $this->uploads->store($request->file('evidence'), $user, 'evidence');
            $stream = Storage::disk($upload->disk)->readStream($upload->path);
            abort_if($stream === false, 500, 'The uploaded result evidence could not be verified.');
            $hash = hash_init('sha256');
            hash_update_stream($hash, $stream);
            fclose($stream);
            $checksum = hash_final($hash);
        }

        $result = DB::transaction(function () use ($meet, $event, $user, $delegationIds, $upload, $checksum, $data, $result): EventResult {
            $previous = null;
            if ($result !== null) {
                $result = EventResult::query()->lockForUpdate()->findOrFail($result->id);
                abort_unless(in_array($result->status, [ResultStatus::Encoded, ResultStatus::Submitted, ResultStatus::Returned, ResultStatus::Reopened], true), 422, 'Reopen an accepted Result before editing it.');
                $previous = $result->placements()->get()->toArray();
                $result->medalAwards()->delete();
                $result->placements()->delete();
            }
            $result ??= new EventResult([
                'meet_id' => $meet->id, 'event_id' => $event->id, 'result_source' => 'direct',
                'result_scope' => 'event', 'operational_remarks' => 'Direct Event Result submitted from delegation medal placements.',
            ]);
            $result->forceFill([
                'status' => ResultStatus::Submitted, 'encoded_by' => $user->id, 'encoded_at' => now(),
                'submitted_by' => $user->id, 'submitted_at' => now(),
                'version' => $result->exists ? $result->version + 1 : 1,
                'validated_by' => null, 'validated_at' => null, 'official_by' => null, 'official_at' => null,
            ])->save();
            foreach ($delegationIds as $index => $delegationId) {
                $medal = ['gold', 'silver', 'bronze'][$index];
                $result->placements()->create([
                    'delegation_id' => $delegationId, 'rank' => $index + 1, 'is_tie' => false,
                    'mark' => $data[$medal.'_mark'] ?? null, 'tally_quantity' => $data[$medal.'_count'] ?? 0,
                ]);
            }
            if ($upload !== null) {
                $result->attachments()->where('attachment_type', ResultAttachment::DIRECT_RESULT_EVIDENCE)->update(['is_current' => false]);
                $result->attachments()->create([
                    'file_upload_id' => $upload->id, 'attachment_type' => ResultAttachment::DIRECT_RESULT_EVIDENCE,
                    'result_version' => $result->version, 'checksum_sha256' => $checksum,
                    'uploaded_by' => $user->id, 'is_current' => true,
                ]);

            }
            $this->audit->record($previous === null ? 'direct_result.created' : 'direct_result.updated', $result, [...$this->context($result), 'superseded_placements' => $previous]);
            $this->audit->record('result.submitted', $result, $this->context($result));

            return $result;
        });

        return redirect()->route('results.index')->with('success', 'Direct Event Result submitted to the Event Secretariat.');
    }

    public function form(Request $request, EventResult $result): View
    {
        $this->authorizeSportDocument($request->user(), $result);
        abort_if($result->isLocked(), 403, 'Official results use the locked official record.');
        abort_if($result->match_id !== null && $result->tm_confirmed_at === null, 422, 'Tournament Manager confirmation is required before generating the Result Form.');

        $result->loadMissing([
            'meet', 'event.sport', 'event.sportCategory',
            'placements.entry.athlete.school.district', 'match', 'schedule.venue',
            'placements.teamEntry.delegation.school', 'placements.teamEntry.delegation.district',
            'placements.delegation.school', 'placements.delegation.district',
        ]);

        $result->forceFill([
            'form_generated_version' => $result->version,
            'form_generated_at' => now(),
        ])->save();

        $this->audit->record('result_form.generated', $result, $this->context($result));

        $schedule = $result->schedule;

        return view('results.form', [
            'result' => $result,
            'schedule' => $schedule,
            'reference' => $result->referenceNumber(),
            'generatedAt' => now(),
        ]);
    }

    public function upload(Request $request, EventResult $result): RedirectResponse
    {
        $this->authorizeSportDocument($request->user(), $result);
        abort_if($result->isLocked(), 403, 'Official result attachments are locked.');

        $validated = $request->validate([
            'file' => ['required', File::types(['pdf', 'jpg', 'jpeg', 'png'])->max((int) config('uploads.max_kb'))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $upload = $this->uploads->store($request->file('file'), $request->user(), 'file');
        $stream = Storage::disk($upload->disk)->readStream($upload->path);
        abort_if($stream === false, 500, 'The uploaded file could not be verified.');
        $hash = hash_init('sha256');
        hash_update_stream($hash, $stream);
        fclose($stream);
        $checksum = hash_final($hash);

        $attachment = DB::transaction(function () use ($result, $upload, $request, $validated, $checksum): ResultAttachment {
            $result->attachments()
                ->where('attachment_type', ResultAttachment::SIGNED_RESULT_FORM)
                ->where('is_current', true)
                ->update(['is_current' => false]);

            return $result->attachments()->create([
                'file_upload_id' => $upload->id,
                'attachment_type' => ResultAttachment::SIGNED_RESULT_FORM,
                'result_version' => $result->version,
                'checksum_sha256' => $checksum,
                'uploaded_by' => $request->user()->id,
                'is_current' => true,
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        $this->audit->record(
            $result->attachments()->where('attachment_type', ResultAttachment::SIGNED_RESULT_FORM)->count() > 1
                ? 'result_attachment.replaced' : 'result_attachment.uploaded',
            $result,
            [...$this->context($result), 'attachment_id' => $attachment->id],
        );

        return back()->with('success', 'Signed Result Form uploaded.');
    }

    public function uploadPhoto(Request $request, EventResult $result): RedirectResponse
    {
        $this->authorizeSportDocument($request->user(), $result);
        $validated = $request->validate([
            'photo' => ['required', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max((int) config('uploads.max_kb'))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $upload = $this->uploads->store($request->file('photo'), $request->user(), 'result_photo');
        $stream = Storage::disk($upload->disk)->readStream($upload->path);
        abort_if($stream === false, 500, 'The uploaded result photo could not be verified.');
        $hash = hash_init('sha256');
        hash_update_stream($hash, $stream);
        fclose($stream);
        $checksum = hash_final($hash);

        $attachment = DB::transaction(function () use ($result, $upload, $request, $validated, $checksum): ResultAttachment {
            $result->attachments()->where('attachment_type', ResultAttachment::RESULT_PHOTO)
                ->where('is_current', true)->update(['is_current' => false]);

            return $result->attachments()->create([
                'file_upload_id' => $upload->id,
                'attachment_type' => ResultAttachment::RESULT_PHOTO,
                'result_version' => $result->version,
                'checksum_sha256' => $checksum,
                'uploaded_by' => $request->user()->id,
                'is_current' => true,
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        $this->audit->record('result_photo.uploaded', $result, [
            ...$this->context($result), 'attachment_id' => $attachment->id,
        ]);

        return back()->with('success', 'Written result photo attached.');
    }

    public function photo(Request $request, EventResult $result, ResultAttachment $attachment): StreamedResponse|BinaryFileResponse
    {
        abort_unless($attachment->event_result_id === $result->id
            && $attachment->attachment_type === ResultAttachment::RESULT_PHOTO, 404);
        abort_unless($this->canManageSportDocument($request->user(), $result)
            || $this->isEventSecretariat($request->user(), $result)
            || $request->user()->hasPermission(Permission::ResultsOfficialize, $result->meet)
            || $result->status === ResultStatus::Official, 403);
        $attachment->loadMissing('file');

        return Storage::disk($attachment->file->disk)->response(
            $attachment->file->path,
            $attachment->file->original_name,
            ['Content-Type' => $attachment->file->mime_type, 'Cache-Control' => 'private, max-age=300'],
        );
    }

    public function download(Request $request, EventResult $result, ResultAttachment $attachment): StreamedResponse
    {
        abort_unless($attachment->event_result_id === $result->id, 404);
        abort_unless(
            $this->canManageSportDocument($request->user(), $result)
                || $this->isEventSecretariat($request->user(), $result)
                || $request->user()->hasPermission(Permission::ResultsOfficialize, $result->meet),
            403,
        );

        $attachment->loadMissing('file');
        $this->audit->record('result_attachment.viewed', $result, [
            ...$this->context($result), 'attachment_id' => $attachment->id,
        ]);

        return Storage::disk($attachment->file->disk)
            ->download($attachment->file->path, $attachment->file->original_name);
    }

    public function submit(Request $request, EventResult $result): RedirectResponse
    {
        $this->authorizeSportDocument($request->user(), $result);
        $canDeferIssues = $request->user()->isAdmin()
            || $this->isAssignedTournamentIct($request->user(), $result)
            || $this->isEventSecretariat($request->user(), $result);
        abort_unless(in_array($result->status, [ResultStatus::Encoded, ResultStatus::Returned, ResultStatus::Reopened], true), 422);

        if ($canDeferIssues) {
            return $this->acceptWithDeferredIssues($request, $result);
        }

        abort_if($result->match_id !== null && $result->tm_confirmed_at === null, 422, 'Tournament Manager confirmation is required before submission.');

        $urgentManualResult = $result->result_source === 'manual' && $result->event_schedule_id === null;
        if (! $urgentManualResult && (bool) config('pmms.results.signed_result_form_required') && $result->currentSignedForm() === null) {
            throw ValidationException::withMessages(['file' => 'A signed Result Form for the current result version is required.']);
        }

        abort_unless($result->placements()->exists(), 422, 'The result has no placements.');
        // Historical event-level records remain operable. Every newly
        // created result is match-linked by ResultController/service.
        if ($result->match_id !== null) {
            abort_unless($canDeferIssues || $result->event_schedule_id !== null, 422, 'The result must belong to a scheduled competition.');
            $this->competitionIsCompleted($result);
        }

        $action = $result->status === ResultStatus::Returned ? 'result.resubmitted' : 'result.submitted';
        $result->forceFill([
            'status' => ResultStatus::Submitted,
            'submitted_by' => $request->user()->id,
            'submitted_at' => now(),
            'returned_by' => null,
            'returned_at' => null,
            'return_reason' => null,
            'cancellation_requested_by' => null,
            'cancellation_requested_at' => null,
            'cancellation_request_reason' => null,
        ])->save();

        $this->audit->record($action, $result, $this->context($result));

        return back()->with('success', 'Result submitted to the Event Secretariat.');
    }

    private function acceptWithDeferredIssues(Request $request, EventResult $result): RedirectResponse
    {
        $result->loadMissing(['match.participantSlots', 'match.teamEntries', 'match.entries']);
        $issues = collect();

        if ($result->event_schedule_id === null) {
            $issues->push('Schedule is not linked.');
        }
        if ($result->match_id !== null && $result->tm_confirmed_at === null) {
            $issues->push('Tournament Manager confirmation is pending.');
        }
        if ($result->currentSignedForm() === null) {
            $issues->push('Signed Result Form is pending.');
        }

        DB::transaction(function () use ($request, $result, $issues): void {
            if ($this->reconstructMissingPlacements($result)) {
                $issues->push('Placements were reconstructed from match delegations. Athlete/team links may be completed later.');
            }

            if (! $result->placements()->exists()) {
                $issues->push('No placement is linked yet. Add the winning delegation later so its medal can be attributed.');
            }

            $now = now();
            $result->forceFill([
                'status' => ResultStatus::Submitted,
                'submitted_by' => $request->user()->id,
                'submitted_at' => $now,
                'operational_remarks' => $issues->unique()->implode("\n"),
                'returned_by' => null,
                'returned_at' => null,
                'return_reason' => null,
                'cancellation_requested_by' => null,
                'cancellation_requested_at' => null,
                'cancellation_request_reason' => null,
            ])->save();
        });

        $this->audit->record('result.accepted_with_deferred_issues', $result, [
            ...$this->context($result),
            'remarks' => $issues->unique()->values()->all(),
        ]);
        $this->audit->record('result.submitted', $result, $this->context($result));

        return back()->with('success', 'Result accepted and posted. Incomplete information is recorded in backend remarks for later resolution, and available medal placements now count in the tally.');
    }

    public function requestCancellation(Request $request, EventResult $result): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($this->isAssignedTournamentIct($user, $result), 403);
        abort_unless($result->status === ResultStatus::Submitted, 422, 'Only a submitted result may have cancellation requested.');
        abort_if($result->cancellation_requested_at !== null, 422, 'Cancellation has already been requested for this result.');
        $validated = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        $result->forceFill([
            'cancellation_requested_by' => $user->id,
            'cancellation_requested_at' => now(),
            'cancellation_request_reason' => $validated['reason'],
        ])->save();

        $this->audit->record('result.cancellation_requested', $result, [
            ...$this->context($result),
            'reason' => $validated['reason'],
        ]);

        return back()->with('success', 'Cancellation requested. The submitted result remains locked while the Event Secretariat reviews the problem.');
    }

    public function cancel(Request $request, EventResult $result): RedirectResponse
    {
        $this->authorizeEventSecretariat($request->user(), $result);
        $validated = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        DB::transaction(function () use ($result, $validated): void {
            $result = EventResult::query()->lockForUpdate()->findOrFail($result->id);
            abort_unless(in_array($result->status, [ResultStatus::Submitted, ResultStatus::Returned, ResultStatus::Validated], true)
                || ($result->result_source === 'direct' && $result->status === ResultStatus::Official), 422);
            $previousStatus = $result->status->value;
            $result->medalAwards()->delete();
            $result->forceFill([
                'status' => ResultStatus::Cancelled,
                'returned_by' => auth()->id(), 'returned_at' => now(), 'return_reason' => $validated['reason'],
                'validated_by' => null, 'validated_at' => null, 'official_by' => null, 'official_at' => null,
            ])->save();
            $this->audit->record('result.cancelled', $result, [
                ...$this->context($result), 'previous_status' => $previousStatus, 'reason' => $validated['reason'],
            ]);
        });

        return back()->with('success', 'Result cancelled. Its placements, attachments, and audit history were retained.');
    }

    public function confirmByTournamentManager(Request $request, EventResult $result): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless(in_array($result->status, [ResultStatus::Encoded, ResultStatus::Returned, ResultStatus::Reopened], true), 422);
        abort_unless($result->placements()->exists(), 422, 'The result has no placements to confirm.');
        abort_unless($this->isAssignedTournamentManager($user, $result), 403);

        $result->forceFill([
            'tm_confirmed_by' => $user->id,
            'tm_confirmed_at' => now(),
        ])->save();

        $this->audit->record('result.tm_confirmed', $result, $this->context($result));

        return back()->with('success', 'Sport-level result confirmed. The Result Form may now be generated.');
    }

    private function competitionIsCompleted(EventResult $result): void
    {
        $result->loadMissing('match');
        abort_unless(in_array($result->match?->status, [MatchStatus::Completed, MatchStatus::Walkover], true), 422, 'The competition must be completed before submission.');
    }

    public function returnResult(Request $request, EventResult $result): RedirectResponse
    {
        $this->authorizeEventSecretariat($request->user(), $result);
        abort_unless(in_array($result->status, [ResultStatus::Submitted, ResultStatus::Validated], true), 422);
        $validated = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        $result->forceFill([
            'status' => ResultStatus::Returned,
            'returned_by' => $request->user()->id,
            'returned_at' => now(),
            'return_reason' => $validated['reason'],
            'version' => $result->version + 1,
            'tm_confirmed_by' => null,
            'tm_confirmed_at' => null,
        ])->save();

        $this->audit->record('result.returned', $result, [...$this->context($result), 'reason' => $validated['reason']]);

        return back()->with('success', 'Result returned for correction.');
    }

    public function validateResult(Request $request, EventResult $result): RedirectResponse
    {
        $this->authorizeEventSecretariat($request->user(), $result);

        if ($result->status === ResultStatus::Validated) {
            return back()->with('success', 'Result was already validated. It remains pending acceptance.');
        }

        abort_unless($result->status === ResultStatus::Submitted, 422, 'Only a submitted result may be accepted.');

        DB::transaction(function () use ($request, $result): void {
            $locked = EventResult::query()->lockForUpdate()->with([
                'event.medalConfig', 'match.participantSlots', 'match.teamEntries', 'match.entries',
            ])->findOrFail($result->id);

            if ($locked->status === ResultStatus::Validated) {
                return;
            }
            abort_unless($locked->status === ResultStatus::Submitted, 422, 'Only a submitted result may be accepted.');
            abort_unless($locked->event !== null && $locked->event->meets()->whereKey($locked->meet_id)->exists(), 409, 'The Result Event is not linked to its Meet. Repair this integrity issue before acceptance.');

            if ($locked->result_source === 'direct') {
                $this->assertDirectResultIntegrity($locked);
            }

            $concerns = $this->operationalConcerns($locked);
            if ($this->reconstructMissingPlacements($locked)) {
                $concerns->push('Placements were reconstructed from match delegations. Athlete/team links may be completed later.');
            }
            if (! $locked->placements()->exists()) {
                $concerns->push('No placement is linked yet. Add the winning delegation later so its medal can be attributed.');
            }

            $config = $locked->event->resolvedMedalConfig();
            if ($locked->result_source !== 'direct' && $config->awards_medals && $config->isComplete() && $locked->placements()->exists()) {
                $this->medalAwards->synchronize($locked, $request->user());
            } elseif ($config->awards_medals && ! $config->isComplete()) {
                $concerns->push('Medal configuration is incomplete; the operational tally uses placements until the snapshot can be reconciled.');
            }

            $locked->forceFill([
                'status' => ResultStatus::Validated,
                'validated_by' => $request->user()->id,
                'validated_at' => now(),
                'operational_remarks' => $this->mergeOperationalRemarks($locked->operational_remarks, $concerns),
            ])->save();
            $this->audit->record('result.validated', $locked, [
                ...$this->context($locked),
                'operational_concerns' => $concerns->unique()->values()->all(),
            ]);
        });

        return back()->with('success', 'Result validated. It will remain internal until the Event Secretariat accepts it.');
    }

    private function operationalConcerns(EventResult $result): Collection
    {
        if ($result->result_source === 'direct') {
            return collect();
        }

        return collect([
            $result->event_schedule_id === null ? 'Schedule is not linked.' : null,
            $result->match_id !== null && $result->tm_confirmed_at === null ? 'Tournament Manager confirmation is pending.' : null,
            $result->currentSignedForm() === null ? 'Signed Result Form is pending.' : null,
        ])->filter()->values();
    }

    private function reconstructMissingPlacements(EventResult $result): bool
    {
        if ($result->placements()->exists() || $result->match === null) {
            return false;
        }

        $result->loadMissing(['match.participantSlots', 'match.teamEntries', 'match.entries']);
        $delegationIds = $result->match->participantSlots->where('is_selected', true)->pluck('delegation_id')
            ->merge($result->match->teamEntries->pluck('delegation_id'))
            ->merge($result->match->entries->pluck('delegation_id'))->unique()->values();
        $winnerId = $result->match->winner_delegation_id;
        $nextRank = 2;
        foreach ($delegationIds as $delegationId) {
            $result->placements()->create([
                'delegation_id' => $delegationId,
                'rank' => (int) $delegationId === (int) $winnerId ? 1 : $nextRank++,
                'mark' => collect([$result->match->manual_score_a, $result->match->manual_score_b])->filter()->implode(' - ') ?: null,
                'is_tie' => false,
            ]);
        }

        return $delegationIds->isNotEmpty();
    }

    private function mergeOperationalRemarks(?string $existing, Collection $concerns): ?string
    {
        return collect(preg_split('/\R/', (string) $existing) ?: [])
            ->merge($concerns)->map(fn ($remark) => trim((string) $remark))->filter()->unique()->implode("\n") ?: null;
    }

    public function makeOfficial(Request $request, EventResult $result): RedirectResponse
    {
        abort_unless($request->user()->hasPermission(Permission::ResultsOfficialize, $result->meet)
            || $request->user()->isAdmin() || $this->isEventSecretariat($request->user(), $result), 403);

        DB::transaction(function () use ($result, $request): void {
            $locked = EventResult::query()->lockForUpdate()->with(['event.medalConfig', 'placements', 'attachments'])->findOrFail($result->id);
            if ($locked->status === ResultStatus::Official) {
                if ($locked->result_source === 'direct') {
                    $this->assertDirectResultIntegrity($locked);
                    $this->medalAwards->synchronize($locked, $request->user());
                }

                return;
            }
            abort_unless($locked->status === ResultStatus::Validated
                || ($locked->result_source === 'direct' && $locked->status === ResultStatus::Submitted), 422,
                'Only a submitted direct Result or validated Result may be accepted.');
            abort_unless($locked->isFinalEventResult(), 422, 'Only a final Sports Event Result may be marked official. Completed Match Results remain operational and unofficial.');
            abort_unless($locked->submitted_at !== null && $locked->submitted_by !== null, 422, 'The final result must be submitted before officialization.');
            abort_if($locked->currentSignedForm() === null
                && $locked->result_source !== 'direct'
                && ! ($locked->result_source === 'manual' && $locked->event_schedule_id === null), 422, 'The signed Result Form is missing.');
            abort_unless($locked->placements()->exists(), 422, 'The final result has no placements.');

            if ($locked->result_source === 'direct') {
                $this->assertDirectResultIntegrity($locked);
                abort_if(EventResult::query()->where('meet_id', $locked->meet_id)->where('event_id', $locked->event_id)
                    ->where('status', ResultStatus::Official->value)->whereKeyNot($locked->id)->exists(), 422,
                    'Another accepted Result already owns this Sports Event medal allocation. Reopen or correct it first.');
            }

            $duplicateRanks = $locked->placements()->select('rank')->groupBy('rank')->havingRaw('COUNT(*) > 1')->pluck('rank');
            foreach ($duplicateRanks as $rank) {
                abort_unless($locked->placements()->where('rank', $rank)->where('is_tie', false)->doesntExist(), 422, 'Duplicate placements must be explicitly recorded as ties.');
            }

            $this->medalAwards->synchronize($locked, $request->user());
            $locked->forceFill([
                'status' => ResultStatus::Official,
                'official_by' => $request->user()->id,
                'official_at' => now(),
            ])->save();

            $this->audit->record('result.made_official', $locked, $this->context($locked));
        });

        return back()->with('success', 'Result accepted and posted to the official public medal tally.');
    }

    private function assertDirectResultIntegrity(EventResult $result): void
    {
        abort_unless($result->event !== null && $result->event->meets()->whereKey($result->meet_id)->exists(), 409,
            'The direct Result Event is not linked to its Meet.');
        abort_unless($result->attachments->where('is_current', true)->contains('attachment_type', ResultAttachment::DIRECT_RESULT_EVIDENCE), 422,
            'Direct Result evidence is required.');
        $placements = $result->placements->whereIn('rank', [1, 2, 3]);
        abort_unless($placements->isNotEmpty()
            && $placements->pluck('rank')->unique()->count() === $placements->count()
            && $placements->pluck('delegation_id')->filter()->count() === $placements->count(), 422,
            'Direct Results require at least one participant delegation with a unique place.');
        abort_unless(Delegation::query()->where('meet_id', $result->meet_id)
            ->whereKey($placements->pluck('delegation_id'))->count() === $placements->pluck('delegation_id')->unique()->count(), 409,
            'A medal Delegation no longer belongs to the Result Meet.');
    }

    public function reopen(Request $request, EventResult $result): RedirectResponse
    {
        abort_unless($request->user()->hasPermission(Permission::ResultsReopen, $result->meet), 403);
        abort_unless($result->status === ResultStatus::Official, 422);
        $validated = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        DB::transaction(function () use ($result, $validated): void {
            $result = EventResult::query()->lockForUpdate()->findOrFail($result->id);
            abort_unless($result->status === ResultStatus::Official, 422);
            $result->medalAwards()->delete();
            $result->forceFill([
                'status' => ResultStatus::Reopened,
                'version' => $result->version + 1,
                'tm_confirmed_by' => null, 'tm_confirmed_at' => null,
                'official_by' => null, 'official_at' => null,
            ])->save();
            $this->audit->record('result.reopened', $result, [...$this->context($result), 'reason' => $validated['reason']]);
        });

        return back()->with('success', 'Official result reopened for correction.');
    }

    public function recalculateMedalAwards(Request $request, EventResult $result): RedirectResponse
    {
        abort_unless($request->user()->hasPermission(Permission::ResultsOfficialize, $result->meet), 403);
        abort_unless($result->status === ResultStatus::Official, 422);
        $validated = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        DB::transaction(function () use ($result, $request): void {
            $locked = EventResult::query()->lockForUpdate()->findOrFail($result->id);
            abort_unless($locked->status === ResultStatus::Official, 422);
            $this->medalAwards->synchronize($locked, $request->user());
        });
        $this->audit->record('result.medal_awards_recalculated', $result, [
            ...$this->context($result),
            'reason' => $validated['reason'],
        ]);

        return back()->with('success', 'Official medal award quantities recalculated with an audit record.');
    }

    private function authorizeSportDocument(User $user, EventResult $result): void
    {
        abort_unless($this->canManageSportDocument($user, $result), 403);
    }

    private function canManageSportDocument(User $user, EventResult $result): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($this->isEventSecretariat($user, $result)) {
            return true;
        }

        $hasRole = $user->meetSportAssignments()
            ->where('status', MeetSportAssignmentStatus::Active)
            ->whereIn('role', [
                MeetSportAssignmentRole::TournamentManager,
                MeetSportAssignmentRole::AssistantTournamentManager,
                MeetSportAssignmentRole::TournamentSecretary,
                MeetSportAssignmentRole::TournamentICT,
            ])
            ->exists();

        return $hasRole && app(CompetitionAccessService::class)
            ->canAccessEvent($user, $result->event, $result->meet_id);
    }

    private function authorizeEventSecretariat(User $user, EventResult $result): void
    {
        abort_unless($user->isAdmin() || $this->isEventSecretariat($user, $result), 403);
    }

    private function isAssignedTournamentManager(User $user, EventResult $result): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $hasRole = $user->meetSportAssignments()
            ->where('status', MeetSportAssignmentStatus::Active)
            ->whereIn('role', [
                MeetSportAssignmentRole::TournamentManager,
                MeetSportAssignmentRole::AssistantTournamentManager,
                MeetSportAssignmentRole::TrackTournamentManager,
                MeetSportAssignmentRole::FieldTournamentManager,
                MeetSportAssignmentRole::BoysTournamentManager,
                MeetSportAssignmentRole::GirlsTournamentManager,
                MeetSportAssignmentRole::CategoryTournamentManager,
            ])
            ->exists();

        return $hasRole && app(CompetitionAccessService::class)
            ->canAccessEvent($user, $result->event, $result->meet_id);
    }

    private function isAssignedTournamentIct(User $user, EventResult $result): bool
    {
        return $user->meetSportAssignments()
            ->where('status', MeetSportAssignmentStatus::Active)
            ->where('role', MeetSportAssignmentRole::TournamentICT->value)
            ->whereHas('meetSport', fn ($meetSport) => $meetSport
                ->where('meet_id', $result->meet_id)
                ->where('sport_id', $result->event->sport_id))
            ->exists()
            && app(CompetitionAccessService::class)->canAccessEvent($user, $result->event, $result->meet_id);
    }

    private function isAssignedIctForEvent(User $user, Event $event, int $meetId): bool
    {
        return $user->meetSportAssignments()
            ->where('status', MeetSportAssignmentStatus::Active)
            ->where('role', MeetSportAssignmentRole::TournamentICT->value)
            ->whereHas('meetSport', fn ($meetSport) => $meetSport
                ->where('meet_id', $meetId)->where('sport_id', $event->sport_id))->exists();
    }

    private function isCentralSecretariatForMeet(User $user, int $meetId): bool
    {
        return $user->managementTeamMemberships()->where('status', ManagementTeamMemberStatus::Active)
            ->whereHas('managementTeam', fn ($team) => $team
                ->where('meet_id', $meetId)->where('source_code', 'EVENT_SECRETARIAT'))->exists();
    }

    private function isEventSecretariat(User $user, EventResult $result): bool
    {
        $isCentralSecretariat = $user->managementTeamMemberships()
            ->where('status', ManagementTeamMemberStatus::Active)
            ->whereHas('managementTeam', fn ($query) => $query
                ->where('meet_id', $result->meet_id)
                ->where('source_code', 'EVENT_SECRETARIAT'))
            ->exists();

        if ($isCentralSecretariat) {
            return true;
        }

        return $user->meetSportAssignments()
            ->where('status', MeetSportAssignmentStatus::Active)
            ->where('role', MeetSportAssignmentRole::TournamentSecretary->value)
            ->whereHas('meetSport', fn ($meetSport) => $meetSport
                ->where('meet_id', $result->meet_id)
                ->where('sport_id', $result->event->sport_id))
            ->exists()
            && app(CompetitionAccessService::class)->canAccessEvent($user, $result->event, $result->meet_id);
    }

    private function context(EventResult $result): array
    {
        $result->loadMissing('event.sport');

        return [
            'result_id' => $result->id,
            'reference' => $result->referenceNumber(),
            'sport' => $result->event->sport->name,
            'version' => $result->version,
            'status' => $result->status->value,
        ];
    }
}
