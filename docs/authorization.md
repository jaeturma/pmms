# Authorization

WP-02-01 foundation, reconciled against the current code by
**WP-REALIGN-13** (2026-09-11) — the model below matches
`app/Enums/UserRole.php`, `app/Enums/Permission.php`,
`app/Providers/AppServiceProvider.php`, `app/Models/User.php`'s capability
methods, `app/Policies/*.php`, `app/Services/CompetitionAccessService.php`,
`app/Services/CoachAccessService.php`, and `routes/web.php` as of that date.

Two realignments happened between the original WP-02-01 write-up and this
reconciliation and are the reason older revisions of this file read
differently:

- **"Added new roles" / `f48f4d7` and the WP-REALIGN-04…08 series** —
  introduced the tournament-personnel roles, the `MeetSport` /
  `MeetSportAssignment` layer, first-class Coach logins, and the
  `ManagementTeam` committee model.
- **"align roles, athlete entries, team entries and competition access" /
  `f0b3b4fc` (2026-08-23)** — narrowed the `manage-meet-data` gate to
  **Admin only** (it used to be Admin + Organizer), moved registry/catalog
  writes to `role:admin`, and added the `Permission` enum +
  `User::hasPermission()` capability layer that most non-Admin management
  access now flows through.

Module-specific rules ("officers manage only their own delegation",
"a Supply Team member manages only their meet's equipment") live in the
per-model policies and the access-service classes; this document is the
cross-cutting map and the source the `AuthorizationMatrixTest` sweep is
checked against.

## Roles

`App\Enums\UserRole` (string-backed, stored in `users.role`, default
`viewer`). A user may also hold **`users.additional_roles`** — a JSON array
of extra `UserRole` values; `User::hasRole(...)` and every `role:` route
check consider the base role and the additional roles together, so one
login can be, e.g., both a Delegation Officer and a Technical Official.

| Role | Value | Intent |
|---|---|---|
| Administrator | `admin` | Everything, including user/role administration. The only role that passes `manage-meet-data` / `administer` unconditionally. |
| Meet Organizer | `organizer` | Broad read access to meet data and management reports; a shrinking set of direct writes (events/venues, live scoring **only** with a Tournament Secretary/ICT assignment). No longer passes `manage-meet-data`. |
| Delegation Officer | `delegation_officer` | Manage only their own delegation's records (`delegation_user` pivot → `Delegation::hasOfficer()`), and only while the delegation is a draft + the meet's registration window is open. |
| Technical Official | `technical_official` | Run live scoring, and encode (not validate) results, for their assigned sport(s) only — `sport_user` pivot (`User::sports()`), global across meets. No other meet-data-management permission. |
| Tournament Manager | `tournament_manager` | Build and run the schedule/matches/live scoring/result validation for exactly **one** assigned sport (`sports.tournament_manager_id`, a 1:1 FK — `User::managedSport()`). No other meet-data-management permission. |
| Tournament ICT | `tournament_ict` | Event/venue creation, athlete-roster assistance, result encoding, and DAVRAA-report building — scoped by their `MeetSportAssignment` rows (and by ICT `ManagementTeam` membership for the central variant). |
| Tournament Secretary | `tournament_secretary` | Event/venue creation, schedule/result work, and scoreboard access — scoped by their `MeetSportAssignment` rows. |
| Coach | `coach` | Register athletes, upload eligibility documents, and submit/withdraw entries for their **own** delegation only — scoped through their roster identity (`Personnel.user_id` → `Delegation::hasCoach()`) and an approved `CoachAssignmentRequest` / `CoachOnboardingRequest` (`CoachAccessService`). No delegation-administration or decision-making permission. |
| Viewer | `viewer` | Read-only, non-sensitive views. Excluded from every minors/sensitive module (Athletes, Personnel, Eligibility, Accreditation, Medical). |

`role` and `additional_roles` are deliberately **not mass assignable** —
assigned by trusted code only (`UserManagementController`,
`$user->forceFill([...])`, or factory states `admin()`, `organizer()`,
`delegationOfficer()`, `technicalOfficial()`, `tournamentManager()`,
`coach()`).

## Scope is assigned separately from the role

Holding a role is necessary but rarely sufficient — most access is narrowed
by a **separate assignment record**:

