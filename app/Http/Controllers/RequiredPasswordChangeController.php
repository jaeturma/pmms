<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class RequiredPasswordChangeController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function edit(): Response
    {
        return Inertia::render('auth/change-initial-password', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();
        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
            'password_changed_at' => now(),
        ])->save();

        $user->person?->accountProvision?->forceFill([
            'status' => 'active',
            'activated_at' => now(),
        ])->save();

        $this->audit->record('user.password_changed', $user, ['initial_password_replaced' => true]);

        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
