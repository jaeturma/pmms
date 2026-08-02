# PMMS Organizational Realignment — Gap Assessment

**Status:** Assessment complete — no code changed. **Scope:** compare the currently-running PMMS implementation (Laravel 13 + Inertia/React, `D:\lara\www\pmms`, the code actually in `app/`, `database/`, `routes/`, `resources/js/`) against the "approved DdOPAA Provincial Meet organizational structure" supplied 2026-08-02. **Method:** direct inspection of models, migrations, enums, controllers, policies, routes, seeders, tests, and docs — every claim below cites a file (and line, where a specific line matters). No file was modified while producing this report.

**Companion documents** (Task 2, all in `docs/architecture/`): [pmms-approved-organizational-model.md](../../architecture/pmms-approved-organizational-model.md), [pmms-relationship-map.md](../../architecture/pmms-relationship-map.md), [pmms-role-and-scope-map.md](../../architecture/pmms-role-and-scope-map.md), [pmms-workflow-map.md](../../architecture/pmms-workflow-map.md), [pmms-data-migration-plan.md](../../architecture/pmms-data-migration-plan.md).

---

## 0. Executive summary — read this first

The running system is **much closer to the approved DdOPAA model than a first glance at the mandate suggests**. The geographic hierarchy, the "municipality is the delegation" rule, athlete/coach roster management, DSAC-style eligibility review, accreditation, results encode→validate, and medal tally are all already built and already follow the mandate's core rules in practice — just under different names and with fewer, coarser roles than the mandate's ~18-role target. The genuine gaps cluster almost entirely around **operational/logistics domains that were never built at all**: Management Teams/Committees, Tournament Manager/Secretary/ICT assignments, Supply, Food, Billeting, Transport, Medical case management, and DRRM.

One structural fact shapes every recommendation in this report and must be understood before reading further:

