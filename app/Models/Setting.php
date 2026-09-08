<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property bool $recaptcha_enabled
 * @property string|null $recaptcha_site_key
 * @property string|null $recaptcha_secret_key
 * @property string|null $smtp_host
 * @property int|null $smtp_port
 * @property string|null $smtp_username
 * @property string|null $smtp_password
 * @property string|null $smtp_encryption
 * @property string|null $smtp_from_address
 * @property string|null $smtp_from_name
 * @property bool $email_verification_enabled
 * @property bool $user_registration_enabled
 * @property bool $coach_registration_enabled
 * @property bool $coach_athlete_registration_enabled
 * @property bool $medal_tally_official
 * @property bool $live_scoreboards_suspended
 * @property bool $authenticated_inactivity_expiry_enabled
 * @property int $authenticated_inactivity_timeout_minutes
 * @property string|null $login_splash_title
 * @property int|null $login_background_upload_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'recaptcha_enabled',
    'app_title',
    'facebook_live_enabled',
    'facebook_live_url',
    'recaptcha_site_key',
    'recaptcha_secret_key',
    'smtp_host',
    'smtp_port',
    'smtp_username',
    'smtp_password',
    'smtp_encryption',
    'smtp_from_address',
    'smtp_from_name',
    'email_verification_enabled',
    'user_registration_enabled',
    'coach_registration_enabled',
    'coach_athlete_registration_enabled',
    'medal_tally_official',
    'live_scoreboards_suspended',
    'authenticated_inactivity_expiry_enabled',
    'authenticated_inactivity_timeout_minutes',
    'team_photo_visibility',
    'login_splash_title',
])]
class Setting extends Model
{
    protected $table = 'system_settings';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recaptcha_enabled' => 'boolean',
            'facebook_live_enabled' => 'boolean',
            // Laravel's 'encrypted' cast transparently encrypts on save
            // and decrypts on read using APP_KEY — these two are the only
            // real secrets this app stores in the database anywhere.
            'recaptcha_secret_key' => 'encrypted',
            'smtp_port' => 'integer',
            'smtp_password' => 'encrypted',
            'email_verification_enabled' => 'boolean',
            'user_registration_enabled' => 'boolean',
            'coach_registration_enabled' => 'boolean',
            'coach_athlete_registration_enabled' => 'boolean',
            'medal_tally_official' => 'boolean',
            'live_scoreboards_suspended' => 'boolean',
            'authenticated_inactivity_expiry_enabled' => 'boolean',
            'authenticated_inactivity_timeout_minutes' => 'integer',
        ];
    }

    /**
     * The minimum inactivity window this app will ever enforce — a
     * shorter value would log ordinary operators out mid-task. Both the
     * request validation and this accessor clamp to it, so a legacy or
     * hand-edited row can never drop below it.
     */
    public const MIN_INACTIVITY_TIMEOUT_MINUTES = 5;

    /**
     * The single system settings row, created empty (every feature off)
     * on first access — same pattern as Division::current().
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], []);
    }

    public function appLogo(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'app_logo_upload_id');
    }

    public function loginBackground(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'login_background_upload_id');
    }

    public function favicon(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'favicon_upload_id');
    }

    /**
     * reCAPTCHA is only actually usable once an admin has turned it on
     * *and* both keys are present — a half-configured toggle (enabled with
     * no keys yet, or keys saved but never enabled) must never silently
     * pretend to protect a form it can't actually verify.
     */
    public function recaptchaReady(): bool
    {
        return $this->recaptcha_enabled
            && filled($this->recaptcha_site_key)
            && filled($this->recaptcha_secret_key);
    }

    /**
     * SMTP is "ready" once enough is filled in to actually attempt a send
     * — encryption and a display name are optional, everything else isn't.
     */
    public function smtpReady(): bool
    {
        return filled($this->smtp_host)
            && filled($this->smtp_port)
            && filled($this->smtp_username)
            && filled($this->smtp_password)
            && filled($this->smtp_from_address);
    }

    /**
     * Email verification is only actually enforced once an admin has
     * turned it on *and* outgoing mail can actually be sent — otherwise a
     * new registrant would receive a verification prompt with no way to
     * ever receive the link, permanently locking them out.
     */
    public function emailVerificationActive(): bool
    {
        return $this->email_verification_enabled && $this->smtpReady();
    }

    public function medalTallyIsOfficial(): bool
    {
        return $this->medal_tally_official === true;
    }

    /**
     * System Administrator has pulled the public-load emergency lever:
     * all PUBLIC live-scoreboard viewing/polling is suspended. Never
     * affects authenticated ICT scoring.
     */
    public function liveScoreboardsAreSuspended(): bool
    {
        return $this->live_scoreboards_suspended === true;
    }

    /**
     * Whether the idle-session logout is switched on at all. Defaults
     * OFF — enabling the Production Load Controls feature changes no
     * session behaviour until a System Administrator flips this.
     */
    public function inactivityExpiryEnabled(): bool
    {
        return $this->authenticated_inactivity_expiry_enabled === true;
    }

    /**
     * The idle window (minutes) after which a logged-in web session is
     * expired on its next genuine request — never below
     * MIN_INACTIVITY_TIMEOUT_MINUTES regardless of what is stored.
     */
    public function inactivityTimeoutMinutes(): int
    {
        return max(
            self::MIN_INACTIVITY_TIMEOUT_MINUTES,
            (int) ($this->authenticated_inactivity_timeout_minutes ?: self::MIN_INACTIVITY_TIMEOUT_MINUTES),
        );
    }

    /**
     * A 60-second cached projection of the two Production Load Controls,
     * for the hot paths that read them on every request (the inactivity
     * middleware, the public-scoreboard gate, Inertia shared props) so
     * they never add a `system_settings` query per request. Busted by
     * every writer (`SystemSettingsController`, `LoadControlController`).
     *
     * @return array{suspended: bool, inactivity_expiry_enabled: bool, inactivity_timeout_minutes: int}
     */
    public static function loadControls(): array
    {
        return Cache::remember(
            self::LOAD_CONTROLS_CACHE_KEY,
            60,
            function (): array {
                $settings = self::current();

                return [
                    'suspended' => $settings->liveScoreboardsAreSuspended(),
                    'inactivity_expiry_enabled' => $settings->inactivityExpiryEnabled(),
                    'inactivity_timeout_minutes' => $settings->inactivityTimeoutMinutes(),
                ];
            },
        );
    }

    public const LOAD_CONTROLS_CACHE_KEY = 'pmms:load-controls';

    public static function forgetLoadControlsCache(): void
    {
        Cache::forget(self::LOAD_CONTROLS_CACHE_KEY);
    }
}
