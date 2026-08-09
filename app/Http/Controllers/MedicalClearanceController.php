<?php

namespace App\Http\Controllers;

use App\Enums\ManagementTeamMemberStatus;
use App\Enums\ManagementTeamType;
use App\Enums\MedicalClearanceStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Concerns\ScopesToManagementTeam;
use App\Models\Athlete;
use App\Models\ManagementTeamMember;
use App\Models\MedicalAccessLog;
use App\Models\MedicalClearance;
use App\Models\Meet;
use App\Models\Personnel;
use App\Models\User;
use App\Policies\MedicalPolicy;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Management UI for the Medical Team's clearance roster (WP-REALIGN-12)
 * — see docs/medical-drrm.md. Three-tier visibility: everyone who can
 * `viewAny()` sees the aggregate `status` for every row; only rows for a
 * meet where the viewer has detail access (`MedicalPolicy::viewDetail()`
 * — Medical Team or Admin) also carry `conditions`/`emergency_contact_*`/
 * `notes` in the response. Break-glass access is handled separately by
 * `MedicalAccessController`.
 */
class MedicalClearanceController extends Controller
{
    use ScopesToManagementTeam;

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly MedicalPolicy $policy,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        abort_unless($this->policy->viewAny($user), 403);

        $accessibleMeetIds = $this->accessibleMeetIds($user, ManagementTeamType::Medical);
        $detailMeetIds = $this->detailMeetIds($user);

        $clearances = MedicalClearance::query()
            ->with(['athlete:id,delegation_id,first_name,last_name', 'personnel:id,delegation_id,first_name,last_name'])
            ->when($accessibleMeetIds !== null, fn ($q) => $q->whereIn('meet_id', $accessibleMeetIds))
            ->orderByDesc('id')
            ->get();

        return Inertia::render('medical/index', [
            'clearances' => $clearances->map(function (MedicalClearance $clearance) use ($user, $detailMeetIds): array {
                $canViewDetail = $user->hasRole(UserRole::Admin) || $detailMeetIds->contains($clearance->meet_id);

                return [
                    'id' => $clearance->id,
                    'person' => $clearance->personName(),
                    'person_type' => $clearance->athlete_id !== null ? 'athlete' : 'personnel',
                    'status' => $clearance->status->value,
                    'status_label' => $clearance->status->label(),
                    'can_view_detail' => $canViewDetail,
                    'conditions' => $canViewDetail ? $clearance->conditions : null,
                    'emergency_contact_name' => $canViewDetail ? $clearance->emergency_contact_name : null,
                    'emergency_contact_phone' => $canViewDetail ? $clearance->emergency_contact_phone : null,
                    'consent_confirmed' => $canViewDetail ? $clearance->consent_confirmed : null,
                    'notes' => $canViewDetail ? $clearance->notes : null,
                ];
            }),
            'canManage' => $this->policy->manage($user, Meet::current()),
            'athleteOptions' => Athlete::query()
                ->whereHas('delegation', fn ($q) => $q->where('meet_id', Meet::current()->id))
                ->get(['id', 'first_name', 'last_name'])
                ->map(fn (Athlete $athlete): array => [
                    'id' => $athlete->id,
                    'label' => "{$athlete->first_name} {$athlete->last_name}",
                ]),
            'personnelOptions' => Personnel::query()
                ->whereHas('delegation', fn ($q) => $q->where('meet_id', Meet::current()->id))
                ->get(['id', 'first_name', 'last_name'])
                ->map(fn (Personnel $personnel): array => [
                    'id' => $personnel->id,
                    'label' => "{$personnel->first_name} {$personnel->last_name}",
                ]),
            'statusOptions' => array_map(
                fn (MedicalClearanceStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
                MedicalClearanceStatus::cases(),
            ),
            'canRequestEmergencyAccess' => $this->policy->requestEmergencyAccess($user),
            'pendingAccessLogs' => $this->pendingAccessLogs($user, $accessibleMeetIds),
        ]);
    }

