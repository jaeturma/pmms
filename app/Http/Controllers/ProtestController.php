<?php

namespace App\Http\Controllers;

use App\Enums\ProtestStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Concerns\SearchesAndPaginates;
use App\Models\Delegation;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\EventResult;
use App\Models\Protest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProtestController extends Controller
{
    use SearchesAndPaginates;

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Protest list: managers see all, officers their own delegation's,
     * viewers none.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Protest::class);

        /** @var User $user */
        $user = $request->user();

        $canManage = Gate::allows('manage-meet-data');
        $status = $request->string('status')->toString();

        $query = Protest::query()
            ->with([
                'delegation.school:id,name',
                'delegation.meet:id,name',
                'result.event.sport:id,name',
                'match.event.sport:id,name',
                'filedBy:id,name',
                'decidedBy:id,name',
            ])
            ->orderByDesc('id');

        if ($user->role === UserRole::DelegationOfficer) {
            $query->whereHas(
                'delegation.officers',
                fn ($officers) => $officers->whereKey($user->getKey()),
            );
        }

        if ($status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }

        $delegationScope = Delegation::query()->with(['school:id,name', 'meet:id,name']);

        if ($user->role === UserRole::DelegationOfficer) {
            $delegationScope->whereHas(
                'officers',
                fn ($officers) => $officers->whereKey($user->getKey()),
            );
        }

        return Inertia::render('protests/index', [
            'protests' => $query->paginate($this->registryPageSize)->withQueryString()
                ->through(fn (Protest $protest): array => [
                    'id' => $protest->id,
                    'delegation' => "{$protest->delegation->school->name} — {$protest->delegation->meet->name}",
                    'target' => $this->targetLabel($protest),
                    'grounds' => $protest->grounds,
                    'status' => $protest->status->value,
                    'status_label' => $protest->status->label(),
                    'filed_by' => $protest->filedBy?->name,
                    'filed_at' => $protest->created_at?->toDayDateTimeString(),
                    'decided_by' => $protest->decidedBy?->name,
                    'decided_at' => $protest->decided_at?->toDayDateTimeString(),
                    'remarks' => $protest->remarks,
                    'can_review' => $canManage && $protest->status === ProtestStatus::Filed,
                    'can_decide' => $canManage && $protest->status === ProtestStatus::UnderReview,
                    'correctable_result_id' => $canManage
                        && $protest->status === ProtestStatus::Upheld
                        && $protest->result?->isValidated() === true
                            ? $protest->event_result_id
                            : null,
                    'correction_reason' => "Protest #{$protest->id} upheld: ".($protest->remarks ?? $protest->grounds),
                ]),
            'filters' => ['status' => $status !== '' ? $status : null],
            'statusOptions' => array_map(
                fn (ProtestStatus $option): array => [
                    'value' => $option->value,
                    'label' => $option->label(),
                ],
                ProtestStatus::cases(),
            ),
            'delegationOptions' => $delegationScope->get()
                ->map(fn (Delegation $delegation): array => [
                    'id' => $delegation->id,
                    'meet_id' => $delegation->meet_id,
                    'label' => "{$delegation->school->name} — {$delegation->meet->name}",
                ])
                ->sortBy('label')
                ->values(),
            'resultOptions' => EventResult::query()
                ->with('event.sport:id,name')
                ->get()
                ->map(fn (EventResult $result): array => [
                    'id' => $result->id,
                    'meet_id' => $result->meet_id,
                    'label' => 'Result: '.$this->eventLabel($result->event),
                ])
                ->sortBy('label')
                ->values(),
            'matchOptions' => EventMatch::query()
                ->with('event.sport:id,name')
                ->get()
                ->map(fn (EventMatch $match): array => [
                    'id' => $match->id,
                    'meet_id' => $match->meet_id,
                    'label' => sprintf(
                        'Match: %s — %s #%d',
                        $this->eventLabel($match->event),
                        $match->round_label,
                        $match->sequence,
                    ),
                ])
                ->sortBy('label')
                ->values(),
            'canManage' => $canManage,
        ]);
    }

    /**
     * File a protest against a result or match of the delegation's meet.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'delegation_id' => ['required', 'integer', Rule::exists('delegations', 'id')],
            'event_result_id' => [
                'nullable', 'integer', Rule::exists('event_results', 'id'),
                'required_without:match_id', 'prohibits:match_id',
            ],
            'match_id' => ['nullable', 'integer', Rule::exists('matches', 'id')],
            'grounds' => ['required', 'string', 'max:1000'],
        ]);

        $delegation = Delegation::query()->findOrFail((int) $validated['delegation_id']);

        Gate::authorize('create', [Protest::class, $delegation]);

        $resultId = $request->integer('event_result_id');
        $matchId = $request->integer('match_id');

        if ($resultId > 0) {
            $target = EventResult::query()->findOrFail($resultId);

            if ($target->meet_id !== $delegation->meet_id) {
                throw ValidationException::withMessages([
                    'event_result_id' => __('That result belongs to a different meet.'),
                ]);
            }
        } else {
            $target = EventMatch::query()->findOrFail($matchId);

            if ($target->meet_id !== $delegation->meet_id) {
                throw ValidationException::withMessages([
                    'match_id' => __('That match belongs to a different meet.'),
                ]);
            }
        }

        /** @var User $user */
        $user = $request->user();

        $protest = new Protest([
            'delegation_id' => $delegation->id,
            'event_result_id' => $resultId > 0 ? $resultId : null,
            'match_id' => $matchId > 0 ? $matchId : null,
            'grounds' => $validated['grounds'],
        ]);
        $protest->forceFill(['filed_by' => $user->id])->save();

        $this->audit->record('protest.filed', $protest, $this->context($protest));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Protest filed.')]);

        return back();
    }

    /**
     * Take a filed protest under review (manager step).
     */
    public function review(Protest $protest): RedirectResponse
    {
        if (! $protest->status->canTransitionTo(ProtestStatus::UnderReview)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Only filed protests can be taken under review.'),
            ]);

            return back();
        }

        $protest->forceFill(['status' => ProtestStatus::UnderReview])->save();

        $this->audit->record('protest.under_review', $protest, $this->context($protest));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Protest is now under review.')]);

        return back();
    }

    /**
     * Decide a protest under review: upheld or dismissed, remarks required.
     * Upholding never changes a result — use the result correction flow.
     */
    public function decide(Request $request, Protest $protest): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', Rule::in([ProtestStatus::Upheld->value, ProtestStatus::Dismissed->value])],
            'remarks' => ['required', 'string', 'max:1000'],
        ]);

        $target = ProtestStatus::from($validated['decision']);

        if (! $protest->status->canTransitionTo($target)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Only protests under review can be decided.'),
            ]);

            return back();
        }

        /** @var User $user */
        $user = $request->user();

        $protest->forceFill([
            'status' => $target,
            'decided_by' => $user->id,
            'decided_at' => now(),
            'remarks' => $validated['remarks'],
        ])->save();

        $this->audit->record("protest.{$target->value}", $protest, [
            ...$this->context($protest),
            'remarks' => $validated['remarks'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Protest decided.')]);

        return back();
    }

    private function targetLabel(Protest $protest): string
    {
        if ($protest->result !== null) {
            return 'Result: '.$this->eventLabel($protest->result->event);
        }

        if ($protest->match !== null) {
            return sprintf(
                'Match: %s — %s #%d',
                $this->eventLabel($protest->match->event),
                $protest->match->round_label,
                $protest->match->sequence,
            );
        }

        return '—';
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
    private function context(Protest $protest): array
    {
        $protest->loadMissing(['delegation.school:id,name', 'delegation.meet:id,name']);

        return [
            'school' => $protest->delegation->school->name,
            'meet' => $protest->delegation->meet->name,
            'target' => $this->targetLabel($protest),
        ];
    }
}
