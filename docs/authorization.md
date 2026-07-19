# Authorization

WP-02-01 foundation. Module-specific rules (e.g., "officers manage only their own
delegation") belong in per-model policies added by later work packages; this document
covers the cross-cutting pieces every module reuses.

## Roles

`App\Enums\UserRole` (string-backed, stored in `users.role`, default `viewer`):

| Role | Value | Intent |
|---|---|---|
| Administrator | `admin` | Everything, including user/role administration |
| Meet Organizer | `organizer` | Manage meet data (registries, catalog, meets, reviews) |
| Delegation Officer | `delegation_officer` | Manage only their own delegation's records |
| Viewer | `viewer` | Read-only, non-sensitive views |

`role` is deliberately **not mass assignable** — assign it explicitly
(`$user->forceFill(['role' => …])` in trusted code) or via factory states
(`User::factory()->admin()`, `->organizer()`, `->delegationOfficer()`).

## Gates

Defined in `AppServiceProvider::configureAuthorization()`:

- `administer` — admin only.
- `manage-meet-data` — admin or organizer.

Usage: `Gate::authorize('manage-meet-data')`, `@can`/`user.can` on the frontend via
shared props (later WPs), or route middleware `can:administer`.

## Route protection

`role` middleware alias (`App\Http\Middleware\EnsureUserHasRole`):

```php
Route::middleware(['auth', 'verified', 'role:admin,organizer'])->group(…);
```

Unauthenticated users are redirected to login by `auth` before `role` runs; an
authenticated user without a listed role gets 403.

## Permission-denied UI

Any web 403 renders the Inertia page `resources/js/pages/error.tsx` (wired in
`bootstrap/app.php` via `$exceptions->respond`), built on the shared `EmptyState`
component with a link back to the dashboard. JSON/API requests keep plain 403 responses.

## Initial administrator

`Database\Seeders\AdminUserSeeder` (also called from `DatabaseSeeder`) creates or
updates the admin account from `PMMS_ADMIN_NAME` / `PMMS_ADMIN_EMAIL` /
`PMMS_ADMIN_PASSWORD` (see `.env.example`; config in `config/pmms.php`). In production
the password variable is required; locally it falls back to `password`. No credentials
live in code.

## Model helpers

- `$user->hasRole(UserRole::Admin, UserRole::Organizer)` — variadic membership check.
- `$user->isAdmin()`.

## Authorization matrix (Phase 2 verified WP-02-11; Phase 3 verified WP-03-10)

Legend: ✓ allowed · ✗ forbidden (403) · **own** = only for delegations the officer is
assigned to. "Managers" = admin + organizer. Conditions in parentheses are enforced by
the named policy on top of the role check.

| Module / action | Admin | Organizer | Delegation Officer | Viewer |
|---|---|---|---|---|
| Dashboard | ✓ | ✓ | ✓ | ✓ |
| Districts / Schools / Sports / Events / Meets / Venues — view lists | ✓ | ✓ | ✓ | ✓ |
| Districts / Schools / Sports / Events / Meets / Venues — create, update, archive, restore, delete | ✓ | ✓ | ✗ | ✗ |
| Schedule — view | ✓ | ✓ | ✓ | ✓ |
| Schedule — create, update, delete slots (meet registration-closed or active) | ✓ | ✓ | ✗ | ✗ |
| Delegations — list | ✓ all | ✓ all | own only | ✓ all |
| Delegations — register, delete (draft only) | ✓ | ✓ | ✗ | ✗ |
| Delegations — update head contact | ✓ | ✓ | own (draft + registration open) | ✗ |
| Delegations — submit | ✓ | ✓ | own (registration open) | ✗ |
| Delegations — approve, return to draft | ✓ | ✓ | ✗ | ✗ |
| Delegations — assign officers | ✓ | ✓ | ✗ | ✗ |
| Athletes — list, profile, photo | ✓ all | ✓ all | own only | ✗ |
| Athletes — register, update, delete | ✓ | ✓ | own (delegation draft + registration open) | ✗ |
| Personnel — list, photo | ✓ all | ✓ all | own only | ✗ |
| Personnel — register, update, sync sports, delete | ✓ | ✓ | own (delegation draft + registration open) | ✗ |
| Entries — list | ✓ all | ✓ all | own only | ✗ |
| Entries — submit | ✓ | ✓ | own (registration open; delegation need not be draft) | ✗ |
| Entries — confirm | ✓ | ✓ | ✗ | ✗ |
| Entries — withdraw | ✓ | ✓ | own submitted (registration open) | ✗ |
| Entries — delete (withdrawn only) | ✓ | ✓ | own (registration open) | ✗ |
| Matches — list | ✓ all | ✓ all | own delegation's only | ✗ |
| Matches — create, update, participants, status, delete | ✓ | ✓ | ✗ | ✗ |
| Results — validated results | ✓ | ✓ | ✓ | ✓ |
| Results — encoded (unvalidated) results | ✓ | ✓ | ✗ | ✗ |
| Results — encode, update, validate, correct, delete | ✓ | ✓ | ✗ | ✗ |
| Medal tally | ✓ | ✓ | ✓ | ✓ |
| Protests — list | ✓ all | ✓ all | own delegation's only | ✗ |
| Protests — file | ✓ any delegation | ✓ any delegation | own delegation only | ✗ |
| Protests — review, decide | ✓ | ✓ | ✗ | ✗ |
| Incidents — list, log, update, resolve, reopen, delete | ✓ | ✓ | ✗ | ✗ |
| Eligibility — list, view document | ✓ all | ✓ all | own only | ✗ |
| Eligibility — upload / delete document | ✓ | ✓ | own (registration open) | ✗ |
| Eligibility — approve, return | ✓ | ✓ | ✗ | ✗ |
| Accreditation — per-delegation view, ID cards (single + batch) | ✓ all | ✓ all | own only | ✗ |
| Accreditation — grant, revoke | ✓ | ✓ | ✗ | ✗ |
| File uploads — download, delete | uploader only | uploader only | uploader only | uploader only |
| Reports — delegation roster (page + CSV) | ✓ | ✓ | own only | ✗ |
| Reports — event entry list (page + CSV) | ✓ all rows | ✓ all rows | own rows only | ✗ |
| Reports — school participation (page + CSV) | ✓ | ✓ | ✓ | ✓ |
| Reports — official result sheet (page + CSV; validated results only, 404 otherwise) | ✓ | ✓ | ✓ | ✓ |
| Reports — medal tally (page + CSV) | ✓ | ✓ | ✓ | ✓ |
| Reports — daily schedule sheet (page + CSV) | ✓ | ✓ | ✓ | ✓ |
| Audit log viewer | ✓ | ✗ | ✗ | ✗ |

Enforcement lives in two layers: the `role:admin,organizer` route middleware group in
`routes/web.php` (registry/catalog/meet writes, delegation register/delete, and all
Phase 3 operations mutations — schedule, matches, results, protest decisions,
incidents, accreditation grant/revoke) and the per-model policies in `app/Policies/`
(everything scoped to delegations or minors, plus `ProtestPolicy` for filing and
list access). The audit viewer uses the `can:administer` route middleware.

`tests/Feature/AuthorizationMatrixTest.php` sweeps every forbidden role × action
combination above; per-module tests cover the allowed paths and window conditions.

## Testing pattern

See `tests/Feature/AuthorizationTest.php`: gate matrix as a Pest dataset, middleware
behavior via ad-hoc test routes, 403 page assertion with `assertInertia`, seeder
idempotence. Later modules should add their policy tests in the same style.
