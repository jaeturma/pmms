# PMMS Audit Trail Foundation

Immutable record of who did what, when, and from where (WP-01-11).

## Storage

`audit_logs` table: `user_id` (FK `users`, null on user deletion — history is preserved), `action` (dot-notation string, e.g. `auth.login`, `file.uploaded`), nullable `auditable_type`/`auditable_id` morph to the affected record, `context` JSON, `ip_address`, `user_agent`, `created_at` only (no updates — audit entries are never modified).

## Recording

Use `App\Services\AuditLogger`:

```php
$auditLogger->record('athlete.updated', $athlete, ['changes' => $changes]);
// action, optional subject model, optional context array, optional explicit user
```

The current authenticated user, IP, and user agent are captured automatically; pass `user:` explicitly for event listeners or system actions.

## Events Recorded Today

| Action | Source |
|---|---|
| `auth.login` / `auth.logout` | `App\Listeners\RecordSuccessfulLogin` / `RecordLogout` (auto-discovered Fortify/auth event listeners) |
| `file.uploaded` / `file.deleted` | `App\Services\FileUploadService` |

## Conventions

- Action names: `<domain>.<verb-past-tense>` (`meet.created`, `result.published`).
- Record audit entries from services (not controllers) so every code path is covered.
- Never store secrets or full sensitive payloads in `context` — store identifying fields and summaries only.
- No delete/update path exists in application code; retention/pruning is a future, explicitly-decided policy.

## Tests

`tests/Feature/AuditLogTest.php` — login/logout capture, upload/delete capture, system (userless) entries, user-deletion null-out (6 tests).
