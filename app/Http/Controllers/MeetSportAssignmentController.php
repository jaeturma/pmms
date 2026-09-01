<?php

namespace App\Http\Controllers;

use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Management UI for `MeetSportAssignment` (WP-REALIGN-07) — the first
 * controller/UI layer on top of the schema WP-REALIGN-04 introduced.
 * Deliberately does not touch `sport_user` or
 * `ScoringSessionController`/`ResultController`'s Technical Official
 * scoping — those still read the older, meet-unscoped pivot; cutting them
 * over to this table is a separate, later step gated on a backfill
 * decision (see `MeetSportAssignment`'s own docblock).
 */
class MeetSportAssignmentController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * All assignments, optionally filtered to one meet. Open to any
     * authenticated role, same as the Districts/Schools/Sports/Events
     * "view lists" precedent — assignment rosters aren't minors data.
     */
    public function index(Request $request): Response
    {
        $search = trim($request->string('search')->toString());
        $globalManager = $this->isGlobalManager($request);
        $manageableMeetSportIds = $this->ictManageableMeetSportIds($request);
        $canManage = $globalManager || $manageableMeetSportIds->isNotEmpty();
        $query = MeetSportAssignment::query()
            ->with(['meetSport.sport:id,name', 'sportCategory:id,display_name', 'user:id,name,email', 'person:id,full_name'])
            ->when(! $globalManager && $manageableMeetSportIds->isNotEmpty(), fn ($assignments) => $assignments->whereIn('meet_sport_id', $manageableMeetSportIds))
            ->when($search !== '', function ($query) use ($search): void {
                $roleSearch = str($search)->lower()->replace([' ', '-'], '_')->toString();

                $query->where(function ($scope) use ($search, $roleSearch): void {
                    $scope->where('role', 'like', "%{$roleSearch}%")
                        ->orWhere('status', 'like', "%{$roleSearch}%")
                        ->orWhereHas('user', fn ($user) => $user
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"))
                        ->orWhereHas('person', fn ($person) => $person
                            ->where('full_name', 'like', "%{$search}%"))
                        ->orWhereHas('meetSport.sport', fn ($sport) => $sport
                            ->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('sportCategory', fn ($category) => $category
                            ->where('display_name', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('id');

        return Inertia::render('meet-sport-assignments/index', [
            'assignments' => $query->paginate(10)->withQueryString()->through(fn (MeetSportAssignment $assignment): array => [
                'id' => $assignment->id,
                'sport' => $assignment->meetSport->sport->name,
                'category' => $assignment->sportCategory?->display_name,
                'user' => $this->assignmentName($assignment),
                'user_email' => $assignment->user?->email ?? '',
                'role' => $assignment->role->value,
                'role_label' => $assignment->role->label(),
                'is_lead' => $assignment->is_lead,
                'start_date' => $assignment->start_date?->toDateString(),
                'end_date' => $assignment->end_date?->toDateString(),
                'status' => $assignment->status->value,
                'status_label' => $assignment->status->label(),
            ]),
            'filters' => ['search' => $search],
            'sportOptions' => Sport::query()
                ->when(! $globalManager && $manageableMeetSportIds->isNotEmpty(), fn ($sports) => $sports->whereIn('id', MeetSport::query()->whereKey($manageableMeetSportIds)->select('sport_id')))
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Sport $sport): array => [
                    'id' => $sport->id,
                    'label' => $sport->name,
                ]),
            'sportCategoryOptions' => SportCategory::query()
                ->orderBy('display_name')
                ->get(['id', 'sport_id', 'display_name'])
                ->map(fn (SportCategory $category): array => [
                    'id' => $category->id,
                    'sport_id' => $category->sport_id,
                    'label' => $category->display_name,
                ]),
            'roleOptions' => array_map(
                fn (MeetSportAssignmentRole $role): array => ['value' => $role->value, 'label' => $role->label()],
                $globalManager ? MeetSportAssignmentRole::cases() : $this->ictAssignableRoles(),
            ),
            'statusOptions' => array_map(
                fn (MeetSportAssignmentStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
                MeetSportAssignmentStatus::cases(),
            ),
            'userOptions' => User::query()
                ->whereNull('disabled_at')
                ->orderBy('name')
                ->get(['id', 'name', 'username', 'email', 'role'])
                ->map(fn (User $user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'identity' => $user->email ?? $user->username ?? __('No login identifier'),
                    'role' => $user->role->label(),
                ]),
            'canManage' => $canManage,
        ]);
    }

    /**
     * Assign a person to a role for one meet's inclusion of a sport.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'meet_sport_id' => ['nullable', 'required_without:sport_id', 'integer', Rule::exists('meet_sports', 'id')],
            'sport_id' => ['nullable', 'required_without:meet_sport_id', 'integer', Rule::exists('sports', 'id')],
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->whereNull('disabled_at')],
            'sport_category_id' => ['nullable', 'integer', Rule::exists('sport_categories', 'id')],
            'role' => ['required', Rule::enum(MeetSportAssignmentRole::class)],
            'is_lead' => ['boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        if (! $this->isGlobalManager($request) && isset($validated['sport_id'])) {
            abort_unless(MeetSport::query()
                ->whereKey($this->ictManageableMeetSportIds($request))
                ->where('meet_id', Meet::current()->id)
                ->where('sport_id', $validated['sport_id'])
                ->exists(), 403);
        }

        $meetSport = isset($validated['meet_sport_id'])
            ? MeetSport::query()->findOrFail($validated['meet_sport_id'])
            : MeetSport::query()->firstOrCreate(
                ['meet_id' => Meet::current()->id, 'sport_id' => $validated['sport_id']],
                ['active' => true],
            );
        $validated['meet_sport_id'] = $meetSport->id;
        $this->authorizeManage($request, $meetSport, MeetSportAssignmentRole::from($validated['role']));

        if (isset($validated['sport_category_id'])) {
            $categoryMatchesSport = SportCategory::query()
                ->whereKey($validated['sport_category_id'])
                ->where('sport_id', $meetSport->sport_id)
                ->exists();

            if (! $categoryMatchesSport) {
                throw ValidationException::withMessages([
                    'sport_category_id' => __('The selected category does not belong to the selected sport.'),
                ]);
            }
        }

        // The (meet_sport_id, user_id, role) unique index (migration
        // 2026_08_02_090926) is the real guarantee; this check exists
        // only to surface a clean validation-style error instead of a
        // raw database exception.
        if (MeetSportAssignment::query()
            ->where('meet_sport_id', $validated['meet_sport_id'])
            ->where('user_id', $validated['user_id'])
            ->where('role', $validated['role'])
            ->exists()) {
            throw ValidationException::withMessages([
                'user_id' => __('This person already holds that role for this meet sport.'),
            ]);
        }

        $assignment = MeetSportAssignment::create([
            'meet_sport_id' => $validated['meet_sport_id'],
            'user_id' => $validated['user_id'],
            'sport_category_id' => $validated['sport_category_id'] ?? null,
            'role' => $validated['role'],
            'is_lead' => $request->boolean('is_lead'),
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'status' => MeetSportAssignmentStatus::Pending,
        ]);

        $assignment->load(['meetSport.meet:id,name', 'meetSport.sport:id,name', 'user:id,name']);

        $this->audit->record('meet_sport_assignment.created', $assignment, [
            'meet' => $assignment->meetSport->meet->name,
            'sport' => $assignment->meetSport->sport->name,
            'user' => $assignment->user->name,
            'role' => $assignment->role->value,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Assignment created.')]);

        return back();
    }

    /**
     * Move an assignment through its own lifecycle (e.g. Pending →
     * Active once confirmed, or → Ended) without deleting the record.
     */
    public function updateStatus(Request $request, MeetSportAssignment $meetSportAssignment): RedirectResponse
    {
        $this->authorizeManage($request, $meetSportAssignment->meetSport, $meetSportAssignment->role);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(MeetSportAssignmentStatus::class)],
        ]);

        $meetSportAssignment->forceFill(['status' => $validated['status']])->save();

        $meetSportAssignment->load(['meetSport.meet:id,name', 'meetSport.sport:id,name', 'user:id,name', 'person:id,full_name']);

        $this->audit->record('meet_sport_assignment.status_updated', $meetSportAssignment, [
            'meet' => $meetSportAssignment->meetSport->meet->name,
            'sport' => $meetSportAssignment->meetSport->sport->name,
            'user' => $this->assignmentName($meetSportAssignment),
            'status' => $meetSportAssignment->status->value,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Assignment status updated.')]);

        return back();
    }

    /**
     * Remove an assignment outright (as opposed to marking it Ended —
     * for correcting a mistaken assignment, not recording a normal
     * lifecycle transition).
     */
    public function destroy(Request $request, MeetSportAssignment $meetSportAssignment): RedirectResponse
    {
        $this->authorizeManage($request, $meetSportAssignment->meetSport, $meetSportAssignment->role);

        $meetSportAssignment->load(['meetSport.meet:id,name', 'meetSport.sport:id,name', 'user:id,name', 'person:id,full_name']);

        $context = [
            'meet' => $meetSportAssignment->meetSport->meet->name,
            'sport' => $meetSportAssignment->meetSport->sport->name,
            'user' => $this->assignmentName($meetSportAssignment),
            'role' => $meetSportAssignment->role->value,
        ];

        $meetSportAssignment->delete();

        $this->audit->record('meet_sport_assignment.deleted', $meetSportAssignment, $context);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Assignment removed.')]);

        return back();
    }

    private function assignmentName(MeetSportAssignment $assignment): string
    {
        return $assignment->user?->name ?? $assignment->person?->full_name ?? __('Unknown person');
    }

    private function authorizeManage(Request $request, ?MeetSport $meetSport = null, ?MeetSportAssignmentRole $role = null): void
    {
        if ($this->isGlobalManager($request)) {
            return;
        }

        abort_unless($meetSport === null || $this->ictManageableMeetSportIds($request)->contains($meetSport->id), 403);
        abort_unless($role === null || in_array($role, $this->ictAssignableRoles(), true), 403);
        abort_unless($this->ictManageableMeetSportIds($request)->isNotEmpty(), 403);
    }

    private function isGlobalManager(Request $request): bool
    {
        return Gate::allows('manage-meet-data') || $request->user()->canManageProductionAccounts();
    }

    private function ictManageableMeetSportIds(Request $request): Collection
    {
        return $request->user()->meetSportAssignments()
            ->where('status', MeetSportAssignmentStatus::Active->value)
            ->where('role', MeetSportAssignmentRole::TournamentICT->value)
            ->pluck('meet_sport_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()->values();
    }

    /** @return list<MeetSportAssignmentRole> */
    private function ictAssignableRoles(): array
    {
        return [
            MeetSportAssignmentRole::TournamentManager,
            MeetSportAssignmentRole::TournamentSecretary,
            MeetSportAssignmentRole::TournamentICT,
            MeetSportAssignmentRole::TechnicalOfficial,
        ];
    }
}
