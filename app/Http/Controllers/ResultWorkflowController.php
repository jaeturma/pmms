<?php

namespace App\Http\Controllers;

use App\Enums\ManagementTeamMemberStatus;
use App\Enums\MatchStatus;
use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\Permission;
use App\Enums\ResultStatus;
use App\Models\EventResult;
use App\Models\ResultAttachment;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\CompetitionAccessService;
use App\Services\FileUploadService;
use App\Services\MedalAwardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResultWorkflowController extends Controller
{
    public function __construct(
        private readonly FileUploadService $uploads,
        private readonly AuditLogger $audit,
        private readonly MedalAwardService $medalAwards,
    ) {}

    public function form(Request $request, EventResult $result): View
    {
        $this->authorizeSportDocument($request->user(), $result);
        abort_if($result->isLocked(), 403, 'Official results use the locked official record.');
        abort_if($result->match_id !== null && $result->tm_confirmed_at === null, 422, 'Tournament Manager confirmation is required before generating the Result Form.');

        $result->loadMissing([
            'meet', 'event.sport', 'event.sportCategory',
            'placements.entry.athlete.school.district', 'match', 'schedule.venue',
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
        abort_unless(in_array($result->status, [ResultStatus::Encoded, ResultStatus::Returned, ResultStatus::Reopened], true), 422);
        abort_if($result->match_id !== null && $result->tm_confirmed_at === null, 422, 'Tournament Manager confirmation is required before submission.');

        if ((bool) config('pmms.results.signed_result_form_required') && $result->currentSignedForm() === null) {
            throw ValidationException::withMessages(['file' => 'A signed Result Form for the current result version is required.']);
        }

        abort_unless($result->placements()->exists(), 422, 'The result has no placements.');
        // Historical event-level records remain operable. Every newly
        // created result is match-linked by ResultController/service.
        if ($result->match_id !== null) {
            abort_unless($result->event_schedule_id !== null, 422, 'The result must belong to a scheduled competition.');
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
        abort_unless($result->status === ResultStatus::Submitted, 422);

        $result->forceFill([
            'status' => ResultStatus::Validated,
            'validated_by' => $request->user()->id,
            'validated_at' => now(),
        ])->save();
        $this->audit->record('result.validated', $result, $this->context($result));

        return back()->with('success', 'Result validated. It is ready to be made official.');
    }

    public function makeOfficial(Request $request, EventResult $result): RedirectResponse
    {
        abort_unless($request->user()->hasPermission(Permission::ResultsOfficialize, $result->meet), 403);

        DB::transaction(function () use ($result, $request): void {
            $locked = EventResult::query()->lockForUpdate()->findOrFail($result->id);
            abort_unless($locked->status === ResultStatus::Validated, 422, 'Only a validated result awaiting officialization may be marked official.');
            abort_unless($locked->isFinalEventResult(), 422, 'Only a final Sports Event Result may be marked official. Completed Match Results remain operational and unofficial.');
            abort_unless($locked->submitted_at !== null && $locked->submitted_by !== null, 422, 'The final result must be submitted before officialization.');
            abort_unless($locked->submitted_at !== null && $locked->submitted_by !== null, 422, 'The final result must be submitted before officialization.');
            abort_if($locked->currentSignedForm() === null, 422, 'The signed Result Form is missing.');
            abort_unless($locked->placements()->exists(), 422, 'The final result has no placements.');

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

        return back()->with('success', 'Result marked official.');
    }

    public function reopen(Request $request, EventResult $result): RedirectResponse
    {
        abort_unless($request->user()->hasPermission(Permission::ResultsReopen, $result->meet), 403);
        abort_unless($result->status === ResultStatus::Official, 422);
        $validated = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        $result->forceFill([
            'status' => ResultStatus::Reopened,
            'version' => $result->version + 1,
            'tm_confirmed_by' => null,
            'tm_confirmed_at' => null,
        ])->save();
        $this->audit->record('result.reopened', $result, [...$this->context($result), 'reason' => $validated['reason']]);

        return back()->with('success', 'Official result reopened for correction.');
    }

    public function recalculateMedalAwards(Request $request, EventResult $result): RedirectResponse
    {
        abort_unless($request->user()->hasPermission(Permission::ResultsOfficialize, $result->meet), 403);
        abort_unless($result->status === ResultStatus::Official, 422);
        $validated = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        DB::transaction(fn () => $this->medalAwards->synchronize($result, $request->user()));
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
