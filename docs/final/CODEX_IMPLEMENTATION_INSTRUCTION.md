# Codex VS Code Instruction — DdOPAA Provincial Meet 2026 Final Data Migration

## Mission

Use the supplied SQL and source workbook to finalize the Provincial Meet 2026 organizational structure, TWG responsibilities, District Sports Coordinators, Sports personnel assignments, user provisioning, and Coach onboarding behavior in the existing PMMS Laravel 13 + React + Inertia project.

Files:

```text
pmms_provincial_meet_2026_migration.sql
TWG_ROLE_MATRIX.md
DATA_REVIEW.md
TWG TM and TO.xlsx
```

This is an EXISTING application. Do not blindly create duplicate tables.

## First: inspect the repository

Inspect:

- migrations
- current database schema
- models
- seeders
- factories
- users / people / personnel tables
- roles and permissions
- Meet / MeetSport / Sport / SportCategory
- Municipality / SchoolDistrict / School
- TWG / committee structures
- DSC assignments
- Tournament Manager assignments
- Technical Official assignments
- Tournament Secretary assignments
- Tournament ICT assignments
- Coach registration
- athlete registration
- DSAC eligibility
- Medical clearance
- Results confirmation
- audit logs

Create:

```text
docs/reports/architecture/ddopaa-2026-final-migration-alignment.md
```

Before modifying code, map every SQL target table to the existing PMMS equivalent.

Classify each as:

- REUSE
- EXTEND
- CREATE
- DO_NOT_CREATE
- DATA_BACKFILL_ONLY

Do not create a second table when an equivalent already exists.

## Approved operational structure

```text
DdOPAA Meet 2026
│
├── Municipality / Delegation
│   └── School District
│       ├── District Sports Coordinator(s)
│       └── Schools
│           ├── Coaches
│           └── Athletes
│
├── Meet Sports
│   ├── Tournament Manager(s)
│   ├── Assistant Tournament Manager(s)
│   ├── Tournament Secretary
│   ├── Tournament ICT
│   └── Technical Officials
│
└── Technical Working Groups / Management Functions
```

Municipality remains the official delegation.
School remains athlete/coach origin.

## Authority rules

### Coach

Coach will NOT be seeded from the workbook.

Coach must:

1. self-register;
2. complete profile;
3. select/request authorized sport assignment(s);
4. be scoped to Municipality/School/Sport as approved;
5. enroll/register athletes;
6. upload athlete requirements;
7. submit registrations;
8. correct returned registrations.

A Coach cannot validate DSAC eligibility or Medical clearance.

### DSAC

DSAC validates:

- athlete profile
- School ID
- PSA Birth Certificate
- Parental Consent
- school/delegation consistency
- age/grade/category requirements
- duplicate/event-limit rules and other approved eligibility rules

DSAC may approve, return, or reject athlete eligibility components.

### Medical Team

Medical Team controls:

- medical certificate evaluation
- medical clearance
- restrictions/referrals

DSC, Team Managers and Coaches may monitor medical STATUS only, not confidential medical details.

### District Sports Coordinator

DSC is an internal monitoring/readiness role scoped to Meet + Municipality + School District.

DSC monitors:

- schools
- coaches
- athletes
- profile completion
- document status
- DSAC status
- medical clearance status
- accreditation/readiness

DSC cannot approve DSAC validation or Medical clearance.

### Municipal / Team Manager

Municipal/Team Manager monitors the whole Municipality/Delegation and all authorized School Districts, schools, DSCs, coaches and athletes.

Monitoring role; not DSAC/Medical approving authority.

### Tournament Manager

TM is the operational lead for the assigned MeetSport/category.

TM may:

- manage assigned sport schedules/operations
- coordinate assigned tournament personnel
- submit/endorse sport results within existing workflow
- operate/authorize live or result-only scoring as configured
- monitor sport participants

TM cannot bypass Results Committee final confirmation.

### Assistant Tournament Manager

Supports TM in assigned scope. Do not automatically grant all TM privileges unless configured.

### Tournament Secretary

Maintains sport-level records, schedule/result documents, routing and secretariat functions.

### Tournament ICT

Provides sport-level PMMS, live scoring, scoreboard, device and connectivity support.

Tournament ICT is different from central ICT Team.

### Technical Official

Performs sport-specific officiating/technical duties. TO assignment must support accreditation attachment/status under existing eligibility/accreditation logic.

### Results Committee

Confirms submitted results before finalization.

Only finalized results update official standings, rankings and medal tally.

## TM / TO users

All people imported from the `TM and TO` worksheet must be represented as People/Personnel and MeetSport assignments.

They are intended to become system users.

Do NOT create insecure accounts with known/default passwords.

Use the application's existing account invitation/provisioning workflow.

The SQL contains `pmms_user_provisioning` as a staging concept.

If the project already has:

- invitations
- pending users
- account activation
- first-login password setup

reuse it.

Requirements:

- unique username/account identity
- no invented email
- no plaintext/default shared password
- must set password / activate account
- link User to Person/Personnel
- assign permissions based on actual MeetSport role and scope

