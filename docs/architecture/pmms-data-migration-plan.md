# PMMS Data Migration Plan

Companion to [pmms-organizational-realignment-gap-assessment.md](../reports/architecture/pmms-organizational-realignment-gap-assessment.md) and [pmms-approved-organizational-model.md](pmms-approved-organizational-model.md). **This is a plan, not an executed migration** — no schema was changed while producing this document. Hard constraints carried over from the mandate and from this session's own instructions: no destructive migrations, no `migrate:fresh`, no dropped column/table in the first pass, no deleted records, additive-first throughout.

## 1. Tables reused (unchanged)

`districts`, `school_districts`, `schools`, `meets`, `delegations`, `sports`, `events`, `meet_events`, `athletes`, `personnel`, `personnel_sport`, `entries`, `eligibility_reviews`, `eligibility_documents`, `accreditations`, `event_results`, `result_placements`, `event_matches`, `scoring_sessions`, `score_events`, `venues`, `event_schedules`, `announcements`, `incidents`, `protests`, `audit_logs`, `file_uploads`, `users`, `divisions`. No structural change to any of these in this plan.

## 2. Tables extended (additive columns only)

| Table | New column(s) | Nullable | Note |
|---|---|---|---|
| `districts` | `congressional_district_id` (FK → new `congressional_districts.id`) | yes | Existing `congressional_district` string column (added 2026-08-02) is **kept**, not dropped, until every consumer has migrated to the FK — see §7 |
| `events` | `sport_category_id` (FK → new `sport_categories.id`) | yes | Existing `gender`/`age_division` columns are **kept** — see §7 |

No other existing table gains a column in this plan. Every other change is a new table.

## 3. New tables required

Grouped by work package (see the gap assessment §24 for WP descriptions):

**WP-REALIGN-01**
- `congressional_districts` — `id`, `name`, `active`, timestamps.

**WP-REALIGN-02**
- `meet_sports` — `id`, `meet_id` (FK, cascade), `sport_id` (FK, restrict), `active`, `notes`, timestamps. Unique `(meet_id, sport_id)`.

**WP-REALIGN-03**
- `sport_categories` — `id`, `sport_id` (FK, restrict), `meet_sport_id` (FK, cascade, nullable), `level` (string, mirrors `AgeDivision` values), `sex` (string, mirrors `GenderCategory` values), `discipline` (nullable string), `event_type` (nullable string), `display_name`, timestamps.

**WP-REALIGN-04 / -07**
- `meet_sport_assignments` — `id`, `meet_sport_id` (FK, cascade), `sport_category_id` (FK, cascade, nullable), `user_id` (FK, cascade), `role` (string enum: TournamentManager/AssistantTournamentManager/TrackTournamentManager/FieldTournamentManager/BoysTournamentManager/GirlsTournamentManager/CategoryTournamentManager/TournamentSecretary/TournamentICT/TechnicalOfficial), `is_lead` (boolean), `start_date`, `end_date`, `status`, timestamps.

**WP-REALIGN-09**
- `management_teams` — `id`, `meet_id` (FK, cascade), `team_type` (string enum: TopManagement/MeetManagement/ResultsCommittee/DivisionScreeningAndAccreditation/ICT/Supply/Food/Billeting/Transport/Medical/DRRM), `name`, `description`, `status`, timestamps.
- `management_team_members` — `id`, `management_team_id` (FK, cascade), `user_id` (FK, cascade), `role_title`, `is_head` (boolean), `responsibilities` (text, nullable), `status`, timestamps.

**WP-REALIGN-10**
- `equipment_categories`, `equipment_items`, `equipment_issues`, `equipment_returns`, `equipment_transfers`, `inventory_adjustments` — each FK'd to `management_teams` (Supply) and, where relevant, `meets`/`venues`.

**WP-REALIGN-11**
- `meal_announcements`, `meal_schedules` — FK'd to `management_teams` (Food) + `meets`.
- `billeting_venues`, `billeting_assignments` — FK'd to `management_teams` (Billeting) + `meets` + `delegations`.
- `vehicles`, `transport_trips`, `transport_requests` — FK'd to `management_teams` (Transport) + `meets` + `delegations`.

**WP-REALIGN-12**
- `medical_clearances`, `medical_incidents` — FK'd to `management_teams` (Medical) + `meets` + nullable `athlete_id`/`personnel_id`/`user_id`. **Access-controlled from this migration, not after.**
- `drrm_plans`, `venue_emergency_plans`, `evacuation_routes`, `emergency_contacts`, `drrm_equipment`, `readiness_checklists`, `emergency_incidents`, `emergency_communication_logs` — FK'd to `management_teams` (DRRM) + `meets`/`venues`.

