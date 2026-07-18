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
| `file.downloaded` | `FileUploadController` (sensitive read) |
| `district.*`, `school.*`, `sport.*`, `event.*` — `created`, `updated`, `archived`, `restored`, `deleted` | registry/catalog controllers |
| `meet.created` / `updated` / `status_changed` / `events_updated` / `deleted` | `MeetController` |
| `delegation.created` / `updated` / `submitted` / `approved` / `returned` / `officers_updated` / `deleted` | `DelegationController` |
| `athlete.created` / `updated` / `deleted` / `viewed` (profile read — minor data) | `AthleteController` |
| `personnel.created` / `updated` / `sports_updated` / `deleted` | `PersonnelController` |
| `entry.submitted` / `confirmed` / `withdrawn` / `deleted` | `EntryController` |
| `eligibility.document_uploaded` / `document_viewed` / `document_deleted` / `resubmitted` / `approved` / `returned` | `EligibilityController` |
| `report.roster_exported` / `event_entries_exported` / `participation_exported` (sensitive CSV exports) | `ReportController` |

Deliberately not audited: athlete/personnel photo serving (`/athletes/{id}/photo`,
`/personnel/{id}/photo`) — photos render inline on pages whose access is already
audited (`athlete.viewed`) or scoped by policy, and per-image entries would only add
noise. Read access to lists is likewise not audited; sensitive reads are the athlete
profile and eligibility documents.

## Audit viewer (WP-02-11)

Admins (only) can browse the trail at `/audit-logs` — sidebar item "Audit log". The
page is built on the shared registry pattern: `SearchBar` (matches action and user
name), an action filter, and `PaginationControls`, newest entries first.
`AuditLogController` is guarded by the `can:administer` route middleware.

## Conventions

- Action names: `<domain>.<verb-past-tense>` (`meet.created`, `result.published`).
- Record audit entries from services (not controllers) so every code path is covered.
- Never store secrets or full sensitive payloads in `context` — store identifying fields and summaries only.
- No delete/update path exists in application code; retention/pruning is a future, explicitly-decided policy.

## Tests

`tests/Feature/AuditLogTest.php` — login/logout capture, upload/delete capture, system (userless) entries, user-deletion null-out (6 tests). `tests/Feature/AuditLogViewerTest.php` — viewer access control, pagination, search, and action filter. Module tests assert their own `*.created`/`*.updated`/… entries.
