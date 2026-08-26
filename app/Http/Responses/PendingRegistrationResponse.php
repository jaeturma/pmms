<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\RegisterResponse;

class PendingRegistrationResponse implements RegisterResponse
{
    public function toResponse($request): RedirectResponse
    {
        Auth::guard(config('fortify.guard'))->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $message = __('Registration submitted. You can sign in after an administrator or ICT reviewer approves your account.');
        if ($request->input('account_type') === 'coach') {
            $message .= ' '.__('After approval, ICT can assign your sports events or you can add them yourself from Coach Sports Events.');
        }

        return redirect()->route('login')->with('status', $message);
    }
}
