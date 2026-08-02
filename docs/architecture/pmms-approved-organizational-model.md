# PMMS Approved Organizational Model

Companion to [pmms-organizational-realignment-gap-assessment.md](../reports/architecture/pmms-organizational-realignment-gap-assessment.md). Status: **proposed target**, not yet implemented — no migrations exist for anything marked "New" below. Where an entity already exists and is aligned, this document says so and moves on rather than re-describing it; full evidence is in the gap assessment.

## Design principle

Extend the running system (Track A, per the gap assessment §0) additively. Reuse the existing 34-bounded-context catalog (`docs/01-architecture/bounded-context-catalog.md`) as a **naming and scoping reference** for the genuinely new domains — it already thought through Committee/Medical/Billeting/Food/Transport/ICT boundaries in more depth than a from-scratch design would — but implement each as a plain Eloquent model + policy + Inertia page in this app's existing pattern, not as a separate service boundary.

## 1. Geography and schools (existing, one addition)

```
CongressionalDistrict (NEW)
└── District  [ = Municipality, existing, unchanged class name]
    └── SchoolDistrict  [existing]
        └── School  [existing]
```

- `CongressionalDistrict` — new table: `id`, `name` ("First District", "Second District"), `active`, timestamps. `District` gets a new nullable `congressional_district_id` FK **alongside** (not replacing) the existing `congressional_district` string column, per the migration-risk note in the gap assessment §21.
- Everything below `District` is unchanged: `SchoolDistrict`, `School`.

## 2. Meet, delegation, sport (existing, two additions)

```
Meet  [existing]
├── MeetDelegation  [= existing Delegation model, unchanged — already meet-scoped via meet_id]
│   └── (municipality-registered only, under Province — already enforced, see gap assessment §1-6)
└── MeetSport (NEW)
    └── Sport  [existing, becomes meet-scoped only via this new join]
        └── SportCategory (NEW, optional layer)
            └── Event  [existing — team sports may skip SportCategory and attach directly, as today]
```

- `MeetSport` — new table: `id`, `meet_id`, `sport_id`, `active` (include/exclude this sport from this meet), `notes`, timestamps. Unique on `(meet_id, sport_id)`.
- `SportCategory` — new table: `id`, `sport_id`, `meet_sport_id` (nullable — a category can be catalog-wide or meet-specific), `level` (enum, reuses `AgeDivision`), `sex` (enum, reuses `GenderCategory`), `discipline` (nullable string — "Track", "Field", "Lightweight"), `event_type` (nullable string — "individual"/"team", mirrors `Event.is_team_event`), `display_name`. `Event` gains an optional nullable `sport_category_id` — existing `Event.gender`/`Event.age_division` columns are **not removed**; a category, where used, is additive context, not a replacement (avoids the high-risk rewrite flagged in the gap assessment §21).
- `Delegation` is not renamed. The mandate's "MeetDelegation" concept is what `Delegation` already is (meet-scoped via `meet_id`, municipality-scoped via `district_id` under Province).

## 3. Personnel and assignments (existing pivots kept, one new generic layer)

```
Person  [NOT built — see Open Question OQ-1 below]
├── User  [existing — login/auth, unchanged]
└── MeetSportAssignment (NEW)
    ├── role: Tournament Manager | Assistant TM | Track TM | Field TM |
    │         Boys TM | Girls TM | Category TM | Tournament Secretary |
    │         Tournament ICT | Technical Official
    ├── meet_sport_id (FK, replaces sport_user's bare sport_id)
    ├── sport_category_id (nullable FK)
    ├── user_id
    ├── is_lead, start_date, end_date, status
```

- `MeetSportAssignment` — new table, the structural fix for the gap assessment's #1 finding (§7/§9): every tournament-personnel role becomes meet-scoped, dated, and multi-role-per-sport instead of the current bare `sport_user` pivot. The existing `sport_user` pivot and `Sport::technicalOfficials()` relation are **retired in favor of this**, not kept in parallel (a Technical Official assignment migrates from `sport_user` into `MeetSportAssignment` with `role = 'Technical Official'` and a backfilled `meet_sport_id` — see the migration plan for the backfill strategy).
- `delegation_user` (existing) is unchanged — Delegation Officer assignment is meet-and-delegation-scoped already, no gap there.
- **Open Question OQ-1**: the mandate's "Person → may have User Account → may have multiple assignments" model implies unifying `User` (login) and `Personnel`/roster records (no login) into one `Person` entity. This is a materially larger change than anything else in this document (touches every FK currently pointing at either `users` or `personnel`) and is **not recommended for this phase** — flagged as an open question for the product owner rather than assumed. Short-term: keep `User` and `Personnel` separate; `MeetSportAssignment.user_id` only covers people who already have logins (Admin/Organizer/TechnicalOfficial/DelegationOfficer today).

