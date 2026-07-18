<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuditLogger
{
    /**
     * Record an audit trail entry.
     *
     * @param  array<string, mixed>  $context
     */
    public function record(
        string $action,
        ?Model $subject = null,
        array $context = [],
        ?Authenticatable $user = null,
    ): AuditLog {
        $user ??= Auth::user();

        $userAgent = request()->userAgent();

        return AuditLog::create([
            'user_id' => $user?->getAuthIdentifier(),
            'action' => $action,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'context' => $context === [] ? null : $context,
            'ip_address' => request()->ip(),
            'user_agent' => $userAgent === null ? null : Str::limit($userAgent, 255, ''),
        ]);
    }
}
