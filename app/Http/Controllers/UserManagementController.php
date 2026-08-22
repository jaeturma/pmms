<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->canManageProductionAccounts(), 403);
        $search = trim($request->string('search')->toString());

        $users = User::query()
            ->when($search !== '', fn ($query) => $query->where(function ($scope) use ($search) {
                $scope->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role->value,
                'role_label' => $user->role->label(),
                'disabled' => $user->disabled_at !== null,
                'approval_status' => $user->approval_status,
                'last_updated' => $user->updated_at?->format('M j, Y g:i A'),
            ]);

        return Inertia::render('system/users', [
            'users' => $users,
            'filters' => ['search' => $search],
            'roles' => collect(UserRole::cases())->map(fn (UserRole $role) => [
                'value' => $role->value,
                'label' => $role->label(),
                'permissions' => $this->rolePermissions($role),
            ])->values(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->canManageProductionAccounts(), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'disabled' => ['required', 'boolean'],
        ]);
        abort_if($request->user()->is($user) && ($data['role'] !== UserRole::Admin->value || $data['disabled']), 422, 'You cannot remove or disable your own administrator access.');

        $before = $user->only(['name', 'username', 'email', 'role', 'disabled_at']);
        $user->forceFill([
            'name' => $data['name'],
            'username' => $data['username'] ?: null,
            'email' => $data['email'] ?: null,
            'role' => $data['role'],
            'disabled_at' => $data['disabled'] ? ($user->disabled_at ?? now()) : null,
        ])->save();

        $this->audit->record('user.updated', $user, [
            'before' => $before,
            'role' => $user->role->value,
            'disabled' => $user->disabled_at !== null,
        ]);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('User account updated.')]);

        return back();
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->canManageProductionAccounts(), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255', Rule::unique('users')],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')],
            'role' => ['required', Rule::enum(UserRole::class)],
        ]);
        abort_if(empty($data['username']) && empty($data['email']), 422, 'A username or email address is required.');

        $password = config('pmms.accounts.default_reset_password');
        abort_unless(is_string($password) && $password !== '', 503, 'The initial password is not configured.');
        $user = User::query()->create([
            'name' => $data['name'],
            'username' => $data['username'] ?: null,
            'email' => $data['email'] ?: null,
            'password' => $password,
        ]);
        $user->forceFill(['role' => $data['role'], 'email_verified_at' => $data['email'] ? now() : null])->save();
        $this->audit->record('user.created', $user, ['role' => $user->role->value]);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created with password DdOPaa2026!.')]);

        return back();
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->canManageProductionAccounts(), 403);
        $password = config('pmms.accounts.default_reset_password');
        abort_unless(is_string($password) && $password !== '', 503, 'The reset password is not configured.');

        $user->forceFill([
            'password' => Hash::make($password),
            'must_change_password' => false,
            'password_changed_at' => null,
        ])->save();
        $this->audit->record('user.password_reset', $user, ['reset_by' => $request->user()->id]);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Password reset to DdOPaa2026!.')]);

        return back();
    }

    public function approve(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->canManageProductionAccounts(), 403);
        if ($user->coachOnboardingRequest()->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages([
                'approval' => __('Approve coach accounts from Registration > Coach so their requested sport and event access is activated correctly.'),
            ]);
        }
        DB::transaction(function () use ($request, $user): void {
            $coachRequest = $user->coachOnboardingRequest()->where('status', 'pending')->first();
            $user->forceFill([
                'role' => $coachRequest === null ? $user->role : UserRole::Coach,
                'approval_status' => 'approved',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'disabled_at' => null,
            ])->save();
            $coachRequest?->forceFill([
                'status' => 'approved',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ])->save();
        });
        $this->audit->record('user.approved', $user, ['approved_by' => $request->user()->id]);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('User account approved.')]);

        return back();
    }

    /** @return list<string> */
    private function rolePermissions(UserRole $role): array
    {
        return match ($role) {
            UserRole::Admin => ['Full system administration', 'Manage users, roles and settings', 'Manage all meet data'],
            UserRole::Organizer => ['Manage meet setup and operations', 'Manage registrations and competition data'],
            UserRole::DelegationOfficer => ['Manage assigned delegation registration'],
            UserRole::TechnicalOfficial => ['Operate assigned sport scoring'],
            UserRole::TournamentManager => ['Manage assigned sport schedule, matches and results'],
            UserRole::Coach => ['Manage approved team athletes and entries'],
            UserRole::Viewer => ['View published schedules, results and medal tally'],
        };
    }
}