| Mechanism | Table / relation | Scopes |
|---|---|---|
| Delegation officer | `delegation_user` pivot → `Delegation::hasOfficer($user)` | Their own delegation's roster/entries/eligibility/accreditation/protests |
| Coach roster identity | `Personnel.user_id` → `Delegation::hasCoach($user)`, plus `CoachAssignmentRequest`/`CoachOnboardingRequest` (`CoachAccessService`) | Their own delegation's athletes/entries/eligibility, limited to approved events |
| Technical Official sport | `sport_user` pivot → `User::sports()` | Live scoring + result encoding for those sports (all meets) |
| Tournament Manager sport | `sports.tournament_manager_id` → `User::managedSport()` | Schedule/matches/live scoring/result validation for that one sport |
| Tournament personnel | `meet_sport_assignments` (role + `MeetSport`) → `User::meetSportAssignments()`, resolved by `CompetitionAccessService` | Event/athlete/entry/result access for that meet + sport (+ optional `SportCategory`) |
| Management-team membership | `management_team_members` (Active) of a `ManagementTeamType` → `User::managementTeamMemberships()` | The matching committee domain (Supply/Food/Billeting/Transport/Medical/DRRM/ICT/DSAC/Top&Meet Management), optionally for one meet |
| Athlete oversight | `athlete_oversight_assignments` (Active) of an `AthleteOversightType` | Read-only readiness/roster views for a school-district (District Sports Coordinator) or municipality (Municipality Team Manager) |

`App\Enums\MeetSportAssignmentRole` has ten values (Tournament Manager,
Assistant / Track / Field / Boys / Girls / Category Tournament Manager,
Tournament Secretary, Tournament ICT, Technical Official) — a per-assignment
**duty**, distinct from the `UserRole` login level of the same name.

`App\Enums\ManagementTeamType` has eleven values (Top Management, Meet
Management, Results Committee, DSAC, ICT, Supply, Food, Billeting,
Transport, Medical, DRRM). Some teams also carry a `source_code`
(`CENTRAL_ICT`, `ICT`, `EVENT_SECRETARIAT`, `INFORMATION`, `DSAC`) that a
few checks key off directly.

## Gates

Defined in `AppServiceProvider::configureAuthorization()`:

| Gate | Passes for |
|---|---|
| `administer` | Admin only. |
| `manage-meet-data` | **Admin only** (was Admin + Organizer before `f0b3b4fc`). Now mostly a "full manager" shorthand for `canManage` view props and a handful of `Gate::authorize()` calls; Organizer access to a given action is granted by that action's own `role:` group or capability method, not this gate. |
| `view-system-logs` | Admin, or `User::canManageProductionAccounts()` (ICT / Central-ICT team member). Gates the audit-log viewer. |
| `view-management-reports` | Admin, Organizer, `canManageProductionAccounts()`, or an Active Top-Management / Meet-Management team member. Gates the management dashboard + its report/CSV. |

## Capability methods and the `Permission` enum

Non-Admin management access flows through named methods on `User` rather
than the coarse gates. The load-bearing ones:

| Method | Grants, for a non-Admin |
|---|---|
| `canManageProductionAccounts()` | Active ICT / `CENTRAL_ICT` / `ICT` team member — treated as a near-manager across Athletes, Personnel, Announcements, the full Results register, data-integrity tools. |
| `canManageSchoolMasterData()` | Active ICT team member, or a member whose `role_title` is "Meet Manager"/"Meet Director" — School catalog writes. |
| `canReviewCoachRegistrations()` | Admin or ICT team member — approve/reject Coach account requests. |
| `canManageAnnouncements()` / `canManageEditorialContent()` | Admin, ICT, or `INFORMATION`-team member — announcements + public content (news/FAQ/gallery). |
| `canManagePersonnel()` | Admin or ICT team member. |
| `canFileProtest()` | Anyone linked as a `PersonnelRole::DelegationManager` for a delegation. |
| `canViewManagementReports()` | see the gate table above. |
| `isDsacAccreditationLeader($meet)` | Admin, or an Active DSAC-team **lead** (`is_head`, or a chair/leader `role_title`) for that meet — the only path to `EligibilityReviewPolicy::decide` and final accreditation. |
| `hasPermission(Permission, ?Meet)` | The generic check — see below. |

`App\Enums\Permission` (22 cases) is resolved by `User::hasPermission()`:
Admin always passes; otherwise it maps the permission to the
`ManagementTeamType` or `AthleteOversightType` that grants it and checks
for an Active membership (optionally meet-scoped). Groups:

