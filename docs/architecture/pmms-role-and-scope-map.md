# PMMS Role and Scope Map

Companion to [pmms-organizational-realignment-gap-assessment.md](../reports/architecture/pmms-organizational-realignment-gap-assessment.md). Current state verified against `app/Enums/UserRole.php`, `app/Providers/AppServiceProvider.php` (the two `Gate::define` calls), `app/Policies/*.php`, and `docs/authorization.md`'s full matrix. Target state is proposed, not implemented.

## Current roles (5) — verified

| Role | Enum value | Scope mechanism |
|---|---|---|
| Administrator | `admin` | Unconditional pass on both gates (`administer`, `manage-meet-data`) |
| Meet Organizer | `organizer` | Passes `manage-meet-data`, not `administer` |
| Delegation Officer | `delegation_officer` | Passes only for delegations where `delegation_user` links them (`Delegation::hasOfficer()`), and only while the delegation is `draft` + the meet's registration window is open, for mutations |
| Technical Official | `technical_official` | Passes only for sports where `sport_user` links them (`User::sports()`) — global across meets, not meet-scoped (the gap) |
| Viewer | `viewer` | Read-only; excluded from every sensitive/minors-data module (Athletes, Personnel, Eligibility, Accreditation) entirely |

Two gates only: `administer` (Admin-only, `AppServiceProvider.php:44`) and `manage-meet-data` (Admin+Organizer, `:46`). Seven policies (Athlete/Delegation/EligibilityReview/Entry/FileUpload/Personnel/Protest) carry the fine-grained per-record scoping; everything else uses route-group role middleware only.

## Target roles (mandate §29, ~18) — mapped to current, gap-flagged

| Target role | Maps to today | Gap |
|---|---|---|
| System Administrator | `admin` | None — already exists |
| ICT Team | *(none)* | New role or new `ManagementTeam` membership (`team_type = ICT`) — recommend the latter, not a new `UserRole` case (see "Design note" below) |
| Top Management | `admin`/`organizer` (undifferentiated) | New scoped read/approve capability, not full admin — currently anyone passing `manage-meet-data` gets identical access regardless of seniority |
| Meet Manager | `organizer` (undifferentiated) | Same gap — no distinction between "Organizer generally" and "the Meet Manager for this specific meet" |
| Results Committee | `admin`/`organizer` via `manage-meet-data` | No distinct role; validate/correct/delete result is folded into the same gate as everything else Organizer can do |
| Division Secretariat / DSAC | `admin`/`organizer` via `EligibilityReviewPolicy::decide` | Same pattern — any Organizer can decide any eligibility review, not a scoped DSAC group |
| Tournament Manager | *(rejected previously, see gap assessment §9)* | Needs `MeetSportAssignment.role = 'Tournament Manager'`, scoped to a `MeetSport` |
| Assistant Tournament Manager | *(none)* | Same mechanism, `is_lead = false` |
| Tournament Secretary | *(none)* | Same mechanism, different `role` value |
| Tournament ICT | *(none)* | Same mechanism, different `role` value — distinct from the central "ICT Team" row above (mandate §12 explicitly separates these two scopes) |
| Technical Official | `technical_official` (global per sport) | Needs re-scoping from `Sport` to `MeetSport` (same mechanism, narrower scope) |
| Coach | `delegation_officer` acts on behalf of `PersonnelRole::Coach` roster rows (no coach login) | Open product decision — see gap assessment §10 / OQ-2 |
| Supply Team | *(none)* | New `ManagementTeam` membership, `team_type = Supply` |
| Food Team | *(none)* | New `ManagementTeam` membership, `team_type = Food` |
| Billeting Team | *(none)* | New `ManagementTeam` membership, `team_type = Billeting` |
| Transport Team | *(none)* | New `ManagementTeam` membership, `team_type = Transport` |
| Medical Team | *(none)* | New `ManagementTeam` membership, `team_type = Medical` — highest-sensitivity, needs its own policy from day one |
| DRRM Team | *(none)* | New `ManagementTeam` membership, `team_type = DRRM` |
| Authorized Viewer | `viewer` | Already exists, unchanged |

**Design note**: the mandate lists ICT/Supply/Food/Billeting/Transport/Medical/DRRM as if they were `UserRole` enum cases (like Admin/Organizer today), but they are better modeled as **`ManagementTeamMember` rows scoped by `team_type`**, not new `UserRole` cases — a person's base `UserRole` (most will be `Organizer` or a new lightweight "Committee Member" case) stays coarse, and their *specific* committee scope comes from which `ManagementTeam` rows they belong to, mirroring exactly how Technical Official sport-scoping already works via a pivot rather than one `UserRole` case per sport. This avoids the enum exploding to 18+ cases and matches the existing, working pattern (`sport_user`) rather than inventing a new authorization shape.

## Scope dimensions (mandate §29, cross-checked against what's enforced today)

| Dimension | Enforced today | Enforcement layer |
|---|---|---|
| Current meet | Partially — most modules are meet-agnostic lists filtered by `meet_id` query param, not hard-scoped per role | Controller query filters |
| Municipality | Yes, for Delegation Officer (`Delegation::hasOfficer()`) | `DelegationPolicy` + `AthletePolicy`/`PersonnelPolicy`/`EligibilityReviewPolicy` (all key off the officer's delegation) |
| School | No — Delegation Officer scope is the whole pooled municipal roster, not per-school (`docs/delegations.md` "Officer roster scope," an accepted, documented consequence, not a bug) | — |
| Sport | Yes, for Technical Official, but globally not per-meet (the gap) | `ScoringSessionController::canManage()`, `ResultController::authorizeEncode()` |
| Sport category | No — no `SportCategory` model exists to scope against yet | — |
| Management team | No — no `ManagementTeam` model exists yet | — |
| Committee | No — same | — |
| Assignment ownership | Partially — `FileUploadPolicy` scopes strictly to uploader; most other policies scope to delegation membership, not individual assignment ownership | Per-policy |

## Recommended enforcement pattern for new domains (WP-REALIGN-09 through -13)

Follow the pattern already proven by Technical Official scoping, not a new pattern:
1. A `ManagementTeamMember` row is the source of truth for "is this user on this team, for this meet."
2. Each new domain's policy (`SupplyPolicy`, `MedicalPolicy`, etc.) checks `Gate::allows('manage-meet-data')` for Admin/Organizer (unconditional), then falls back to `$user->managementTeamMemberships()->where('management_team_id', $team->id)->exists()` for everyone else — the exact shape `ScoringSessionController::canManage()` already uses for `$user->sports()->whereKey(...)->exists()`.
3. **Backend enforcement only** — every existing policy in this app computes `can_*` flags server-side and never trusts a hidden frontend button; the mandate's "Do not rely only on frontend-hidden controls" instruction (§29) is already this app's convention, not a new requirement to introduce.
