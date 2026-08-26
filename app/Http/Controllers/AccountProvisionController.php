<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\AccountProvision;
use App\Notifications\AccountActivationInvitation;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AccountProvisionController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->canManageProductionAccounts(), 403);

        $status = $request->string('status')->toString();
        $search = trim($request->string('search')->toString());

        $provisions = AccountProvision::query()
            ->with(['linkedUser:id,must_change_password,disabled_at', 'person:id,full_name,user_id', 'person.meetSportAssignments.meetSport.sport:id,name'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->whereHas('person', fn ($person) => $person->where('full_name', 'like', "%{$search}%")))
            ->orderByRaw("FIELD(status, 'pending', 'invited', 'activated')")
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (AccountProvision $provision): array => [
                'id' => $provision->id,
                'person' => $provision->person->full_name,
                'email' => $provision->email,
                'suggested_username' => $provision->suggested_username,
                'target_role' => $provision->target_role,
                'status' => $provision->status,
                'reason' => $provision->reason,
                'invited_at' => $provision->invited_at?->format('M j, Y g:i A'),
                'activated_at' => $provision->activated_at?->format('M j, Y g:i A'),
                'must_change_password' => $provision->linkedUser?->must_change_password ?? false,
                'disabled' => $provision->linkedUser?->disabled_at !== null,
                'has_user' => $provision->linked_user_id !== null || $provision->person->user_id !== null,
                'assignments' => $provision->person->meetSportAssignments
                    ->pluck('meetSport.sport.name')->filter()->unique()->values()->join(', '),
            ]);

        return Inertia::render('account-provisions/index', [
            'provisions' => $provisions,
            'filters' => ['search' => $search, 'status' => $status],
            'roleOptions' => [
                ['value' => UserRole::TournamentManager->value, 'label' => UserRole::TournamentManager->label()],
                ['value' => UserRole::TechnicalOfficial->value, 'label' => UserRole::TechnicalOfficial->label()],
            ],
            'canResetPasswords' => $request->user()->canManageProductionAccounts(),
        ]);
    }

    public function invite(Request $request, AccountProvision $accountProvision): RedirectResponse
    {
        abort_unless($request->user()->canManageProductionAccounts(), 403);
        abort_if($accountProvision->status === 'activated', 422, 'This account is already activated.');

        $validated = $request->validate([
            'email' => [
                'required', 'email:rfc', 'max:255', Rule::unique('users', 'email'),
                Rule::unique('account_provisions', 'email')->ignore($accountProvision->id),
            ],
            'target_role' => ['required', Rule::in([
                UserRole::TournamentManager->value,
                UserRole::TechnicalOfficial->value,
            ])],
        ]);

        $token = Str::random(64);
        $accountProvision->forceFill([
            'email' => Str::lower($validated['email']),
            'target_role' => $validated['target_role'],
            'status' => 'invited',
            'activation_token_hash' => hash('sha256', $token),
            'invited_at' => now(),
            'activated_at' => null,
        ])->save();

        $accountProvision->load('person');
        Notification::route('mail', $accountProvision->email)->notify(
            new AccountActivationInvitation(
                $accountProvision,
                route('account-activation.show', ['token' => $token]),
            ),
        );

        $this->audit->record('account_provision.invited', $accountProvision, [
            'person' => $accountProvision->person->full_name,
            'email' => $accountProvision->email,
            'role' => $accountProvision->target_role,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Activation invitation sent.')]);

        return back();
    }

    public function resetPassword(Request $request, AccountProvision $accountProvision): RedirectResponse
    {
        abort_unless($request->user()->canManageProductionAccounts(), 403);

        $password = config('pmms.accounts.default_reset_password');
        abort_unless(is_string($password) && $password !== '', 503, 'The production reset password is not configured.');

        $user = $accountProvision->linkedUser ?? $accountProvision->person->user;
        abort_if($user === null, 422, 'This provision does not have a linked user account.');

        $user->forceFill([
            'password' => Hash::make($password),
            'must_change_password' => true,
            'password_changed_at' => null,
        ])->save();
        $accountProvision->forceFill(['status' => 'active', 'activated_at' => now()])->save();

        $this->audit->record('user.password_reset', $user, [
            'account_provision_id' => $accountProvision->id,
            'reset_by' => $request->user()->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Password reset. The user may change it from Security settings.')]);

        return back();
    }
}
