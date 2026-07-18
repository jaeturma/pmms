<?php

namespace App\Listeners;

use App\Services\AuditLogger;
use Illuminate\Auth\Events\Logout;

class RecordLogout
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        $this->auditLogger->record('auth.logout', user: $event->user);
    }
}
