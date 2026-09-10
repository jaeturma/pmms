# WP-REALIGN-13 — Authorization and Data Scoping (reconciliation)

**Status:** Docs reconciled 2026-09-11. No behaviour changed.

## What WP-REALIGN-13 was scoped to do

The organizational-realignment gap assessment
(`docs/reports/architecture/pmms-organizational-realignment-gap-assessment.md`
§18, §24) planned WP-REALIGN-13 as *"Authorization and Data Scoping —
expand the 5 roles toward the mandate's ~18, add policies for currently
policy-less modules once they gain owner-scoping."* Its companion,
`pmms-role-and-scope-map.md`, recommended **not** exploding `UserRole` to
18 cases but instead scoping new committee functions through
`ManagementTeamMember` rows, mirroring the existing Technical-Official
`sport_user` pattern.

## What actually happened

WP-REALIGN-13 was never run as a discrete work package. Its substance was
delivered incrementally between 2026-08 and 2026-09:

| Planned piece | Delivered by | Result |
|---|---|---|
| More login roles | `f48f4d7` "Added new roles", Phase 13, WP-REALIGN-05/07 | `UserRole` now has 9 cases (Admin, Organizer, Delegation Officer, Technical Official, Tournament Manager, Tournament ICT, Tournament Secretary, Coach, Viewer) plus `users.additional_roles` for multi-role logins |
| Committee scoping via `ManagementTeamMember` | WP-REALIGN-09 … -12 | `ManagementTeam` / `ManagementTeamMember` with 11 `ManagementTeamType` values; `User` capability methods (`canManageProductionAccounts`, `canManageSchoolMasterData`, `canReviewCoachRegistrations`, `canManageEditorialContent`, `canManagePersonnel`, `canViewManagementReports`, `isDsacAccreditationLeader`) all key off Active memberships |
| Meet-scoped tournament personnel | WP-REALIGN-04/07, Phase 13, Phase 16 | `MeetSportAssignment` (10 `MeetSportAssignmentRole` values), `CompetitionAccessService`, `CoachAccessService`, the `ScopesToAssignedSport` controller trait |
| Fine-grained `Permission` layer | `f0b3b4fc` "align roles, athlete entries, team entries and competition access" (2026-08-23) | `App\Enums\Permission` (22 cases) + `User::hasPermission(Permission, ?Meet)`, resolving each permission to the `ManagementTeamType` / `AthleteOversightType` that grants it |
| `manage-meet-data` narrowed | `f0b3b4fc` | Gate is now **Admin-only** (was Admin + Organizer). Registry/catalog writes moved to `role:admin`. The Organizer role is now predominantly read + oversight + assignment-scoped |
| New scoped gates | Production Load Controls, management dashboard work | `view-system-logs`, `view-management-reports` |
| Policies for new domains | WP-REALIGN-10 … -12, `f0b3b4fc` | `SupplyPolicy`, `FoodPolicy`, `BilletingPolicy`, `TransportPolicy`, `MedicalPolicy`, `DrrmPolicy`, `EligibilityDocumentPolicy` added (15 policy classes total) |
| Athlete oversight roles | `f0b3b4fc` | `AthleteOversightAssignment` + `AthleteOversightType` (District Sports Coordinator, Municipality Team Manager) for read-only readiness/roster views |

Net effect: the authorization model is **more granular than the mandate's
~18-role target asked for**, but expressed as `role + assignment record`
rather than one enum case per function — exactly the shape
`pmms-role-and-scope-map.md` recommended.

## What this reconciliation pass did (2026-09-11)

Documentation and comments only — no behaviour change:

1. **Rewrote `docs/authorization.md`** to describe the current model: the
   9 roles + `additional_roles`, the assignment-based scope mechanisms, the
   4 gates, the capability methods, the `Permission` enum, the actual
   `routes/web.php` group structure, the 15 policy classes, and a corrected
   authorization matrix with explicit "policies/services are authoritative"
   disclaimers. Fixed matrix cells that still showed the pre-`f0b3b4fc`
   Organizer capability (athlete/personnel/entry registration, result
   encoding, event/venue/schedule/match writes are no longer plain
   Organizer actions).
2. **Fixed stale comments in `routes/web.php`** that referenced a "flat
   `role:admin,organizer` group" — that group is now `role:admin`, and the
   Supply/Food/Medical policy access notes were corrected (Food/Supply are
   Admin-or-team, not Admin/Organizer/team).
3. **Updated the `AppServiceProvider::configureAuthorization()` docblock**
   to note the Admin-only narrowing of `manage-meet-data`.

## Code portion — `SchoolPolicy` + `AnnouncementPolicy` (2026-09-11)

Owner decision: lift only the two modules that had **real capability
scoping** scattered across in-controller `abort_unless()` calls.

- `app/Policies/SchoolPolicy.php` — `create`/`update`/`delete` →
  `User::canManageSchoolMasterData()`; `viewAny`/`view` → any login.
  `SchoolController::archive`/`restore`/`destroy` now
  `Gate::authorize('update'|'delete', $school)`; `SchoolRequest::authorize()`
  routes through `can('create'|'update', …)`; the `canManage` index prop
  is `can('create', School::class)`.
- `app/Policies/AnnouncementPolicy.php` — `viewAny`/`view` →
  `canViewAnnouncements()`; `create`/`update`/`publish`/`delete` →
  `canManageAnnouncements()`. All 7 `AnnouncementController`
  `abort_unless()` calls replaced with `Gate::authorize(...)`; unused
  `Request` params dropped from the now-bodyless actions.
- Behaviour is identical — the policies delegate to the same predicates.
  `HandleInertiaRequests`'s shared `can_manage_*` props keep calling the
  predicate directly (same convention as the ~15 sibling props).
- Tests: `AnnouncementTest` gains an "organizer may browse but not
  manage" case and an "active ICT team member may manage" case;
  `SchoolTest`'s existing ICT / unauthorized-roles coverage already
  exercises `SchoolPolicy`.

The other 10 modules stay **deliberately** policy-free — see
docs/authorization.md § Policies for the per-module rationale (flat
admin-only; request-payload-scoped Event/Venue; the Audit-Log Gate).

## What remains (not done here)

- **WP-REALIGN-14** (Reports & Dashboard alignment for the new domains),
  **WP-REALIGN-15** (seeder / reference-data alignment), **WP-REALIGN-16**
  (integration / migration testing & acceptance) — still unbuilt.
- A single `AuthorizationMatrixTest` covering the *allowed* paths for the
  committee/assignment roles (today those live scattered across the
  per-domain feature tests).

## Follow-up (2026-09-11, `2e61b55`)

Investigated whether the Organizer‑role reduction in `f0b3b4fc` was
intentional. **It was** — the same commit added compensating
`view-management-reports` / `view-system-logs` gates, it implements
`pmms-role-and-scope-map.md`'s stated recommendation, and the real DdOPAA
production account set (`docs/reports/testing/production-account-login-matrix.md`)
is built entirely around it: every one of the ~182 "Meet Organizer"
accounts carries an explicit `MeetSportAssignment` or `ManagementTeam`
membership, and no later commit re‑widened Organizer access.

The gap was test hygiene: `f0b3b4fc` swapped ~25 `"organizers can X"`
tests from `->organizer()` to `->admin()` actors without renaming them and
never added `organizer` to the `AuthorizationMatrixTest` forbidden sweep.
Fixed in `2e61b55` — a dedicated plain‑organizer sweep now guards the
reduction, and the mislabeled tests are renamed to `"administrators can
X"`.
