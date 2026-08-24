<?php

namespace App\Http\Controllers;

use App\Http\Requests\SystemSettingsRequest;
use App\Models\Setting;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\FileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * System-wide settings (reCAPTCHA, outgoing mail, email verification) —
 * Administrator-only (`can:administer` route middleware), distinct from
 * the per-user `resources/js/pages/settings/` area (profile/security/
 * appearance) and from Division::current()'s per-deployment configuration,
 * which this deliberately doesn't touch.
 */
class SystemSettingsController extends Controller
{
    public function __construct(private readonly AuditLogger $audit, private readonly FileUploadService $uploads) {}

    /**
     * Secrets (`recaptcha_secret_key`/`smtp_password`) are never sent back
     * to the browser once saved — only a `has_*` boolean, so the edit form
     * can show "already set" without ever re-transmitting a decrypted
     * secret the admin didn't just type.
     */
    public function edit(): Response
    {
        $settings = Setting::current();

        return Inertia::render('system-settings/edit', [
            'settings' => [
                'app_title' => $settings->app_title ?? config('app.name'),
                'app_logo_url' => $settings->app_logo_upload_id === null ? null : route('branding.logo'),
                'login_splash_title' => $settings->login_splash_title ?: 'One secure place to manage every moment of the meet.',
                'login_background_url' => $settings->login_background_upload_id === null ? null : route('branding.login-background'),
                'facebook_live_enabled' => $settings->facebook_live_enabled,
                'facebook_live_url' => $settings->facebook_live_url,
                'recaptcha_enabled' => $settings->recaptcha_enabled,
                'recaptcha_site_key' => $settings->recaptcha_site_key,
                'has_recaptcha_secret_key' => filled($settings->recaptcha_secret_key),
                'recaptcha_ready' => $settings->recaptchaReady(),

                'smtp_host' => $settings->smtp_host,
                'smtp_port' => $settings->smtp_port,
                'smtp_username' => $settings->smtp_username,
                'has_smtp_password' => filled($settings->smtp_password),
                'smtp_encryption' => $settings->smtp_encryption,
                'smtp_from_address' => $settings->smtp_from_address,
                'smtp_from_name' => $settings->smtp_from_name,
                'smtp_ready' => $settings->smtpReady(),

                'email_verification_enabled' => $settings->email_verification_enabled,
                'email_verification_active' => $settings->emailVerificationActive(),
                'user_registration_enabled' => $settings->user_registration_enabled,
                'coach_registration_enabled' => $settings->coach_registration_enabled,
                'coach_athlete_registration_enabled' => $settings->coach_athlete_registration_enabled,
                'medal_tally_official' => $settings->medalTallyIsOfficial(),
            ],
        ]);
    }

    public function update(SystemSettingsRequest $request): RedirectResponse
    {
        $settings = Setting::current();
        $validated = $request->validated();

        $wasEmailVerificationActive = $settings->emailVerificationActive();

        $settings->forceFill([
            'app_title' => $validated['app_title'] ?? $settings->app_title ?? config('app.name'),
            'login_splash_title' => $validated['login_splash_title'] ?? $settings->login_splash_title,
            'facebook_live_enabled' => $validated['facebook_live_enabled'] ?? $settings->facebook_live_enabled,
            'facebook_live_url' => $validated['facebook_live_url'] ?? null,
            'recaptcha_enabled' => $validated['recaptcha_enabled'],
            'recaptcha_site_key' => $validated['recaptcha_site_key'] ?? null,
            'smtp_host' => $validated['smtp_host'] ?? null,
            'smtp_port' => $validated['smtp_port'] ?? null,
            'smtp_username' => $validated['smtp_username'] ?? null,
            'smtp_encryption' => $validated['smtp_encryption'] ?? null,
            'smtp_from_address' => $validated['smtp_from_address'] ?? null,
            'smtp_from_name' => $validated['smtp_from_name'] ?? null,
            'email_verification_enabled' => $validated['email_verification_enabled'],
            'user_registration_enabled' => $validated['user_registration_enabled'] ?? $settings->user_registration_enabled,
            'coach_registration_enabled' => $validated['coach_registration_enabled'] ?? $settings->coach_registration_enabled,
            'coach_athlete_registration_enabled' => $validated['coach_athlete_registration_enabled'] ?? $settings->coach_athlete_registration_enabled,
            'medal_tally_official' => $validated['medal_tally_official'] ?? $settings->medalTallyIsOfficial(),
        ]);

        if ($request->hasFile('app_logo')) {
            $oldLogo = $settings->appLogo;
            $settings->app_logo_upload_id = $this->uploads->store($request->file('app_logo'), $request->user(), 'app_logo')->id;
            $settings->save();
            if ($oldLogo !== null) {
                $this->uploads->delete($oldLogo);
            }
        }

        $oldLoginBackground = null;
        if ($request->hasFile('login_background')) {
            $oldLoginBackground = $settings->loginBackground;
            $settings->login_background_upload_id = $this->uploads->store($request->file('login_background'), $request->user(), 'login_background')->id;
        } elseif ($request->boolean('remove_login_background') && $settings->login_background_upload_id !== null) {
            $oldLoginBackground = $settings->loginBackground;
            $settings->login_background_upload_id = null;
        }

        if ($oldLoginBackground !== null) {
            $this->uploads->delete($oldLoginBackground);
        }

        // A blank submitted secret means "leave the stored one unchanged,"
        // never "clear it" — see edit()'s note on why the real value is
        // never round-tripped to the browser in the first place.
        if (filled($validated['recaptcha_secret_key'] ?? null)) {
            $settings->recaptcha_secret_key = $validated['recaptcha_secret_key'];
        }

        if (filled($validated['smtp_password'] ?? null)) {
            $settings->smtp_password = $validated['smtp_password'];
        }

        $settings->save();

        // Grandfather every existing account the moment email verification
        // actually becomes *enforced* (not just toggled on — it also needs
        // working SMTP), so no one who already had access is retroactively
        // locked out. Only accounts registered after this point start
        // unverified.
        if (! $wasEmailVerificationActive && $settings->emailVerificationActive()) {
            $grandfathered = User::query()->whereNull('email_verified_at')->update(['email_verified_at' => now()]);

            $this->audit->record('system_settings.email_verification_grandfathered', $settings, [
                'account_count' => $grandfathered,
            ]);
        }

        $this->audit->record('system_settings.updated', $settings, [
            'recaptcha_enabled' => $settings->recaptcha_enabled,
            'facebook_live_enabled' => $settings->facebook_live_enabled,
            'recaptcha_ready' => $settings->recaptchaReady(),
            'email_verification_enabled' => $settings->email_verification_enabled,
            'email_verification_active' => $settings->emailVerificationActive(),
            'user_registration_enabled' => $settings->user_registration_enabled,
            'coach_registration_enabled' => $settings->coach_registration_enabled,
            'coach_athlete_registration_enabled' => $settings->coach_athlete_registration_enabled,
            'medal_tally_official' => $settings->medal_tally_official,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('System settings updated.')]);

        return back();
    }

    public function logo(): HttpResponse
    {
        $upload = Setting::current()->appLogo;
        abort_if($upload === null, 404);

        return Storage::disk($upload->disk)->response($upload->path, $upload->original_name);
    }

    public function loginBackground(): HttpResponse
    {
        $upload = Setting::current()->loginBackground;
        abort_if($upload === null, 404);

        return Storage::disk($upload->disk)->response($upload->path, $upload->original_name);
    }
}
