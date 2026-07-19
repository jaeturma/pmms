<?php

namespace App\Http\Controllers;

use App\Enums\EntryStatus;
use App\Enums\MeetStatus;
use App\Enums\ResultStatus;
use App\Http\Controllers\Concerns\SearchesAndPaginates;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventResult;
use App\Models\Meet;
use App\Models\ResultPlacement;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ResultController extends Controller
{
    use SearchesAndPaginates;

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Results per event. Validated results are official and readable by all
     * roles; encoded results are working data, visible to managers only.
     */
    public function index(Request $request): Response
    {
        $canManage = Gate::allows('manage-meet-data');

        $meetId = $request->integer('meet_id');
        $eventId = $request->integer('event_id');

        $query = EventResult::query()
            ->with([
                'meet:id,name',
                'event.sport:id,name',
                'encodedBy:id,name',
                'validatedBy:id,name',
                'placements.entry.athlete:id,first_name,last_name',
                'placements.entry.delegation.school:id,name',
            ])
            ->orderByDesc('id');

        if (! $canManage) {
            $query->where('status', ResultStatus::Validated->value);
        }

        if ($meetId > 0) {
            $query->where('meet_id', $meetId);
        }

        if ($eventId > 0) {
            $query->where('event_id', $eventId);
        }

        return Inertia::render('results/index', [
            'results' => $query->paginate($this->registryPageSize)->withQueryString()
                ->through(fn (EventResult $result): array => [
                    'id' => $result->id,
                    'meet_id' => $result->meet_id,
                    'event_id' => $result->event_id,
                    'meet' => $result->meet->name,
                    'event' => $this->eventLabel($result->event),
                    'status' => $result->status->value,
                    'status_label' => $result->status->label(),
                    'encoded_by' => $result->encodedBy?->name,
                    'encoded_at' => $result->encoded_at->toDayDateTimeString(),
                    'validated_by' => $result->validatedBy?->name,
                    'validated_at' => $result->validated_at?->toDayDateTimeString(),
                    'placements' => $result->placements
                        ->sortBy([['rank', 'asc']])
                        ->map(fn (ResultPlacement $placement): array => [
                            'entry_id' => $placement->entry_id,
                            'rank' => $placement->rank,
                            'athlete' => $placement->entry->athlete->fullName(),
                            'school' => $placement->entry->delegation->school->name,
                            'mark' => $placement->mark,
                            'is_tie' => $placement->is_tie,
                        ])
                        ->values()
                        ->all(),
                ]),
            'filters' => [
                'meet_id' => $meetId > 0 ? $meetId : null,
                'event_id' => $eventId > 0 ? $eventId : null,
            ],
            'meetOptions' => Meet::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Meet $meet): array => ['id' => $meet->id, 'label' => $meet->name]),
            'eventOptionsByMeet' => Event::query()
                ->whereHas('meets')
                ->with(['sport:id,name', 'meets:id'])
                ->get(['id', 'sport_id', 'name', 'gender', 'age_division'])
                ->flatMap(fn (Event $event) => $event->meets->map(fn (Meet $meet): array => [
                    'id' => $event->id,
                    'meet_id' => $meet->id,
                    'label' => $this->eventLabel($event),
                ]))
                ->values(),
            'activeMeets' => Meet::query()
                ->where('status', MeetStatus::Active->value)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Meet $meet): array => ['id' => $meet->id, 'label' => $meet->name]),
            'encodedEventKeys' => $canManage
                ? EventResult::query()->get(['meet_id', 'event_id'])
                    ->map(fn (EventResult $result): string => "{$result->meet_id}-{$result->event_id}")
                    ->values()
                : [],
            'entryOptions' => $canManage
                ? Entry::query()
                    ->where('status', EntryStatus::Confirmed->value)
                    ->with(['athlete:id,first_name,last_name', 'delegation:id,meet_id,school_id', 'delegation.school:id,name'])
                    ->get()
                    ->map(fn (Entry $entry): array => [
                        'id' => $entry->id,
                        'event_id' => $entry->event_id,
                        'meet_id' => $entry->delegation->meet_id,
                        'label' => "{$entry->athlete->fullName()} — {$entry->delegation->school->name}",
                    ])
                    ->sortBy('label')
                    ->values()
                : [],
            'canManage' => $canManage,
        ]);
    }

    /**
     * Encode an event's final standing (first manager decision).
     */
    public function store(Request $request): RedirectResponse
    {
        $meetData = $request->validate([
            'meet_id' => ['required', 'integer', Rule::exists('meets', 'id')],
        ]);

        $data = $this->validatePayload($request);

        $meet = Meet::query()->findOrFail((int) $meetData['meet_id']);

        $this->assertEncodable($meet, (int) $data['event_id']);

        if (EventResult::query()
            ->where('meet_id', $meet->id)
            ->where('event_id', (int) $data['event_id'])
            ->exists()) {
            throw ValidationException::withMessages([
                'event_id' => __('This event already has a result. Edit or correct it instead.'),
            ]);
        }

        $this->assertPlacementsValid($data['placements'], $meet->id, (int) $data['event_id']);

        /** @var User $user */
        $user = $request->user();

        $result = DB::transaction(function () use ($data, $meet, $user): EventResult {
            $result = new EventResult([
                'meet_id' => $meet->id,
                'event_id' => (int) $data['event_id'],
            ]);
            $result->forceFill([
                'encoded_by' => $user->id,
                'encoded_at' => now(),
            ])->save();

            $this->writePlacements($result, $data['placements']);

            return $result;
        });

        $this->audit->record('result.encoded', $result, [
            ...$this->context($result),
            'placements' => $this->placementSnapshot($result),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Result encoded.')]);

        return back();
    }

    /**
     * Re-encode placements while the result is still unvalidated.
     */
    public function update(Request $request, EventResult $result): RedirectResponse
    {
        if ($result->isValidated()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Validated results are locked. Record a correction instead.'),
            ]);

            return back();
        }

        $data = $this->validatePayload($request);

        $this->assertEncodable($result->meet, $result->event_id);
        $this->assertPlacementsValid($data['placements'], $result->meet_id, $result->event_id);

        DB::transaction(function () use ($result, $data): void {
            $result->placements()->delete();
            $this->writePlacements($result, $data['placements']);
        });

        $this->audit->record('result.encoded', $result, [
            ...$this->context($result),
            'revision' => true,
            'placements' => $this->placementSnapshot($result->refresh()),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Result updated.')]);

        return back();
    }

    /**
     * Validate a result (second explicit manager decision). Validated
     * results are official, locked, and feed the medal tally.
     */
    public function validateResult(Request $request, EventResult $result): RedirectResponse
    {
        if ($result->isValidated()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This result is already validated.'),
            ]);

            return back();
        }

        /** @var User $user */
        $user = $request->user();

        $result->forceFill([
            'status' => ResultStatus::Validated,
            'validated_by' => $user->id,
            'validated_at' => now(),
        ])->save();

        $this->audit->record('result.validated', $result, $this->context($result));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Result validated.')]);

        return back();
    }

    /**
     * Correct a validated result: never a silent edit. Requires a reason,
     * reopens the result to encoded, and preserves the superseded
     * placements in the audit record.
     */
    public function correct(Request $request, EventResult $result): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        if (! $result->isValidated()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Only validated results need a correction — encoded results can be edited directly.'),
            ]);

            return back();
        }

        $superseded = $this->placementSnapshot($result);

        $result->forceFill([
            'status' => ResultStatus::Encoded,
            'validated_by' => null,
            'validated_at' => null,
        ])->save();

        $this->audit->record('result.corrected', $result, [
            ...$this->context($result),
            'reason' => $validated['reason'],
            'superseded_placements' => $superseded,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Result reopened for correction. Re-encode and validate it again.'),
        ]);

        return back();
    }

    /**
     * Delete an unvalidated result (working data only).
     */
    public function destroy(EventResult $result): RedirectResponse
    {
        if ($result->isValidated()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Validated results cannot be deleted. Record a correction instead.'),
            ]);

            return back();
        }

        $context = $this->context($result);

        $result->placements()->delete();
        $result->delete();

        $this->audit->record('result.deleted', $result, $context);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Result deleted.')]);

        return back();
    }

    /**
     * @return array{event_id: mixed, placements: array<int, array{entry_id: int, rank: int, mark?: string|null, is_tie?: bool}>}
     */
    private function validatePayload(Request $request): array
    {
        /** @var array{event_id: mixed, placements: array<int, array{entry_id: int, rank: int, mark?: string|null, is_tie?: bool}>} */
        return $request->validate([
            'event_id' => ['required', 'integer', Rule::exists('events', 'id')],
            'placements' => ['required', 'array', 'min:1'],
            'placements.*.entry_id' => ['required', 'integer', 'distinct', Rule::exists('entries', 'id')],
            'placements.*.rank' => ['required', 'integer', 'min:1', 'max:999'],
            'placements.*.mark' => ['nullable', 'string', 'max:60'],
            'placements.*.is_tie' => ['boolean'],
        ]);
    }

    /**
     * Results are encoded only for events of an active meet.
     */
    private function assertEncodable(Meet $meet, int $eventId): void
    {
        if ($meet->status !== MeetStatus::Active) {
            throw ValidationException::withMessages([
                'meet_id' => __('Results can only be encoded while the meet is active.'),
            ]);
        }

        if (! $meet->events()->whereKey($eventId)->exists()) {
            throw ValidationException::withMessages([
                'event_id' => __('That event is not part of the selected meet.'),
            ]);
        }
    }

    /**
     * Placement integrity: confirmed entries of this meet+event only, and
     * duplicate ranks only when every entry sharing the rank is tied.
     *
     * @param  array<int, array{entry_id: int, rank: int, mark?: string|null, is_tie?: bool}>  $placements
     */
    private function assertPlacementsValid(array $placements, int $meetId, int $eventId): void
    {
        $entries = Entry::query()
            ->with(['athlete:id,first_name,last_name', 'delegation:id,meet_id'])
            ->whereIn('id', array_column($placements, 'entry_id'))
            ->get()
            ->keyBy('id');

        foreach ($placements as $placement) {
            /** @var Entry|null $entry */
            $entry = $entries->get($placement['entry_id']);

            if ($entry === null
                || $entry->event_id !== $eventId
                || $entry->delegation->meet_id !== $meetId) {
                throw ValidationException::withMessages([
                    'placements' => __('Every placement must be an entry of this meet event.'),
                ]);
            }

            if ($entry->status !== EntryStatus::Confirmed) {
                throw ValidationException::withMessages([
                    'placements' => __('Only confirmed entries can be placed (:name is :status).', [
                        'name' => $entry->athlete->fullName(),
                        'status' => $entry->status->label(),
                    ]),
                ]);
            }
        }

        $byRank = collect($placements)->groupBy('rank');

        foreach ($byRank as $rank => $group) {
            if ($group->count() > 1 && $group->contains(fn (array $placement): bool => ! ($placement['is_tie'] ?? false))) {
                throw ValidationException::withMessages([
                    'placements' => __('Rank :rank appears more than once — mark those placements as a tie.', [
                        'rank' => $rank,
                    ]),
                ]);
            }
        }
    }

    /**
     * @param  array<int, array{entry_id: int, rank: int, mark?: string|null, is_tie?: bool}>  $placements
     */
    private function writePlacements(EventResult $result, array $placements): void
    {
        foreach ($placements as $placement) {
            $result->placements()->create([
                'entry_id' => $placement['entry_id'],
                'rank' => $placement['rank'],
                'mark' => $placement['mark'] ?? null,
                'is_tie' => $placement['is_tie'] ?? false,
            ]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function placementSnapshot(EventResult $result): array
    {
        return $result->placements()
            ->with(['entry.athlete:id,first_name,last_name', 'entry.delegation.school:id,name'])
            ->orderBy('rank')
            ->get()
            ->map(fn (ResultPlacement $placement): array => [
                'rank' => $placement->rank,
                'athlete' => $placement->entry->athlete->fullName(),
                'school' => $placement->entry->delegation->school->name,
                'mark' => $placement->mark,
                'is_tie' => $placement->is_tie,
            ])
            ->all();
    }

    private function eventLabel(Event $event): string
    {
        $event->loadMissing('sport:id,name');

        return sprintf(
            '%s — %s (%s, %s)',
            $event->sport->name,
            $event->name,
            $event->gender->label(),
            $event->age_division->label(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function context(EventResult $result): array
    {
        $result->loadMissing(['meet:id,name', 'event:id,sport_id,name']);

        return [
            'meet' => $result->meet->name,
            'event' => $result->event->name,
        ];
    }
}
