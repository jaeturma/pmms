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

## Testing pattern

See `tests/Feature/AuthorizationTest.php`: gate matrix as a Pest dataset, middleware
behavior via ad-hoc test routes, 403 page assertion with `assertInertia`, seeder
idempotence. Later modules should add their policy tests in the same style.
