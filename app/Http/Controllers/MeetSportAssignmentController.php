<?php

namespace App\Http\Controllers;

use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\UserRole;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    public function index(): Response
    {
        $query = MeetSportAssignment::query()
            ->with(['meetSport.sport:id,name', 'sportCategory:id,display_name', 'user:id,name,email', 'person:id,full_name'])
            ->orderByDesc('id');

        return Inertia::render('meet-sport-assignments/index', [
            'assignments' => $query->get()->map(fn (MeetSportAssignment $assignment): array => [
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
            'meetSportOptions' => MeetSport::query()
                ->where('meet_id', Meet::current()->id)
                ->with('sport:id,name')
                ->get(['id', 'sport_id'])
                ->map(fn (MeetSport $meetSport): array => [
                    'id' => $meetSport->id,
                    'label' => $meetSport->sport->name,
                ]),
            'roleOptions' => array_map(
                fn (MeetSportAssignmentRole $role): array => ['value' => $role->value, 'label' => $role->label()],
                MeetSportAssignmentRole::cases(),
            ),
            'statusOptions' => array_map(
                fn (MeetSportAssignmentStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
                MeetSportAssignmentStatus::cases(),
            ),
            'userOptions' => User::query()
                ->whereIn('role', [UserRole::Admin->value, UserRole::Organizer->value, UserRole::TechnicalOfficial->value])
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->map(fn (User $user): array => ['id' => $user->id, 'label' => "{$user->name} ({$user->email})"]),
            'canManage' => Gate::allows('manage-meet-data'),
        ]);
    }

    /**
     * Assign a person to a role for one meet's inclusion of a sport.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'meet_sport_id' => ['required', 'integer', Rule::exists('meet_sports', 'id')],
            'user_id' => [
                'required', 'integer',
                Rule::exists('users', 'id')->whereIn('role', [
                    UserRole::Admin->value, UserRole::Organizer->value, UserRole::TechnicalOfficial->value,
                ]),
            ],
            'role' => ['required', Rule::enum(MeetSportAssignmentRole::class)],
            'is_lead' => ['boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

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
    public function destroy(MeetSportAssignment $meetSportAssignment): RedirectResponse
    {
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
}
