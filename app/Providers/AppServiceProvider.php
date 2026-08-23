<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();
        $this->configureMail();
    }

    /**
     * Define the application's coarse authorization gates.
     *
     * Module-specific rules belong in policies; these gates cover the two
     * cross-cutting levels: full administration and meet-data management.
     */
    protected function configureAuthorization(): void
    {
        Gate::define('administer', fn (User $user): bool => $user->role === UserRole::Admin);
        Gate::define('view-system-logs', fn (User $user): bool => $user->isAdmin() || $user->canManageProductionAccounts());

        Gate::define('manage-meet-data', fn (User $user): bool => $user->isAdmin());
        Gate::define('view-management-reports', fn (User $user): bool => $user->canViewManagementReports());
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Override the SMTP mailer's config from the admin-editable
     * `Setting::current()` row when it's fully filled in — this is the
     * only mailer this app ever asks an admin to configure through the
     * UI, so `.env`'s MAIL_MAILER/MAIL_HOST/etc. stay the pre-database
     * fallback (`config/mail.php` untouched) for local development and
     * for the moment before an admin has saved anything here yet.
     *
     * Guarded by `Schema::hasTable()`, not just a try/catch: this runs on
     * every request/command, including `php artisan migrate` itself
     * before this table exists — a plain query here would break every
     * artisan command on a fresh clone, not just fail silently.
     */
    private function configureMail(): void
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            // Queue workers are the one console context that genuinely
            // sends mail (queued verification emails), but ordinary
            // artisan commands (migrate, tinker, etc.) never need to —
            // and querying `system_settings` from every single command
            // invocation, including ones that run before it exists, isn't
            // worth it just to support the one command that does.
            if (! app()->runningConsoleCommand('queue:work') && ! app()->runningConsoleCommand('queue:listen')) {
                return;
            }
        }

        if (! Schema::hasTable('system_settings')) {
            return;
        }

        $settings = Setting::current();

        if (! $settings->smtpReady()) {
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $settings->smtp_host,
            'mail.mailers.smtp.port' => $settings->smtp_port,
            'mail.mailers.smtp.username' => $settings->smtp_username,
            'mail.mailers.smtp.password' => $settings->smtp_password,
            // Laravel 11+'s MailManager reads a Symfony-style 'scheme',
            // not a plain 'encryption' key — 'ssl' means implicit TLS
            // ('smtps'); leaving it unset for 'tls'/null lets Laravel's
            // own port-based default (465 → smtps, else smtp+STARTTLS)
            // apply, which is already correct for the common 587/STARTTLS
            // case.
            'mail.mailers.smtp.scheme' => $settings->smtp_encryption === 'ssl' ? 'smtps' : null,
            'mail.from.address' => $settings->smtp_from_address,
            'mail.from.name' => $settings->smtp_from_name ?: config('mail.from.name'),
        ]);
    }
}
