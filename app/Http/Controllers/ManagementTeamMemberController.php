<?php

namespace App\Http\Controllers;

use App\Enums\ManagementTeamMemberStatus;
use App\Models\ManagementTeamMember;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Membership management for `ManagementTeam` (WP-REALIGN-09). Unlike
 * `MeetSportAssignmentController::store()`, candidate members are not
 * restricted by `UserRole` — a committee (e.g. Billeting, Food,
 * Transport) may reasonably be staffed by any account, not just Admin/
 * Organizer/Technical Official.
 */
class ManagementTeamMemberController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Add a person to a team.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'management_team_id' => ['required', 'integer', Rule::exists('management_teams', 'id')],
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'role_title' => ['nullable', 'string', 'max:120'],
            'is_head' => ['boolean'],
            'responsibilities' => ['nullable', 'string', 'max:1000'],
        ]);

        if (ManagementTeamMember::query()
            ->where('management_team_id', $validated['management_team_id'])
            ->where('user_id', $validated['user_id'])
            ->exists()) {
            throw ValidationException::withMessages([
                'user_id' => __('This person is already a member of this team.'),
            ]);
        }

        $member = ManagementTeamMember::create([
            'management_team_id' => $validated['management_team_id'],
            'user_id' => $validated['user_id'],
            'role_title' => $validated['role_title'] ?? null,
            'is_head' => $request->boolean('is_head'),
            'responsibilities' => $validated['responsibilities'] ?? null,
            'status' => ManagementTeamMemberStatus::Pending,
        ]);

        $member->load(['managementTeam:id,name', 'user:id,name']);

        $this->audit->record('management_team_member.added', $member, [
            'team' => $member->managementTeam->name,
            'user' => $member->user->name,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member added.')]);

        return back();
    }

    /**
     * Move a membership through its own lifecycle (e.g. Pending → Active
     * once confirmed, or → Ended) without deleting the record.
     */
    public function updateStatus(Request $request, ManagementTeamMember $managementTeamMember): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(ManagementTeamMemberStatus::class)],
        ]);

        $managementTeamMember->forceFill(['status' => $validated['status']])->save();

        $managementTeamMember->load(['managementTeam:id,name', 'user:id,name']);

        $this->audit->record('management_team_member.status_updated', $managementTeamMember, [
            'team' => $managementTeamMember->managementTeam->name,
            'user' => $managementTeamMember->user->name,
            'status' => $managementTeamMember->status->value,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member status updated.')]);

        return back();
    }

    /**
     * Remove a person from a team outright.
     */
    public function destroy(ManagementTeamMember $managementTeamMember): RedirectResponse
    {
        $managementTeamMember->load(['managementTeam:id,name', 'user:id,name']);

        $context = [
            'team' => $managementTeamMember->managementTeam->name,
            'user' => $managementTeamMember->user->name,
        ];

        $managementTeamMember->delete();

        $this->audit->record('management_team_member.removed', $managementTeamMember, $context);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member removed.')]);

        return back();
    }
}