**WP-REALIGN-17 (product-owner confirmed 2026-08-05)**
- No new table. `event_schedules` gains nullable `sport_category_id` (FK → `sport_categories.id`), additive alongside its existing `event_id`/`venue_id` — mirrors how `events.sport_category_id` was added in WP-REALIGN-03 without removing `events.gender`/`events.age_division`. `SportCategory` gains a `schedules()` relation (`hasMany(EventSchedule)`) and a derived `venues()` accessor through it. Nothing existing is removed or renamed; `EventSchedule::event()`/`::venue()` are unchanged.

## 4. Obsolete tables retained temporarily

`sport_user` (Technical Official global-sport pivot) is **not dropped** when `meet_sport_assignments` is introduced. It is retained, unused by new code, until:
1. Every existing `sport_user` row has a corresponding `meet_sport_assignments` row (see backfill, §5), **and**
2. `ScoringSessionController::canManage()` and `ResultController::authorizeEncode()` have been switched to read from `meet_sport_assignments` instead, **and**
3. At least one full meet cycle has run successfully against the new table in production, confirming no missed edge case.

Only then does dropping `sport_user` become its own separate, later migration — never bundled with the table's replacement being introduced.

## 5. Backfill procedures

- **`congressional_district_id` backfill**: deterministic, low-risk. The existing `congressional_district` string column already holds "First"/"Second" for all 11 municipalities (seeded 2026-08-02). Create the two `congressional_districts` rows, then `UPDATE districts SET congressional_district_id = (SELECT id FROM congressional_districts WHERE name = districts.congressional_district)`. No ambiguity, no manual review needed.
- **`sport_user` → `meet_sport_assignments` backfill**: **not deterministic — requires a product decision before it can run.** `sport_user` has no `meet_id`, so "which meet(s) was this Technical Official actually assigned to" cannot be derived from existing data. Options to bring to the product owner: (a) backfill every row against every currently-`Active`/`RegistrationOpen` meet (broadest, safest for continuity, over-grants scope); (b) backfill only against the single most-recent meet each TO has any `ScoringSession`/`EventResult` activity on (narrowest, may under-grant and lock out a TO mid-assignment); (c) require every TO to be manually re-assigned per meet going forward, with `sport_user` frozen (no auto-backfill at all, cleanest data but real operational disruption at cutover). **Recommendation: (a) for the first meet cycle after this WP ships, to avoid a live disruption, with an explicit plan to move to (c)'s discipline afterward.** This must be confirmed with the product owner, not assumed by the implementer.
- **`sport_categories` backfill**: additive only, no backfill required — existing `Event` rows keep working unchanged with `sport_category_id = null` until a WP explicitly populates categories for a given sport (Athletics first, per the mandate's own worked example).
- **New operational domains (§3, WP-REALIGN-09 through -12)**: no backfill — these are net-new, there is no prior data to migrate into them.

## 6. Compatibility layer

- `District.congressional_district` (string) and `District.congressional_district_id` (FK) coexist; any code reading the old column continues to work unmodified during the transition. New code should prefer the FK.
- `Event.gender`/`Event.age_division` continue to be the source of truth for filtering/reporting until a specific sport's `SportCategory` rollout is complete and verified; `sport_category_id` is purely additive context in the interim, read by nothing that isn't explicitly opted in.
- `sport_user` and `meet_sport_assignments` (role=TechnicalOfficial) both remain readable during the transition window (§4); `ScoringSessionController`/`ResultController` are the only two consumers and are switched together, atomically, in one deploy — not gradually per-endpoint, to avoid a window where the two authorization sources disagree.

## 7. Rollback strategy

- Every migration in this plan has a corresponding `down()` that only drops what that same migration added — standard Laravel migration discipline, already the pattern every existing migration in this repo follows (verified: every migration read during this assessment has a symmetric `up()`/`down()`).
- Column additions (`congressional_district_id`, `sport_category_id`) roll back by dropping just the column — the pre-existing string/enum columns they sit alongside are untouched either direction.
- The `sport_user` → `meet_sport_assignments` cutover (§6) is the one step that needs a **tested rollback rehearsal before the real cutover deploy**, not just a migration `down()`: if `meet_sport_assignments` backfill turns out wrong post-deploy, reverting `ScoringSessionController`/`ResultController` to read `sport_user` again must be a fast code revert, not a data-loss event — this is why §4 keeps `sport_user` alive well past the cutover rather than dropping it same-day.
- No new table in this plan is ever the *only* copy of pre-existing data (everything net-new has no predecessor to lose); the one genuine one-way risk in the whole plan is the `sport_user` cutover, called out explicitly here rather than left implicit.

## 8. Data validation

Before any migration in this plan is considered done:
- Row-count parity checks: `sport_user` row count == `meet_sport_assignments` (role=TechnicalOfficial) row count, post-backfill.
- Every `districts.congressional_district_id` non-null where `districts.congressional_district` is non-null, and the two values agree, post-backfill.
- No orphaned FK: every `meet_sports.sport_id`/`meet_sports.meet_id` resolves; same for every other new table's FKs — standard `php artisan db:show`/foreign-key-constraint enforcement, not a custom check, since every new table uses real FK constraints (`constrained()->restrictOnDelete()`/`cascadeOnDelete()` per this app's existing convention).
- New Pest feature tests per WP, following the existing test-per-workflow convention (`tests/Feature/*Test.php`) — see §9.

