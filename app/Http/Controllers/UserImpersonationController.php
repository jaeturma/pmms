<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserImpersonationController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function store(Request $request, User $user): RedirectResponse
    {
        $admin = $request->user();
        abort_unless($admin->isAdmin(), 403);
        abort_if($request->session()->has('impersonator_user_id'), 422, 'Stop the current user session before switching accounts.');
        abort_unless(
            $user->hasRole(UserRole::TournamentICT)
                && $user->approval_status === 'approved'
                && $user->disabled_at === null
                && ! $user->trashed(),
            422,
            'Only an active, approved ICT account can be used.',
        );

        $this->audit->record('user.impersonation_started', $user, [
            'administrator_id' => $admin->id,
            'impersonated_user_id' => $user->id,
        ]);
        $request->session()->put('impersonator_user_id', $admin->id);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('results.index')->with('success', __('You are now working as :name. Use the account menu to return to Admin.', ['name' => $user->name]));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $adminId = $request->session()->get('impersonator_user_id');
        abort_unless(is_int($adminId), 403);
        $admin = User::query()->findOrFail($adminId);
        abort_unless($admin->isAdmin() && $admin->disabled_at === null, 403);
        $impersonatedId = $request->user()->id;

        Auth::login($admin);
        $request->session()->forget('impersonator_user_id');
        $request->session()->regenerate();
        $this->audit->record('user.impersonation_stopped', $admin, [
            'administrator_id' => $admin->id,
            'impersonated_user_id' => $impersonatedId,
        ]);

        return redirect()->route('system.users.index')->with('success', __('Returned to the administrator account.'));
    }
}
