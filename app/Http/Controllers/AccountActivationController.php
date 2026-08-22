<?php

namespace App\Http\Controllers;

use App\Enums\MeetSportAssignmentStatus;
use App\Enums\UserRole;
use App\Models\AccountProvision;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class AccountActivationController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function show(string $token): Response
    {
        $provision = $this->validProvision($token);

        return Inertia::render('auth/activate-account', [
            'token' => $token,
            'person' => $provision->person->full_name,
            'email' => $provision->email,
            'role' => UserRole::from($provision->target_role)->label(),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }

    public function activate(Request $request, string $token): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($token, $validated): User {
            $provision = AccountProvision::query()
                ->with('person')
                ->where('activation_token_hash', hash('sha256', $token))
                ->lockForUpdate()
                ->firstOrFail();

            abort_if($provision->status !== 'invited' || $provision->invited_at === null || $provision->invited_at->lt(now()->subDays(7)), 410, 'This activation link is invalid or has expired.');
            abort_if($provision->email === null, 422, 'This invitation does not have an email address.');

            $user = User::query()->create([
                'name' => $provision->person->full_name,
                'email' => $provision->email,
                'password' => Hash::make($validated['password']),
            ]);
            $user->forceFill([
                'role' => UserRole::from($provision->target_role),
                'email_verified_at' => now(),
            ])->save();

            $provision->person->forceFill(['user_id' => $user->id])->save();
            $provision->person->meetSportAssignments()->update([
                'user_id' => $user->id,
                'status' => MeetSportAssignmentStatus::Active->value,
                'start_date' => now()->toDateString(),
            ]);
            $provision->person->managementTeamMemberships()->update(['user_id' => $user->id]);

            if ($user->role === UserRole::TechnicalOfficial) {
                $sportIds = $provision->person->meetSportAssignments()
                    ->join('meet_sports', 'meet_sports.id', '=', 'meet_sport_assignments.meet_sport_id')
                    ->pluck('meet_sports.sport_id');
                $user->sports()->syncWithoutDetaching($sportIds);
            }

            $provision->forceFill([
                'status' => 'activated',
                'activation_token_hash' => null,
                'activated_at' => now(),
            ])->save();

            $this->audit->record('account_provision.activated', $provision, [
                'person' => $provision->person->full_name,
                'user_id' => $user->id,
                'role' => $user->role->value,
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Your account is active. Welcome to PMMS.')]);

        return redirect()->route('dashboard');
    }

    private function validProvision(string $token): AccountProvision
    {
        $provision = AccountProvision::query()
            ->with('person')
            ->where('activation_token_hash', hash('sha256', $token))
            ->firstOrFail();

        abort_if($provision->status !== 'invited' || $provision->invited_at === null || $provision->invited_at->lt(now()->subDays(7)), 410, 'This activation link is invalid or has expired.');

        return $provision;
    }
}