- **Content** (`content.view`, `news.manage`, `announcements.manage`,
  `faq.manage`, `gallery.*`) → ICT / Information team, or a Secretary/ICT
  `MeetSportAssignment` for gallery uploads.
- **Athlete screening** (`athlete.profile.validate`,
  `athlete.documents.verify`, `athlete.eligibility.review`,
  `athlete.eligibility.approve`) → DSAC team.
- **Medical** (`medical.clearance.evaluate`, `medical.clearance.approve`)
  → Medical team.
- **Results** (`results.officialize`, `results.reopen`) → Top Management
  team.
- **Oversight** (`district.*`, `municipality.*`) →
  `AthleteOversightAssignment`.

## Route protection

`role` middleware alias (`App\Http\Middleware\EnsureUserHasRole`) —
`->middleware('role:admin,organizer')` aborts 403 for an authenticated user
holding none of the listed roles (base or additional). Current groups in
`routes/web.php`:

| Group | Covers |
|---|---|
| `role:admin` | District / SchoolDistrict / Sport / Meet catalog writes; Sport TO/TM assignment; Event & Venue archive/restore/delete; ManagementTeam create/update/delete; Delegation register/delete; Accreditation delete; Protest review/decide; **all Incident actions**. |
| `role:admin,organizer,technical_official,tournament_ict,tournament_secretary` | Event create/update/delete; Venue create/update; Result **encode**/update (own sport, controller-scoped). |
| `role:admin,organizer,technical_official,tournament_manager,tournament_ict,tournament_secretary` | Schedule create/update/delete; Result **validate/correct/delete**; **all live-scoring mutations**. Each controller's own `ScopesToAssignedSport::userOperatesSport()` / `ScoringSessionController::canManage()` then narrows to the assigned sport — a plain Organizer with no Tournament Secretary/ICT assignment is still denied scoring, and a TM/TO gains nothing else in this group. |
| No `role:` group | Everything else in the `['auth','verified']` block — Schools writes, Delegations, Athletes, Personnel, Entries, Eligibility, Accreditation, Announcements/Content, Supply/Food/Billeting/Transport/Medical/DRRM, Management-team membership, Meet-sport assignments, Coach onboarding, User management, Reports. Authorization is enforced **inside** the controller via a policy, an access-service, or a capability method. |

`can:` middleware guards a few routes directly: `can:administer` (Division
settings, System settings, Production Load Controls),
`can:view-management-reports` (management dashboard), `can:view-system-logs`
is checked in-controller.

