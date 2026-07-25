<?php

namespace App\Http\Controllers;

use App\Enums\DelegationStatus;
use App\Enums\DivisionType;
use App\Enums\MeetStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Concerns\SearchesAndPaginates;
use App\Http\Requests\DelegationStoreRequest;
use App\Http\Requests\DelegationUpdateRequest;
use App\Models\Delegation;
use App\Models\District;
use App\Models\Division;
use App\Models\Meet;
use App\Models\School;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DelegationController extends Controller
{
    use SearchesAndPaginates;

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * List delegations — all of them for managers and viewers, only their
     * own for delegation officers.
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $search = $this->searchTerm($request);

        $query = Delegation::query()
            ->with(['school:id,name', 'district:id,name', 'meet:id,name', 'officers:id,name'])
            ->orderByDesc('id');

        if ($user->role === UserRole::DelegationOfficer) {
            $query->whereHas('officers', fn ($officers) => $officers->whereKey($user->getKey()));
        }

        $this->applySearch($query, $search, ['head_name', 'school.name', 'district.name', 'meet.name']);

        $division = Division::current();

        return Inertia::render('delegations/index', [
            'filters' => ['search' => $search],
            'delegations' => $query->paginate($this->registryPageSize)->withQueryString()
                ->through(fn (Delegation $delegation): array => [
                    'id' => $delegation->id,
                    'registrant' => $delegation->registrantName(),
                    'meet' => $delegation->meet->name,
                    'head_name' => $delegation->head_name,
                    'head_phone' => $delegation->head_phone,
                    'head_email' => $delegation->head_email,
                    'status' => $delegation->status->value,
                    'status_label' => $delegation->status->label(),
                    'officers' => $delegation->officers->map(fn (User $officer): array => [
                        'id' => $officer->id,
                        'name' => $officer->name,
                    ])->values(),
                    'can_view_roster' => $user->can('viewRoster', $delegation),
                    'can_update' => $user->can('update', $delegation),
                    'can_submit' => $user->can('submit', $delegation)
                        && $delegation->status === DelegationStatus::Draft,
                    'can_approve' => $user->can('approve', $delegation)
                        && $delegation->status === DelegationStatus::Submitted,
                    'can_delete' => $user->can('delete', $delegation),
                    'can_assign' => $user->can('assignOfficers', $delegation),
                ]),
            'meetOptions' => Meet::query()
                ->where('status', MeetStatus::RegistrationOpen->value)
                ->orderByDesc('starts_at')
                ->get(['id', 'name']),
            'schoolOptions' => $division->type === DivisionType::City
                ? School::query()
                    ->where('active', true)
                    ->orderBy('name')
                    ->get(['id', 'name'])
                : [],
            'districtOptions' => $division->type === DivisionType::Province
                ? District::query()
                    ->where('active', true)
                    ->orderBy('name')
                    ->get(['id', 'name'])
                : [],
            'officerOptions' => User::query()
                ->where('role', UserRole::DelegationOfficer->value)
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'canManage' => Gate::allows('manage-meet-data'),
        ]);
    }

    /**
     * Register a delegation (managers only; meet registration must be open).
     */
    public function store(DelegationStoreRequest $request): RedirectResponse
    {
        $meet = Meet::query()->findOrFail($request->integer('meet_id'));

        if (! $meet->isRegistrationOpen()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Registration is not open for that meet.'),
            ]);

            return back();
        }

        $delegation = Delegation::create($request->validated());

        $this->audit->record('delegation.created', $delegation, [
            'registrant' => $delegation->registrantName(),
            'meet' => $meet->name,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Delegation registered.')]);

        return back();
    }

    /**
     * Update head-of-delegation contact details.
     */
    public function update(DelegationUpdateRequest $request, Delegation $delegation): RedirectResponse
    {
        Gate::authorize('update', $delegation);

        $delegation->update($request->validated());

        $this->audit->record('delegation.updated', $delegation, [
            'registrant' => $delegation->registrantName(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Delegation updated.')]);

        return back();
    }

    /**
     * Submit a draft delegation for approval.
     */
    public function submit(Delegation $delegation): RedirectResponse
    {
        Gate::authorize('submit', $delegation);

        if ($delegation->status !== DelegationStatus::Draft) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Only draft delegations can be submitted.'),
            ]);

            return back();
        }

        $delegation->forceFill(['status' => DelegationStatus::Submitted])->save();

        $this->audit->record('delegation.submitted', $delegation, [
            'registrant' => $delegation->registrantName(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Delegation submitted.')]);

        return back();
    }

    /**
     * Approve a submitted delegation.
     */
    public function approve(Delegation $delegation): RedirectResponse
    {
        Gate::authorize('approve', $delegation);

        if ($delegation->status !== DelegationStatus::Submitted) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Only submitted delegations can be approved.'),
            ]);

            return back();
        }

        $delegation->forceFill(['status' => DelegationStatus::Approved])->save();

        $this->audit->record('delegation.approved', $delegation, [
            'registrant' => $delegation->registrantName(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Delegation approved.')]);

        return back();
    }

    /**
     * Return a submitted delegation to draft for correction.
     */
    public function returnToDraft(Delegation $delegation): RedirectResponse
    {
        Gate::authorize('approve', $delegation);

        if ($delegation->status !== DelegationStatus::Submitted) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Only submitted delegations can be returned.'),
            ]);

            return back();
        }

        $delegation->forceFill(['status' => DelegationStatus::Draft])->save();

        $this->audit->record('delegation.returned', $delegation, [
            'registrant' => $delegation->registrantName(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Delegation returned to draft.')]);

        return back();
    }

    /**
     * Replace the officers assigned to a delegation.
     */
    public function syncOfficers(Request $request, Delegation $delegation): RedirectResponse
    {
        Gate::authorize('assignOfficers', $delegation);

        $validated = $request->validate([
            'user_ids' => ['array'],
            'user_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where('role', UserRole::DelegationOfficer->value),
            ],
        ]);

        /** @var array<int, int> $userIds */
        $userIds = $validated['user_ids'] ?? [];

        $delegation->officers()->sync($userIds);

        $this->audit->record('delegation.officers_updated', $delegation, [
            'registrant' => $delegation->registrantName(),
            'officer_count' => count($userIds),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Delegation officers updated.')]);

        return back();
    }

    /**
     * Delete a draft delegation.
     */
    public function destroy(Delegation $delegation): RedirectResponse
    {
        Gate::authorize('delete', $delegation);

        if ($delegation->athletes()->exists() || $delegation->personnel()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This delegation has registered athletes or personnel. Remove them first.'),
            ]);

            return back();
        }

        $registrant = $delegation->registrantName();

        $delegation->delete();

        $this->audit->record('delegation.deleted', $delegation, ['registrant' => $registrant]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Delegation deleted.')]);

        return back();
    }
}