> **This repository contains two non-reconciled planning tracks.** Track A is the pragmatic system that was actually built and is running today (`app/`, phase reports up to `docs/reports/phase-12`, `docs/phases/phase-12-lightweight-sport-mini-portals`, and this conversation's own prior sessions — e.g. Phase 16 Technical-Official result encoding, the 2026-08-02 municipality/sports seeder work). Track B is a separate, far more elaborate **34-bounded-context enterprise/multi-tenant platform architecture** (`docs/00-product/` … `docs/11-backlog/`, `docs/01-architecture/bounded-context-catalog.md`, `.ai/*`), whose own "Phase 1" is explicitly a *generic foundation* that still lists athlete/delegation registration, eligibility, results, medal tally, and accreditation as **excluded/deferred to a future Phase 2** (`docs/11-backlog/phase-1-deferred-scope.md:10-15`) — even though Track A has already shipped and is running all of those. Track B's own product-vision docs never mention Davao de Oro, DdOPAA, or "municipality" as a resolved concept — `docs/00-product/open-decisions.md:40-45` (OD-04) leaves "what grouping unit — municipality, district, cluster" **explicitly open, "requires DepEd/organizer input; do not assume,"** a question Track A already answered in code weeks ago (`docs/delegations.md:7-21`).
>
> **Recommendation, adopted throughout this report:** treat Track A (the running code) as the system of record to realign, per the mandate's own instruction ("inspect the existing implementation ... prepare a safe migration plan"). Treat Track B's `docs/01-architecture/bounded-context-catalog.md` as a **reference vocabulary**, not a blueprint to implement literally — it independently anticipated almost every domain the mandate asks for (Committee Operations = BC-05, Technical Officials = BC-13, Medical = BC-21, Billeting = BC-22, Food = BC-23, Transportation = BC-24, ICT = BC-27), so its entity names and responsibility boundaries are a strong naming/scoping starting point for the genuinely-missing WP-REALIGN-09 through -12 work, adapted down to this deployment's actual single-meet Laravel-monolith pattern rather than the generic multi-tenant framing it was written in. Reconciling or retiring Track B outright is **out of scope for this assessment** and flagged as an open question (§21).

---

## 1–6. Current organizational hierarchy, delegation model, municipality model, Congressional District model, Schools District model, school relationships

**Classification: Aligned**, with one **Partially Aligned** sub-item (Congressional District).

The live hierarchy is `District` (→ municipality) → `SchoolDistrict` → `School`, exactly matching the mandate's `CongressionalDistrict → Municipality → SchoolsDistrict → School` shape one level down (see below for the top level):

- `app/Models/District.php:24-88` — `#[Fillable(['name', 'nickname', 'congressional_district'])]`, `hasMany(School)`, `hasMany(SchoolDistrict)`. This is the **Municipality** entity — confirmed by its own `SchoolDistrict::municipality()` relation naming (`app/Models/SchoolDistrict.php:48-51`, comment: "`district_id` means 'municipality' here, matching the convention already established on School/Delegation").
- `app/Models/SchoolDistrict.php:24-60` — distinct model name, distinct table (`school_districts`), `belongsTo(District)`, `hasMany(School)`. The mandate's explicit warning ("do not call both entities 'District' without clear model and database naming") is **already satisfied**: `District` = municipality, `SchoolDistrict` = the DepEd sub-unit, two different class names, no collision.
- `app/Models/School.php:31-105` — `belongsTo(District)` + `belongsTo(SchoolDistrict, nullable)`, `hasMany(Athlete)`, `hasMany(Personnel)`.
- All 11 real Davao de Oro municipalities, their confirmed nicknames, and 7 real East/West or North/South school-district splits are seeded as reference data (`database/seeders/DivisionRegistrySeeder.php`, committed 2026-08-02, commit `4a642f3`).

**Congressional District** — `districts.congressional_district` (nullable string, "First"/"Second"), added 2026-08-02 (`database/migrations/2026_08_02_071344_add_congressional_district_to_districts_table.php`). This satisfies the mandate's data requirement (every municipality has a stored congressional district) but **not** its relationship shape: the mandate specifies `CongressionalDistrict → has many Municipalities` (a real parent entity), while today it is a flat descriptive column on `District` — there is no `CongressionalDistrict` model/table, so "list all municipalities in the First District" is a `WHERE congressional_district = 'First'` filter, not a relationship traversal, and nothing prevents a typo'd value. Classified **Partially Aligned** — functionally sufficient for reporting today, structurally short of the mandate's real hierarchy. See §22/WP-REALIGN-01 for the additive fix (promote to a real `congressional_districts` table + FK, non-breaking).

**Delegation model — the mandate's central rule is already enforced in code, not just by convention:**

- `app/Models/Delegation.php:27-136` — `belongsTo(Meet)`, nullable `belongsTo(School)`, nullable `belongsTo(District)`, `belongsToMany(User, 'delegation_user')` as `officers()`.
- `docs/delegations.md:7-21` (§"Registering unit: School or Municipality") — a delegation registers under **exactly one** of `school_id`/`district_id`, gated by `Division::current()->type`. **Under Province (this deployment's type, Davao de Oro), `school_id` is `prohibited` outright in `DelegationStoreRequest` — not just optional** — "so a Province deployment can never accidentally create a school-rooted delegation." This is precisely the mandate's core rule ("Municipality = Official Competing Delegation... do not treat a school as the official Provincial Meet delegation") already enforced at the validation layer, for the deployment type actually in use.
- `App\Services\MedalTallyService::standings()` (`app/Services/MedalTallyService.php:41-116`) groups official medal credit by the placed athlete's **own school → that school's municipality** (`$school->district->name`, line 84), never by the delegation row directly — meaning even a school's individual contribution is traceable, but the **official standings table is the municipality rollup**, exactly matching "Athlete medal credit → Municipality Delegation, Athlete origin report → School."
- The one caveat: the schema is generic enough to *also* support a City-type deployment where delegations register by `school_id` (dormant capability, unused by this deployment, not a conflict — see `app/Enums/DivisionType.php`).

---

## 7. Current meet and sport relationships

**Classification: Partially Aligned.**

- `Meet` (`app/Models/Meet.php`) ↔ `Event` is `belongsToMany` via the `meet_events` pivot (`Event::meets()`, `app/Models/Event.php:76-79`). A meet's sports are only *derivable* — by which events (each `belongsTo` exactly one `Sport`) are attached to it — not directly modeled.
- **`Sport` itself has zero meet-scoping.** `app/Models/Sport.php:22-54` — only `hasMany(Event)` and `belongsToMany(User)` as `technicalOfficials()`. The `sports` table (`database/migrations/2026_07_18_000006_create_sports_table.php:11-16`) has no `meet_id` and no per-meet configuration columns. Confirmed via full-repo search: **no `MeetSport` model or `meet_sport(s)` table exists anywhere.**
- Consequence: "a sport included or excluded per meet," "meet-specific Technical Officials," "meet-specific rules" (mandate §8) are not representable today — a Technical Official's sport assignment (`sport_user` pivot, `database/migrations/2026_08_01_100000_create_sport_user_table.php`) is global across every meet, forever, the moment they're assigned. This is the single most consequential structural gap in the assessment — see §9/§20.

---

## 8. Current sport-category implementation

**Classification: Partially Aligned.**

- `Event` (`app/Models/Event.php:17-35`) carries `sport_id`, `name` (free text — e.g. "100 Meter Dash", "Artistic Gymnastics"), `gender` (`App\Enums\GenderCategory`: Boys/Girls/Mixed — a real enum, not free text), `age_division` (`App\Enums\AgeDivision`: Elementary/Secondary — also a real enum), `is_team_event`, `max_entries_per_delegation`.
- This means the mandate's "do not encode all category information into a single uncontrolled text field" instruction is **already honored for sex and school level** (both are structured enum columns, not text) — genuinely aligned, not a gap.
- The gap: `Event` **conflates** the mandate's two distinct concepts — `SportCategory` (Elementary Boys / Secondary Girls, a classification) and the specific competed discipline/event within it ("100 Meter Dash" vs. "Artistic Gymnastics" vs. plain "Basketball"). There is no separate `SportCategory` model sitting between `Sport` and the concrete event; `Sport → has many SportCategories` from the mandate doesn't exist as its own layer. For team sports (Basketball, Volleyball) `Event` already looks exactly like the mandate's `SportCategory` example (Sport=Basketball, Event name="Basketball", gender+division = the category) — it's specifically the individual/track-and-field sports where one `Event` row is really "category × discipline" combined.
- No `weight_class`, `discipline`, or `event_type` structured columns exist (boxing weight classes, e.g., currently live only inside a `ScoringSession.sport_state` JSON blob at runtime, never in the registry/catalog layer).

---

## 9. Current personnel-assignment implementation

**Classification: Missing (structurally), Partial (functionally, for the two roles that exist).**

Two personnel-assignment pivots exist, and both are bare two-column pivots with no role/date/scope metadata:

- `sport_user` (`sport_id`, `user_id`, unique pair, no `meet_id`, no role column) — a Technical Official's only assignment mechanism. Global across meets (§7).
- `delegation_user` (`delegation_id`, `user_id`, unique pair, no role/title column) — a Delegation Officer's only assignment mechanism.

There is **no generic "assign a person to a meet/sport/committee with a role and date range" model** anywhere — no `MeetPersonnelAssignment`, no `SportPersonnelAssignment`. Confirmed by full-repo search (zero matches for either term). This means Tournament Manager, Assistant Tournament Manager, Tournament Secretary, Tournament ICT, Track/Field Tournament Manager, and every other role-with-assignment-metadata the mandate describes (§9–§12) has **no structural home today** — not a naming mismatch, a genuine missing layer.

One important nuance: `.ai/current-phase.md` (a large, actively-maintained working log — too large to read in full, ~309KB) documents, around its discussion of the live-scoring phase, that an **earlier draft already proposed inventing a "Tournament Manager" role and it was deliberately rejected** in favor of reusing the existing `Admin`/`Organizer`/`TechnicalOfficial` roles, on the stated grounds that a new role needs deliberate justification against the project's separation-of-duties model (`docs/01-architecture/separation-of-duties-matrix.md`, ADR referenced in `.ai/current-phase.md`). This is relevant, not disqualifying: it means WP-REALIGN-04/07 (below) should be scoped as a **conscious, documented reversal of a prior explicit decision**, not treated as an oversight nobody considered.

---

## 10. Current coach workflow

**Classification: Missing — by explicit prior scope decision, not oversight.**

- `App\Enums\PersonnelRole` (`app/Enums/PersonnelRole.php`) has exactly `Coach`, `AssistantCoach`, `Chaperone` — a **roster label** on a `Personnel` row (`app/Models/Personnel.php`), nothing more. A coach has no login.
- `docs/personnel.md:32-35` states this explicitly under "Out of scope (per WP)": *"Technical officials and officiating assignment (Phase 3), DepEd HR integration, accreditation, **user accounts for coaches**."*
- The entire mandate §14 workflow ("Coach creates or receives an account → selects/assigned to Municipality Delegation → registers athletes → uploads required documents → submits for eligibility review → tracks returned/approved/rejected") is performed today **by a `DelegationOfficer`-role user** (a real login, scoped to their own delegation via `delegation_user`) on the coach's behalf — `EligibilityController::storeDocument()` (`app/Http/Controllers/EligibilityController.php:168-228`) is reachable by the delegation officer, not a coach account. Functionally the workflow exists end-to-end (upload → pending → approve/return → resubmit); structurally, the actor is wrong relative to the mandate ("Coach" as a first-class login role).

---

## 11. Current athlete-registration workflow

**Classification: Aligned.**

`Athlete` (`app/Models/Athlete.php:32-147`) — `belongsTo(Delegation)`, `belongsTo(School)` (the athlete's own home school, deliberately decoupled from the delegation's registering unit — line 60-64 docblock exists specifically to prevent the mandate's forbidden case "athlete assigned to a school outside the municipality delegation"), `hasOne(EligibilityReview)`, `hasMany(EligibilityDocument)`, `hasOne(Accreditation)`. `Entry` (`app/Models/Entry.php`) links `Athlete` × `Event` with `EntryStatus` (Submitted/Confirmed/Withdrawn). `ResultController::assertPlacementsValid()` (`app/Http/Controllers/ResultController.php:435-476`) already **prevents** several of the mandate's explicit "prevent" list: only `Confirmed` entries of the correct meet+event can be placed, duplicate ranks require an explicit tie flag. Registration-window and eligibility-gating (`AthletePolicy`, `EligibilityReviewPolicy`) are enforced in policies, not just the frontend.

---

## 12. Current DSAC eligibility workflow

**Classification: Partially Aligned — the workflow exists almost exactly as specified; the role does not.**

- `EligibilityReview` (`app/Models/EligibilityReview.php`) — `athlete_id`, `meet_id`, `status` (`App\Enums\EligibilityStatus`: **Pending → Approved | Returned**), `reviewer_id`, `remarks`, `decided_at`.
- `EligibilityController` (`app/Http/Controllers/EligibilityController.php`, full read) — `storeDocument()` creates/reopens a review on upload, auto-transitioning `Returned → Pending` on resubmission (lines 212-223); `approve()`/`returnReview()` are the two decisions, both requiring `Gate::authorize('decide', $review)`, both audited (`eligibility.approved`/`eligibility.returned`).
- This **is** the mandate's DSAC workflow (submit → review → verify documents → approve/return → eligible for accreditation), missing only a **Rejected** terminal state (today: Pending/Approved/Returned only — a returned review can always be resubmitted, there's no hard-reject) and a **distinct DSAC role**: `EligibilityReviewPolicy::decide()` passes for `Admin`/`Organizer` generically, not a scoped "Division Secretariat" role — any Organizer can decide any meet's eligibility, not just their assigned one.

---

## 13. Current result-confirmation workflow

**Classification: Partially Aligned.**

- `EventResult` (`app/Models/EventResult.php`) — `status` (`App\Enums\ResultStatus`: **Encoded → Validated** only — confirmed, no Draft/Submitted/For Confirmation/Returned/Reopened/Cancelled states).
- `ResultController` (`app/Http/Controllers/ResultController.php`, full read): `store()`/`update()` = encode, reachable by `Admin`/`Organizer` (any event) or a `TechnicalOfficial` scoped to their own assigned sport (`authorizeEncode()`, lines 394-409, reusing the same `user->sports()` pattern as live scoring). `validateResult()`/`correct()`/`destroy()` = the second decision, gated by `Gate::allows('manage-meet-data')` i.e. **Admin/Organizer only** — no distinct Results Committee role. `correct()` never silently edits: it requires a `reason`, reopens the result to `Encoded`, and snapshots the superseded placements into the audit record (lines 306-341) — this already satisfies the mandate's "must not silently edit... traceable... include a reason... preserve original values... generate audit events" requirement in full.
- Gap is purely the state machine's granularity (2 states vs. the mandate's richer 8-state list) and the missing distinct role — the *behavior* (two-step encode-then-validate, corrections are reopen-and-reaudit, never a raw UPDATE) is already exactly what the mandate asks for.

---

## 14–15. Current management-team and committee implementation

**Classification: Missing.**

Zero matches anywhere in `app/`, `database/migrations`, `database/seeders` for `ManagementTeam`, `Committee`, or `management_team`. The only membership-style constructs in the running app are the two narrow pivots in §9. **This is extensively planned but unbuilt** in the separate Track B corpus: `docs/01-architecture/bounded-context-catalog.md:22,99-107` (BC-05 Committee Operations — "Committee, CommitteeMembership" aggregate candidates, explicitly scoped to own the "administrative shell," deferring specialized committee data to BC-21/22/23/24/25/26/27) plus `docs/06-design/committee-logistics-medical-finance-and-support-experience.md`, `docs/08-workflows/committee-logistics-medical-finance-and-ict-workflows.md`. None of this reached the Track A implementation or its own Phase-1 backlog — confirmed: `docs/11-backlog/phase-1-scope-and-release-strategy.md` and `phase-1-epic-catalog.md` contain **zero** mentions of BC-05 or BC-21 through BC-27. Top Management/Meet Manager (mandate §16–17) is likewise absent as a distinct role — oversight today is the generic `manage-meet-data` gate (Admin/Organizer), and `ManagementDashboardController` (`app/Http/Controllers/ManagementDashboardController.php`, route `management.index`, gated `can:manage-meet-data`) already provides real cross-meet oversight *reporting* (participation trends, operations progress, performance history, venue utilization — `docs/management-dashboard.md`) without a dedicated "Top Management" role consuming it differently from "Organizer."

---

## 16. Current inventory implementation

**Classification: Missing — not even planned.** Zero matches for `Equipment`/`Inventory`/`Supply` anywhere in code **or** in the Track B docs corpus (the only hits are generic security-writing usage of "supply chain," unrelated to a PMMS domain). This is the one operational domain with no prior planning artifact at all.

---

## 17. Current food, billeting, transport, medical, and DRRM implementation

**Classification: Missing**, with materially different provenance per item:

| Domain | In running code | In Track B planning docs | Deferral status |
|---|---|---|---|
| Food | Nothing | BC-23 Food Services (`bounded-context-catalog.md:40,279-287`) | Informal — grouped under a "Pilot Enhancement" note on the `venues` reference table's own docblock (`docs/11-backlog/.../WP-07-05-venue-reference-skeleton.md:18`) |
| Billeting | Nothing | BC-22 Billeting (`:39,269-277`) | Same informal note as Food |
| Transport | Nothing | BC-24 Transportation (`:41,289-297`) | Same informal note as Food |
| Medical | One boolean flag, `Incident.medical_referral` (`app/Models/Incident.php:23` — "record only that a referral happened... never medical details," `IncidentController.php:15-16`) | BC-21 Medical Operations (Restricted) (`:38,259-267`) | **Formally, explicitly deferred**, `docs/11-backlog/phase-1-deferred-scope.md:17` — "Requires Policy Validation; strongest privacy boundary," target "Phase 2, contingent on policy source" |
| DRRM | Nothing | **Not mentioned anywhere** — not in the 34-context catalog, not in any deferred-scope register | Genuine unplanned gap — the closest adjacent construct is `Incident` (`app/Models/Incident.php`), a generic meet-day log with `IncidentSeverity` (Minor/Moderate/Serious) and `IncidentStatus` (Open/Resolved only), no DRRM-specific classification, responder assignment, or escalation chain |

---

## 18. Current roles and permissions

**Classification: Partially Aligned.** Solid, well-documented foundation (`docs/authorization.md`, 5 roles, 2 `Gate::define` calls — `app/Providers/AppServiceProvider.php:44,46` — `administer`=Admin-only, `manage-meet-data`=Admin+Organizer, plus 7 model policies: Athlete/Delegation/EligibilityReview/Entry/FileUpload/Personnel/Protest), consistently enforced server-side (per-row `can_*` flags computed server-side, not frontend-hidden buttons — matches the mandate's "Do not rely only on frontend-hidden controls" instruction already). Districts/Schools/Sports/Events/Venues/Meets/Schedule/Matches/Announcements/Incidents/Accreditation/Division/System-Settings/Audit-Log have **no dedicated policy class** — gated only by coarse `role:admin,organizer` route middleware or the `manage-meet-data`/`administer` gates, not per-record scoping (acceptable today because those modules have no owner-scoping concept to enforce; would need policies once e.g. committee-scoped roles exist). The mandate's ~18-role target (§29) is a real expansion, not a refactor of something broken — see §24/WP-REALIGN-13.

---

## 19. Conflicting or duplicate tables

**None found that are actively harmful.** The one **naming risk to actively avoid** going forward: `District` already means Municipality throughout the codebase, audit log, and public UI. Any new "Congressional District" entity (§22/WP-REALIGN-01) **must not** be named `District` or reuse that model — follow the mandate's own naming instruction and the precedent `SchoolDistrict` already set (a distinct class name, never overloading "District").

---

## 20. Missing relationships

1. `Sport ↔ Meet` (no `MeetSport`) — §7.
2. `Municipality → CongressionalDistrict` as a real parent relation, not a string column — §1/22.
3. `Person ↔ User` — no unified "one person, several roles" model; a Technical Official (`User`, has login) and a Coach (`Personnel`, no login) are structurally unrelated even when the same human holds both, so today there is no way to represent "this person is both a Delegation Officer and, next meet, also a Technical Official" without two disconnected records.
4. Meet-scoped, dated, role-metadata-carrying personnel assignment (Tournament Manager/Secretary/ICT/Technical Official-per-meet) — §9.
5. `Committee`/`ManagementTeam` membership — §14-15.
6. Every relationship implied by Food/Billeting/Transport/Medical/DRRM (§17) — none exist to be missing "from," these are whole new subtrees.

---

## 21. Data migration risks

- **Renaming `District`** to "Municipality" (even just the class/table name) would touch every controller, policy, audit-log context key, seeder, factory, and ~40+ Inertia pages that reference it — high blast radius for a purely cosmetic change. **Recommendation: do not rename.** `Division::areaLabel()` already solves the *display* problem (renders "Municipality" for Province-type deployments, `app/Models/Division.php:70-73`) without touching the model name — extend that pattern rather than renaming underlying tables.
- **Promoting `congressional_district` from a string column to a real table** is additive and low-risk *if* sequenced correctly: add the new `congressional_districts` table and a nullable `congressional_district_id` FK on `districts` alongside (not replacing) the existing string column, backfill, verify, only then consider dropping the string column in a later, separate migration — never in the same deploy.
- **Introducing `MeetSport`** is the highest-leverage, lowest-risk new table (additive, no existing column touched) but has real backfill work: every currently-assigned `sport_user` row needs a deterministic "which meet(s)" answer before Technical Official assignment can become meet-scoped, and there is no historical meet_id on that pivot to backfill from — this needs an explicit product decision (§ open questions), not a guessed default.
- **Two-tier hierarchy for Sport → SportCategory → Event**, if implemented, risks breaking every existing `Event`-keyed reference (`Entry.event_id`, `EventResult.event_id`, `ScoringSession` chains, `meet_events` pivot, ~15+ report/portal pages) if done as a replace-in-place. Must be additive: introduce `SportCategory` as a new table that `Event` optionally references, migrate data forward, only tighten `Event`'s own gender/age_division columns to be derived-from-category later once every consumer has moved off the old columns.
- **New committee/logistics domains (§14-17)** carry the lowest schema risk (nothing existing to conflict with) but the highest **privacy/scope risk** — Medical and DRRM data must be access-controlled from the first migration, not retrofitted (the mandate itself flags this: "Medical-related data is sensitive... Do not expose diagnosis... to unauthorized roles").
- **Seeder/reference-data drift**: `DivisionRegistrySeeder`/`SportsCatalogSeeder` are the authoritative real-data seeders (committed 2026-08-02); any new congressional-district or sport-category table must extend these, not introduce a parallel seeding path.

---

## 22. Recommended target model

Full detail in [pmms-approved-organizational-model.md](../../architecture/pmms-approved-organizational-model.md). Summary: keep every aligned entity as-is (District/SchoolDistrict/School/Delegation/Athlete/Personnel/Entry/EligibilityReview/EventResult/Accreditation/MedalTallyService), add `CongressionalDistrict` (real table, additive FK), add `MeetSport` (real table, the pivot that makes every downstream personnel-assignment meet-scoped), add `SportCategory` as an optional intermediate layer between `Sport` and `Event` (only where a sport actually needs it — Athletics; team sports can leave `Event` as-is), add a generic `PersonAssignment`-style model for Tournament Manager/Secretary/ICT/meet-scoped Technical Official, and add five new bounded subtrees (Committee/ManagementTeam, Supply/Equipment, Food, Billeting, Transport, Medical, DRRM) reusing Track B's `bounded-context-catalog.md` entity names as a starting vocabulary, scaled to this deployment's actual Laravel-monolith pattern (plain Eloquent models + policies + Inertia pages, not the generic multi-tenant service boundaries Track B describes).

## 23. Recommended migrations

Full detail in [pmms-data-migration-plan.md](../../architecture/pmms-data-migration-plan.md). All additive-first (§21); no rename, no drop, no `migrate:fresh`, in this phase.

## 24. Recommended work packages

| WP | Title | Depends on | Risk |
|---|---|---|---|
| WP-REALIGN-01 | Geographic and School Hierarchy — promote `congressional_district` to a real `congressional_districts` table + FK, additive | none | Low |
| WP-REALIGN-02 | Meet and Municipality Delegation Model — introduce `MeetSport`; backfill decision required (§21) | WP-REALIGN-01 not required, independent | Medium (backfill) |
| WP-REALIGN-03 | Sports and Category Model — introduce `SportCategory` as an optional layer, additive, `Event` unchanged initially | WP-REALIGN-02 (MeetSport should exist first so a category can be meet-scoped where needed) | Medium |
| WP-REALIGN-04 | Personnel and Assignment Architecture — generic meet-scoped assignment model (Tournament Manager/Secretary/ICT/TO); explicitly revisit the documented prior rejection of a "Tournament Manager" role (§9) with the product owner before building | WP-REALIGN-02 | Medium (role-model decision, not just schema) |
| WP-REALIGN-05 | Coach and Athlete Registration Workflow — decide whether to build first-class Coach login accounts (currently explicitly out of scope, §10) or keep the DelegationOfficer-proxy model and only relabel it | none (product decision first) | Low schema / Medium product-decision |
| WP-REALIGN-06 | DSAC Eligibility Workflow — add a scoped "reviewer" concept (today: any Admin/Organizer) and a terminal Rejected state if wanted; core workflow already built (§12) | WP-REALIGN-04 if a distinct DSAC role is wanted | Low |
| WP-REALIGN-07 | Tournament Personnel Assignments — Technical Official/Secretary/ICT per `MeetSport`, not per `Sport` globally | WP-REALIGN-02, WP-REALIGN-04 | Medium |
| WP-REALIGN-08 | Results Committee Confirmation Workflow — richer state machine only if the product genuinely needs it; the encode→validate→correct behavior already meets the mandate's audit/traceability bar (§13) | WP-REALIGN-04 if a distinct role is wanted | Low |
| WP-REALIGN-09 | Management Teams and Committees — net-new, reuse Track B's BC-05 vocabulary | none | Medium (net-new domain) |
| WP-REALIGN-10 | Supply and Equipment Management — net-new, no prior art anywhere | none | Medium |
| WP-REALIGN-11 | Food, Billeting, and Transport — net-new, reuse Track B's BC-22/23/24 vocabulary | WP-REALIGN-09 (committee ownership) | Medium |
| WP-REALIGN-12 | Medical and DRRM — net-new; Medical has a formal policy-validation blocker already on record (`docs/11-backlog/phase-1-deferred-scope.md:17`) that should be resolved with the product owner before schema work; DRRM has zero prior art and needs scope definition from scratch | WP-REALIGN-09 | **High** (privacy/policy-gated) |
| WP-REALIGN-13 | Authorization and Data Scoping — expand 5 roles toward the mandate's ~18, add policies for currently policy-less modules once they gain owner-scoping | WP-REALIGN-04, -09 through -12 | Medium |
| WP-REALIGN-14 | Reports and Dashboard Alignment — extend `ReportController`/`ManagementDashboardController` patterns to new domains | trails each domain WP | Low |
| WP-REALIGN-15 | Seeder and Reference Data Alignment — extend `DivisionRegistrySeeder`/`SportsCatalogSeeder`, never a parallel path | WP-REALIGN-01, -03 | Low |
| WP-REALIGN-16 | Integration, Migration Testing, and Acceptance | all above | — |

## 25. Files expected to change (by future WP, for planning only — none touched in this assessment)

- **WP-REALIGN-01**: new migration, `app/Models/District.php`, `app/Models/CongressionalDistrict.php` (new), `DivisionRegistrySeeder.php`, `DistrictController.php` (read-side only), `docs/division.md`.
- **WP-REALIGN-02/07**: new migration(s), `app/Models/Sport.php`, `app/Models/MeetSport.php` (new), `ScoringSessionController.php`/`ResultController.php` (their `canManage()`/`authorizeEncode()` scoping moves from `Sport` to `MeetSport`), `sport_user` pivot retired in favor of a `MeetSport`-scoped assignment table, `docs/authorization.md`.
- **WP-REALIGN-03**: new migration, `app/Models/SportCategory.php` (new), `app/Models/Event.php`, `SportsCatalogSeeder.php`.
- **WP-REALIGN-09 through -12**: entirely new models/controllers/migrations/Inertia pages under new namespaces; no existing file requires modification to add them (purely additive), aside from navigation/sidebar wiring (`resources/js/components/app-sidebar.tsx`) and the dashboard.
- **WP-REALIGN-13**: `app/Enums/UserRole.php`, new policy classes, `routes/web.php` role-group middleware, `docs/authorization.md`.

---

## Companion documents produced

1. [`docs/architecture/pmms-approved-organizational-model.md`](../../architecture/pmms-approved-organizational-model.md)
2. [`docs/architecture/pmms-relationship-map.md`](../../architecture/pmms-relationship-map.md)
3. [`docs/architecture/pmms-role-and-scope-map.md`](../../architecture/pmms-role-and-scope-map.md)
4. [`docs/architecture/pmms-workflow-map.md`](../../architecture/pmms-workflow-map.md)
5. [`docs/architecture/pmms-data-migration-plan.md`](../../architecture/pmms-data-migration-plan.md)