The `verified` alias is overridden in `bootstrap/app.php` to point at
`App\Http\Middleware\EnsureEmailIsVerifiedIfRequired` — see
[System settings](#system-settings).

## Policies

`app/Policies/` — 15 classes. The per-model, auto-discovered ones
(`AthletePolicy`, `DelegationPolicy`, `EligibilityReviewPolicy`,
`EligibilityDocumentPolicy`, `EntryPolicy`, `FileUploadPolicy`,
`PersonnelPolicy`, `ProtestPolicy`) carry the delegation/minor/coach
scoping. The committee ones (`SupplyPolicy`, `FoodPolicy`,
`BilletingPolicy`, `TransportPolicy`, `MedicalPolicy`, `DrrmPolicy`) are
plain classes injected into their controllers and called via
`abort_unless`, sharing `Concerns\ChecksManagementTeamMembership`
(`viewAny` = any-meet membership, `manage` = this-meet membership; Admin
always passes; `BilletingPolicy`/`TransportPolicy` add a read-only
DelegationOfficer tier; `MedicalPolicy` is the one three-tier exception —
Organizer gets aggregate status only, raw detail is Medical-Team-or-Admin,
and any non-Viewer may break-glass with a logged, reviewable
`MedicalAccessLog`).

Modules with **no policy class** (Districts, SchoolDistricts, Schools,
Sports, Events, Venues, Meets, Announcements, Incidents, Division, System
Settings, Audit Log) are gated by the `role:` group above and/or an
in-controller `abort_unless(...)` on a capability method
(`canManageSchoolMasterData()`, `canManageAnnouncements()`), or a private
`authorizeSport()` helper (Events/Venues, scoped to
`meetSportAssignments`). Promoting these to real policy classes is
deferred — see `docs/architecture/wp-realign-13-authorization-reconciliation.md`.

## Permission-denied UI

Any web 403 renders the Inertia page `resources/js/pages/error.tsx` (wired
in `bootstrap/app.php` via `$exceptions->respond`), built on the shared
`EmptyState` component with a link back to the dashboard. JSON/API requests
keep plain 403 responses.

## Initial administrator

`Database\Seeders\AdminUserSeeder` (also called from `DatabaseSeeder`)
creates or updates the admin account from `PMMS_ADMIN_NAME` /
`PMMS_ADMIN_EMAIL` / `PMMS_ADMIN_PASSWORD` (see `.env.example`; config in
`config/pmms.php`). In production the password variable is required;
locally it falls back to `password`. No credentials live in code.

## System settings

`App\Models\Setting` (`system_settings` table, one row via
`Setting::current()`, same singleton pattern as `Division::current()`)
holds reCAPTCHA, outgoing-mail and Production Load Control configuration.
Admin-only page at `/system-settings` (`SystemSettingsController`,
`can:administer`). `recaptcha_secret_key` and `smtp_password` are
`encrypted` casts and are never sent back to the browser — the edit page
only receives `has_recaptcha_secret_key`/`has_smtp_password` booleans, and
a blank submitted secret means "leave it unchanged," never "clear it."

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

### Production Load Controls

Also on the same `system_settings` row and the same `/system-settings`
page: `live_scoreboards_suspended`, `authenticated_inactivity_expiry_enabled`
(default off) and `authenticated_inactivity_timeout_minutes` (min 5). The
suspend/resume and disconnect-sessions **actions** have their own
`POST /system/load-controls/*` routes, all under `can:administer`
(`LoadControlController`). Full behaviour in `docs/production-load-controls.md`.
The two middlewares that read these — `EnsurePublicScoreboardsActive`
(4 public routes) and `ExpireInactiveSessions` (`web` group) — read a
60-second cached projection (`Setting::loadControls()`), busted by every
writer.

## Model helpers

- `$user->hasRole(UserRole::Admin, UserRole::Organizer)` — variadic
  membership check over the base role **and** `additional_roles`.
- `$user->isAdmin()`.
- The capability methods and `$user->hasPermission(Permission::…, $meet)` —
  see "Capability methods and the `Permission` enum" above.

## Authorization matrix

Legend: ✓ allowed · ✗ forbidden (403) · **own** = only for delegations the
officer/coach is assigned to · **sport** = only for the user's assigned
sport(s), enforced by the controller/policy on top of the role check ·
**cmte** = also allowed for an Active member of the relevant
`ManagementTeam` (Admin always included). Conditions in parentheses are
enforced by the named policy. Tournament ICT / Tournament Secretary follow
the Organizer column except where a **sport**/**cmte** note applies. This
grid is the load-bearing summary; `app/Policies/*` and
`app/Services/{Competition,Coach}AccessService.php` are the source of truth
for the fine scoping.

| Module / action | Admin | Organizer | Delegation Officer | Technical Official | Tournament Manager | Coach | Viewer |
|---|---|---|---|---|---|---|---|
| Dashboard | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Management dashboard (page + report + CSV) | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ |
| Districts / SchoolDistricts / Sports / Meets — view lists | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Districts / SchoolDistricts / Sports / Meets — create, update, archive, restore, delete | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ | ✗ |
| Schools — view list | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Schools — create, update, archive, restore, delete | ✓ | ✗ (unless ICT/Meet-Manager cmte) | ✗ | ✗ | ✗ | ✗ | ✗ |
| Events / Venues — view lists | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Events / Venues — create, update | ✓ | **sport** (TM/Sec/ICT assignment) | ✗ | ✗ | **sport** | ✗ | ✗ |
| Events / Venues — archive, restore, delete | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ | ✗ |
| Sports — assign technical officials / tournament manager | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ | ✗ |
| Meets — publish/unpublish, activate/deactivate, sync events | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ | ✗ |
| Schedule — view | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Schedule — create, update, delete slots | ✓ | **sport** (competition-manager assignment) | ✗ | ✗ | **sport** (per-row `can_manage`) | ✗ | ✗ |
| Meet-sport assignments — view | ✓ all | ✓ all | ✓ all | ✓ all | ✓ all | ✓ all | ✓ all |
| Meet-sport assignments — create, update status, remove | ✓ | ✗ (unless production-account cmte) | ✗ | ✗ | ✗ | ✗ | ✗ |
| Management teams — view | ✓ all | ✓ all | ✓ all | ✓ all | ✓ all | ✓ all | ✓ all |
| Management teams — create/update/remove team | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ | ✗ |
| Management-team membership — add/update-status/remove | ✓ | ✗ (unless production-account cmte) | ✗ | ✗ | ✗ | ✗ | ✗ |
| Delegations — list | ✓ all | ✓ all | own only | ✗ | ✗ | ✓ all | ✓ all |
| Delegations — register, delete (draft only) | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ | ✗ |
| Delegations — update, submit | ✓ | ✗ | own (window rules) | ✗ | ✗ | ✗ | ✗ |
| Delegations — approve, return, assign officers | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ | ✗ |
| Athletes — list, profile, photo | ✓ all | ✓ all | own roster | own **sport** (via assignment) | ✗ | own roster | ✗ |
| Athletes — register, update, delete | ✓ | ✗ (unless ICT cmte) | own (draft + window) | ICT-assignment only | ✗ | own (draft + window, if enabled) | ✗ |
| Personnel — list, photo | ✓ all | ✓ all | own roster | ✗ | ✗ | own roster | ✗ |
| Personnel — register, update, sync sports, delete | ✓ (or ICT cmte) | ✗ | ✗ | ✗ | ✗ | ✗ | ✗ |
| Entries — list | ✓ all | ✓ all | own only | **sport** | **sport** | own only | ✗ |
| Entries — submit, withdraw, delete | ✓ | ✗ (unless ICT assignment) | own (window rules) | ICT-assignment only | ✗ | own (window rules) | ✗ |
| Entries — confirm | ✓ | ✗ (unless TM/Sec/ICT assignment) | ✗ | ✗ | **sport** (via assignment) | ✗ | ✗ |
| Matches — list | ✓ all | ✓ all | own delegation's | own **sport** | own **sport** | ✗ | ✗ |
| Matches — create, update, participants, status, delete | ✓ | **sport** (competition-manager assignment) | ✗ | ✗ | **sport** | ✗ | ✗ |
| Live scoring — view session / scoreboards list | ✓ all | scoped (own assignments; `ScheduleScoreboardService::canOperate`) | own delegation's | own **sport** | own **sport** | ✗ | ✗ |
| Live scoring — start / score / clocks / all mutations | ✓ | Tournament Secretary/ICT assignment only | ✗ | own **sport** | own **sport** | ✗ | ✗ |
| Results — validated results | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Results — full register (every meet/status) | ✓ | ✗ (unless Central Secretariat cmte) | ✗ | ✗ | ✗ | ✗ | ✗ |
| Results — encoded (unvalidated) results | ✓ all | ✓ all | ✗ | own **sport** | own **sport** | ✗ | ✗ |
| Results — encode, update | ✓ | ✗ (unless Sec/ICT assignment or Central Secretariat) | ✗ | own **sport** | ✗ | ✗ | ✗ |
| Results — validate (submitted → validated) | ✓ | ✗ (unless Results Committee cmte / Event Secretariat) | ✗ | ✗ | tm-confirm step only | ✗ | ✗ |
| Results — correct, delete | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ | ✗ |
| Medal tally | ✓ | ✓ | ✓ | ✗ | ✓ | ✓ | ✓ |
| Protests — list | ✓ all | ✓ all | own delegation's | ✗ | ✗ | ✗ | ✗ |
| Protests — file | ✓ any | ✓ any | own (as Delegation Manager) | ✗ | ✗ | ✗ | ✗ |
| Protests — review, decide | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ | ✗ |
| Incidents — list, log, update, resolve, reopen, delete | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ | ✗ |
| Announcements — list | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ |
| Announcements / public content — create, update, publish, delete | ✓ | ✗ (unless ICT/Information cmte) | ✗ | ✗ | ✗ | ✗ | ✗ |
| Eligibility — list, view document | ✓ all | ✓ all | own only | ✗ | ✗ | own only | ✗ |
| Eligibility — upload / delete document | ✓ | ✗ | own (window) | ✗ | ✗ | own (window) | ✗ |
| Eligibility — approve, return, reject | ✓ | ✗ (unless DSAC-lead cmte) | ✗ | ✗ | ✗ | ✗ | ✗ |
| Eligibility document — verify | ✓ | ✗ (unless DSAC cmte) | ✗ | ✗ | ✗ | ✗ | ✗ |
| Accreditation — per-delegation view, ID cards | ✓ all | ✓ all | own only | ✗ | ✗ | ✗ | ✗ |
| Accreditation — grant, revoke | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ |
| Accreditation — delete | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ | ✗ |
| Supply / Equipment — view, manage | ✓ | ✗ unless Supply cmte | ✗ | ✗ | ✗ | ✗ | ✗ |
| Food — view, manage | ✓ | ✗ unless Food cmte | ✗ | ✗ | ✗ | ✗ | ✗ |
| Billeting / Transport — view, manage | ✓ | manage only via cmte; read-only for own delegation (DelegationOfficer) | own delegation read-only (+ file own transport request) | ✗ | ✗ | ✗ | ✗ |
| Medical — aggregate status | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ |
| Medical — raw detail, manage | ✓ | ✗ unless Medical cmte | ✗ | ✗ | ✗ | ✗ | ✗ |
| Medical — emergency (break-glass) access | ✓ | ✓ (logged, reviewable) | ✓ (logged) | ✓ (logged) | ✓ (logged) | ✓ (logged) | ✗ |
| DRRM — view, manage | ✓ | ✗ unless DRRM cmte | ✗ | ✗ | ✗ | ✗ | ✗ |
| File uploads — download, delete | uploader only | uploader only | uploader only | uploader only | uploader only | uploader only | uploader only |
| Reports — school participation / result sheet / medal tally / schedule sheet (page + CSV) | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Reports — delegation roster / event entry list (page + CSV) | ✓ all | ✓ all | own rows only | ✗ | ✗ | ✗ | ✗ |
| Audit log viewer | ✓ | ✗ (unless production-account cmte) | ✗ | ✗ | ✗ | ✗ | ✗ |
| Division settings — view, update | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ | ✗ |
| System settings, Production Load Controls | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ | ✗ |

**Reading the Organizer column**: since `f0b3b4fc` the Organizer role is
predominantly **read + oversight**. It has almost no unconditional write:
it can view every registry/roster/result, see the management dashboard,
grant/revoke athlete accreditation, and run a scoreboard **only** with a
Tournament Secretary/ICT `MeetSportAssignment`. Athlete/personnel/entry
registration, result encoding, and event/venue creation are **not** plain
Organizer capabilities — they require an ICT/Secretary assignment, a
committee membership, or Admin. Where this table shows "Organizer ✓" for a
write, that is the pre-`f0b3b4fc` behaviour only if a matching assignment
exists; the authoritative check is the policy/controller named in the row.

**Results lifecycle**: the newer `ResultWorkflowController` state machine
(direct/encoded → `submit` → Tournament-Manager `tm-confirm` →
Event-Secretariat `validate` → `official`, with `return`/`reopen`/`cancel`)
supersedes the simple encode→validate rows below for anything created
through the submission flow. Each transition is gated by a specific
assignment (`isAssignedTournamentManager`, `authorizeEventSecretariat`,
`Permission::ResultsOfficialize` = Top Management) — see
`docs/results.md` and `app/Http/Controllers/ResultWorkflowController.php`.

**Oversight roles not shown as columns**: a `District Sports Coordinator`
or `Municipality Team Manager` (`AthleteOversightAssignment`) gets
read-only readiness/roster views for their school-district / municipality —
`Permission::District*` / `Permission::Municipality*`.

## Testing

- `tests/Feature/AuthorizationMatrixTest.php` — `forbiddenActionCases()`
  swept against every forbidden role (viewer, delegation officer, technical
  official, tournament manager, coach), asserting each returns 403. The
  `schedule`/`match` create-update and `result encode`/`update` cases are
  carved out of the tournament-manager / technical-official sweeps (they
  reach a `FormRequest` that validates before the controller's scope check,
  so an empty body redirects instead of 403ing) — dedicated wrong-sport
  coverage lives in `ScheduleTest`/`MatchTest`/`ResultTest`.
- `tests/Feature/AuthorizationTest.php` — the gate matrix as a Pest
  dataset, `role`-middleware behaviour via ad-hoc test routes, the 403 page
  assertion, seeder idempotence.
- Per-module allowed paths: `ScoringSessionTest`, `MatchTest`,
  `ResultTest`, `ScheduleTest`, `SportTest`, `CoachAccountTest`,
  `EquipmentTest`, `MedicalTest`, `DrrmTest`, `EligibilityTest`,
  `ManagementTeamTest`, plus the committee-domain feature tests.
- `Division` / `AuditLogViewer` admin-only rows are swept in their own
  dedicated files rather than duplicated into the matrix sweep.