A person appearing in multiple sports/roles gets ONE user account with MULTIPLE assignments.

Do not create duplicate users for the same normalized person.

## Role normalization

Map source labels into reusable roles:

- TOURNAMENT_MANAGER
- TOURNAMENT_MANAGER_TRACK
- TOURNAMENT_MANAGER_FIELD
- ASSISTANT_TOURNAMENT_MANAGER
- ASSISTANT_TM_TRACK
- TOURNAMENT_SECRETARY
- TOURNAMENT_SECRETARY_ICT
- TOURNAMENT_ICT_TECHNICAL_OFFICIAL
- TECHNICAL_OFFICIAL

Keep the original designation text for audit/display.

If one source role combines functions (e.g. `TOURNAMENT SECRETARY/ICT`), either:

1. preserve the combined assignment and grant both scoped capabilities; or
2. create two role assignments to the same Person/User if the current architecture expects atomic roles.

Do not create two People.

## Sports

The final TM/TO worksheet has 28 event headers and is the authoritative personnel-assignment list for this migration.

Do not silently resolve these two source discrepancies:

1. Players sheet includes Basketball 3x3 but final TM/TO list does not.
2. TM/TO sheet combines Weightlifting / Kickboxing under one event header.

Flag both in the implementation report.

If existing PMMS already has Basketball 3x3 or separate Weightlifting/Kickboxing records, preserve them and map assignments only after confirming intended scope.

## TWG

Use `TWG_ROLE_MATRIX.md` to finalize unit roles.

Prefer generic:

Meet / TWG Unit
→ Members
→ Role Title

Do not create one database table per committee.

People may belong to multiple TWGs.

## DSC

Import the 18 DSC rows.

Use assignment-based modeling:

Meet + Municipality + SchoolDistrict + Person

Support 1 or more DSCs per School District, even if the current workbook has one listed per district.

## Geographic safety

Use the current official geographic master data already in the project.

The workbook does not provide Congressional District mapping.

Do not invent it.

Resolve source District text against canonical SchoolDistrict records using aliases.

For municipality-only source labels, preserve original source text and map Municipality, leaving SchoolDistrict null unless confidently resolvable.

## SQL application strategy

The SQL file is a target migration/reference script.

DO NOT execute it blindly against the existing PMMS schema.

Instead:

1. inspect existing schema;
2. create Laravel migrations only for missing fields/relationships;
3. convert source inserts into Laravel seeders/importers or safe SQL inserts matching existing tables;
4. preserve IDs and data already present;
5. make imports idempotent;
6. use transactions;
7. create backup/checkpoint instructions;
8. do not use `migrate:fresh`;
9. do not use `db:wipe`.

## Seeder/importer outputs

Create or update appropriate seeders/importers such as:

```text
DdOPAA2026MeetSeeder
DdOPAA2026TWGSeeder
DdOPAA2026DSCSeeder
DdOPAA2026SportPersonnelSeeder
```

Names may follow current conventions.

Do not put 600+ raw assignments in application code if the project already has a clean import/data fixture approach. A structured PHP data file is acceptable.

## Coach self-registration update

Verify the current Coach onboarding workflow.

Required:

```text
Coach Self Registration
→ Profile
→ Municipality / School selection
→ Sport selection/request
→ Assignment approval if configured
→ Athlete enrollment/registration
```

Coach may select more than one sport only if allowed by approved assignment rules.

Coach must only enroll athletes within authorized Municipality/School scope.

## Permissions

Do not rely only on role names.

Permissions must be scope-aware.

Examples:

TM:
- assigned MeetSport only

TO:
- assigned MeetSport/category/venue only

DSC:
- assigned SchoolDistrict only

Coach:
- assigned Municipality/School/Sport only

DSAC:
- eligibility validation

Medical:
- medical evaluation

Results Committee:
- result confirmation

## Required tests

Add/verify tests for:

- one Person may have multiple sport assignments
- user provisioning does not duplicate people
- TM scope
- Assistant TM scope
- Tournament Secretary scope
- Tournament ICT scope
- TO scope
- TO accreditation support
- DSC District scope
- Coach self-registration
- Coach sport selection
- Coach athlete enrollment
- Coach cannot enroll cross-municipality athlete
- DSAC authority
- Medical authority
- result confirmation
- finalized results only update medals
- municipality remains official delegation
- school remains origin institution

## Data verification

After migration/import, report:

- 28 final TM/TO event headers
- sport personnel assignment count
- unique people count
- pending account provisions
- TWG membership count
- DSC count
- unresolved District aliases
- users linked/provisioned
- duplicate candidates
- unresolved sport discrepancies

## Documentation

Create:

```text
docs/architecture/ddopaa-2026-final-organization.md
docs/features/twg-roles.md
docs/features/sport-personnel-and-user-provisioning.md
docs/features/coach-self-registration.md
docs/reports/data/ddopaa-2026-final-import-report.md
```

## Stop conditions

Do not commit.
Do not push.
Do not destroy existing data.

Begin with repository/database alignment and report first.

After the alignment report, implement the safe migrations + seeders/importers and run tests.
