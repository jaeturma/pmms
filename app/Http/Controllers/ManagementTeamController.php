<?php

namespace App\Http\Controllers;

use App\Enums\ManagementTeamMemberStatus;
use App\Enums\ManagementTeamStatus;
use App\Enums\ManagementTeamType;
use App\Models\ManagementTeam;
use App\Models\ManagementTeamMember;
use App\Models\Meet;
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
 * Management UI for `ManagementTeam` (WP-REALIGN-09) — the administrative
 * shell only (who is on the team, what is its mandate), per
 * `ManagementTeamType`'s own docblock. Team-specific operational data
 * (Medical records, Equipment, Meals, etc.) is out of scope here — later
 * work packages (WP-REALIGN-10 through -12) build those, referencing a
 * team by id.
 */
class ManagementTeamController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * All teams, optionally filtered to one meet. Open to any
     * authenticated role — "who's on the ICT team" isn't sensitive,
     * same view-open/mutate-restricted shape as
     * `MeetSportAssignmentController::index()`.
     */
    public function index(): Response
    {
        $query = ManagementTeam::query()
            ->with(['members.user:id,name,email', 'members.person:id,full_name'])
            ->where('meet_id', Meet::current()->id)
            ->orderBy('team_type');

        return Inertia::render('management-teams/index', [
            'teams' => $query->paginate(10)->withQueryString()->through(fn (ManagementTeam $team): array => $this->teamRow($team)),
            'teamTypeOptions' => array_map(
                fn (ManagementTeamType $type): array => ['value' => $type->value, 'label' => $type->label()],
                ManagementTeamType::cases(),
            ),
            'teamStatusOptions' => array_map(
                fn (ManagementTeamStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
                ManagementTeamStatus::cases(),
            ),
            'memberStatusOptions' => array_map(
                fn (ManagementTeamMemberStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
                ManagementTeamMemberStatus::cases(),
            ),
            // Deliberately not role-restricted, unlike
            // MeetSportAssignmentController's userOptions — a committee
            // may reasonably be staffed by any account (see
            // ManagementTeamMemberController's own docblock).
            'userOptions' => User::query()->orderBy('name')->get(['id', 'name', 'email'])
                ->map(fn (User $user): array => ['id' => $user->id, 'label' => "{$user->name} ({$user->email})"]),
            'canManage' => Gate::allows('manage-meet-data'),
        ]);
    }

    /**
     * @return array{id: int, team_type: string, team_type_label: string, name: string, description: string|null, status: string, status_label: string, members: array<int, array{id: int, user: string, user_email: string, role_title: string|null, is_head: bool, responsibilities: string|null, status: string, status_label: string}>}
     */
    private function teamRow(ManagementTeam $team): array
    {
        return [
            'id' => $team->id,
            'team_type' => $team->team_type->value,
            'team_type_label' => $team->team_type->label(),
            'name' => $team->name,
            'description' => $team->description,
            'status' => $team->status->value,
            'status_label' => $team->status->label(),
            'members' => $team->members->map(fn (ManagementTeamMember $member): array => $this->memberRow($member))->values()->all(),
        ];
    }

    /**
     * @return array{id: int, user: string, user_email: string, role_title: string|null, is_head: bool, responsibilities: string|null, status: string, status_label: string}
     */
    private function memberRow(ManagementTeamMember $member): array
    {
        return [
            'id' => $member->id,
            'user' => $member->user?->name ?? $member->person?->full_name ?? __('Unknown person'),
            'user_email' => $member->user?->email ?? '',
            'role_title' => $member->role_title,
            'is_head' => $member->is_head,
            'responsibilities' => $member->responsibilities,
            'status' => $member->status->value,
            'status_label' => $member->status->label(),
        ];
    }

    /**
     * Create a team for a meet (at most one per team_type — enforced by
     * the unique index, surfaced here as a clean validation error).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'team_type' => ['required', Rule::enum(ManagementTeamType::class)],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $meet = Meet::current();

        if (ManagementTeam::query()
            ->where('meet_id', $meet->id)
            ->where('team_type', $validated['team_type'])
            ->exists()) {
            throw ValidationException::withMessages([
                'team_type' => __('This meet already has a team of that type.'),
            ]);
        }

        $team = ManagementTeam::create([
            'meet_id' => $meet->id,
            'team_type' => $validated['team_type'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => ManagementTeamStatus::Forming,
        ]);

        $this->audit->record('management_team.created', $team, [
            'meet' => $meet->name,
            'team_type' => $team->team_type->value,
            'name' => $team->name,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Team created.')]);

        return back();
    }

    /**
     * Update a team's name, description, or status.
     */
    public function update(Request $request, ManagementTeam $managementTeam): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::enum(ManagementTeamStatus::class)],
        ]);

        $managementTeam->fill($validated)->save();

        $managementTeam->load('meet:id,name');

        $this->audit->record('management_team.updated', $managementTeam, [
            'meet' => $managementTeam->meet->name,
            'name' => $managementTeam->name,
            'status' => $managementTeam->status->value,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Team updated.')]);

        return back();
    }

    /**
     * Remove a team outright — cascades to its members.
     */
    public function destroy(ManagementTeam $managementTeam): RedirectResponse
    {
        $managementTeam->load('meet:id,name');

        $context = [
            'meet' => $managementTeam->meet->name,
            'team_type' => $managementTeam->team_type->value,
            'name' => $managementTeam->name,
        ];

        $managementTeam->delete();

        $this->audit->record('management_team.deleted', $managementTeam, $context);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Team removed.')]);

        return back();
    }
}