## 4. Coach and athlete registration (existing, one product decision pending)

Unchanged: `Delegation → Athlete/Personnel → Entry → EligibilityReview → Accreditation`, all already meet/delegation/school scoped correctly (gap assessment §11-12). **Open Question OQ-2**: whether to build first-class Coach login accounts (mandate §14) or keep today's model where a `DelegationOfficer` performs registration on the roster `PersonnelRole::Coach` record's behalf. No schema is proposed here until that product decision is made — see the workflow map for both options laid out side by side.

## 5. Results and medal tally (existing, unchanged)

`EventResult` (Encoded → Validated), `ResultPlacement`, `MedalTallyService` — all already aligned (gap assessment §13). No new entities. If a richer Results Committee state machine is wanted later (Draft/Submitted/For Confirmation/Returned/Reopened/Cancelled), it is an **enum expansion on the existing `ResultStatus`**, not a new model — deferred to product decision (WP-REALIGN-08).

## 6. Management teams and committees (net-new)

```
ManagementTeam (NEW)
├── meet_id
├── team_type: TopManagement | MeetManagement | ResultsCommittee |
│              DivisionSecretariat | ICT | Supply | Food | Billeting |
│              Transport | Medical | DRRM
├── name, description, status
└── ManagementTeamMember (NEW)
    ├── management_team_id, user_id
    ├── role_title, is_head, responsibilities, status
```

One configurable pair of tables (per the mandate's own §15 recommendation — "do not create a separate hardcoded database table for every committee"), `team_type` as an enum. This becomes the ownership anchor every domain in §7 below reports "assigned to."

## 7. Operational domains (net-new, reusing Track B naming)

Each below is its own small subtree, owned by a `ManagementTeam` row of the matching `team_type`. None of these exist today in any form beyond `Incident.medical_referral` (a single boolean flag).

- **Supply**: `EquipmentItem`, `EquipmentCategory`, `EquipmentIssue`, `EquipmentReturn`, `EquipmentTransfer`, `InventoryAdjustment`. No procurement/accounting layer (mandate explicitly excludes it).
- **Food**: `MealAnnouncement`, `MealSchedule` — deliberately kept to the mandate's "initial implementation should remain simple" instruction; no catering/accounting system.
- **Billeting**: `BilletingVenue`, `BilletingAssignment` — public-facing pages must show only approved general info (mandate §23), contact/room detail restricted to Billeting Team + the assigned delegation's own officer.
- **Transport**: `Vehicle`, `TransportTrip`, `TransportRequest` — driver/passenger PII restricted same as Billeting.
- **Medical**: `MedicalClearance`, `MedicalIncident` — **highest sensitivity in this entire model**. Per the mandate §25 and the gap assessment's §21 flag, access control must ship in the *first* migration, not be retrofitted; only safe aggregate statuses (cleared/pending/restricted/referred) surface outside the Medical Team. This domain also has a standing, formal "requires policy validation" flag already on record in the existing Track B backlog (`docs/11-backlog/phase-1-deferred-scope.md:17`) — resolve that with the product owner before WP-REALIGN-12 schema work begins, don't silently override it.
- **DRRM**: `DrrmPlan`, `VenueEmergencyPlan`, `EvacuationRoute`, `EmergencyContact`, `DrrmEquipment`, `ReadinessChecklist`, `EmergencyIncident`, `EmergencyCommunicationLog` — genuinely net-new with no prior art anywhere in either planning track; scope needs to be defined from scratch with the product owner (what "emergency" means for a meet — weather, medical mass-casualty, security — is undefined today).

`Incident` (existing, protest-adjacent meet-day log) is left as-is; it is not repurposed as the DRRM incident model — DRRM incidents need fields (classification, responder, escalation) that would bloat `Incident`'s current, working, simpler purpose for the majority of its existing use (non-emergency meet-day issues). A DRRM-specific `EmergencyIncident` table is cleaner than overloading `Incident`.

## 8. What is deliberately NOT changed

- `District`/`SchoolDistrict`/`School`/`Delegation`/`Athlete`/`Personnel`/`Entry`/`EligibilityReview`/`Accreditation`/`EventResult`/`ResultPlacement`/`MedalTallyService` — all already aligned, zero schema change proposed.
- No table is renamed. No table is dropped. `sport_user` is retired only once `MeetSportAssignment` fully replaces its one consumer (`ScoringSessionController::canManage()`), in a later, separate migration — never in the same deploy as its replacement's introduction.
