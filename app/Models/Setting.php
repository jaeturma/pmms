<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

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
        ];
    }

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
}
