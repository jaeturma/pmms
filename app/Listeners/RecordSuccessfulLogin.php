<?php

namespace App\Listeners;

use App\Services\AuditLogger;
use Illuminate\Auth\Events\Login;

class RecordSuccessfulLogin
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $this->auditLogger->record('auth.login', user: $event->user);
    }
}
