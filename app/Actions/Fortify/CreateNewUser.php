<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\CoachOnboardingRequest;
use App\Models\Setting;
use App\Models\User;
use App\Services\FileUploadService;
use App\Services\RecaptchaVerifier;
use App\Services\RegistrationCodeChallenge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    private readonly RecaptchaVerifier $recaptcha;

    public function __construct(
        RecaptchaVerifier $recaptcha,
        private readonly RegistrationCodeChallenge $codeChallenge,
        private readonly Request $request,
        private readonly FileUploadService $uploads,
    ) {
        $this->recaptcha = $recaptcha;
    }

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): User
    {
        $settings = Setting::current();
        $isCoach = ($input['account_type'] ?? 'viewer') === 'coach';

        if (($isCoach && ! $settings->coach_registration_enabled)
            || (! $isCoach && ! $settings->user_registration_enabled)) {
            throw ValidationException::withMessages([
                'registration' => [__('This type of account registration is currently suspended by the administrator.')],
            ]);
        }

        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'account_type' => ['nullable', 'in:viewer,coach'],
            'district_id' => [
                Rule::requiredIf(($input['account_type'] ?? 'viewer') === 'coach'),
                'nullable',
                Rule::exists('districts', 'id')->where('active', true),
            ],
            'event_ids' => [Rule::requiredIf(($input['account_type'] ?? 'viewer') === 'coach'), 'nullable', 'array', 'min:1'],
            'event_ids.*' => ['integer', 'distinct', Rule::exists('events', 'id')->where('active', true)],
            'coach_profile' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'coach_certification' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'code_challenge' => ['required', 'string', 'size:5'],
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

        if (! $this->codeChallenge->verify($this->request, $input['code_challenge'] ?? null)) {
            throw ValidationException::withMessages([
                'code_challenge' => [__('The image verification code is incorrect. Refresh the page for a new code.')],
            ]);
        }

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);
        $user->forceFill(['approval_status' => 'pending', 'approved_at' => null, 'approved_by' => null])->save();

        if (($input['account_type'] ?? 'viewer') === 'coach') {
            $onboardingRequest = CoachOnboardingRequest::query()->create([
                'user_id' => $user->id,
                'district_id' => $input['district_id'],
                'event_id' => $input['event_ids'][0],
                'profile_upload_id' => isset($input['coach_profile'])
                    ? $this->uploads->store($input['coach_profile'], $user, 'coach_profile')->id
                    : null,
                'certification_upload_id' => isset($input['coach_certification'])
                    ? $this->uploads->store($input['coach_certification'], $user, 'coach_certification')->id
                    : null,
            ]);
            $onboardingRequest->events()->sync($input['event_ids']);
        }

        return $user;
    }
}
