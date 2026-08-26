<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\EnsureRecaptchaIsValid;
use App\Actions\Fortify\ResetUserPassword;
use App\Http\Responses\PendingRegistrationResponse;
use App\Models\MeetSport;
use App\Models\Setting;
use App\Models\User;
use App\Services\RegistrationCodeChallenge;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Laravel\Fortify\Actions\AttemptToAuthenticate;
use Laravel\Fortify\Actions\CanonicalizeUsername;
use Laravel\Fortify\Actions\EnsureLoginIsNotThrottled;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Contracts\RedirectsIfTwoFactorAuthenticatable;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(RegisterResponse::class, PendingRegistrationResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::authenticateUsing(function (Request $request): ?User {
            $login = Str::lower(trim((string) $request->input('email')));
            $user = User::query()
                ->whereNull('disabled_at')
                ->where(function ($query) use ($login) {
                    $query->where('username', $login)->orWhere('email', $login);
                })
                ->first();

            if ($user === null || ! Hash::check((string) $request->input('password'), $user->password)) {
                return null;
            }
            if ($user->approval_status !== 'approved') {
                throw ValidationException::withMessages([
                    Fortify::username() => [__('Your account is awaiting approval. Please contact the system administrator or ICT team if you need assistance.')],
                ]);
            }

            return $user;
        });

        // Fortify's own default login pipeline (AuthenticatedSessionController::
        // loginPipeline()) with EnsureRecaptchaIsValid prepended — reCAPTCHA is
        // checked before the throttle/2FA/credential steps so a failed
        // challenge never counts as a failed login attempt against the rate
        // limiter. Registration's own reCAPTCHA check lives in CreateNewUser
        // instead, since that's not pipeline-based.
        Fortify::authenticateThrough(fn (Request $request): array => array_filter([
            EnsureRecaptchaIsValid::class,
            config('fortify.limiters.login') ? null : EnsureLoginIsNotThrottled::class,
            config('fortify.lowercase_usernames') ? CanonicalizeUsername::class : null,
            Features::enabled(Features::twoFactorAuthentication()) ? RedirectsIfTwoFactorAuthenticatable::class : null,
            AttemptToAuthenticate::class,
            PrepareAuthenticatedSession::class,
        ]));
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => $request->session()->get('status'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/reset-password', [
            'email' => $request->email,
            'token' => $request->route('token'),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/forgot-password', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/verify-email', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(function (Request $request) {
            $settings = Setting::current();

            return Inertia::render('auth/register', [
                'registration' => [
                    'users_enabled' => $settings->user_registration_enabled,
                    'coaches_enabled' => $settings->coach_registration_enabled,
                ],
                'passwordRules' => Password::defaults()->toPasswordRulesString(),
                'coachOptions' => MeetSport::query()->where('active', true)
                    ->whereHas('meet', fn ($meets) => $meets->where('is_active', true))
                    ->whereHas('sport', fn ($sports) => $sports->where('active', true))
                    ->with(['sport:id,name', 'meet.delegations.school:id,name', 'meet.delegations.district:id,name,nickname'])
                    ->get()->flatMap(fn (MeetSport $meetSport) => $meetSport->meet->delegations->map(fn ($delegation): array => [
                        'meet_sport_id' => $meetSport->id,
                        'sport' => $meetSport->sport->name, 'delegation_id' => $delegation->id,
                        'delegation' => $delegation->district?->nickname
                            ? sprintf('%s "%s"', $delegation->registrantName(), $delegation->district->nickname)
                            : $delegation->registrantName(),
                    ]))->values(),
                'codeChallengeImage' => app(RegistrationCodeChallenge::class)->generate($request),
            ]);
        });

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/two-factor-challenge'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/confirm-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('passkeys', function (Request $request) {
            return Limit::perMinute(10)->by(
                ($request->input('credential.id') ?: $request->session()->getId()).'|'.$request->ip(),
            );
        });
    }
}