    /**
     * Unreviewed break-glass access invocations this user is entitled to
     * review — every other role gets an empty list, since only Admin or
     * a Medical Team lead can review (`MedicalPolicy::reviewAccess()`).
     *
     * @param  Collection<int, int>|null  $accessibleMeetIds
     * @return array<int, array<string, mixed>>
     */
    private function pendingAccessLogs(User $user, ?Collection $accessibleMeetIds): array
    {
        $query = MedicalAccessLog::query()
            ->with(['medicalClearance.meet:id,name', 'medicalClearance.athlete:id,first_name,last_name', 'medicalClearance.personnel:id,first_name,last_name', 'accessedBy:id,name'])
            ->whereNull('reviewed_at');

        if ($accessibleMeetIds !== null) {
            $query->whereHas('medicalClearance', fn ($q) => $q->whereIn('meet_id', $accessibleMeetIds));
        }

        return $query->orderByDesc('id')->get()
            ->filter(fn (MedicalAccessLog $log): bool => $this->policy->reviewAccess($user, $log->medicalClearance->meet))
            ->map(fn (MedicalAccessLog $log): array => [
                'id' => $log->id,
                'person' => $log->medicalClearance->personName(),
                'meet' => $log->medicalClearance->meet->name,
                'accessed_by' => $log->accessedBy->name,
                'reason' => $log->reason,
                'accessed_at' => $log->accessed_at->toDayDateTimeString(),
            ])
            ->values()
            ->all();
    }

    /**
     * Every meet_id where this user has an Active Medical Team
     * membership, regardless of role — distinct from `accessibleMeetIds()`,
     * which returns `null` (unrestricted) for Organizer even though
     * Organizer alone never grants detail access.
     *
     * @return Collection<int, int>
     */
    private function detailMeetIds(User $user): Collection
    {
        return ManagementTeamMember::query()
            ->where('user_id', $user->id)
            ->where('status', ManagementTeamMemberStatus::Active)
            ->whereHas('managementTeam', fn ($q) => $q->where('team_type', ManagementTeamType::Medical))
            ->with('managementTeam:id,meet_id')
            ->get()
            ->pluck('managementTeam.meet_id')
            ->unique()
            ->values();
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'athlete_id' => ['nullable', 'integer', Rule::exists('athletes', 'id'), 'required_without:personnel_id', 'prohibits:personnel_id'],
            'personnel_id' => ['nullable', 'integer', Rule::exists('personnel', 'id')],
            'status' => ['required', Rule::enum(MedicalClearanceStatus::class)],
            'conditions' => ['nullable', 'string', 'max:2000'],
            'emergency_contact_name' => ['nullable', 'string', 'max:120'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'consent_confirmed' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $meet = Meet::current();
        abort_unless($this->policy->manage($request->user(), $meet), 403);

        if (MedicalClearance::query()
            ->where('meet_id', $meet->id)
            ->where(function ($q) use ($validated) {
                if (! empty($validated['athlete_id'])) {
                    $q->where('athlete_id', $validated['athlete_id']);
                } else {
                    $q->where('personnel_id', $validated['personnel_id']);
                }
            })
            ->exists()) {
            throw ValidationException::withMessages([
                'athlete_id' => __('A clearance record already exists for this person at this meet.'),
            ]);
        }

        $clearance = MedicalClearance::create([
            ...$validated,
            'meet_id' => $meet->id,
            'consent_confirmed' => $request->boolean('consent_confirmed'),
            'consent_confirmed_at' => $request->boolean('consent_confirmed') ? now() : null,
        ]);

        $this->audit->record('medical_clearance.created', $clearance, [
            'meet' => $meet->name,
            'status' => $clearance->status->value,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Clearance record added.')]);

        return back();
    }

    public function update(Request $request, MedicalClearance $medicalClearance): RedirectResponse
    {
        abort_unless($this->policy->manage($request->user(), $medicalClearance->meet), 403);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(MedicalClearanceStatus::class)],
            'conditions' => ['nullable', 'string', 'max:2000'],
            'emergency_contact_name' => ['nullable', 'string', 'max:120'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'consent_confirmed' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $wasConfirmed = $medicalClearance->consent_confirmed;
        $nowConfirmed = $request->boolean('consent_confirmed');

        $medicalClearance->fill([
            ...$validated,
            'consent_confirmed' => $nowConfirmed,
            'consent_confirmed_at' => match (true) {
                $nowConfirmed && ! $wasConfirmed => now(),
                ! $nowConfirmed => null,
                default => $medicalClearance->consent_confirmed_at,
            },
        ])->save();

        $this->audit->record('medical_clearance.updated', $medicalClearance, [
            'meet' => $medicalClearance->meet->name,
            'status' => $medicalClearance->status->value,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Clearance record updated.')]);

        return back();
    }
}
