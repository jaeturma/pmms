<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\CoachOnboardingRequest;
use App\Models\User;
use App\Services\RecaptchaVerifier;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    private readonly RecaptchaVerifier $recaptcha;

    public function __construct(RecaptchaVerifier $recaptcha)
    {
        $this->recaptcha = $recaptcha;
    }

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'account_type' => ['nullable', 'in:viewer,coach'],
        ])->validate();

        // Same reCAPTCHA check as the login pipeline
        // (`App\Actions\Fortify\EnsureRecaptchaIsValid`) — registration
        // doesn't go through Fortify's pipeline mechanism, so it's a
        // direct check here instead, but the same no-op-unless-ready
        // `RecaptchaVerifier::passes()` behavior applies.
        if (! $this->recaptcha->passes($input['g-recaptcha-response'] ?? null)) {
            throw ValidationException::withMessages([
                'recaptcha' => [__('Please complete the reCAPTCHA challenge.')],
            ]);
        }

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);

        if (($input['account_type'] ?? 'viewer') === 'coach') {
            CoachOnboardingRequest::query()->create(['user_id' => $user->id]);
        }

        return $user;
    }
}
