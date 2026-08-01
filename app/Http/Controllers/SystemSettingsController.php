<?php

namespace App\Http\Controllers;

use App\Http\Requests\SystemSettingsRequest;
use App\Models\Setting;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * System-wide settings (reCAPTCHA, outgoing mail, email verification) —
 * Administrator-only (`can:administer` route middleware), distinct from
 * the per-user `resources/js/pages/settings/` area (profile/security/
 * appearance) and from Division::current()'s per-deployment configuration,
 * which this deliberately doesn't touch.
 */
class SystemSettingsController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

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
            ],
        ]);
    }

    public function update(SystemSettingsRequest $request): RedirectResponse
    {
        $settings = Setting::current();
        $validated = $request->validated();

        $wasEmailVerificationActive = $settings->emailVerificationActive();

        $settings->forceFill([
            'recaptcha_enabled' => $validated['recaptcha_enabled'],
            'recaptcha_site_key' => $validated['recaptcha_site_key'] ?? null,
            'smtp_host' => $validated['smtp_host'] ?? null,
            'smtp_port' => $validated['smtp_port'] ?? null,
            'smtp_username' => $validated['smtp_username'] ?? null,
            'smtp_encryption' => $validated['smtp_encryption'] ?? null,
            'smtp_from_address' => $validated['smtp_from_address'] ?? null,
            'smtp_from_name' => $validated['smtp_from_name'] ?? null,
            'email_verification_enabled' => $validated['email_verification_enabled'],
        ]);

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
            'recaptcha_ready' => $settings->recaptchaReady(),
            'email_verification_enabled' => $settings->email_verification_enabled,
            'email_verification_active' => $settings->emailVerificationActive(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('System settings updated.')]);

        return back();
    }
}
