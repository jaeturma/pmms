# PMMS Production User, Role, Assignment, and Management-Team Alignment

Date audited: 2026-08-21  
Baseline: DdOPAA Provincial Meet 2026  
Environment inspected: local MySQL database (`pmmsdb`) and current repository worktree

## Executive finding

The repository already contains the correct foundation for production identity and scoped sport personnel: `people`, `users`, `account_provisions`, `meet_sports`, `meet_sport_assignments`, management teams/members, DSC assignments, and athlete oversight assignments. These models must be aligned and completed; they must not be duplicated.

The current database has exactly one operational meet, `DdOPAA Meet 2026` (ID 1), and it is active. The production seeder entry point calls the DdOPAA 2026 final import and does not call the demo/live-scoring seeders. However, the inspected local database still contains legacy/demo records from earlier explicit seeder runs. Those records are not classified as approved production data and must not be removed automatically without operator confirmation.

The most important implementation gap is that live competition authorization still uses the legacy, meet-unscoped `sport_user` pivot and single `sports.tournament_manager_id`, while the production import writes the authoritative scope to `meet_sport_assignments`. Account provisioning exists, but all 614 approved provisioning records remain pending and none of the 780 production `people` records are linked to users.

## Audited production data

| Area | Current evidence | Assessment |
|---|---:|---|
| Meets | 1 active: `DdOPAA Meet 2026` | Production baseline present; naming differs from the mission's display wording but the source seeder and tests consistently use this name. Do not create another meet. |
| People | 780 | Production identity registry present. |
| People linked to users | 0 | Blocking provisioning gap. |
| Users | 9 | Existing accounts require classification; none are linked to a production person. |
| Account provisions | 614 pending | Approved personnel have provisioning intents, not activated accounts. |
| Meet-sport assignments | 642 total | Production assignment scope exists and supports many people and roles per sport. |
| Management teams | 26 | 25 imported operational teams plus one demo DSAC team in the current local database. |
| Medical clearances | 0 | Reusable athlete/personnel model exists, but production coverage has not started. |
| Event results | 0 | No competition result or medal-tally contamination was found in the inspected database. |

### Meet-sport assignment inventory

| Role | Pending | Active |
|---|---:|---:|
| Tournament Manager | 53 | 1 |
| Assistant Tournament Manager | 3 | 0 |
| Track Tournament Manager | 1 | 0 |
| Field Tournament Manager | 1 | 0 |
| Tournament Secretary | 19 | 0 |
| Tournament ICT | 26 | 0 |
| Technical Official | 538 | 0 |

The assignment table already supports multiple personnel per meet sport and multiple assignments for one person. It also preserves `person_id`, optional `user_id`, category, district, school district, original designation, assignment scope, dates, status, and source sequence. Replacement history should use an ended/inactive old row and a new row; existing rows must not be overwritten or deleted.

## Existing architecture to preserve

### Identity

- `Person` is the canonical real-person record.
- `User` is the authentication account.
- `people.user_id` is unique, enforcing at most one user link per person.
- `meet_sport_assignments.person_id` allows production assignments to exist before activation.
- `account_provisions.person_id` is unique, preventing duplicate provisioning intents for one person.
- `Personnel` remains the meet/delegation roster record and is not a replacement for `Person`.

Target invariant:

```text
one Person -> zero or one User -> many scoped assignments
```

### Meet and sports catalog

- `Meet`, `Sport`, `MeetSport`, `SportCategory`, and `Event` are working models.
- `MeetSport` is the correct meet-year boundary for sport personnel.
- `MeetSportAssignment` is the correct production relationship for Tournament Managers, Assistant TMs, Tournament Secretaries, Tournament ICT, and Technical Officials.
- `sports.tournament_manager_id` and `sport_user` are legacy compatibility relationships and must not remain authorization authorities.

### Organization and monitoring

- Municipalities/delegations, school districts, and schools are already modeled.
- DSC production assignments have a dedicated meet-scoped table.
- Municipality/Team Manager and DSC monitoring scopes have a reusable `athlete_oversight_assignments` structure.
- Management team membership is person-first and can optionally link a user.

### Eligibility and medical

- DSAC permissions are already separated from medical permissions in `Permission` and `User::hasPermission()`.
- `MedicalClearance` already accepts an athlete or a personnel record, so no athlete-only replacement table should be introduced.
- The medical model does not yet directly cover canonical `Person` subjects such as management-team members who do not have a `Personnel` roster row. A forward-only extension is required before full production coverage can be claimed.
- Medical access logging and policy classes exist and should remain the confidentiality boundary.

### Coach workflow

- Fortify self-registration, coach onboarding, coach assignment requests, municipality/school/sport selection, and scoped athlete enrollment are present.
- Coaches must continue through self-registration; production seeders must not create placeholder coaches.

### Audit

- `audit_logs` and `AuditLogger` are established and already used for result and provisioning actions.
- Password resets, activation/deactivation, assignment replacement, official-result validation, return, reopen, and correction must all use this existing audit path.

## Production management-team alignment

The imported teams are source-coded records and must be preserved, including teams not named in the mission (for example Incident Command, Opening and Closing Program, Decoration, Usherettes, Finance, Clean & Green, Support Staff, Announcers, and Kitchen Personnel).

