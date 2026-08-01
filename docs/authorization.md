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
| Technical Official | `technical_official` | Run live scoring for their assigned sport(s) only — no other meet-data-management permission |
| Viewer | `viewer` | Read-only, non-sensitive views |

`role` is deliberately **not mass assignable** — assign it explicitly
(`$user->forceFill(['role' => …])` in trusted code) or via factory states
(`User::factory()->admin()`, `->organizer()`, `->delegationOfficer()`,
`->technicalOfficial()`).

A Technical Official's sport assignment is separate from their role: an
Admin/Organizer attaches sports to them from the **Sports** catalog page
(`sports/{sport}/technical-officials`, `Sport::technicalOfficials()` /
`User::sports()`, a `sport_user` pivot) — the same two-step pattern as
Delegation Officer assignment (role first, then a specific delegation from
the Delegations page).

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

The `verified` alias is overridden in `bootstrap/app.php` to point at
`App\Http\Middleware\EnsureEmailIsVerifiedIfRequired` instead of the framework
default — see [System settings](#system-settings) below for when it actually
enforces anything.

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

## System settings

`App\Models\Setting` (`system_settings` table, one row via `Setting::current()`,
same singleton pattern as `Division::current()`) holds reCAPTCHA and outgoing-mail
configuration. Admin-only page at `/system-settings`
(`SystemSettingsController`, `can:administer`). `recaptcha_secret_key` and
`smtp_password` are `encrypted` casts and are never sent back to the browser —
the edit page only receives `has_recaptcha_secret_key`/`has_smtp_password`
booleans, and a blank submitted secret means "leave it unchanged," never
"clear it."

Three readiness checks gate everything so a half-filled-in form is inert
rather than half-enforced:

- `Setting::recaptchaReady()` — enabled + both keys present. Gates the
  reCAPTCHA v2 checkbox on login/registration (`HandleInertiaRequests`'s
  shared `recaptcha` prop, guest-only) and the server-side check
  (`App\Services\RecaptchaVerifier`, used by
  `App\Actions\Fortify\EnsureRecaptchaIsValid` in the login pipeline and
  directly in `App\Actions\Fortify\CreateNewUser` for registration). Not
  ready means `RecaptchaVerifier::passes()` is always `true` — no widget, no
  check.
- `Setting::smtpReady()` — every SMTP field present. Read by
  `AppServiceProvider::configureMail()` at boot to override `mail.*` config
  at runtime; falls back to the server's `.env` mail config when incomplete.
- `Setting::emailVerificationActive()` — the toggle **and** `smtpReady()`.
  Read by `EnsureEmailIsVerifiedIfRequired` (see above) to decide whether the
  `verified` middleware enforces anything at all; when inactive it's a
  no-op regardless of any individual user's real `email_verified_at`. Only
  new registrations are affected — turning this on grandfathers every
  existing unverified account (`SystemSettingsController::update()` bulk-sets
  `email_verified_at` the moment the transition to active happens, audited
  as `system_settings.email_verification_grandfathered`).

`Database\Seeders\RoleDemoUsersSeeder` creates one already-verified demo
account per `UserRole` case (`{role}@pmms.test` / password `password`) for
local development; it refuses to run outside `local`/`testing`.

## Model helpers

- `$user->hasRole(UserRole::Admin, UserRole::Organizer)` — variadic membership check.
- `$user->isAdmin()`.

## Authorization matrix (Phase 2 verified WP-02-11; Phase 3 verified WP-03-10; Division initiative verified WP7)

Legend: ✓ allowed · ✗ forbidden (403) · **own** = only for delegations the officer is
assigned to. "Managers" = admin + organizer. Conditions in parentheses are enforced by
the named policy on top of the role check.

| Module / action | Admin | Organizer | Delegation Officer | Technical Official | Viewer |
|---|---|---|---|---|---|
| Dashboard | ✓ | ✓ | ✓ | ✓ | ✓ |
| Management dashboard (cross-meet oversight, Phase 5; page + printable report + CSV) | ✓ | ✓ | ✗ | ✗ | ✗ |
| Districts / Schools / Sports / Events / Meets / Venues — view lists | ✓ | ✓ | ✓ | ✓ | ✓ |
| Districts / Schools / Sports / Events / Meets / Venues — create, update, archive, restore, delete | ✓ | ✓ | ✗ | ✗ | ✗ |
| Sports — assign technical officials | ✓ | ✓ | ✗ | ✗ | ✗ |
| Meets — publish / unpublish to public portal (non-draft only) | ✓ | ✓ | ✗ | ✗ | ✗ |
| Public portal (`/`) | public — no authentication; published meets only | | | | |
| Schedule — view | ✓ | ✓ | ✓ | ✓ | ✓ |
| Schedule — create, update, delete slots (meet registration-closed or active) | ✓ | ✓ | ✗ | ✗ | ✗ |
| Delegations — list | ✓ all | ✓ all | own only | ✗ | ✓ all |
| Delegations — register, delete (draft only) | ✓ | ✓ | ✗ | ✗ | ✗ |
| Delegations — update head contact | ✓ | ✓ | own (draft + registration open) | ✗ | ✗ |
| Delegations — submit | ✓ | ✓ | own (registration open) | ✗ | ✗ |
| Delegations — approve, return to draft | ✓ | ✓ | ✗ | ✗ | ✗ |
| Delegations — assign officers | ✓ | ✓ | ✗ | ✗ | ✗ |
| Athletes — list, profile, photo | ✓ all | ✓ all | own delegation's whole roster¹ | ✗ | ✗ |
| Athletes — register, update, delete | ✓ | ✓ | own (delegation draft + registration open) | ✗ | ✗ |
| Personnel — list, photo | ✓ all | ✓ all | own delegation's whole roster¹ | ✗ | ✗ |
| Personnel — register, update, sync sports, delete | ✓ | ✓ | own (delegation draft + registration open) | ✗ | ✗ |
| Entries — list | ✓ all | ✓ all | own only | ✗ | ✗ |
| Entries — submit | ✓ | ✓ | own (registration open; delegation need not be draft) | ✗ | ✗ |
| Entries — confirm | ✓ | ✓ | ✗ | ✗ | ✗ |
| Entries — withdraw | ✓ | ✓ | own submitted (registration open) | ✗ | ✗ |
| Entries — delete (withdrawn only) | ✓ | ✓ | own (registration open) | ✗ | ✗ |
| Matches — list | ✓ all | ✓ all | own delegation's only | own assigned sport(s)² only | ✗ |
| Matches — create, update, participants, status, delete | ✓ | ✓ | ✗ | ✗ | ✗ |
| Live scoring — view session (Phase 7) | ✓ all | ✓ all | own delegation's matches only | own assigned sport(s)² only | ✗ |
| Live scoring — start, score, correct, period, pause, resume, end, record/reset team fouls, record a boxing round score, advance count/outs, record an inning run (Phase 7) | ✓ | ✓ | ✗ | own assigned sport(s)² only | ✗ |
| Results — validated results | ✓ | ✓ | ✓ | ✓ | ✓ |
| Results — encoded (unvalidated) results | ✓ all | ✓ all | ✗ | own sport's only | ✗ |
| Results — encode, update | ✓ | ✓ | ✗ | own sport's only | ✗ |
| Results — validate, correct, delete | ✓ | ✓ | ✗ | ✗ | ✗ |
| Medal tally | ✓ | ✓ | ✓ | ✗ | ✓ |
| Protests — list | ✓ all | ✓ all | own delegation's only | ✗ | ✗ |
| Protests — file | ✓ any delegation | ✓ any delegation | own delegation only | ✗ | ✗ |
| Protests — review, decide | ✓ | ✓ | ✗ | ✗ | ✗ |
| Incidents — list, log, update, resolve, reopen, delete | ✓ | ✓ | ✗ | ✗ | ✗ |
| Announcements — list, create, update, publish, unpublish, delete | ✓ | ✓ | ✗ | ✗ | ✗ |
| Eligibility — list, view document | ✓ all | ✓ all | own only | ✗ | ✗ |
| Eligibility — upload / delete document | ✓ | ✓ | own (registration open) | ✗ | ✗ |
| Eligibility — approve, return | ✓ | ✓ | ✗ | ✗ | ✗ |
| Accreditation — per-delegation view, ID cards (single + batch) | ✓ all | ✓ all | own only | ✗ | ✗ |
| Accreditation — grant, revoke | ✓ | ✓ | ✗ | ✗ | ✗ |
| File uploads — download, delete | uploader only | uploader only | uploader only | uploader only | uploader only |
| Reports — delegation roster (page + CSV) | ✓ | ✓ | own only | ✗ | ✗ |
| Reports — event entry list (page + CSV) | ✓ all rows | ✓ all rows | own rows only | ✗ | ✗ |
| Reports — school participation (page + CSV) | ✓ | ✓ | ✓ | ✓ | ✓ |
| Reports — official result sheet (page + CSV; validated results only, 404 otherwise) | ✓ | ✓ | ✓ | ✓ | ✓ |
| Reports — medal tally (page + CSV) | ✓ | ✓ | ✓ | ✓ | ✓ |
| Reports — daily schedule sheet (page + CSV) | ✓ | ✓ | ✓ | ✓ | ✓ |
| Audit log viewer | ✓ | ✗ | ✗ | ✗ | ✗ |
| Division settings — view, update | ✓ | ✗ | ✗ | ✗ | ✗ |

Enforcement lives in four layers: the `role:admin,organizer` route middleware group in
`routes/web.php` (registry/catalog/meet writes, delegation register/delete, schedule/
matches/protest-decision/incidents/accreditation mutations, and the three
validate/correct/delete result actions), the separate `role:admin,organizer,
technical_official` route group for the 9 live-scoring mutation endpoints (kept apart
deliberately so a Technical Official gains no other meet-data-management permission)
plus a second, equally deliberate carve-out of that same shape for `results.store`/
`results.update` only (Phase 16 — a Technical Official may encode a result directly,
but never validate/correct/delete one; that trio stays manager-only), and the per-model
policies in `app/Policies/` (everything scoped to delegations or minors, plus
`ProtestPolicy` for filing and list access). Matches list, live-scoring, and result-
encoding sport scoping for a Technical Official is enforced in `MatchController::
index()`, `ScoringSessionController::canManage()`, and `ResultController::
authorizeEncode()` respectively, not a policy class. The audit viewer uses the
`can:administer` route middleware.

`tests/Feature/AuthorizationMatrixTest.php` sweeps every forbidden role × action
combination above; per-module tests cover the allowed paths and window conditions
(a Technical Official's own allowed paths are covered in `ScoringSessionTest.php`/
`MatchTest.php`/`ResultTest.php` instead, since that role's scoping isn't a per-delegation
"own" check the shared matrix sweep already models). `Division`/`AuditLogViewer`
admin-only rows are swept in their own dedicated test files rather than duplicated into
the matrix sweep, matching this doc's rows.

¹ Scoped by **delegation**, not by the individual's own school — under a municipal
(Province) delegation an assigned officer sees every school pooled under it, not
just one. Accepted/intended, reviewed WP7 — see `docs/delegations.md` "Officer
roster scope".

² A Technical Official's sport assignment (`User::sports()`, a `sport_user` pivot —
see "Roles" above) — not a delegation. A match belongs to exactly one sport via its
event, so this scoping is a straightforward `sport_id` check, no policy class needed.

## Testing pattern

See `tests/Feature/AuthorizationTest.php`: gate matrix as a Pest dataset, middleware
behavior via ad-hoc test routes, 403 page assertion with `assertInertia`, seeder
idempotence. Later modules should add their policy tests in the same style.