## 9. Migration order

1. WP-REALIGN-01 (`congressional_districts`) — independent, can run first, zero dependents blocking it.
2. WP-REALIGN-02 (`meet_sports`) — independent of #1.
3. WP-REALIGN-03 (`sport_categories`) — depends on #2 existing (a category can optionally scope to a `MeetSport`).
4. WP-REALIGN-04/07 (`meet_sport_assignments`) — depends on #2. Ships with `sport_user` still live (§4) — the cutover of `ScoringSessionController`/`ResultController` is a **separate**, later step, not part of this migration.
5. **Product decision checkpoint** (not a migration): confirm the `sport_user` backfill strategy (§5) with the product owner before proceeding to the cutover.
6. Cutover deploy: `ScoringSessionController`/`ResultController` switched to `meet_sport_assignments`; `sport_user` frozen but not dropped.
7. WP-REALIGN-09 (`management_teams`, `management_team_members`) — independent of 1-6, can run any time; recommended before 8-9 since those depend on it for ownership.
8. WP-REALIGN-10, WP-REALIGN-11 (Supply, Food, Billeting, Transport) — depend on #7, independent of each other, can run in parallel.
9. WP-REALIGN-12 (Medical, DRRM) — depends on #7 **and** the Medical policy-validation checkpoint (gap assessment §17/§21) being resolved with the product owner first.
10. WP-REALIGN-13 (authorization expansion) — trails every role-bearing WP above (4, 9-12).
11. Only after a full meet cycle running cleanly on the new `meet_sport_assignments` table: separate migration to drop `sport_user` (§4's third condition).

## 10. Testing requirements

Per the gap assessment mandate §34 list, mapped to where each test belongs:
- Geographic/delegation invariants (municipality↔CongressionalDistrict, school↔SchoolDistrict↔municipality consistency, delegation-represents-municipality, school-is-not-a-delegation) — extend `tests/Feature/DivisionTest.php` and `tests/Feature/DelegationTest.php` (existing files).
- Sport/category/personnel-assignment invariants (sport has multiple categories, multiple Tournament Managers, multiple Technical Officials, multiple Secretaries, multiple ICT) — new `tests/Feature/MeetSportAssignmentTest.php`.
- DSAC/Results Committee workflow invariants — extend `tests/Feature/EligibilityTest.php`/`tests/Feature/ResultTest.php` (existing files) rather than new files, since the workflows themselves are existing and only gaining role/state refinements.
- Management-team/committee membership and committee-specific permissions — new `tests/Feature/ManagementTeamTest.php`.
- Medical-record privacy, DRRM incident authorization, equipment accountability — new test files per new domain, following the existing `AuthorizationMatrixTest.php` pattern of sweeping every role × action combination explicitly.
- Cross-municipality/cross-sport access prevention, audit-event generation — extend the existing `AuthorizationMatrixTest.php` and each domain's own audit assertions (the existing convention of asserting `AuditLog::query()->where('action', '...')->exists()` per mutating action, seen throughout `ResultController`/`EligibilityController`/`AccreditationController` tests).
- Migration backfill accuracy — the row-count/FK-parity checks in §8, wrapped as a Pest test run against a seeded pre-migration fixture, not just a manual `tinker` check.

## 11. What this plan deliberately does not decide

Three open product decisions block downstream WPs and are called out rather than guessed at, consistent with the mandate's own instruction not to assume:
- **OQ-1** (Person/User unification) — gap assessment §20, approved model §3.
- **OQ-2** (first-class Coach login vs. keep DelegationOfficer-as-proxy) — gap assessment §10, approved model §4.
- **Medical policy validation** — already an open item in the existing Track B backlog (`docs/11-backlog/phase-1-deferred-scope.md:17`), inherited here rather than silently resolved.

No migration in this plan depends on these being resolved except WP-REALIGN-12 (Medical/DRRM) and, partially, WP-REALIGN-04/-05/-06 (assignment/coach/DSAC role modeling) — everything else in this plan can proceed independent of these three decisions.