The imported source contains the required Top Management, QA/Monitoring & Evaluation, Learners Rights and Protection, Sports Lines Up and Placement, Secretariat, Grievance, Playing Venue, Peace and Security, Logistics, Water/Light/Sanitation, Information, Event Secretariat, Medical, Food/Meals, and DSAC groups.

`EVENT_SECRETARIAT` is currently classified as generic `meet_management`. This is acceptable for preserving source data, but authorization must select the source code (or a dedicated equivalent capability) when granting official result authority. It must not grant that authority to every meet-management member. The legacy `ResultsCommittee` enum value/data should be retained for history but must not be the production finalization authority when Event Secretariat is configured.

The current local database also contains `COACH_WORKFLOW_DEMO_DSAC` / `DSAC Demo Team`. It came from a demo-only workflow seeder, is not called by `DatabaseSeeder`, and is not approved production data. It is flagged for an explicit, backed-up cleanup operation; this audit does not silently delete it.

## Authorization gaps

1. `UserRole` contains only broad legacy roles (`admin`, `organizer`, `delegation_officer`, `technical_official`, `tournament_manager`, `coach`, `viewer`). Most production capabilities are represented by assignments or team membership, which is correct, but results and route middleware still assume the broad roles are the authority.
2. Tournament Manager scoping currently reads `sports.tournament_manager_id`, which permits only one catalog-wide TM and has no meet boundary.
3. Technical Official scoping currently reads `sport_user`, which has no meet boundary.
4. Tournament Secretary, Tournament ICT, and Assistant TM do not have complete login-capability mappings even though their assignments are imported.
5. Admin/Organizer can currently validate results; Tournament Managers can validate their sport's results. This conflicts with Event Secretariat being the approved official-result authority.
6. Result statuses are only encoded/validated. The submission, return, official, reopen, and cancellation lifecycle is not represented.
7. Medal tally uses `validated_at`/validated results as official. It must ultimately consume only `OFFICIAL` results.
8. There is no production default-password reset/forced-change implementation.
9. Current users require classification and linkage; unlinked accounts must not be assumed to represent imported personnel.

## Seeder and demo classification

`DatabaseSeeder` currently calls only reference catalogs and `DdOPAA2026FinalSeeder`. The final seeder calls the actual meet, TWG, DSC, and sport-personnel imports. This is the correct production entry path.

The following seeders are explicitly demo/showcase/test utilities and must never be called by the production entry point:

- `*LiveDemoSeeder`
- `CoachWorkflowDemoSeeder`
- `Ddopaa2026ShowcaseSeeder`
- `RoleShowcaseSeeder`
- sample player/live-score seeders such as `BasketballPlayersSeeder`

`AdminUserSeeder` and `RoleAccountSeeder` create generic accounts and are not approved production provisioning sources. They must remain excluded from `DatabaseSeeder`. Automated tests may call them only in isolated testing databases.

Occurrences of demo/sample/fake text under `tests/`, factories, documentation, UI examples, and dedicated demo seeders are testing/reference material rather than production bootstrapping. They should not be deleted merely because their text matches a search term.

## Required minimum changes

In dependency order:

1. Make `meet_sport_assignments` the authorization source for all sport personnel, retaining legacy relationships only as transitional read compatibility where needed.
2. Extend capabilities so Event Secretariat alone can review, return, validate/make official, and tightly controlled reopen results.
3. Introduce a forward-only result workflow migration with an auditable revision/correction record; make medal tally consume only official results.
4. Provision existing approved people without duplication. Activation must first reuse/link an existing matching account where safely deterministic; otherwise create one through the provisioning workflow.
5. Support username authentication where email is absent, or collect a verified unique email before email-based activation. The current users table requires email and the activation UI requires one, so email-less production records cannot currently activate.
6. Add secure configured-default password reset, account active state, and forced next-login password change. Never persist or audit the configured password itself.
7. Extend medical clearance to canonical `Person` subjects while preserving athlete/personnel compatibility and confidential-field policies.
8. Keep coach production self-registration and prove municipality/school/sport scope boundaries with tests.
9. Produce an operator-reviewed cleanup list for the nine existing unlinked users and demo DSAC team. Do not delete records based only on naming heuristics.

## Decisions and non-decisions

- No schema was rewritten during this audit.
- No production or local records were deleted or updated.
- No new meet, sport, person, user, athlete, coach, result, or assignment was invented.
- No pending provision was auto-activated because the source lacks sufficient authentication credentials and the configured production account policy must be applied.
- The existing user worktree changes were preserved.
- The actual approved Super Administrator cannot be identified from the imported person/provision data by code alone. Configuration must identify the approved person/account; a generic `admin@pmms.local` fallback is not production approval.

## Production blockers at audit completion

- 614 approved account provisions are pending; zero production people are linked to users.
- Existing user records are unlinked and require operator classification/deduplication.
- Sport authorization still depends on legacy unscoped relationships.
- Event Secretariat is not yet the official result authority.
- Result workflow lacks submitted/returned/official/reopened states and revision history.
- Default reset and forced password change are absent.
- Medical clearance cannot cover a canonical person without an athlete/personnel row.
- The current environment is `local`, debug is enabled, timezone is UTC, and therefore it is not a production deployment configuration.
- The current local database contains an explicitly named demo DSAC team. Cleanup requires confirmation after backup.

