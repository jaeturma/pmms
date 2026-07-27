# Current Phase
**Phase 8 — UI/UX Implementation and Visual Alignment is now COMPLETE
(all 16 WPs, 2026-07-27)** — see its own section below; compliance
review: `docs/phases/phase-08-ui-ux-visual-alignment/
phase-8-compliance-review.md`. One real open item carried into any
sign-off decision: zero live browser verification was possible across
the entire phase (Chrome extension disconnected every session) — a
manual visual/responsive/accessibility QA pass is recommended before
treating the phase's visual work as fully signed off. Not committed/
pushed. Phase 9 — Post-Deployment Support is scaffolded and ready on
owner instruction.

Division Type & Municipality-Based Delegations — COMPLETE 2026-07-25, all 7 WPs
executed one at a time on owner instruction. Phase 4 — Responsive Public
Portal and Phase 5 — Executive and Management Dashboards are ALSO complete
(see their own sections below). Committed and pushed through Phase 5
(main @ 01e0721 as of 2026-07-25; see git log). **Phase 7 — Live Scoring
Enhancement is now COMPLETE (all 3 WPs, 2026-07-26)** — see its own section
below — note the renumbering: the roadmap's original Phase 7
(Post-Deployment Support) is renamed Phase 8 per
`docs/howtorun/ROADMAP-UPDATE.md`; this new Phase 7 is a different feature
(live/real-time match scoring) inserted ahead of it, owner-directed
2026-07-25.

**Phase 6 — Reports, UAT, Deployment, and Turnover is now COMPLETE (all 9
WPs, 2026-07-26/27)** — see its own section below. The old
`docs/phases/phase-06-reports-uat-deployment-turnover/` scaffolding
(unreviewed generic template, same "municipality as the official delegation"
assumption WP-06-01 carried, colliding with the Division initiative's real
model) was replaced with a real plan written fresh for this codebase, the
same way Phase 4/5/7's were. COMPLIANT compliance review:
docs/phases/phase-06-reports-uat-deployment-turnover/phase-6-compliance-review.md;
full gate green: Pint+PHPStan+Pest 650/650 (3,245 assertions)+ESLint+Prettier+
tsc+build; composer audit + npm audit both clean; zero new migrations (pure
verification/documentation/ops-tooling phase); app HTTP 200 at http://pmms.app.
Real deployment requirement (not part of the numbered Phase 2-4 plan above):
the division can be City (schools/districts compete) or Province
(municipalities compete, pooling multiple schools' athletes under one
delegation) — this deployment defaults to Province, Davao de Oro, 11
municipalities. District/municipality standings are the official verdict for
a meet; school-level standings remain visible as a secondary reference below
them (owner clarification, 2026-07-25 — see "Post-Division refinement" log
below). Plan:
C:\Users\DEPED\.claude\plans\wondrous-spinning-cosmos.md — 7 WPs (Division
Setup Foundation → Municipal Delegation Registration → Athlete/Personnel
Home-School Attribution → Entry/Match/Result/Report Re-keying → Medal Tally
Rewrite → Sample Seeder → Compliance Review). Execute one WP at a time on
owner instruction; nothing committed or pushed.

## Post-Division refinement — 2026-07-25
Owner clarification after the Division initiative closed: district/
municipality standings are the meet's official verdict, not school
standings — school rows exist only to show which school each medal came
from, and must not read as a competing "standing." Re-ordered every medal
tally surface district-first: `MedalTallyService::standings()` return array
(`districts` before `schools`, docblock updated — no behavior change, both
keys still present); internal `tally/index.tsx`, public `public/tally.tsx`,
and the printable `reports/medal-tally.tsx` all now render the
{areaLabel} standings section first (default `Heading` weight, "Overall
standings for this meet") with the school table below it demoted to `small`
variant + "Reference only — shows which school each medal came from.";
`downloadTallyReport()`'s CSV rows follow the same district-then-school
order. `DashboardController::operations()`'s "Medal tally — top five"
widget switched from top-5 schools to top-5 districts/municipalities to
match — it's a headline verdict widget, not a reference table
(`dashboard.tsx` TallyRow field renamed `school`→`district`, header now
{areaLabel}-aware via the shared `division` prop). No schema/data changes —
`MedalTallyService` still computes both groupings identically; this is
presentation-order and labeling only. docs/medal-tally.md, docs/dashboard.md,
docs/reports.md updated; docs/public-portal.md's stale pre-WP5 "public tally
still excludes municipal placements" note (already false since WP5) also
corrected while in the area. Pest 568/568 (no test changes needed — existing
assertions are prop-key-based, not order-dependent), full gate green:
Pint+PHPStan+ESLint+Prettier+tsc+build; not committed/pushed.

## Division Work Package Log
- WP7 Compliance & Authorization Review — done 2026-07-25 (COMPLIANT, no
  enforcement gaps found — three documentation gaps and two test-coverage
  gaps closed. Reviewed against the plan's own checklist: (1) type-change
  guard — already covered by WP1's `DivisionTest` (`typeIsLocked()` false→
  true on first delegation; a submitted type change is silently ignored once
  locked), confirmed still passing, no new test needed; (2) the "officer
  sees whole municipal roster" consequence flagged by the design review back
  in the planning stage — confirmed real (`AthletePolicy`/`PersonnelPolicy`
  scope via `$record->delegation->hasOfficer($user)`, i.e. by delegation,
  never by the individual's own `school_id`) and, per plan, now explicitly
  documented as accepted/intended rather than left implicit: new "Officer
  roster scope" section in docs/delegations.md (assigning an officer is
  already a deliberate manager trust decision; the delegation, not the
  school, is this app's authorization boundary everywhere else too — narrower
  scoping isn't built and isn't required by this deployment), cross-referenced
  from docs/athletes.md and docs/personnel.md, and a new footnoted matrix row
  in docs/authorization.md ("own delegation's whole roster¹"); proven with a
  new definitive test in AthleteTest (`an officer assigned to a municipal
  delegation sees the whole pooled roster` — one officer, one municipal
  delegation, two different schools, both athletes visible, a foreign
  delegation's athlete not); (3) City's "district competes" deferral — was
  already noted inline in docs/division.md but that section had gone stale
  (still described WP2-WP6 as future work after they'd shipped); rewrote as
  two sections, "Division initiative — complete (WP1–WP7)" and a dedicated
  "Open item: City's 'district competes' option" recording the deferral
  explicitly, per plan, rather than leaving it buried in prose. Swept
  AuthorizationMatrixTest coverage against docs/authorization.md's matrix:
  found `/division` (admin-only, `can:administer`, same sensitivity as the
  audit log viewer) had a real test gap — `DivisionTest`'s admin-only test
  checked only organizer-forbidden, not delegation-officer/viewer-forbidden,
  unlike the equivalent AuditLogViewerTest pattern it should have matched —
  fixed by parametrizing over all three non-admin roles for both GET and
  PATCH; added the missing "Division settings — view, update" matrix row
  (admin-only, mirrors the audit log viewer row) to docs/authorization.md.
  Also fixed two stale cross-references found during the sweep: docs/
  athletes.md still pointed at docs/delegations.md's "Known interim gap"
  section, renamed to "Individual attribution is fully re-keyed" back in
  WP5. No schema/behavior changes — pure review, docs, and test-coverage
  closure; Pest 568/568 (3 new tests: 2 from parametrizing the division
  admin-only test over 3 roles, 1 new officer-roster-scope proof), full gate
  green: Pint+PHPStan+ESLint+Prettier+tsc+build; not committed/pushed).
  This closes the Division initiative (WP1–WP7): a Province deployment's
  municipal delegation pools multiple schools' athletes/personnel under one
  registration while every module — entries, matches, results, reports, ID
  cards, medal tally, public portal — correctly attributes each individual
  to their own real school; City's own registration option remains a
  documented, deliberate open item, not a silent gap.
- WP6 Sample Seeder: Province Demonstration Data — done 2026-07-25
  (`SampleProvinceDemoSeeder`, new — env-gated local/testing only, same
  `"Sample "`-prefixed/idempotent convention as `SampleRegistrySeeder`, but
  its own sibling seeder rather than an extension, since it needs the
  richer object graph (meet→delegation→athletes→entries→result) that
  registry-only seeding doesn't touch; registered in `DatabaseSeeder` after
  `SportsCatalogSeeder`, since it depends on the real "100 Meter Dash"
  Boys/Secondary event existing. Demonstrates the initiative's actual
  payoff end-to-end: two `"Sample Municipality — X"` District rows (own
  rows, never attached to the real 11 Davao de Oro municipalities) each
  with their own sample schools; "Sample Municipality — Alpha"'s single
  municipal delegation pools athletes from *two different* sample schools
  (Alpha Central, Alpha North) — one placing gold, the other silver — while
  "Sample Municipality — Bravo"'s delegation (one school) places bronze.
  Verified by hand against the real `MedalTallyService`: the two Alpha
  athletes produce two separate, correctly-attributed school rows (1 gold /
  1 silver) that still roll up into one Alpha municipality row (1 gold, 1
  silver) — the concrete proof, in seed data instead of a test, that a
  province's municipal delegation and its member schools are both visible
  at once. Two guarded-field pitfalls worked around in the seeder itself
  (not app bugs): `Meet`'s `#[Fillable]` doesn't include `status`/
  `is_published`, so those are set via `forceFill()` after `firstOrCreate()`
  mirrors `MeetController`'s own transition pattern; `EventResult.encoded_at`
  is a NOT-NULL column but not fillable, so the result row is constructed
  bare and force-filled, mirroring `ResultController::store()`'s real
  pattern — both `Entry`/`Delegation.status` transitions use the same
  firstOrCreate-then-forceFill shape once already established in this
  codebase for guarded state fields. Verified idempotent (re-ran the
  seeder; district/school/delegation/athlete/placement counts unchanged)
  and manually inspected via tinker (registrantName, per-athlete school,
  and the live tally output all correct). No schema changes, no docs
  changes (the seeder documents itself via the WP2–WP5 docs it
  demonstrates); Pest 565/565 (no new tests — this WP is seed data, not
  behavior), full gate green: Pint+PHPStan+ESLint+Prettier+tsc+build; ran
  against MySQL pmmsdb; not committed/pushed) — next: WP7 Compliance &
  Authorization Review.
- WP5 Medal Tally Rewrite — done 2026-07-25 (`MedalTallyService::standings()`
  re-keyed: dropped the WP2 exclusion filter and regrouped school-level
  standings by `placement.entry.athlete.school_id` instead of
  `entry.delegation.school_id` — the highest-risk single change in the whole
  Division initiative, isolated in its own WP with no other feature work, per
  plan. District/municipality rollup logic mechanically unchanged (it just
  sums the already-computed school rows by district name), which is exactly
  why it correctly totals a municipal delegation's per-school medals back
  into one municipality row without needing its own rewrite. Added the two
  "definitive proof" tests the plan called for (MedalTallyTest +
  PublicTallyTest): one municipal delegation, two athletes at two different
  schools, asserting two separate correctly-counted school rows that roll up
  into a single municipality row — not one merged row, not mis-attributed
  medals. Swapped every remaining hardcoded "District" table header/section
  title to `division.areaLabel` across all four tally surfaces (internal
  `tally/index.tsx`, public `public/tally.tsx`, the printable
  `reports/medal-tally.tsx`, and its CSV export's `Type`/header column) plus
  the school-participation report and its CSV, which used the same hardcoded
  label; `Division::current()->areaLabel()` is the one new backend touch
  point (`ReportController`'s two CSV exports). No schema changes — nothing
  to migrate. docs/medal-tally.md (school-level grouping explained,
  area-label-aware UI noted) and docs/delegations.md ("Individual attribution
  is fully re-keyed" updated to WP5, since MedalTallyService was the last
  holdout) updated. This closes out the Division initiative's core data-model
  work — every module now attributes an individual to their real school, and
  every internal/public/printable surface presents the right area label for
  the deployment's division type; Pest 565/565 (2 new tests), full gate
  green: Pint+PHPStan+ESLint+Prettier+tsc+build; not committed/pushed) —
  next: WP6 Sample Seeder: Province Demonstration Data.
- WP4 Entry/Match/Result/Report Re-keying — done 2026-07-25 (resolved every
  remaining `TODO(WP4)` marker from WP2/WP3: EntryController (list +
  athleteOptions label), MatchController (participants list, entryOptions
  label, and critically `syncParticipants()`'s same-school team-event rule —
  now keyed on `athlete.school_id` instead of `delegation.school_id`, so it
  correctly allows several different schools' entries from one municipal
  delegation on a team roster while still blocking two entries from the
  *same* school), ResultController (index placements, entryOptions label,
  `placementSnapshot()`), ReportController (`resultSheetData()`,
  `eventEntryRows()`), PortalController (public results placements),
  EligibilityController (review queue + athleteOptions label — this one
  was actually mis-marked `TODO(WP3)` in WP2 but always belonged here, since
  it reads an athlete's school via a different record); AccreditationController
  ::cardData()/context() get a new `subjectSchoolName()` helper resolving the
  accredited athlete's-or-personnel's own school (the per-card school gap the
  WP2 design review flagged) — the delegation-header `registrantName()` call
  sites (roster header, ID-card batch header, protest label, audit
  `registrant` context) are correctly left untouched, since they describe the
  delegation itself, not an individual. Added the explicit cross-school proof
  tests the plan called for: two MatchTest cases (different-schools-same-
  municipal-delegation allowed; same-school-even-under-municipal-delegation
  still blocked) plus one each in EntryTest/ResultTest/AccreditationTest
  confirming the displayed school is the individual's own, not the
  municipality's. No schema changes — pure re-keying, so no new migration.
  `MedalTallyService` is now the *only* module still working around the
  municipal-delegation gap (excludes those placements from standings,
  unchanged, correctly deferred to WP5). docs/delegations.md ("Known interim
  gap" replaced with "Individual attribution is fully re-keyed"),
  docs/accreditation.md, docs/entries.md, docs/matches.md, docs/results.md,
  docs/reports.md, docs/public-portal.md, docs/eligibility.md, and
  docs/medal-tally.md (cross-reference fix only, still correctly pending
  WP5) updated; Pest 563/563 (5 new tests), full gate green:
  Pint+PHPStan+ESLint+Prettier+tsc+build; not committed/pushed) — next: WP5
  Medal Tally Rewrite.
- WP3 Athlete & Personnel Home-School Attribution — done 2026-07-25
  (`athletes.school_id`/`personnel.school_id` added — NOT NULL, restrict on
  delete, backfilled from `delegation.school_id` where derivable (a no-op on
  this deployment: 0 athletes/personnel/delegations existed in pmmsdb before
  this WP); `Athlete::school()`/`Personnel::school()` relations; new required
  "Home school" field on both registration forms, server-validated
  (`AthleteRequest`/`PersonnelRequest::withValidator()`) to the delegation's
  own school (City) or any active school within the delegation's municipality
  (Province) — never a school outside where the delegation registered,
  immutable after creation like `delegation_id`; registration dialogs narrow
  the school picker to `schoolOptionsByDelegation[delegation_id]`
  (auto-selected when there's exactly one option, i.e. always for City),
  extracted into a shared `BuildsSchoolOptionsByDelegation` trait rather than
  duplicated across both controllers; `School::athletes()`/`personnel()`
  simplified from `hasManyThrough(Delegation)` to direct `hasMany` (a school's
  members are no longer reachable only via a delegation that might not exist
  for it under Province), `School::entries()` re-pointed to
  `hasManyThrough(Athlete)`; resolved the two `TODO(WP3)` markers left in WP2
  (Athlete/Personnel's own list+profile pages now read their real
  `->school->name`, not the delegation's registrant) — the other TODOs
  (Entry/Match/Result/Report/Accreditation/Portal reading an individual's
  school via a different record) remain, correctly, WP4 scope; fixed a real
  bug surfaced by the School relation change: `ReportController
  ::participationRows()`'s meet filter referenced a `delegations.meet_id`
  column that no longer exists in the athletes/personnel/entries subqueries
  once they stopped joining through Delegation (500 error) — refiled through
  each record's own `delegation()` relation instead; also fixed the report's
  `delegations_count > 0` filter, which would have silently hidden every
  school under a Province deployment (no school ever gets its own delegation
  row there) — now shows a school if it has its own delegation OR any
  directly-attributed athletes/personnel. Caught a factory correctness bug
  before it shipped: the naive `'school_id' => School::factory()` default
  created a real orphaned School row per athlete/personnel even though an
  `afterMaking` hook immediately overwrote the FK — moved school_id
  resolution entirely into `afterMaking` (checking `getAttributes()` for an
  explicit override, not a nullability check, to keep PHPStan honest about
  the NOT NULL column) so no orphan rows are created; hit the same
  Larastan nullsafe-in-`??`-chain false positive from WP2 in two more
  places, same explicit-`!== null` workaround. Shared test helper
  `schoolForDelegation()` moved to `tests/Pest.php` (used by both
  AthleteTest and PersonnelTest, avoiding a function-name collision from
  duplicating it per-file). docs/athletes.md ("Home school" section),
  docs/personnel.md, docs/delegations.md ("Known interim gap" narrowed to
  WP4/WP5 scope only), docs/reports.md (participation-summary fix
  documented) updated; Pest 558/558, full gate green:
  Pint+PHPStan+ESLint+Prettier+tsc+build; migration applied on MySQL
  pmmsdb; not committed/pushed) — next: WP4 Entry/Match/Result/Report
  Re-keying.
- WP2 Delegation Registration: Municipal Delegations — done 2026-07-25
  (`delegations.school_id` now nullable + new nullable `district_id` (restrict
  on delete) + separate `unique(meet_id, district_id)` alongside the existing
  `unique(meet_id, school_id)`; `DelegationStoreRequest` is division-type-aware
  (not a bare "exactly one of" — the field matching `Division::current()->type`
  is required, the other `prohibited`, so a Province deployment can never
  accidentally create a school-rooted delegation); `Delegation::registrantName()`
  /`registrantType()` accessors are the one correct source for a delegation's
  own identity, used across ~6 controllers (Delegation, Accreditation, Protest,
  Report roster) for the delegation-level label; registration UI switches
  between a School picker (City) and a Municipality picker (Province, this
  deployment's default), sourced from `schoolOptions`/`districtOptions`.
  Swept the wider blast radius the migration itself opened up (making
  `school_id` nullable meant every remaining `->delegation->school->name` call
  site — 8 more controllers: Athlete, Personnel, Eligibility, Entry, Match,
  Result, Portal (public), plus MedalTallyService — would crash the moment a
  real municipal delegation existed, not just the ~6 originally scoped label
  sites): all now use `registrantName()` as a documented, TODO-marked interim
  value for individual-attribution fields (true fix is WP3's athlete/personnel
  `school_id` + WP4's re-keying); `MatchController::syncParticipants()`'s
  same-school team-event rule now only checks school-rooted delegations
  (permissive, not silently wrong, for municipal ones until WP4);
  `MedalTallyService` excludes municipal-delegation placements from
  school/district standings entirely rather than mis-attribute them, isolating
  the real fix for WP5 as planned; `AccreditationController::cardData()`'s
  per-card school field gap (flagged in the design review) got the same interim
  treatment. Found and fixed an unrelated PHPStan false-positive (nullsafe `?->`
  on a magic Eloquent relation property inside a `??` chain is misreported as
  "never null" even when genuinely nullable — worked around with an explicit
  `!== null` check, confirmed via isolated reproduction against Accreditation's
  identical existing pattern). docs/delegations.md rewritten (registering-unit
  section + "Known interim gap"); short interim-gap notes added to
  athletes.md, personnel.md, entries.md, accreditation.md, results.md,
  reports.md, matches.md, public-portal.md, division.md; Pest 558/558 (7 new/
  updated tests in DelegationTest, 1 in ReportTest), full gate green:
  Pint+PHPStan+ESLint+Prettier+tsc+build; migrations applied on MySQL pmmsdb;
  not committed/pushed) — next: WP3 Athlete & Personnel Home-School Attribution.
- WP1 Division Setup Foundation — done 2026-07-25 (`divisions` table +
  `Division` model, singleton via `Division::current()` (Province default on
  first access); `App\Enums\DivisionType` (City|Province) with
  `areaLabel()` — "District" for City, "Municipality" for Province, same
  underlying `District` model/table always, never renamed in code; type-
  change guard `Division::typeIsLocked()` refuses once any delegation
  exists, enforced in `DivisionRequest` (type rule omitted entirely when
  locked, so a submitted value is silently ignored, not errored);
  `districts.nickname` column (delegation nicknames e.g. Maco → "Tigers");
  `division` shared on every Inertia page (`{type, name, areaLabel}`) via
  `HandleInertiaRequests`, consumed by the districts/municipalities registry
  page and sidebar nav label so both read "Municipality" under the Province
  default with no hardcoded "District" left in that flow; admin-only
  `/division` settings page (`can:administer`, mirrors the audit-log
  gating); `division.updated` audit action; `DivisionRegistrySeeder` —
  real, unconditional (all environments) default config: Province division
  "Davao de Oro" + its 11 municipalities (Compostela, Laak, Mabini, Maco
  [nickname Tigers], Maragusan, Mawab, Monkayo, Montevista, Nabunturan, New
  Bataan, Pantukan) as District rows, idempotent via firstOrCreate — distinct
  from the local/testing-only SampleRegistrySeeder; docs/division.md;
  Pest 554/554 (9 new tests in DivisionTest), full gate green:
  Pint+PHPStan+ESLint+Prettier+tsc+build; not committed/pushed) — purely
  additive, no registration-flow changes yet; municipal delegation
  registration itself is WP2.

# Phase 7 — Live Scoring Enhancement
Original 3-WP scope (generic scoreboard only, per owner instruction
2026-07-25) COMPLETE 2026-07-26, all 3 WPs executed one at a time on owner
instruction. **Reopened same day**: owner instructed sport-specific
scoreboards after all, appended as WP-07-04 (Basketball, done) → WP-07-05
(Boxing, done) → WP-07-06 (Softball/Baseball, done) rather than
renumbering the shipped, committed/pushed WP-07-02 ("Generic Live
Scoreboard UI") — see the Work Package Log below for WP-07-04/05/06/07/08.
All three sport-specific scoreboards are built, plus WP-07-07 (manual
board-type override, done 2026-07-26 — force the generic board for an
exhibition/non-standard match even when the sport has a dedicated one) and
WP-07-08 (public live scoreboard, done 2026-07-26 — a second scope
reversal the same day, extending the public portal to live/provisional
scores; original Phase 7 scope had explicitly deferred public exposure).
Original review: docs/phases/phase-07-live-scoring-enhancement/
phase-7-compliance-review.md (COMPLIANT; two Low accessibility gaps found
and fixed during the review, no schema/authorization/architectural issues;
full gate green: Pint+PHPStan+Pest 608/608 (3,020 assertions), ESLint+
Prettier+tsc+build; composer audit + npm audit both clean; scoring
migration Ran on MySQL pmmsdb; app HTTP 200 at http://pmms.app — live
browser walkthrough not performed this session, Chrome extension was
unavailable, so the checkpoint evidence is test-based + HTTP-200 liveness,
same bar WP-07-01/02 used; flagged for an optional owner follow-up phone
check, not blocking). Plan:
docs/phases/phase-07-live-scoring-enhancement/ (README + DESIGN-NOTES +
CHECKLIST + WP-07-01..03), written fresh for this codebase after the
unreviewed generic-template draft that occupied this directory was found
to invent a nonexistent "Tournament Manager" role and falsely claim an
existing "Reverb foundation" (composer.json/package.json have no
WebSocket dependency at all). Scope, per owner instruction 2026-07-25 (two
rounds of scoping questions): yes to live scoring with Reverb, but
**generic scoreboard only** (no basketball/boxing/softball-specific
scoreboards this phase) and **internal only** (no public portal exposure
this phase). This is the project's first new dependency after 5 phases of
"zero dependencies added" — deliberately isolated in its own WP (WP-07-01)
and required to work by polling alone if Reverb isn't running, so it's
additive risk, not a hard new requirement. A live scoring session is a
provisional/spectator layer only — it never creates or implies a validated
result; Phase 3's result-integrity core (encode→validate→single-correction-
path) stays completely untouched. Three WPs: live scoring foundation
(Reverb + data model + endpoints, proven via tests with Reverb
unconfigured) → generic live scoreboard UI (operator console + live
display + full-screen mode, internal only) → accessibility/testing/
acceptance. Execute one work package at a time on owner instruction.

## Phase 7 Work Package Log
- WP-07-01 Live Scoring Foundation — done 2026-07-25 (the project's first
  new dependency after 5 phases of zero: `laravel/reverb` (composer) +
  `laravel-echo`/`pusher-js`/`@laravel/echo-react` (npm), via `php artisan
  install:broadcasting --reverb` (also fixed a pre-existing guzzlehttp/
  guzzle medium-severity advisory found by `composer audit` along the way,
  unrelated to Reverb — upgraded to 7.15.1, `composer audit` now clean).
  `reverb:install`'s own connection self-test failed before REVERB_* env
  vars existed (chicken-and-egg); generated them manually into `.env`
  instead (`.env.example` deliberately left at the framework default
  `BROADCAST_CONNECTION=log`, same convention as `DB_CONNECTION=sqlite` —
  a fresh setup gets polling-only live scoring with zero broadcasting
  infra required). New `scoring_sessions` (one per match, generic
  side_a/side_b running score + period/status text, no sport-specific
  columns) + `score_events` (append-only audit/catch-up log) tables;
  `App\Enums\MatchStatus` deliberately NOT touched — live-session state
  is fully decoupled from match lifecycle status. `ScoringSessionController`
  reuses existing authorization exactly: viewing mirrors the "Matches —
  list" rule (Admin/Organizer all, Delegation Officer own delegation's
  matches only, Viewer forbidden), mutations reuse `role:admin,organizer`
  (same as match create/update) — no new role invented, correcting the
  discarded draft's "Tournament Manager." `App\Events\ScoreUpdated`
  broadcasts on a private channel authorized with the identical rule
  (`routes/channels.php`); every write is also readable via a plain
  polling GET (`scoring.show`), proven by test to work correctly with
  broadcasting on the `null` driver (phpunit.xml's test default — the
  whole suite already proves the Reverb-absent path, no special-casing
  needed). Ending a session never touches `EventResult`/
  `ResultPlacement` (explicit test assertion) — Phase 3's result-integrity
  core is completely unmodified. docs/live-scoring.md (new),
  docs/matches.md cross-reference, docs/authorization.md (+2 rows),
  docs/audit-trail.md (+1 row). Pest 602/602 (12 new tests in
  ScoringSessionTest: authorization incl. delegation-scoping, full
  lifecycle via polling only, one-active-session enforcement, scheduled-
  only start, correction requires a reason, score floor at zero, ended
  session immutable, EventResult untouched), full gate green:
  Pint+PHPStan+ESLint+Prettier+tsc+build; migration Ran on MySQL pmmsdb;
  app HTTP 200 at http://pmms.app; not committed/pushed) — next: WP-07-02
  Generic Live Scoreboard UI.
- WP-07-02 Generic Live Scoreboard UI — done 2026-07-25 (one page, not
  two, for both operator console and read-only display —
  `resources/js/pages/scoring/show.tsx`, same pattern as `matches/
  index.tsx` unifying manager/viewer experience with conditional
  controls; new `ScoringSessionController::board()` + `GET /matches/
  {match}/scoreboard` (`scoring.board`, same authorization as WP-07-01's
  polling endpoint). Start form pre-fills side labels from
  `suggestedLabels` (match's two entries' school names, only when exactly
  two exist — deliberately not modeling bracket/team structure for >2).
  Active session: +1/+2/+3 quick-score + a per-side correction dialog
  (delta + required reason), period/status form, pause/resume, End
  ConfirmDialog (description reminds the operator the official result
  still needs separate encoding). Full-screen mode via the browser
  Fullscreen API on the scoreboard's own container. Live updates: always
  polls scoring.show every 5s (the WP-07-01-promised baseline) plus
  `useEcho` (`@laravel/echo-react`) on the private channel for
  near-instant updates when Reverb is available — both write the same
  local state, page behaves identically either way. Hit and fixed a real
  React Compiler lint error twice (`react-hooks/set-state-in-effect` —
  syncing a prop into state inside `useEffect`): switched to React's
  documented "adjust state during render" pattern (compare against a
  tracked last-synced value, update synchronously in the render body, not
  in an effect) for both the main session sync and the period/status
  form's local-edit-vs-external-update reconciliation. Refactored
  WP-07-01's controller `present()` into `ScoringSession::toLivePayload()`
  so the polling endpoint, the board's initial props, and the Reverb
  broadcast payload are now provably the same shape from one source, not
  three hand-written copies. `matches/index.tsx` gets a new always-visible
  "Live" column (not gated behind `canManage`, unlike Actions — a
  Delegation Officer can watch their own delegation's match). docs/
  live-scoring.md extended. Pest 607/607 (5 new tests: guest/Viewer
  forbidden from the board page, Delegation Officer own-match-only,
  suggested labels only for exactly two entries, the board's session prop
  reflects a score change made through the operator endpoints), full gate
  green: Pint+PHPStan+ESLint+Prettier+tsc+build; app HTTP 200 at
  http://pmms.app; not committed/pushed) — next: WP-07-03 Live Scoring
  Accessibility, Testing & Acceptance.
- WP-07-03 Live Scoring Accessibility, Testing & Acceptance — done
  2026-07-26 (COMPLIANT, two Low findings fixed during review — full report
  docs/phases/phase-07-live-scoring-enhancement/phase-7-compliance-review.md.
  Accessibility sweep of `scoring/show.tsx` found and fixed two real gaps:
  the bare +1/+2/+3 quick-score buttons had no accessible name naming which
  side they scored for (added `aria-label`, e.g. "Add 1 point, Home"), and
  the two-side control block's fixed `grid-cols-2` risked the three-button
  row crowding/wrapping awkwardly on a narrow phone (now stacks to one
  column below `sm:`, button rows wrap); also added `aria-live="polite"` +
  `aria-atomic="true"` to the live score grid so a screen-reader user
  watching the read-only display is told when the score changes, not just
  sighted users. Verified already sound: heading order (CardTitle is a
  styled div, same convention app-wide, not a real heading gap), decorative
  icons already aria-hidden, empty state, all form labels. Re-confirmed the
  phase's core rules by direct grep/git-log, not just re-reading the
  DESIGN-NOTES: zero EventResult/ResultPlacement write references anywhere
  in the live-scoring code (only doc-comments describing what it
  deliberately doesn't do), MatchStatus.php has zero diff and was last
  touched in WP-03-04 (Phase 3), authorization mirrors Matches — list
  exactly with no loosening (re-verified against docs/authorization.md).
  Added one new definitive test — `ResultTest`'s "a match can be finalized
  with a result and no live scoring session was ever started (Phase 7)" —
  proving the encode→validate flow works with zero ScoringSession rows ever
  created, the inverse of WP-07-01's existing "ending a session never
  touches EventResult" test, so the two systems are now proven decoupled in
  both directions. Reviewed and documented (docs/live-scoring.md) two
  behaviors the WP scope asked to confirm rather than newly build: Reverb
  stopping mid-session is a non-issue because every write persists before
  the queued ShouldBroadcast event fires and the unconditional 5-second
  poll re-syncs any client relying on the socket (already proven by the
  existing lifecycle test running under the null broadcast driver); and
  concurrent-operator score races are accepted last-write-wins on the
  running total (documented, not lock-guarded — a live session is
  provisional by definition) while `score_events` is still appended
  unconditionally on every request, so no audit row is ever silently
  dropped even when the derived total races. composer audit and npm audit
  (--omit=dev) both came back clean. Live interactive browser verification
  (incl. a phone-width visual pass) was not performed — the Chrome browser
  automation extension was unavailable this session — so that checkpoint's
  evidence is test-based + an HTTP 200 liveness check, the same bar
  WP-07-01/02 already used, not a screenshot walk; flagged in the
  compliance review as an optional owner follow-up, not a blocker. No
  schema changes. Pest 608/608 (1 new test), full gate green: Pint+PHPStan+
  ESLint+Prettier+tsc+build; migration confirmed Ran on MySQL pmmsdb; app
  HTTP 200 at http://pmms.app; not committed/pushed) — this closes Phase 7.
  Next: owner review of the compliance report (optionally with a manual
  phone check) + commit decision for the Phase 7 tree; then owner's choice
  of Phase 8 — Post-Deployment Support or Phase 6 — Reports, UAT,
  Deployment, and Turnover (still needing a real plan written for this
  codebase before any WP-06 work).
- WP-07-04 Basketball Live Scoreboard — done 2026-07-26 (owner reopened
  Phase 7 after its closure to add sport-specific scoreboards; numbered
  WP-07-04 rather than reusing WP-07-02, since that name already means
  "Generic Live Scoreboard UI" and is shipped/pushed. New
  `App\Enums\ScoreboardType` (generic/basketball/boxing/softball_baseball)
  with `forSport(?string $sportName)` — never stored, always derived from
  the match's `event.sport.name` via `ScoringSession::boardType()`, so a
  sport-catalog rename is reflected immediately with no backfill. New
  nullable JSON `scoring_sessions.sport_state` column (one flexible column
  shared by every sport-specific board this phase adds, not one column per
  sport) — for basketball, `{fouls_a, fouls_b}` (running team fouls this
  quarter), initialized to `{0,0}` in `store()` when the match's board type
  is Basketball. New `App\Enums\ScoreEventType::Foul` case and
  `PATCH scoring-sessions/{session}/foul` (`scoring.foul`) — `action` =
  `add` (+1 to one side) or `reset` (both to 0); 422s for a non-Basketball
  session; same `role:admin,organizer` gate as every other scoring
  mutation, no new role. Quarter number reuses the existing generic
  `period_label` free-text field rather than a new structured field — kept
  deliberately simple, per project rules against designing for hypothetical
  need; fouls reset via an explicit operator action, not auto-detected from
  a period-label change. `scoring/show.tsx`: team-fouls row (visible to
  every viewer, not just the operator) with a "Bonus" badge at
  `BASKETBALL_BONUS_THRESHOLD` (5, a documented convention only — not
  enforced elsewhere), per-side "Foul (n)" buttons and a shared "Reset
  fouls" button in the operator controls, all gated the same as the
  existing +1/+2/+3 buttons (`canManage && isActive`). Boxing and
  Softball/Baseball board types exist in the enum already but have no
  `sport_state` shape or UI yet — deferred to their own WP-07-05/06,
  reusing this WP's column/dispatch pattern rather than adding new
  infrastructure per sport. docs/live-scoring.md (new "Sport-specific
  scoreboards" section + endpoint/data-model/test updates), docs/
  audit-trail.md (+1 row: scoring.foul_recorded/fouls_reset), docs/
  authorization.md (extended the existing Live scoring row's action list,
  no new row — same gate). Pest 616/616 (8 new tests in
  ScoringSessionTest: Basketball session initializes board_type/sport_state
  correctly and a non-Basketball one doesn't, foul add/reset mutates the
  right side and audits both actions, the foul endpoint 422s for a
  non-Basketball session, is forbidden for non-managers, rejects a mutation
  on an ended session, and the scoreboard page exposes board_type/
  sport_state), full gate green: Pint+PHPStan+ESLint+Prettier+tsc+build;
  migration Ran on MySQL pmmsdb; app HTTP 200 at http://pmms.app; not
  committed/pushed) — next: WP-07-05 Boxing Live Scoreboard, on owner
  instruction.
- WP-07-05 Boxing Live Scoreboard — done 2026-07-26 (reused WP-07-04's
  `sport_state` column and dispatch pattern with no new migration and no
  new infrastructure — the reuse that update was designed for. Boxing
  `sport_state` is `{rounds: [{round, score_a, score_b}, ...]}`,
  initialized to `{rounds: []}` when a session starts for a Boxing match
  (extended `store()`'s per-board-type init from an `if` into a `match`
  covering both Basketball and Boxing). New `App\Enums\ScoreEventType::
  RoundScore` case and `PATCH scoring-sessions/{session}/round`
  (`scoring.round`) — validates `score_a`/`score_b` each `0..10` (10-point-
  must convention, not enforced beyond the range — a meet's own judging
  decides which side gets 10), appends `{round: count(rounds)+1, score_a,
  score_b}` to the history, and **also** adds both deltas into the
  session's running `score_a`/`score_b` so the main cumulative score
  display stays correct — the round number is always derived from history
  length, never operator-input, so rounds can't be recorded out of order or
  duplicated under a wrong number. Deliberately no per-round edit/undo in
  this WP (keeping it practical, no new capability invented beyond what was
  asked): a mis-scored round is fixed the same way as any other board type,
  through the existing generic `scoring.score` correction endpoint on the
  running total. Round number itself (as in "Round 3") reuses the existing
  generic `period_label` free-text field, same convention WP-07-04 used
  for basketball's quarter — no new structured field for it. `scoring/
  show.tsx`: a "Round-by-round" list (visible to every viewer, not just the
  operator) and an operator-only "Record round N" dialog (two number
  inputs, `RoundScoreDialog`, mirrors the existing `CorrectionDialog`
  pattern) that always shows the next round number. Introduced
  `isBasketballState`/`isBoxingState` TypeScript type guards (`sport_state`
  is now a `BasketballState | BoxingState | null` union) plus two derived
  `const`s (`basketballState`/`boxingState`) computed once per render,
  replacing WP-07-04's inline `session.board_type === 'basketball'` checks
  everywhere they touched `sport_state` — cleaner narrowing for a second
  sport than repeating the string-literal check, and the pattern is now
  established for WP-07-06 to extend a third time. docs/live-scoring.md
  (Boxing moved from "deferred" to a documented board, endpoint table row,
  test list), docs/audit-trail.md (+1 row: scoring.round_scored), docs/
  authorization.md (extended the existing Live scoring row's action list
  again, no new row). Pest 624/624 (8 new tests in ScoringSessionTest:
  Boxing session initializes an empty round history and the right
  board_type, recording rounds appends to history and sums into the
  running total correctly across multiple rounds with correct round
  numbers, a round score outside 0..10 is rejected, the round endpoint
  422s for a non-Boxing session, is forbidden for non-managers, rejects a
  mutation on an ended session, and the scoreboard page exposes
  board_type/sport_state), full gate green: Pint+PHPStan+ESLint+Prettier+
  tsc+build; no new migration (Ran: nothing to migrate, reused WP-07-04's
  column); app HTTP 200 at http://pmms.app; not committed/pushed) — next:
  WP-07-06 Softball/Baseball Live Scoreboard, on owner instruction.
- WP-07-06 Softball/Baseball Live Scoreboard — done 2026-07-26 (closes out
  the owner's sport-specific scoreboard request — Basketball/Boxing/
  Softball/Baseball now built, every other sport still gets the original
  generic board. Again reused WP-07-04's `sport_state` column and
  per-board `store()` init with no new migration — the third sport to do
  so, confirming that extension point holds. `App\Enums\ScoreboardType::
  SoftballBaseball` resolves both "Softball" and "Baseball" sport names to
  one board (proven by a parametrized test over both names). `sport_state`
  is `{inning, half (top|bottom), outs, balls, strikes, innings: [{inning,
  runs_a, runs_b}, ...]}`, initialized to inning 1/top/all-zero/empty
  history. Two endpoints, since runs and the count/outs are independent
  concerns: `scoring.count` (`out`/`ball`/`strike`/`reset_count`) encodes
  the sport's own hard rules as cascading state transitions the same way
  WP-07-04's bonus badge and WP-07-05's derived round number did — any out
  (direct, or a third strike) resets the count for the next batter; a
  *third* out additionally ends the half-inning (flips top<->bottom,
  increments `inning` once bottom ends); a *fourth* ball resets the count
  (a walk — no baserunner model, so no run auto-added); `reset_count` is a
  manual new-batter correction. `scoring.inning-run` (`side` + `runs`,
  1-20) finds-or-creates the current inning's row in `sport_state.innings`
  by `sport_state.inning` (never operator-input) and adds to it **and** to
  the session's running `score_a`/`score_b` in the same request, so the
  linescore breakdown and the cumulative total can never disagree — a
  later inning correctly starts its own row rather than merging into an
  earlier one (explicit test). Both new controller methods needed a
  `default => $state`/`default => $state` arm added to their `match`
  expressions to satisfy PHPStan (`$data['action']` from `$request->
  validate()` is typed `mixed`, so `Rule::in`-validated exhaustiveness
  isn't visible to static analysis even though it's guaranteed at
  runtime) — the only real friction this WP hit. `scoring/show.tsx`: an
  inning/half/outs/count status line, a linescore-style inning list
  (visible to everyone), an operator-only `RunDialog` per side (single
  runs-scored input, mirrors `CorrectionDialog`'s shape) and a shared
  Out/Ball/Strike/Reset-count button row. Same accepted trade-off as
  boxing's rounds: the generic `score` correction endpoint is still
  reachable and would desync the linescore from the total if used instead
  of `inning-run`, documented rather than blocked — nothing is silently
  lost either way since every mutation is still an unconditional
  `score_events` row. docs/live-scoring.md (Softball/Baseball moved from
  "deferred" to a fully documented board, two endpoint table rows, test
  list, closing note that all three sport-specific boards reused
  WP-07-04's extension point with zero schema changes after the first),
  docs/audit-trail.md (+1 row: scoring.count_updated/run_scored), docs/
  authorization.md (extended the Live scoring row's action list a third
  time, no new row). Pest 638/638 (14 new tests in ScoringSessionTest:
  Softball and Baseball both correctly initialize the same board type and
  count/inning state, a run appends to the current inning and sums into
  the total, a later inning starts its own row, three outs flips the half
  and resets the count, the bottom half's third out advances the inning
  number, a third strike is itself an out, a fourth ball resets the count
  without an out, reset_count only zeroes balls/strikes, both endpoints
  422 for a non-Softball/Baseball session, are forbidden for non-managers,
  reject a mutation on an ended session, and the scoreboard page exposes
  board_type/sport_state), full gate green: Pint+PHPStan+ESLint+Prettier+
  tsc+build; no new migration (Ran: nothing to migrate); app HTTP 200 at
  http://pmms.app) — this closed the owner's original sport-specific
  scoreboard request (WP-07-04/05/06). WP-07-04/05/06 committed 2026-07-26
  as three individually-buildable commits (`c818903`/`1e7b9e7`/`650a7f5`,
  each verified against its own full quality gate — WP-07-05's commit
  required manually reconstructing its true intermediate file state since
  it added no exclusive files of its own, backed up the final files first
  and diffed against the backup at each step to confirm no drift) and
  pushed to origin same day, next to WP-07-01..03's earlier commits.
- WP-07-07 Generic Match Scoreboard (manual board-type override) — done
  2026-07-26 (owner asked for this by that name; scoped down after
  clarifying — the generic *board* already existed since WP-07-02 as the
  automatic fallback for every sport without a dedicated one, so this WP
  is specifically the manual **override**: let the operator force the
  generic board at session start even for a sport that has a dedicated one,
  for an exhibition or non-standard match the sport-specific rules don't
  fit. New nullable `scoring_sessions.board_type_override` column;
  `ScoringSession::boardType()` checks it first and returns it without even
  loading the match's sport if present — the override always wins, for the
  session's lifetime, no way to change it mid-session (would orphan
  already-recorded sport_state, out of scope by design).
  `ScoringSessionController::store()` accepts an optional `board_type`
  field validated to only ever equal `"generic"` (deliberately no way to
  force a *sport-specific* board onto an unrelated sport's match, only to
  opt out down to generic); `board()` gains a `suggestedBoardType` prop
  (the auto-derived board, independent of whether a session exists yet) so
  the frontend knows whether the override control is even relevant.
  `scoring/show.tsx`'s Start form shows a "Use the generic scoreboard
  instead of the automatic {board} board" checkbox only when
  `suggestedBoardType !== 'generic'` (new shadcn `Checkbox`, first use in
  this file, matching the existing controlled-checkbox pattern from
  `delegations/index.tsx`). `scoring.started`'s audit context now also
  records the resolved `board_type` for traceability. docs/live-scoring.md
  (new "Manual board-type override" section + data-model/endpoint/test
  updates); no docs/audit-trail.md or docs/authorization.md changes needed
  (no new audit action, same `scoring.start` endpoint/gate). Pest 644/644
  (6 new tests in ScoringSessionTest: a Basketball/Boxing/Softball match's
  session can each be forced to generic with sport_state staying null, a
  Basketball match started without the override still gets the basketball
  board (regression guard), the override rejects any value other than
  "generic", and the scoreboard page exposes the correct suggestedBoardType
  both for a Basketball match and for a match with no dedicated board),
  full gate green: Pint+PHPStan+ESLint+Prettier+tsc+build; migration Ran on
  MySQL pmmsdb; app HTTP 200 at http://pmms.app) — committed as `607bb38`
  and pushed to origin 2026-07-26, a single clean commit (no reconstruction
  needed, unlike WP-07-05 — nothing further modified these files
  afterward).
- WP-07-08 Public Live Scoreboard — done 2026-07-26 (owner instructed
  extending the public portal to live scores, reversing Phase 7's original
  "internal only" decision (DESIGN-NOTES had explicitly deferred this "to
  its own future WP if this version proves out") — clarified first that the
  existing meets.is_published flag should cover it, no separate opt-in,
  matching how the schedule/results/tally already work; a manager's one
  publish decision is the one decision point. New public routes
  `/meets/{meet}/matches/{match}/scoreboard` (`public.scoreboard`, page) and
  its `/poll` sibling (`public.scoreboard.poll`, JSON), both resolved
  through `Meet::published()` **and** requiring the match belong to that
  meet — either condition failing 404s, same as every other public route.
  Read-only always: no operator controls exist on this route at all
  (structurally impossible to leak, not just hidden by a flag), page test
  asserts `canManage`/`suggestedLabels` are structurally absent. Polling
  only, no Reverb for guests (a private Echo channel needs an authenticated
  user; building a public-channel exception was out of scope this pass) —
  same 5-second baseline every internal live-scoring page already
  guarantees works standalone. Explicit "Live score — provisional, not the
  official result" badge on the page, since this is genuinely different in
  character from every other public category (validated-only results,
  official schedule) — the one thing on the portal that's deliberately
  unvalidated. `PortalController::liveMatches()` (new private helper) feeds
  a new "Live now" section on the public meet page listing every match with
  a currently active session, linking into its scoreboard — without this
  the feature would only be reachable by guessing a URL; queries
  `ScoringSession` directly (`status != ended`, scoped through
  `match.meet_id`) since only one non-ended session can exist per match,
  so it's naturally one row per live match already. Extracted
  `resources/js/components/live-score-display.tsx` (`LiveScoreDisplay` +
  the `Session`/`BasketballState`/`BoxingState`/`SoftballState` types and
  guards, all previously private to `scoring/show.tsx`) as the one shared,
  purely presentational read-only rendering both the internal operator
  console and this new public page render from — each page still fetches
  and shapes its own props independently (`PortalController` never reuses
  an internal page's props, the binding public-portal.md rule), only the
  rendering itself is shared, specifically to stop the two views from
  drifting apart over time the way duplicated ~140 lines of JSX would
  invite. No schema change, no new dependency. docs/public-portal.md (new
  "Live scoreboard" section, top summary line updated to flag this as a
  deliberate expansion beyond product-scope §9's original four categories),
  docs/live-scoring.md (short cross-reference section + test list); no
  docs/authorization.md change (the existing generic "Public portal" row
  already covers every public route, individual pages never got their own
  rows). Pest 649/649 (5 new tests in new PublicScoreboardTest: guests can
  view the public scoreboard for a published meet and unpublished meets
  404, a match outside the given meet 404s, the page exposes the live
  session read-only including sport-specific state with operator-only
  fields structurally absent, the poll endpoint mirrors the page and 404s
  the same way, and the public meet page's liveMatches lists only matches
  with a currently active session scoped to that meet), full gate green:
  Pint+PHPStan+ESLint+Prettier+tsc+build (hit and correctly dismissed one
  PHPStan false positive — analysing the new test file in isolation
  reported `$this->get()` as undefined, a known quirk of not loading the
  full Pest/Larastan bootstrap that way; the full-codebase run was clean);
  rebuilt before running the new page's tests (this project's documented
  gotcha: Inertia page-render tests need `public/build`'s manifest to
  include a newly-added page first); app + public portal both HTTP 200 at
  http://pmms.app; not committed/pushed) — next: owner's choice of a fifth
  sport-specific WP, commit decision for WP-07-08, or moving on to Phase
  6/8 planning.

# Phase 5 — Executive and Management Dashboards
COMPLETE 2026-07-25, all 8 WPs executed one at a time on owner instruction.
Review: docs/phases/phase-05-executive-management-dashboards/
phase-5-compliance-review.md (COMPLIANT; full gate green: Pint+PHPStan+
Pest 590/590 (2,919 assertions), ESLint+Prettier+tsc+build; 33 migrations
Ran on MySQL pmmsdb, zero new this phase — every widget is read-side over
existing tables; app HTTP 200 at http://pmms.app). Plan:
docs/phases/phase-05-executive-management-dashboards/ (README + DESIGN-NOTES +
CHECKLIST + WP-05-01..08), written fresh for this codebase after the
unreviewed generic-template draft that previously occupied this directory
was found to invent nonexistent DepEd job-title roles and a wrong
"medal tally is delegation-based" assumption (flagged in a prior commit,
superseded by this real plan). Scope, per owner instruction 2026-07-25:
cross-meet/historical oversight (participation & registration trends,
operations progress & risk, delegation/school performance history, venue
utilization, reports/export) for Admin and Organizer only — the same two
roles Phase 3's `manage-meet-data` gate already treats as managers; Phase 3's
single-Active-meet operations block is left untouched, Phase 5 adds the
across-meets layer on top. Eight WPs: management dashboard foundation →
participation & registration trends → operations progress & risk →
delegation & school performance history → venue utilization → reports &
export → accessibility/mobile review → Phase 5 review and acceptance.
Execute one work package at a time on owner instruction.

## Phase 5 Work Package Log
- WP-05-01 Management Dashboard Foundation — done 2026-07-25
  (`ManagementDashboardController::index()` + `/management` route
  (`management.index`), gated `can:manage-meet-data` (Admin/Organizer only,
  same gate Phase 3's operational queues already use — Delegation Officer
  and Viewer get 403); page shell (`management/index.tsx`, sidebar nav item
  "Management" in `managerNavItems`) with a school-year filter and a table
  of meets in scope — the placeholder proving the filter works end-to-end,
  per plan (real trend widgets are WP-05-02 onward); private
  `meetsInScope(?string $schoolYear)` is the one query every later Phase 5
  WP's aggregate will start from, not duplicated per widget. New
  docs/management-dashboard.md (cross-references docs/dashboard.md,
  explicit about the delegation-vs-school distinction every later widget
  must respect); docs/authorization.md +1 matrix row; docs/dashboard.md
  cross-reference note distinguishing it from the existing single-meet
  `/dashboard`. Pest 575/575 (7 new tests in ManagementDashboardTest: guest
  redirect, Delegation-Officer/Viewer forbidden, Admin/Organizer allowed,
  school-year filter narrows scope correctly, empty state), full gate
  green: Pint+PHPStan+ESLint+Prettier+tsc+build; not committed/pushed) —
  next: WP-05-02 Participation & Registration Trends.
- WP-05-02 Participation & Registration Trends — done 2026-07-25
  (`ManagementDashboardController::participation(Collection $meets)` (private)
  adds a `participation` prop: per-meet `delegations` (draft/submitted/
  approved/total, the registering unit, grouped from `App\Enums\
  DelegationStatus`) kept strictly separate from `athletes`/`personnel`/
  `entries` (individuals, via `whereHas('delegation', ...)` — deliberately
  not scoped by school_id, since this WP counts totals, not per-school
  breakdowns; that's WP-05-04), plus `totals` summed across meets in scope;
  page renders two separate tables ("Delegations by status", "Participation")
  plus four `StatCard`s, keeping the delegation-vs-individual distinction
  visually explicit per DESIGN-NOTES rather than one merged table.
  docs/management-dashboard.md extended. Pest 577/577 (2 new tests: counts
  correct per meet/status and in aggregate; a second meet's delegations/
  individuals never leak into another meet's row, proven with two meets at
  distinct starts_at so row order is deterministic), full gate green:
  Pint+PHPStan+ESLint+Prettier+tsc+build; not committed/pushed) — next:
  WP-05-03 Operations Progress & Risk.
- WP-05-03 Operations Progress & Risk — done 2026-07-25
  (`ManagementDashboardController::operationsProgress(Collection $meets)`
  (private) adds an `operations` prop: per-meet results encoded/validated
  (`App\Enums\ResultStatus`), eligibility pending/approved/returned
  (`EligibilityStatus`), protests filed/under_review/upheld/dismissed
  (`ProtestStatus`, reached via `whereHas('delegation', ...)` since Protest
  has no direct meet_id), incidents open/resolved (`IncidentStatus`) — all
  reused enums, nothing recomputed; plus one explicit `is_stalled` flag
  (Active meet + an encoded result older than `STALLED_RESULT_HOURS` = 24,
  a class constant — a plain age check, not a predictive score). Page adds
  an "Operations progress & risk" table linking Encoded→/results and
  Incidents open→/incidents pre-filtered by meet_id (both controllers
  already support it); Eligibility/Protests link to their plain index
  pages since neither controller supports a meet_id filter and adding one
  was correctly left out of this WP's scope. docs/management-dashboard.md
  extended. Pest 579/579 (2 new tests: all four status breakdowns correct
  per meet; stalled flag true only for an Active meet with an old encoded
  result — false for a non-Active meet with the same old result and false
  for an Active meet with a recent one), full gate green:
  Pint+PHPStan+ESLint+Prettier+tsc+build; not committed/pushed) — next:
  WP-05-04 Delegation & School Performance History.
- WP-05-04 Delegation & School Performance History — done 2026-07-25
  (`ManagementDashboardController::performanceHistory(Collection $meets,
  MedalTallyService $tally)` (private) adds a `performance` prop: calls
  `MedalTallyService::standings($meetId)` once per meet in scope and sums
  gold/silver/bronze/total across meets — tally derivation itself never
  reimplemented; `districts` (official aggregate) and `schools` (reference,
  keyed by "{school}|{district}" to avoid cross-district name collisions,
  never school name alone) both re-sorted with a new `orderedStandings()`
  (private) using the same gold→silver→bronze→name convention as
  `MedalTallyService::ordered()`, applied to the across-meets totals. Page
  renders district history first (default Heading weight, "the official
  verdict") with school history below demoted to small variant +
  "Reference only," mirroring the district-first convention from the
  Post-Division refinement — this WP extends that convention across meets,
  doesn't invent a new one; each row links to its registry entry
  (districts/schools ?search=). docs/management-dashboard.md extended.
  Pest 581/581 (2 new tests: the same school's medals correctly aggregate
  across two different meets in both the district and school rows;
  district/school ordering follows the gold→silver→bronze→name convention),
  full gate green: Pint+PHPStan+ESLint+Prettier+tsc+build; not
  committed/pushed) — next: WP-05-05 Venue Utilization.
- WP-05-05 Venue Utilization — done 2026-07-25
  (`ManagementDashboardController::venueUtilization(Collection $meets)`
  (private) adds a `venues` prop: per venue, `slots` (count), `hours`
  (`abs(diffInMinutes(ends_at, starts_at))` summed /60, rounded to 1
  decimal — fixed a real sign bug during this WP: this Carbon version's
  `diffInMinutes` returns a signed value depending on caller/argument
  order, wrapped in `abs()`), `meets`/`events` (distinct counts) — all
  derived from `EventSchedule`, no new tables; only venues with at least
  one slot in scope are returned. Page adds a "Venue utilization" table,
  each row linking to `/venues?search=...`. docs/management-dashboard.md
  extended. Pest 584/584 (3 new tests: slots/hours/meets/events correct
  across two meets at one venue — including the corrected sign; the
  school-year filter narrows venues to meets in scope; empty state when
  nothing is scheduled), full gate green:
  Pint+PHPStan+ESLint+Prettier+tsc+build; not committed/pushed) — next:
  WP-05-06 Management Reports & Export.
- WP-05-06 Management Reports & Export — done 2026-07-25 (two new routes,
  both `can:manage-meet-data`, both accepting the same `school_year`
  filter as `/management`: `/reports/management` — printable page
  (`reports/management`, same print CSS pattern as every existing report,
  no PDF lib), sharing its data with the interactive dashboard via new
  private `widgetData(Collection $meets, MedalTallyService $tally)`
  (extracted from `index()` so dashboard/report/CSV can never disagree);
  `/reports/management/download` — streamed CSV, audited
  `report.management_exported`, via a new private `csv()` helper
  mirroring ReportController's (duplicated, not extracted to a shared
  trait — not worth a new abstraction for 12 lines). CSV is six
  independent blocks (one per widget, own header row, blank-line
  separator) since the widgets have unrelated shapes, unlike the tally
  report's single shared header. Interactive page links "Printable
  report" from PageHeader, carrying the school_year filter through.
  Real pitfall hit and fixed: `php artisan wayfinder:generate` without
  `--with-form` regenerated every route helper without the `.form()`
  variant, breaking unrelated pre-existing auth/settings pages that
  depend on it (vite.config.ts's wayfinder plugin normally generates
  with `formVariants: true`) — caught immediately by `tsc`, fixed by
  re-running with `--with-form`; documented to prefer `npm run build`
  going forward. docs/management-dashboard.md, docs/audit-trail.md
  (+1 row) extended. Pest 590/590 (6 new tests: forbidden roles on both
  new routes, report renders the same widget data as the dashboard for
  the same filter, CSV download audited and carries all six section
  headers), full gate green: Pint+PHPStan+ESLint+Prettier+tsc+build; not
  committed/pushed) — next: WP-05-07 Accessibility & Mobile Review.
- WP-05-07 Accessibility & Mobile Review — done 2026-07-25 (swept
  management/index.tsx and reports/management.tsx against the WP-04-06
  checklist; real gaps found and fixed: StatCard's icon (shared component,
  also used by the main /dashboard) and ReportActions's Download/Print
  icons (shared, every report page) had no aria-hidden, fixed at the
  component so every consumer benefits, not just Phase 5; local decorative
  icons (Printable-report link's Printer, Stalled badge's TriangleAlert)
  same fix; four bare-number links in the operations table (e.g. a lone
  "3" linking into /results) got descriptive aria-labels since a
  screen-reader user tabbing through links in isolation would otherwise
  hear only the number; reports/management.tsx had no empty state and
  would have rendered seven empty tables with zero meets in scope, fixed
  to match the interactive dashboard and every other report. Verified
  already sound: heading order (h1 via PageHeader, h2 per section, no
  skipped levels), every table already overflow-x-auto, school-year Select
  already aria-labeled, StatCard grid already collapses to one column on
  phones. No backend/behavioral changes — pure presentation, so no new
  Pest tests, consistent with how the Post-Division tally-reorder change
  was handled; Pest 590/590 (unchanged), full gate green:
  Pint+PHPStan+ESLint+Prettier+tsc+build; not committed/pushed) — next:
  WP-05-08 Phase 5 Review and Acceptance.
- WP-05-08 Phase 5 Review and Acceptance — done 2026-07-25 (COMPLIANT, no
  remediation needed — full report docs/phases/phase-05-executive-
  management-dashboards/phase-5-compliance-review.md. Re-verified the
  phase's own core rule with no violations found: every widget keeps
  delegations (registering unit) and individuals (own school, via
  MedalTallyService's already-independently-verified school_id
  attribution) in structurally separate response keys and visually
  distinct tables, never conflated. Re-verified authorization: all three
  routes (/management, /reports/management, /reports/management/download)
  gated can:manage-meet-data, docs/authorization.md matrix row updated to
  cover page+report+CSV explicitly, no new public exposure (all inside the
  auth,verified middleware group), no minor-athlete data anywhere — every
  Phase 5 prop is an aggregate count or sum. Zero schema changes across
  the whole phase — confirmed 33/33 migrations still Ran, nothing new.
  docs/phases/phase-05-executive-management-dashboards/README.md marked
  COMPLETE. Pest 590/590, full gate green:
  Pint+PHPStan+ESLint+Prettier+tsc+build; app HTTP 200 at http://pmms.app;
  not committed/pushed) — this closes Phase 5. Next: owner review of the
  compliance report + commit decision for the Phase 5 tree; Phase 6
  planning not begun (docs/phases/phase-06-reports-uat-deployment-
  turnover/ is still unreviewed generic scaffolding per the note above —
  needs a real plan written for this codebase before any WP-06 work, the
  same way Phase 4's and Phase 5's real plans were).

# Phase 4 — COMPLETE 2026-07-25 (tracking realigned 2026-07-25 — see note below)
Phase 4 — Responsive Public Portal: tracked against
docs/phases/phase-04-responsive-public-portal/README.md + CHECKLIST.md,
11-WP numbering (WP-04-01..11), per owner instruction 2026-07-25 to adopt
this as the final track. Review: docs/phases/phase-04-responsive-public-portal/
phase-4-compliance-review.md (COMPLIANT; full gate green: Pint+PHPStan+
Pest 568/568 (2,627 assertions), ESLint+Prettier+tsc+build; 33 migrations Ran
on MySQL pmmsdb; app HTTP 200 at http://pmms.app). Grounded in product-scope
§9: published schedules,
validated results, medal tally, announcements — nothing else public;
publication is an explicit audited manager decision; athlete identity on the
portal is name+school+placement only.

**Realignment note (2026-07-25):** this 11-WP file set was regenerated from
the same stale pre-Phase-2 draft the original 7-WP plan (git history,
commit `a7bde91`) already replaced once — its Core Rules sections contain
inaccurate boilerplate (e.g. "keep municipality as the official delegation,"
"medal tally is delegation-based" — factually wrong, see docs/delegations.md
and docs/medal-tally.md) not written for this codebase. CHECKLIST.md already
reconciled it against actually-shipped work: WP-04-01/02/03/04/05/10 match
what's built (the log below, written under the old 7-WP numbering, is the
accurate record of that work — not rewritten, just now cross-referenced to
the new numbers via CHECKLIST.md). Per explicit owner confirmation
2026-07-25, WP-04-06 (public delegation/school directory) and WP-04-07
(public athlete/team profiles) stay **excluded by design** — product scope
keeps individual athlete data, mostly minors, off the public portal beyond
name+school+placement on a results row; owner declined to reverse that.
WP-04-08 (downloads) and WP-04-09 (general search) stay **partial** as
documented — owner declined to build out the missing halves. Only WP-04-11
Phase 4 Review and Acceptance is genuinely next (same substance as the old
plan's WP-04-07 Phase 4 Compliance Review); executed using this project's
real conventions (`.ai/phase-review.md`, `.ai/work-package-runner.md`, the
WP-03-11/Division-WP7 pattern), not the generic template's stale specifics.
Execute one work package at a time on owner instruction.

## Phase 4 Work Package Log
- WP-04-11 Phase 4 Review and Acceptance — done 2026-07-25 (COMPLIANT, no
  remediation needed — full report docs/phases/phase-04-responsive-public-portal/
  phase-4-compliance-review.md. Confirmed the publication/privacy boundary
  holds with prop-level evidence, not UI inspection: every public route sits
  behind Meet::published() + throttle:60,1; results structurally filtered to
  Validated status (a correction's reopen removes it automatically); athlete
  props limited to name+school+placement everywhere, missing()-tested against
  entry/status/encoded_by/validated_by/venue notes; no public route touches
  Eligibility/Accreditation/Protest/Incident/AuditLog; drafts (meets,
  announcements) invisible end-to-end; publication itself is an explicit
  audited manager decision (meet.published/unpublished, announcement.
  published/unpublished). Also folded in this session's medal-tally
  standing-order realignment (district/municipality first as the official
  verdict, school standings demoted to a reference table below — see
  "Post-Division refinement" log above) as part of the same review pass, no
  separate WP needed since it's presentation-only. Tracking realignment:
  adopted the regenerated 11-WP numbering (docs/phases/phase-04-responsive-
  public-portal/README.md + CHECKLIST.md) per explicit owner instruction
  2026-07-25 as the final track; reconfirmed by the owner (not just prior
  doc inference) that WP-04-06 (public delegation/school directory) and
  WP-04-07 (public athlete/team profiles) stay excluded — minors' data off
  the public portal beyond name+school+placement — and WP-04-08/09
  (downloads, general search) stay partial. No schema/behavior changes
  beyond the tally reorder — pure review + tracking docs; Pest 568/568, full
  gate green: Pint+PHPStan+ESLint+Prettier+tsc+build; 33 migrations Ran on
  MySQL pmmsdb; app HTTP 200 at http://pmms.app; not committed/pushed) — this
  closes Phase 4. Next: owner review of the compliance report + commit
  decision for the Phase 4 tree; Phase 5 planning not begun.
- WP-04-06 Accessibility & Mobile Review — done 2026-07-24 (swept all four
  portal pages + PublicLayout/PublicMeetNav/PublicAnnouncements at phone/
  tablet/desktop widths; real gap found and fixed: unpublished/nonexistent
  meets 404'd through Laravel's raw unstyled error page (bootstrap/app.php
  only special-cased 403) — extended the same Inertia-render pattern to 404,
  and made the shared error page layout/CTA guest-aware (PublicLayout +
  "Back to portal home" for guests instead of a dead-end "Back to dashboard"
  login bounce) with a dedicated 404 title/message; added a portal-wide meta
  description on PublicLayout; day-selector chips on the meet page wrapped in
  `<nav aria-label="Select day">` (previously unlabeled, unlike
  PublicMeetNav) with aria-current on the active day/section; decorative
  icons paired with visible text (CalendarDays/MapPin/Megaphone, shared
  EmptyState icon) marked aria-hidden; verified sound already: heading order,
  table horizontal-scroll containment, existing EmptyStates for every no-data
  path, focus-visible rings, filter aria-labels; accepted deviation: sm-size
  buttons stay 32px (matches app-wide convention, not redesigned this WP);
  docs/public-portal.md review section; Pest 545/545 (2 new tests in
  PublicPortalTest), full gate green: Pint+PHPStan+ESLint+Prettier+tsc+build;
  not committed/pushed)
- WP-04-05 Announcements — done 2026-07-19 (announcements table — the phase's
  only new entity: optional meet_id cascade, title ≤160, plain-text body ≤2000,
  is_published + published_at (cleared on unpublish, fresh on republish),
  created_by nullOnDelete; Announcement::published() scope; whole internal module
  manager-gated incl. list (registry pattern: title search, pagination, dialog
  with General/meet select + new ui/textarea primitive matching Input styling),
  announcement.created/updated/published/unpublished/deleted audits; public:
  shared PublicAnnouncements component (renders nothing when empty) — portal
  home latest 5 across meets with meet label, public meet page its own published
  only; drafts invisible everywhere public; sidebar Announcements in
  managerNavItems; matrix +6 rows (66 forbidden actions × 2 roles);
  docs/announcements.md + public-portal/authorization docs; Pest 543/543 (6 new
  tests in AnnouncementTest), full gate green; migration applied on pmmsdb)
- WP-04-04 Public Medal Tally — done 2026-07-19 (public tally page
  /meets/{meet}/tally (public.tally, guest+throttled, Meet::published() 404 gate);
  MedalTallyService reused UNCHANGED — derived at read time from validated
  results only, ties share medals, conventional ordering — so public and internal
  tallies can never disagree and corrections ripple automatically; school +
  district standings tables; sport filter via shared validatedSportOptions()
  helper (extracted, reused by results page); PublicMeetNav extended Schedule ↔
  Results ↔ Medal tally; page states validated-only + tie behavior; no new
  tables; docs/public-portal.md updated; Pest 525/525 (4 new tests in
  PublicTallyTest incl. encoded-excluded, other-meet-excluded, tie-sharing,
  ordering — one test expectation fixed: at equal gold/silver more bronze ranks
  higher), full gate green) — Phase 4 Visual Checkpoint 2 achieved (validated
  results + live tally public; unvalidated/unpublished invisible end-to-end)
- WP-04-03 Public Results — done 2026-07-19 (public results page
  /meets/{meet}/results (public.results, guest+throttled, Meet::published() 404
  gate); validated-only enforced STRUCTURALLY (query status filter) so a
  corrected/reopened result vanishes automatically — tested end-to-end through
  the real correction endpoint; per-event standings: rank (ties marked), athlete
  full name, school, mark ONLY — prop privacy asserted with missing() on
  entry/entry_id/status/encoded_by/validated_by; "Official as of" = validation
  timestamp without validator identity; sport filter (options from the meet's
  validated results), newest-validated first; PublicMeetNav shared component
  links Schedule ↔ Results on the meet pages; meetSummary() helper extracted in
  PortalController; no new tables; docs/public-portal.md updated;
  Pest 521/521 (7 new tests in PublicResultsTest), full gate green)
- WP-04-02 Public Schedule & Venue Guide — done 2026-07-19 (public meet page
  /meets/{meet} (route public.meet, whereNumber, in throttled guest group)
  resolved via Meet::published() so unpublished 404 incl. by direct URL; schedule
  for a selected day grouped by venue (venues alphabetical, slots by start time),
  chip day-selector defaulting to today-if-scheduled else first day, slot note
  shown (session info — public per WP), events labeled sport—name (gender,
  division); venue guide = distinct scheduled venues name+address ONLY (internal
  venues.notes never serialized — missing() tested); portal home cards now link
  to the meet page; empty "Schedule not yet available" state; no new tables;
  docs/public-portal.md updated; Pest 514/514 (6 new tests in
  PublicScheduleTest), full gate green) — Phase 4 Visual Checkpoint 1 achieved
  (guest browses a published meet's schedule by day/venue on a phone)
- WP-04-01 Public Portal Foundation & Publication Controls — done 2026-07-19
  (meets.is_published flag (not mass assignable) + Meet::published() scope as the
  shared public-visibility enforcement point; publish/unpublish manager endpoints
  audited meet.published/unpublished, draft meets refused; internal meets page
  Publish/Unpublish ConfirmDialogs + "Public" badge; PublicLayout (guest, no
  sidebar, mobile-first header/footer) wired via app.tsx public/* switch; portal
  home at / (PortalController, public-safe props only, replaces starter welcome
  page — route name kept 'home' for existing auth-layout references, later portal
  routes will be public.*) listing published meets, throttle:60,1; matrix +2 rows
  (60 forbidden actions × 2 roles); docs/public-portal.md privacy baseline +
  meets.md/authorization.md updates; Pest 508/508, full gate green; migration
  applied on pmmsdb)

## Phase 3 — Provincial Meet Operations (complete)
COMPLETE 2026-07-19. All 11 WPs executed one at a time on owner instruction,
then committed per-WP on main and pushed (9918b3a..a73b657). Review: docs/phases/phase-03-provincial-meet-operations/
phase-3-compliance-review.md (COMPLIANT, no findings above Low; full gate green:
Pint+PHPStan+Pest 496/496 (2,080 assertions), ESLint+Prettier+tsc+build; 28
migrations Ran on MySQL pmmsdb; all 3 visual checkpoints demonstrable at
http://pmms.app; zero dependencies added all phase). A Provincial Meet is now
operable end-to-end: registration (Phase 2) → scheduling → accreditation/IDs →
matches → validated results → derived medal tally → protests via the single
correction path → incidents → official printables → meet-day dashboard.
Next: owner commit decision (per-WP commits recommended), then Phase 4 planning
(Responsive Public Portal) on instruction.

## Phase 3 Work Package Log
- WP-03-11 Phase 3 Compliance Review — done 2026-07-19 (COMPLIANT, zero remediations
  needed — WP-03-10 had already closed the phase's only gaps; report in docs/phases/
  phase-03-provincial-meet-operations/phase-3-compliance-review.md; final gate green:
  Pint+PHPStan+Pest 496/496 (2,080 assertions), ESLint+Prettier+tsc+build (2,361
  modules); 28 migrations Ran on MySQL pmmsdb; app HTTP 200 at http://pmms.app, all
  3 visual checkpoints demonstrable; zero dependencies added across the phase;
  result-integrity rules verified against DESIGN-NOTES: derived-only tally,
  locked validated results, reason+snapshot corrections, single result-change path)
- WP-03-10 Operations Audit & Authorization Review — done 2026-07-19 (mirrors
  WP-02-11; routes×roles verified against docs/authorization.md — all Phase 3
  mutations confirmed in role:admin,organizer group, protest filing via
  ProtestPolicy, accreditation views via viewRoster, list gates in controllers;
  matrix completed with the 3 missing WP-03-08 report rows (all-roles) + header
  now "Phase 3 verified WP-03-10"; sweep extended +3 accreditation-view rows
  (unassigned officer + viewer 403) → 58 forbidden actions × 2 roles = 116
  combinations; gap tests added: viewer cannot file protests, Phase 3 action
  families surface in the audit viewer's distinct-action filter; audit catalog in
  docs/audit-trail.md extended with all 8 Phase 3 event families (34 actions) and
  the accepted-deviations list made explicit (photo serving, non-sensitive list/
  report-page reads, validator identity duplicated on the result row by design);
  all Phase 3 state changes verified audited with actor+context, corrections carry
  reason+superseded placements; no enforcement gaps found — doc gap (3 matrix
  rows) + 2 test gaps closed; Pest 496/496, full gate green)
- WP-03-09 Meet Operations Dashboard — done 2026-07-19 (DashboardController extended
  with read-side-only 'operations' prop, null unless a meet is Active; role-aware:
  all roles get today's schedule slots (active meet, today, time/event/venue, linked
  to schedule page + daily sheet) and medal top five (MedalTallyService reused);
  managers additionally get operational-queue StatCards linked into their modules —
  results awaiting validation, open protests (filed+under_review), open incidents,
  accreditation progress accredited/registered; officers get their delegations'
  latest 5 protests for the active meet (queues null); viewers schedule+tally only;
  no new tables/routes/models; responsive sm/lg grid collapse for meet-day mobile
  use; docs/dashboard.md widget table; Pest 488/488 (5 new tests in
  OperationsDashboardTest), full gate green) — Visual Checkpoint 3 achieved
  (protests/incidents, official reports, ops dashboard complete the meet-day demo)
- WP-03-08 Operations Reports & Printables — done 2026-07-19 (three reports on the
  WP-02-12 pattern (print CSS + ReportActions + audited fputcsv CSV), all in
  ReportController, no new tables: official result sheet /reports/results/{result}
  (validated only — 404 for encoded incl. download; validator identity + date;
  linked "Sheet" on each validated result card, all roles), medal tally report
  /reports/tally (school + district sections, meet/sport filters carried from the
  tally page "Printable report" action, single CSV with Type column), daily
  schedule sheet /reports/schedule (?date=Y-m-d, defaults today, grouped by venue
  sorted by name then start time, date input on sheet, linked "Daily sheet" from
  schedule page); CSVs audited report.result_sheet_exported/tally_exported/
  schedule_exported; docs/reports.md extended (6-report table);
  Pest 483/483 (8 new tests in OperationsReportTest), full gate green)
- WP-03-07 Protests & Incident Monitoring — done 2026-07-19 (protests table:
  delegation + exactly one of event_result_id/match_id (nullOnDelete), target must
  belong to delegation's own meet, grounds; ProtestStatus filed→under_review→
  upheld|dismissed (decisions terminal); filing via ProtestPolicy (officers own
  delegation only, managers any), review/decide manager-routes with remarks
  required + decider identity; upheld ≠ result change — protests page "Correct
  result" dialog pre-fills reason "Protest #N upheld: remarks" and PATCHes the
  existing results.correct endpoint (single result-change path preserved, tested
  end-to-end); protest.filed/under_review/upheld/dismissed audits; incidents table:
  meet + optional venue, severity minor|moderate|serious, medical_referral FLAG
  only (never medical details), open⇄resolved lifecycle, whole module (incl. list)
  manager-gated, incident.reported/updated/resolved/reopened/deleted audits;
  /protests page (viewers 403, officers own-only) + /incidents page with status/
  meet filters; sidebar: Protests all roles, Incidents in new managerNavItems
  (admin+organizer); matrix +8 rows (107 forbidden combos); docs/protests.md;
  Pest 475/475, full gate green; migration applied on pmmsdb)
- WP-03-06 Medal Tally & Rankings — done 2026-07-19 (MedalTallyService derives
  standings at READ TIME from validated results only — no stored tally table, so
  corrections ripple automatically (validated→corrected reopens to encoded and its
  medals vanish); rank 1/2/3 → G/S/B, ranks >3 ignored, ties share medals (each
  tied placement counts), conventional ordering gold→silver→bronze→name with
  1-based positions; per-school + per-district standings, meet + sport filters
  (sport via entry→event→sport_id); /tally page readable by every authenticated
  role (aggregates, non-sensitive, no audit needed), no new tables/migrations;
  matrix +1 row (tally all roles ✓); docs/medal-tally.md; Pest 445/445 (7 new tally
  tests incl. correction-ripple end-to-end), full gate green) — Visual Checkpoint 2
  achieved (encode→validate→live tally demonstrable end-to-end at http://pmms.app)
- WP-03-05 Results Encoding & Validation — done 2026-07-19 (integrity core:
  event_results one per meet+event (ResultStatus encoded→validated, encoder+
  validator identity/time) + result_placements (rank, mark, is_tie; entry restrict
  + entry-delete guard extended); encoding manager-only while meet Active, event-in-
  meet, placements = confirmed entries of that meet+event only, duplicate ranks only
  when all flagged tie, one rank per entry; validation = second explicit manager
  decision, locks the result (no edit/delete); correction requires reason, reopens
  to encoded clearing validation, audit result.corrected preserves superseded
  placements (per DESIGN-NOTES: never silent); visibility: validated readable by all
  roles, encoded manager-only (index filters); /results page with placement-row
  editor dialog, Validate/Correct/Delete flows; audit result.encoded (with
  snapshot)/validated/corrected/deleted; matrix +5 rows (99 forbidden combos);
  docs/results.md; Pest 434/434, full gate green; migration applied on pmmsdb)
- WP-03-04 Tournament & Match Management — done 2026-07-19 (matches table (model
  EventMatch — "Match" is a PHP reserved word): meet cascade + event restrict +
  optional event_schedule_id nullOnDelete validated same-meet+event, round_label +
  sequence, MatchStatus enum scheduled→completed|walkover|cancelled (terminal states
  locked); match_entries pivot unique per match+entry, entry restrict-on-delete +
  EntryController destroy guard ("took part in a match"); participants sync
  manager-only, server-enforced: confirmed entries of same event+meet only,
  scheduled-only edits, team events one entry per school; match.* audit incl.
  status_changed from/to + participants_updated; /matches page mirrors entry
  visibility (viewers 403, officers own-delegation matches only) with meet/event
  filters, dependent-select dialog, participants checkbox dialog, transition
  ConfirmDialogs; matrix +5 rows (94 forbidden combos); docs/matches.md;
  Pest 408/408, full gate green; migrations applied on pmmsdb)
- WP-03-03 Accreditation & ID Printing — done 2026-07-19 (accreditations table:
  delegation FK + exactly one of athlete_id/personnel_id (unique, cascade), derived
  unique number ACR-{meet}-{id}, accredited_by/at; presence-of-row = accredited,
  revoke deletes (re-accredit issues new number); gate server-enforced: delegation
  approved + (athletes) approved eligibility review; grant/revoke manager-only +
  audited accreditation.granted/revoked; per-delegation view at
  /delegations/{id}/accreditation (viewRoster-scoped: managers + assigned officers,
  can_accredit flags eligible-not-yet-accredited, linked "IDs" on delegations page);
  printable ID cards (print CSS, no PDF lib, photo/initials via existing photo
  endpoints) single + batch, audited card_viewed/cards_viewed as sensitive views;
  AccreditationFactory (+forPersonnel), matrix +2 rows, docs/accreditation.md;
  Pest 381/381, full gate green; migration applied on pmmsdb) — Visual Checkpoint 1
  achieved (venues + schedule browsable, ID cards printable at http://pmms.app)
- WP-03-02 Event Scheduling & Venue Assignment — done 2026-07-19 (event_schedules
  table (meet cascade, event/venue restrict — venue FK activates WP-03-01 guard:
  Venue::isInUse() now checks schedules), slots = date + H:i:s-normalized start/end +
  note, multiple slots per event; rules server-enforced: event-in-meet, meet must be
  registration-closed/active for create/update/delete, same-venue same-day overlap
  blocked naming the conflict (back-to-back allowed), archived venues rejected;
  schedule.* audit; /schedule page all roles (filters: meet/venue/day + event-name
  search) with manager dialog (dependent meet→event selects); MeetFactory
  registrationClosed()/active() states; AuthorizationMatrixTest +3 rows;
  docs/scheduling.md + venues/authorization docs updated; Pest 363/363, full gate
  green; migration applied on pmmsdb)
- WP-03-01 Venue Registry — done 2026-07-19 (venues table (name unique, optional
  address/notes, active), model/factory, VenueRequest, CRUD+archive/restore controller
  with venue.* audit, role-gated routes, registry page (search over name/address,
  pagination, dialog form) + sidebar entry after Meets, delete guard hook
  Venue::isInUse() stubbed for WP-03-02 schedules, AuthorizationMatrixTest +5 venue
  rows, docs/venues.md + authorization matrix row; Pest 338/338, full gate green:
  Pint+PHPStan+ESLint+Prettier+tsc+build; migration applied on pmmsdb)

## Phase 2 — Meet Setup & Registration (complete)
COMPLETE 2026-07-18, all 13 WPs committed per-WP and pushed (main @ 8c76e2d).
Review: docs/phases/phase-02-meet-setup-and-registration/phase-2-compliance-review.md
(COMPLIANT; full gate green: Pint+PHPStan+Pest 317/317, ESLint+Prettier+tsc+build).

## Phase 2 Work Package Log
- WP-02-01 Roles & Permissions Foundation — done 2026-07-18 (UserRole enum + users.role,
  gates administer/manage-meet-data, role middleware, Inertia 403 page, AdminUserSeeder,
  docs/authorization.md; Pest 69/69, full gate green; role migration applied on pmmsdb)
- WP-02-02 Organization & School Registry — done 2026-07-18 (districts+schools tables,
  models/factories, CRUD+archive/restore controllers with audit, role-gated routes,
  registry pages + sidebar nav, SampleRegistrySeeder local-only, docs/registry.md;
  Pest 93/93, full gate green; migrations applied on pmmsdb)
- WP-02-03 Sports & Events Catalog — done 2026-07-18 (sports+events tables with
  gender/age-division/team/entry-cap config, GenderCategory+AgeDivision enums, CRUD+
  archive controllers with audit, catalog pages + sidebar nav, SportsCatalogSeeder
  (14 sports + 16 athletics events, real reference config), docs/sports-catalog.md;
  Pest 118/118, full gate green; migrations applied + catalog seeded on pmmsdb)
- WP-02-04 Meet Setup & Lifecycle — done 2026-07-18 (meets table + meet_events pivot,
  MeetStatus enum as single source of truth for guarded transitions (with closed→reopen
  exception), status/events/delete endpoints with audit, meets page with transition
  ConfirmDialogs + event checklist dialog, dashboard current-meet card,
  isRegistrationOpen() hook for WP-02-05/08, event-delete guard added,
  docs/meets.md; Pest 137/137, full gate green; migrations applied on pmmsdb)
- WP-02-05 Delegation Registration — done 2026-07-18 (delegations table unique per
  school+meet + delegation_user pivot, DelegationPolicy (first per-record scoping:
  officers manage only their own, window-enforced via isRegistrationOpen), draft→
  submitted→approved flow with return, officer assignment role-validated, per-row can_*
  flags drive the UI, school-delete guard fulfilled, docs/delegations.md;
  Pest 158/158, full gate green; migrations applied on pmmsdb)
- WP-02-06 Athlete Registry — done 2026-07-18 (athletes table minor-safe minimal fields
  + optional photo via FileUploadService, AthletePolicy: viewers excluded entirely,
  officers scoped to own editable delegation, every profile view audited
  (athlete.viewed), photo served by athlete-visibility not upload ownership, first
  searchable+paginated registry (LRN/birthdate only on audited show page),
  delegation-delete guard, docs/athletes.md; Pest 179/179, full gate green;
  migration applied on pmmsdb)
- WP-02-07 Coach & Official Registry — done 2026-07-18 (personnel table (explicit
  $table, Eloquent would pluralize wrong) + personnel_sport pivot, PersonnelRole enum
  with coaches() rule, athlete-style PersonnelPolicy scoping, sport assignment for
  coaching roles only (cleared on demotion), photo lifecycle via FileUploadService,
  searchable+paginated page with edit dialog (_method put spoof for uploads),
  delegation-delete guard extended, docs/personnel.md; Pest 196/196, full gate green;
  migrations applied on pmmsdb)
- WP-02-08 Event Entry Submission — done 2026-07-18 (entries table unique per
  athlete+event, delegation always derived server-side from the athlete, full rule set:
  event-in-meet, sex/gender match (GenderCategory::accepts), grade-derived age division
  (Athlete::ageDivision, grades 1-6/7-12 — age-based cutoffs deferred as policy), no
  duplicates, cap counts non-withdrawn only, officer window enforcement (managers
  bypass; delegation draft NOT required — rosters freeze, entries don't);
  submitted→confirmed|withdrawn flow, withdrawn deletable to free the slot; filterable
  entries page with dependent athlete→event selects; docs/entries.md; Pest 211/211,
  full gate green; migration applied on pmmsdb) — Visual Checkpoint 2 achieved
- WP-02-09 Eligibility Documents & Manual Review — done 2026-07-18
  (eligibility_documents via FileUploadService (pdf/jpg/png ≤10MB, typed) +
  eligibility_reviews unique per athlete+meet (pending→approved|returned; approved
  terminal), upload creates/reopens review (resubmission clears decision), return
  requires remarks, decisions manager-only + human-only, every document view audited,
  entries page flags (not blocks) unapproved eligibility, status-filterable queue with
  pending-first sort, docs/eligibility.md; Pest 225/225, full gate green;
  migrations applied on pmmsdb)
- WP-02-10 Registration Views & Search — done 2026-07-18 (SearchesAndPaginates trait
  (LIKE over plain or relation.column, 15/page, withQueryString) across all 9 registry
  controllers incl. entries, SearchBar + PaginationControls shared components on all
  list pages, dashboard stats real counts (6 StatCards), tests updated to paginator
  shape + new search/pagination/count tests, component-library.md updated;
  Pest 230/230, full gate green)
- WP-02-11 Audit & Authorization Integration Review — done 2026-07-18 (authorization
  matrix documented in docs/authorization.md and swept by AuthorizationMatrixTest —
  69 forbidden role×action cases all 403; audit gap closed: file.downloaded on upload
  downloads; admin-only audit viewer at /audit-logs (can:administer, search + action
  filter on shared components, sidebar item for admins); photo serving documented as
  deliberately unaudited; audit-trail.md event catalog completed; Pest 307/307,
  full gate green)
- WP-02-12 Rosters & Printable Lists — done 2026-07-18 (ReportController: delegation
  roster (DelegationPolicy::viewRoster — managers + assigned officers), per-event
  entry list (officer-scoped, withdrawn excluded), school participation summary
  (aggregates, all roles, meet filter); print via @media print CSS hiding app chrome
  (no PDF lib); CSV via fputcsv streams, all three audited report.*_exported;
  ReportActions shared component; linked from delegations/entries/schools pages;
  School hasManyThrough counts; docs/reports.md; Pest 317/317, full gate green)
- WP-02-13 Phase 2 Compliance Review — done 2026-07-18 (COMPLIANT; DatabaseSeeder
  fake Test User env-gated (only remediation), migrations 22/22 Ran on MySQL pmmsdb,
  all 3 visual checkpoints demonstrable at http://pmms.app, zero dependencies added
  all phase; report in docs/phases/phase-02-meet-setup-and-registration/
  phase-2-compliance-review.md; full gate green: Pint+PHPStan+Pest 317/317 (1,183
  assertions), ESLint+Prettier+tsc+build)

## Phase 1 — Engineering Foundation (complete)
Execute one work package at a time.

## Work Package Log
- WP-01-01 Repository & Framework Baseline — done 2026-07-18 (baseline in engineering-baseline.md)
- WP-01-02 Backend Quality Verification — done 2026-07-18 (PHPStan L7 pass, Pest 39/39, Pint: 12 pre-existing EOF violations documented)
- WP-01-03 Frontend Quality Verification — done 2026-07-18 (ESLint pass, Prettier pass, tsc strict pass, build pass)
- WP-01-04 Authentication Baseline — done 2026-07-18 (30/30 auth tests pass, all Fortify/2FA/passkey routes present, features recorded)
- WP-01-05 Engineering Documentation — done 2026-07-18 (root README.md created: stack, setup, quality gates, structure, workflow)
- WP-01-06 Environment & Secret Hygiene — done 2026-07-18 (no secrets tracked/in history; sqlite→mysql gap documented; .env.example unchanged by design)
- WP-01-07 Git Workflow & Repository Governance — done 2026-07-18 (branch/commit conventions + CI target documented; no CI file created; .github/.claude deletions flagged for owner decision)
- WP-01-08 UI Foundation — done 2026-07-18 (PMMS rebrand: logo/icon/favicon, sidebar+header cleanup, new welcome page, APP_NAME=PMMS; all checks + build pass)
- WP-01-09 Shared Component Library — done 2026-07-18 (ui/table primitive + PageHeader/EmptyState/ConfirmDialog; docs/component-library.md; all frontend checks pass)
- WP-01-10 File Upload Foundation — done 2026-07-18 (file_uploads migration, FileUpload model+factory+policy, FileUploadService, controller+routes, config/uploads.php, 8 tests; migrated on MySQL pmmsdb)
- WP-01-11 Audit Trail Foundation — done 2026-07-18 (audit_logs migration, AuditLog model+factory, AuditLogger service, login/logout listeners, file upload/delete auditing, 6 tests)
- WP-01-12 Dashboard Framework — done 2026-07-18 (DashboardController with stats+recentActivity, StatCard component, dashboard page rebuilt on shared components, Inertia prop tests; all checks + build pass)
- WP-01-13 Architecture Compliance Review — done 2026-07-18 (COMPLIANT; EOF violations remediated, full gate green: Pint+PHPStan+Pest 54/54, ESLint+Prettier+tsc+build; report in docs/phases/phase-01-engineering-foundation/architecture-compliance-review.md)

Phase 1 — Engineering Foundation: COMPLETE (pending owner review). Nothing committed or pushed.

## Readiness Re-Verification — 2026-07-18
Independent Phase 1 readiness check re-ran the full gate: Pint PASS, PHPStan L7 PASS,
Pest 54/54 PASS, ESLint/Prettier/tsc PASS, `npm run build` PASS, app live at
http://pmms.app (Laragon). Result: Ready with Constraints; WP-01-01 verified as already
complete. Report: docs/reports/phase-01/phase-1-readiness-report.md

# Phase 6 — Reports, UAT, Deployment, and Turnover
Planned 2026-07-26, all 9 WPs executed one at a time on owner instruction,
**COMPLETE 2026-07-27** — COMPLIANT compliance review:
docs/phases/phase-06-reports-uat-deployment-turnover/phase-6-compliance-review.md
(no Critical/High/Medium findings open; one Medium security finding found
*and fixed* during WP-06-03, proven by test; every other item is a
deliberate, documented deferral — City's "district competes" registration
option, no `.xlsx` export, Reverb off in production/polling-only, outgoing
production email not yet configured, one low-priority theoretical
performance observation — none silently dropped). Diff against `main`
confirms this phase is entirely verification/documentation/ops-tooling as
scoped: the only application-code change across all 9 WPs is WP-06-03's
scoped `ThrottleRegistration` middleware fix (+ its test); zero new
migrations. Full gate green: Pint+PHPStan+Pest 650/650 (3,245 assertions)+
ESLint+Prettier+tsc+build; composer audit + npm audit both clean; app HTTP
200 at http://pmms.app; database confirmed clean of every WP's test/
benchmark data (re-verified directly against `pmmsdb`, not assumed).
Replaces the
unreviewed generic-template draft that occupied
`docs/phases/phase-06-reports-uat-deployment-turnover/` (same recurring
scaffolding mixup Phase 4/5/7 each found and corrected before planning off it):
it assumed the delegation was still the reporting attribution unit and that
reports/CSV export/a compliance-review cadence didn't exist yet — all false as
of 2026-07-26 (Division initiative WP1-7 re-keyed attribution to the individual's
own school; `docs/reports.md`/`ReportController` already ship six reports with
print + CSV export; Phase 3/4/5/7 each closed with their own compliance review).

Scoped per owner decision 2026-07-26 (asked via four targeted questions, same
approach as Phase 7's scoping rounds):
- Production deployment target: the same local Laragon setup (http://pmms.app)
  — harden what's running, no new cloud/VPS infra.
- Excel `.xlsx` export: not needed — existing CSV export is sufficient, dropped
  from scope entirely (not deferred).
- Database backup: none exists today for `pmmsdb` — build one from scratch.
- UAT: prepare materials only (scripts, checklists, feedback template) — no real
  testers/timeline exist yet, so execution is out of scope for a coding session
  and left as real-world future work.

Nine WPs (leaner than the old 12-WP draft — Excel export, UAT execution, and
pilot deployment/issue resolution are all dropped as either redundant or
dependent on real-world state a coding session can't produce): WP-06-01 Reports
& Print Verification (verify the six existing reports post-Division-initiative,
not rebuild) → WP-06-02 Backup & Restore Baseline (new `mysqldump`-based
backup/restore for `pmmsdb`, proven end-to-end by actually restoring a dump) →
WP-06-03 Security & Privacy Review (re-verify `AuthorizationMatrixTest`/
`AuditLogger`/`composer audit`/`npm audit`, review minor-athlete PII exposure
paths and WP-07-08's public scoreboard routes) → WP-06-04 Performance & Load
Verification (N+1/query review at realistic single-Division scale, not a
synthetic load-testing framework) → WP-06-05 Administrator & User Manuals
(role-based manuals under `docs/manuals/`, written from the real UI) →
WP-06-06 UAT Preparation Materials (`docs/uat/` scripts + feedback template,
runnable against `SampleProvinceDemoSeeder` data, execution deferred) →
WP-06-07 Production Readiness & Deployment Hardening (production `.env`
template, and — the one real functional gap found during planning — a queue
worker needs to actually run continuously in production since
`QUEUE_CONNECTION=database` means Phase 7's `ScoreUpdated` broadcast currently
never fires without one) → WP-06-08 Training & Turnover Package (`docs/
turnover.md` + training outline, building on WP-06-05/07 rather than
duplicating them) → WP-06-09 Phase 6 Compliance Review & Acceptance (closes the
phase the same way Phase 3/4/5/7 did).

Plan: `docs/phases/phase-06-reports-uat-deployment-turnover/` (README +
DESIGN-NOTES + CHECKLIST + WP-06-01..09), written fresh for this codebase.
Execute one work package at a time on owner instruction; nothing committed or
pushed.

## Phase 6 Work Package Log
- WP-06-01 Reports & Print Verification — done 2026-07-26 (verification pass,
  no defects found, no code changes needed. Walked all six reports
  (`ReportController` + `docs/reports.md`'s inventory: delegation roster,
  per-event entry list, school participation summary, official result sheet,
  medal tally, daily schedule) against the app as it stands after the Division
  initiative and Phase 7 — confirmed `athlete.school`/
  `Delegation::registrantName()`/`Division::current()->areaLabel()` are used
  correctly everywhere with no stale delegation-as-school-proxy references,
  all six pages consistently use the shared `ReportActions` component (print +
  CSV, `print:hidden`) so print/export behavior is uniform, the medal tally
  report correctly reflects the 2026-07-25 post-Division district-first
  ordering refinement, and live scoring (Phase 7) correctly has no report of
  its own (grepped `docs/live-scoring.md` — zero report references, consistent
  with a live session being provisional by design). Noted but out of this WP's
  scope: `reports/management` (Phase 5's Executive/Management Dashboards
  report) uses the same `ReportActions` pattern but was never part of the
  WP-02-12/WP-03-08 inventory `docs/reports.md` documents. Confirmed CSV
  remains the only export format per the owner's WP-06-02-drop decision — no
  `.xlsx` gap. `docs/reports.md` updated with a "Verified 2026 — Phase 6"
  note. Pest 649/649 (no new tests — this WP is verification, not new
  behavior; `ReportTest` 18/18 and `OperationsReportTest` 8/8 specifically
  re-run and confirmed still passing), full gate green:
  Pint+PHPStan+ESLint+Prettier+tsc+build; not committed/pushed) — next:
  WP-06-02 Backup & Restore Baseline, on owner instruction.
- WP-06-02 Backup & Restore Baseline — done 2026-07-26 (new operational
  tooling — none of this existed before. `scripts/backup-database.ps1`
  (mysqldump `--single-transaction --routines --triggers`, credentials read
  from `.env` at runtime into a short-lived ACL-restricted defaults-extra-file
  never logged/echoed/passed on the command line, timestamped gzip output to
  `storage/app/private/backups/database/` — Laravel's non-web-accessible
  local disk root, confirmed against `config/filesystems.php` — with a
  keep-newest-N retention default of 14) and `scripts/restore-database.ps1`
  (creates the target DB, transparently decompresses `.sql.gz`, refuses to
  target the production `DB_DATABASE` name from `.env` unless `-Force` is
  passed, so a routine drill can't overwrite live data). Hit and fixed a real
  Windows PowerShell 5.1 gotcha along the way: `$PSScriptRoot` is not
  populated inside `param()` default-value expressions on that PS version —
  moved path resolution into the script body instead (`$PSScriptRoot` falling
  back to `$MyInvocation.MyCommand.Path`'s directory), confirmed via a minimal
  repro before fixing both scripts. **Proved backup->restore end-to-end
  against the real `pmmsdb`, not just scripted**: took a real backup (39
  tables), recorded a baseline directly from production (districts row count
  + current `divisions.name`), restored into a throwaway `pmmsdb_restore_test`
  database, confirmed both facts matched exactly plus the same 39-table
  schema, then dropped the throwaway database — only the one real backup file
  was left behind. `scripts/install-backup-schedule.ps1` (Windows Task
  Scheduler registration via `Register-ScheduledTask`, daily) was written but
  deliberately **not executed** this session — registering a scheduled task
  changes real host state outside git, so per the WP's own scope it's left as
  a script for whoever administers the production server to run themselves,
  documented in `docs/backup-restore.md`. New `docs/backup-restore.md`
  (what's backed up, how, where, the end-to-end proof, retention
  recommendation — 14 daily, no offsite/cloud since the deployment target is
  local-only per the owner's WP-06 scoping decision, scheduling, and a PII
  access-control note cross-referenced to WP-06-03). `storage/app/private/
  backups` added to `.gitignore`. No application code touched — this WP lives
  entirely in `scripts/` + docs, outside the Pest suite by design; re-ran the
  full existing gate to confirm nothing regressed: Pest 649/649 (no new
  tests — not app behavior), Pint+PHPStan+ESLint+Prettier+tsc+build all
  green; not committed/pushed) — next: WP-06-03 Security & Privacy Review, on
  owner instruction.
- WP-06-03 Security & Privacy Review — done 2026-07-26 (COMPLIANT, one
  Medium finding found and fixed during the review — full report
  `docs/phases/phase-06-reports-uat-deployment-turnover/
  phase-6-security-review.md`. `composer audit`/`npm audit --omit=dev` both
  clean (unchanged since Phase 7). Re-verified `AuthorizationMatrixTest`
  coverage against `docs/authorization.md`'s matrix by direct inspection,
  not just re-running it: confirmed WP-07-08's public scoreboard routes are
  correctly covered by the existing blanket "Public portal" row (same
  convention every other public page already follows, no page gets its own
  row) and WP-06-02's backup directory (`storage/app/private/backups/
  database/`) is unreachable via any web route (only `storage/app/public`
  is symlinked). Swept minor-athlete/guardian PII across every public page
  (`PortalController`) and all six internal reports against
  `docs/public-portal.md`'s "name+school+placement only" public rule and
  `docs/reports.md`'s documented field lists — no undocumented leak found
  on either side; the live scoreboard in particular carries zero athlete
  fields by schema design, confirmed by inspection not just the docs'
  claim. Reviewed `FileUploadService`/`FileUploadPolicy` (extension
  allow-list validated against real detected content, hashed storage
  names, forced-attachment download) and confirmed `EligibilityController`
  correctly does NOT route eligibility documents through the generic
  uploader-only `FileUploadPolicy` — it authorizes via
  `EligibilityReviewPolicy::view` instead (so a manager can review a
  document an officer uploaded), a deliberate separation, not a
  contradiction. Session/CSRF/Fortify posture reviewed: no CSRF
  exceptions, no API/CORS surface to review, login/2FA/passkeys all
  correctly rate-limited; `SESSION_SECURE_COOKIE` correctly flagged as a
  `.env` production value and left for WP-06-07 rather than duplicated
  here, per this WP's own scope note. **Finding (Medium, fixed inline):**
  Fortify's `POST /register` had no rate limiter at all — unlike
  login/2FA/passkeys, and Fortify exposes no `limiters.registration` hook
  to attach one declaratively since the route lives entirely inside the
  package. Severity Medium not High: `User`'s `#[Fillable(...)]` excludes
  `role`, so self-registration can never produce anything above the
  `viewer` default; not Low because `viewer` already reads delegation
  lists/schedules/reports, and unlimited free registration is a real
  mail-bombing/spam-account vector regardless. Fixed with a small new
  `App\Http\Middleware\ThrottleRegistration` appended to the global `web`
  group — a no-op for every route except `register.store` (matched via
  `$request->routeIs()`, evaluated post-routing so it's independent of
  Fortify's own route-registration timing), enforcing the same
  5-per-minute-per-IP bar `login` already uses via direct
  `RateLimiter::tooManyAttempts()`/`hit()` calls rather than a named
  Fortify limiter (not attachable to a package-internal route
  declaratively — confirmed the hard way: attempting to attach middleware
  to Fortify's route object via `Route::getRoutes()->getByName(...)`
  inside an `app()->booted()` callback reliably found `null`, since
  Fortify's own route registration timing relative to other providers'
  `booted()`-deferred hooks — e.g. the framework's own `AppRouteServiceProvider`
  for `routes/web.php` — turned out not to be the simple "boot() runs
  synchronously" story its source suggests; the route-name-matching
  middleware sidesteps that entirely by running at actual request time,
  after all routing is unambiguously complete). New test:
  `'registration is rate limited (WP-06-03)'` in
  `tests/Feature/Auth/RegistrationTest.php`. No other findings. Pest
  650/650 (1 new test), full gate green: Pint+PHPStan+ESLint+Prettier+
  tsc+build; not committed/pushed) — next: WP-06-04 Performance & Load
  Verification, on owner instruction.
- WP-06-04 Performance & Load Verification — done 2026-07-26 (no N+1 or
  missing-index issues found, no code changes needed — full report
  `docs/performance.md`. New `database/seeders/PerformanceBenchmarkSeeder.php`
  (not registered in `DatabaseSeeder`'s default chain, run on demand only)
  generates a realistic full-scale dataset distinct from
  `SampleProvinceDemoSeeder`'s deliberately-small 3-athlete demo: unlike
  every other `Sample*` seeder it attaches delegations to the real 11
  Davao de Oro municipalities (`DivisionRegistrySeeder`) rather than
  inventing its own, since the whole point is realistic query shape/volume
  against what this deployment will actually have. First run picked up all
  15 *active* districts (11 real + 4 from other Sample seeders, which are
  also `active`) instead of just the 11 real ones — a real bug, caught by
  checking counts after seeding rather than trusting the code; fixed with
  an explicit `where('name', 'not like', 'Sample %')` filter, re-verified
  correct on re-seed (11 delegations, 88 schools, 1,320 athletes, 2,640
  entries, 80 medal placements across 8 events). Profiled 19 page/action
  paths (medal tally, dashboard, management dashboard, all six WP-06-01
  reports, schools/delegations/entries/athletes index pages) by calling
  each controller directly as an authenticated admin with
  `DB::enableQueryLog()` around the call — chosen over adding
  `barryvdh/laravel-debugbar` as a new one-time dependency, per the WP's
  own preference for manual query-log inspection. Every page's query count
  is small and **bounded** — proven not just asserted: the entries index
  stayed at exactly 19 queries across no-filter, search-term, and page-depth
  variations from page 1 through page 175 (the last page at the fixed
  15-per-row `SearchesAndPaginates` size), confirming pagination genuinely
  limits the query via `LIMIT`/`OFFSET` rather than loading the full table.
  Verified every FK the profiled queries filter/join on already carries an
  index by reading the migrations directly (entries' delegation_id/
  athlete_id/event_id plus an explicit composite (delegation_id, event_id);
  result_placements' composite (event_result_id, rank); etc.) — no missing
  index found. One theoretical (not actual) observation recorded but not
  acted on: `MedalTallyService::standings()`'s `rank IN (1,2,3)` filter
  doesn't lean on the existing composite index's leading column, but at
  this deployment's actual scale (hundreds of placements per meet, a
  handful of meets) that's sub-millisecond regardless — flagged in
  docs/performance.md as only worth revisiting if a future phase adds
  bulk multi-season aggregation. Benchmark data (1,320 synthetic athletes)
  was deliberately removed from pmmsdb after profiling so it doesn't linger
  for WP-06-06's UAT materials or ordinary local use — the seeder remains
  available to re-run on demand, documented in docs/performance.md along
  with cleanup steps. Pest 650/650 (no new tests — this WP is a query
  profiling pass, not new application behavior), full gate green:
  Pint+PHPStan+ESLint+Prettier+tsc all green, build not re-run since no
  frontend file changed this WP (verified green at WP-06-03's close);
  not committed/pushed) — next: WP-06-05 Administrator & User Manuals,
  on owner instruction.
- WP-06-05 Administrator & User Manuals — done 2026-07-26 (pure
  documentation, no code touched — new `docs/manuals/` (five files:
  `admin-manual.md`, `organizer-manual.md`, `delegation-officer-manual.md`,
  `viewer-manual.md`, `public-portal-guide.md`), written from the real
  running app rather than from memory: read every relevant `docs/*.md`
  (division, registry, sports-catalog, meets, venues, scheduling,
  delegations, athletes, personnel, entries, eligibility, accreditation,
  matches, results, protests, medal-tally, live-scoring, announcements,
  dashboard, management-dashboard, public-portal, authorization,
  audit-trail, backup-restore), the full `php artisan route:list`, the
  sidebar's actual nav labels/role-gating (`app-sidebar.tsx` — confirmed
  the dynamic "Districts"→areaLabel pluralization and that
  `managerNavItems`/`adminNavItems` are the only role-filtered groups,
  everything else renders for every signed-in role regardless of access),
  and spot-checked real dialog/button copy in the page components
  (`grep`'d `DialogTitle`s across delegations/athletes/entries/results/
  matches/protests/personnel/eligibility/incidents) rather than guessing
  wording. One real, load-bearing finding surfaced while writing the
  admin manual's planned "user/role management" section: **PMMS has no
  in-app screen for creating accounts or changing roles at all** — new
  accounts come only from the public self-registration page and always
  start as Viewer (confirmed in WP-06-03), and promoting someone to a
  higher role is only possible via direct `$user->forceFill(['role' =>
  …])` database access, exactly as `docs/authorization.md` already
  states but never spelled out for an end-user audience before. Wrote
  this plainly rather than describing an aspirational "Users" screen
  that doesn't exist, per the WP's own explicit instruction; cross-
  referenced from the officer manual (getting assigned) and viewer
  manual (how a role changes) so the same real limitation is consistent
  everywhere it's relevant instead of contradicting itself across
  manuals. Also documented, because it's real and not obvious from the
  UI alone: the sidebar shows every nav item to every signed-in role
  (only two small groups are role-filtered) — a Viewer sees links to
  Athletes/Personnel/Entries/etc. that all 403 on click, which the
  viewer manual now explains upfront so it doesn't read as a bug.
  Manuals are task-oriented (numbered steps, exact navigation labels
  and dialog titles) and cross-reference the technical `docs/*.md`
  files rather than duplicating their detail, per the WP's own
  structure. No screenshots (text + exact labels only, as the WP
  allowed). Pest 650/650 (unchanged — no application behavior touched),
  full gate green: Pint+PHPStan+ESLint+Prettier+tsc all green (build not
  re-run, no frontend file changed); not committed/pushed) — next:
  WP-06-06 UAT Preparation Materials, on owner instruction.
- WP-06-06 UAT Preparation Materials — done 2026-07-26 (new `docs/uat/`:
  `README.md`, `feedback-template.md`, and five per-role scripts (admin,
  organizer, delegation-officer, viewer, public-guest), pure documentation,
  no code touched. Designed as one connected, sequential scenario (create
  a meet → register a delegation → enter athletes → confirm/approve →
  schedule → match → live scoring → encode/validate a result → tally →
  reports), matching the WP's own example, rather than five isolated
  scripts — the Organizer and Delegation Officer scripts share a "UAT Test
  Meet" and hand off to each other at two checkpoints (marked in both
  files and in the README's "Running order"), since a real meet genuinely
  requires that back-and-forth. **Caught and fixed two real errors carried
  over from WP-06-05's manuals while grounding the scripts against the
  actual authorization code** (`routes/web.php`, `DelegationPolicy`,
  `Delegation::isEditableByOfficers()`) rather than trusting the manuals'
  prose — exactly the kind of thing writing a step-by-step script is
  supposed to surface: (1) `POST /delegations` sits inside the
  `role:admin,organizer` route group — a Delegation Officer **cannot**
  register a delegation themselves, only a manager can, after which the
  manager assigns the officer to the already-created record; both
  `docs/manuals/delegation-officer-manual.md` §1–2 and
  `docs/manuals/organizer-manual.md` §5 had this backwards (described the
  officer as self-registering) and are now corrected in place. (2) athlete/
  personnel registration requires the delegation to still be **Draft**
  (`isEditableByOfficers()`) — submitting the delegation first (as an
  early draft of the officer UAT script had it) would have silently locked
  the officer out of registering their roster; fixed by reordering the
  officer script to register the whole roster (athletes, personnel,
  eligibility, entries) before submitting last, matching what the manual
  already correctly said ("submit when your roster is ready") but the
  script itself had gotten backwards. Every dialog title, button label,
  and status-transition name in every script was grepped directly from the
  real page components (`DialogTitle`s, `ConfirmDialog` `confirmLabel`s,
  `MeetStatus`/`MatchStatus::actionLabel()`) rather than guessed or copied
  from the manuals' own prose, so a tester following a script sees exactly
  the words the script told them to look for. Scripts run against a fresh
  `php artisan migrate:fresh --seed` copy (never production) — the
  README's environment-setup section covers the one other real gap this
  surfaces: getting one test account per role ready needs the same
  off-screen role-promotion step as real onboarding (`docs/manuals/
  admin-manual.md` §2), which is itself now the first thing a UAT session
  organizer has to do, documented explicitly rather than assumed. No
  execution performed, per the owner's scoping decision — materials only.
  Pest 650/650 (unchanged), full gate green: Pint+PHPStan+ESLint+Prettier+
  tsc all green (build not re-run, no frontend file changed); not
  committed/pushed) — next: WP-06-07 Production Readiness & Deployment
  Hardening, on owner instruction.
- WP-06-07 Production Readiness & Deployment Hardening — done 2026-07-26.
  Asked the owner the two decisions the WP explicitly flagged rather than
  assuming: (1) Reverb in production vs. polling-only — **polling-only**
  chosen (no second always-on process to supervise; the 5-second poll
  already fully works per Phase 7's design); (2) access topology — **this
  one machine only** (not LAN-exposed to other office computers), which
  settled the HTTPS/TLS question too (traffic never leaves loopback, no
  new TLS work needed — Laragon's existing local HTTPS already covers the
  `APP_URL=https://pmms.app` value); (3) production email — no SMTP
  provider available yet, documented as a real explicit to-do rather than
  invented. New `.env.production.example` (placeholders only, diffed
  against dev `.env.example` with a table explaining every changed key,
  including `SESSION_SECURE_COOKIE=true` — closes the WP-06-03 finding).
  New `scripts/install-queue-worker-schedule.ps1` (same not-run-
  automatically convention as WP-06-02's backup installer) registers a
  Windows scheduled task keeping `php artisan queue:work` running at
  startup with restart-on-failure, closing the real functional gap
  flagged during Phase 6 planning: `QUEUE_CONNECTION=database` +
  Phase 7's queued `ScoreUpdated` broadcast meant every live-scoring score
  change silently piled up in the `jobs` table forever with nothing to
  process it (polling still worked for end users the whole time — this
  was a silent queue-growth issue, never a user-facing break). **Proved
  the fix end-to-end, not just configured it**, per the WP's own explicit
  acceptance criterion: created a real scheduled match + live scoring
  session, submitted a score change through the actual
  `ScoringSessionController::score()` code path, confirmed a job landed in
  `jobs`, ran a real `php artisan queue:work --once` (with
  `BROADCAST_CONNECTION=log`, matching the production decision) and
  confirmed the job processed, `jobs`/`failed_jobs` both returned to 0,
  and `storage/logs/laravel.log` recorded the actual broadcast payload —
  then deleted the test match/session, leaving nothing behind. Validated
  the new PowerShell script's non-mutating logic (php.exe discovery via
  `Get-Command`, repo-root/artisan path resolution) directly against this
  real machine without registering the actual scheduled task — same
  "administrator runs it once, deliberately" boundary WP-06-02 already
  established, not crossed here either. New `docs/deployment.md` ties it
  together: the `.env` table, the queue-worker fix + its proof, the
  broadcast decision and how to reverse it later, the outgoing-email
  to-do, the HTTPS/TLS finding, a build/deploy procedure (confirmed
  `storage:link` is genuinely not needed — grepped the codebase, nothing
  references the public disk, every served file goes through an
  authenticated policy-checked route on the private disk instead), and a
  rollback procedure scoped to this git-based single-server topology
  (prefer restoring a pre-deploy backup over `migrate:rollback`, since
  this project's early migrations aren't all written with safe-rollback
  `down()` methods). No application code changed. Pest 650/650
  (unchanged), full gate green: Pint+PHPStan+ESLint+Prettier+tsc all
  green; app reconfirmed HTTP 200 at http://pmms.app; not committed/
  pushed) — next: WP-06-08 Training & Turnover Package, on owner
  instruction.
- WP-06-08 Training & Turnover Package — done 2026-07-27. New
  `docs/turnover.md` and `docs/training-outline.md`, pure documentation,
  no code touched. **Real finding while inspecting the repo for the
  "architecture map" pointer the WP asked for**: `docs/01-architecture/`
  (and the whole `docs/00-product/` through `docs/11-backlog/` tree) is
  an elaborate pre-implementation planning exercise — 34 bounded
  contexts, 53 roles, a Flutter mobile app, MinIO, Redis, Laravel
  Horizon, AI-assisted duplicate-athlete detection — that does not
  describe the system actually built (single-tenant Laravel modular
  monolith, 4 roles, no Flutter/MinIO/Redis/Horizon/AI, database-backed
  session/cache/queue). Every document in that tree is self-marked
  "Status: Draft Complete — Pending Architecture, Security, and
  Engineering Validation," and `.ai/project-context.md` already says
  explicitly "Enterprise ADRs (`.ai/decisions/`) and `docs/11-backlog/`
  are future-readiness reference only — simplified `.ai` files govern" —
  so this was already a known, deliberately-superseded state, just never
  spelled out for a future maintainer who might otherwise orient
  themselves from it and be badly misled about what this app actually
  is. `docs/turnover.md` flags this explicitly and points instead to
  `.ai/architecture.md`, `.ai/current-phase.md`, and the per-feature
  `docs/*.md` files as the real technical reference — the same
  "stale generic-template" pattern this project has hit repeatedly at
  the docs/phases/ level (Phase 4/5/6/7 each found and discarded a stale
  draft before planning), just discovered here at its original, much
  larger source. `docs/turnover.md` also collects every open/deferred
  item from across the whole project into one place (City's "district
  competes" gap, no `.xlsx` export by decision, Reverb off in production,
  outgoing email not yet configured, the one low-priority WP-06-04
  index observation) and explicitly notes WP-06-03's security findings
  are now fully closed (both items it carried forward were resolved by
  WP-06-07) — rather than leaving anyone to re-discover them. Escalation
  contacts are a deliberate template with `_[fill in]_` placeholders, not
  fabricated names, per the WP's own instruction. `docs/training-outline.md`
  is an agenda (not a slide deck) built entirely on top of already-shipped
  material — `docs/manuals/` (WP-06-05) as what to present, `docs/uat/`
  (WP-06-06) scripts repurposed directly as hands-on exercises, including
  reusing the Organizer/Delegation-Officer scripts' checkpoint handoff as
  a live demonstration of how those two roles actually work together on
  a real meet. Pest 650/650 (unchanged), full gate green: Pint+PHPStan+
  ESLint+Prettier+tsc all green; not committed/pushed) — next: WP-06-09
  Phase 6 Compliance Review & Acceptance, on owner instruction.
- WP-06-09 Phase 6 Compliance Review & Acceptance — done 2026-07-27
  (COMPLIANT, closes Phase 6 — full report `docs/phases/
  phase-06-reports-uat-deployment-turnover/phase-6-compliance-review.md`.
  Verified each of WP-06-01 through WP-06-08's deliverables actually exist
  and match their own log entries (per-WP table in the review) rather
  than trusting the log alone — every manual/script/doc file confirmed
  present on disk. `git diff --stat main` confirms the WP's own explicit
  acceptance criterion: the *only* application-code change across all 9
  WPs is WP-06-03's scoped `ThrottleRegistration` middleware (+2 lines in
  `bootstrap/app.php`, +15 lines of test) — no `app/Models/`,
  `app/Policies/`, `app/Http/Controllers/`, or migration touched beyond
  that one registration line; every other change is docs/scripts/a
  non-default-chain seeder/a `.env` template. Re-verified `pmmsdb`
  directly (not assumed from prior WPs' own claims) is clean of every
  WP's test/benchmark data: only the original "Sample Provincial Meet"
  demo remains, 0 matches, 0 scoring sessions, 0 rows in `jobs`/
  `failed_jobs`, school/athlete counts match the pre-Phase-6 baseline
  exactly, and `migrate:status` shows every migration Ran with zero
  pending — confirms Phase 6 added no schema changes, as scoped. Full
  final gate run: `composer audit`/`npm audit --omit=dev` both clean,
  Pint clean, PHPStan L7 0 errors, Pest 650/650 (3,245 assertions),
  ESLint/Prettier/tsc all clean, `npm run build` succeeded (14.41s), app
  reconfirmed HTTP 200 at http://pmms.app. All 4 of the phase README's
  own Visual Checkpoints confirmed demonstrable. `CHECKLIST.md` all 9
  items checked off; this file's Phase 6 section and top-of-file summary
  both updated to COMPLETE. Not committed/pushed, per project rules —
  the tree is green pending owner instruction.) — this closes Phase 6.
  Next: owner review of the compliance report, then a commit/push
  decision for the Phase 6 tree, then the owner's choice of what comes
  next — Phase 8 (Post-Deployment Support) or a real UAT/pilot session
  using WP-06-06's prepared materials.
- **Phase 6 committed and pushed 2026-07-27** on owner instruction, same
  day as WP-06-09 closed it. Committed as 10 commits following the
  established per-WP convention exactly (one planning commit + one per
  WP-06-01..09): `9251fb1` (docs: plan Phase 6), `7a31b6b` (WP-06-01),
  `2fd80b8` (WP-06-02), `01634f9` (WP-06-03), `70a8e63` (WP-06-04),
  `c25315c` (WP-06-05), `8382f04` (WP-06-06), `1b4d38b` (WP-06-07),
  `d3f2811` (WP-06-08), `0728687` (WP-06-09). Each commit carries only
  the files that WP actually produced (verified via `git status` between
  every commit); `.ai/current-phase.md` and `CHECKLIST.md` — both
  cross-cutting, touched by every WP's log entry — were attributed
  entirely to the closing WP-06-09 commit rather than sliced per-WP,
  the same "final-state, intermediates for history readability only"
  simplification Phase 3 already established for this repo; every other
  file's attribution is exact, not simplified. `.claude/` stayed
  untracked/excluded, matching the Phase 7 precedent. Post-commit,
  re-ran Pint and the full Pest suite against the committed tree —
  still 650/650, still clean. Pushed to `origin/main`
  (`34492cc..0728687`) on explicit owner instruction, same session.

<!--
  NOTE (2026-07-27): the two entries below (Phase 8 planning and the
  ad-hoc schedule live-link feature) were RECONSTRUCTED after an
  accidental overwrite of this file during the DdOPAA WP7 closing
  commit — the exact original wording was lost (never backed up before
  the file was overwritten to build the DdOPAA-only commit slice).
  Content below is rebuilt from the real, still-intact source files
  (`docs/phases/phase-09-post-deployment-support/README.md`, the actual
  code diffs for the schedule live-link feature) rather than from
  memory alone, so the facts are accurate, but the prose is not the
  original text. Flagged here rather than silently presented as
  untouched history.
-->
- **Phase 8 — Post-Deployment Support: planned 2026-07-27**, pending
  owner approval, execution not started. No plan existed anywhere
  before this — the roadmap (`docs/howtorun/ROADMAP-UPDATE.md`) only
  ever named the phase (renamed from the original Phase 7 slot to make
  room for Live Scoring), with no scope written down. Scoped fresh via
  two rounds of owner Q&A the same day: **in scope** — a bug/support
  workflow and monitoring/health-check coverage (a multi-select of four
  options; the owner picked these two); **not in scope** — a real
  UAT/pilot session (WP-06-06 already prepared materials for this, it
  stays future work). **Issue tracking:** GitHub Issues
  (`github.com/jaeturma/pmms`) — zero new infrastructure, not a
  markdown log in the repo. **Monitoring depth:** a documented manual
  routine, explicitly not a new always-on/automated-alerting process,
  matching every prior phase's "no new cloud/VPS infra, no CI/CD
  automation" posture. Wrote a full plan (README, DESIGN-NOTES,
  CHECKLIST, WP-08-01 Bug & Support Workflow, WP-08-02 Monitoring &
  Health-Check Routine, WP-08-03 Compliance Review & Acceptance) at
  `docs/phases/phase-08-post-deployment-support/`, then stopped for
  owner instruction — execution not started in this session.
- **Ad-hoc addition, 2026-07-27 — schedule page live-scoreboard link +
  demo data**, requested before starting the DdOPAA initiative. Added a
  "Live" column to the Schedule page: `ScheduleController` gained a
  `matchesForSlots()` private method (scoped like
  `MatchController::index()`'s own authorization) mapping each
  schedule slot to its match, if any, and whether that match has a
  non-ended `ScoringSession`; `resources/js/pages/schedule/index.tsx`
  renders a `Badge`/`Radio` icon linking straight to the live
  scoreboard when true. `SampleProvinceDemoSeeder` gained a
  `liveBasketballGame()` method seeding one real in-progress demo game
  so the feature has something to show without waiting for a real
  match. Five new tests added to `tests/Feature/ScheduleTest.php`: a
  slot with no match exposes no live-scoreboard link; a slot with an
  in-progress session is flagged live for managers; a slot with only an
  ended session is not flagged live; viewers never get a live-scoreboard
  link even for a live match; delegation officers only get the link for
  their own delegation's match. `docs/scheduling.md` and
  `docs/live-scoring.md` updated to document the new column. Full gate
  green at the time; not committed/pushed.

# DdOPAA 2025 Reference Dataset (standalone initiative, planned 2026-07-27)
Not a roadmap "Phase" — a cross-cutting data initiative, same category as
the Division Type & Municipality-Based Delegations initiative, independent
of Phase 8 (unrelated scope). Owner asked for a realistic 2025 Davao de Oro
Provincial Athletic Association reference dataset sourced primarily from a
named Facebook page, with a Provincial Government article as a supporting
source, and gave 16 detailed parts specifying source classification
(`VERIFIED_OFFICIAL`/`PARTIALLY_VERIFIED`/`SYNTHETIC_DERIVED`/
`SYNTHETIC_DEMO`), privacy rules, seeder structure, testing, and
documentation — explicitly requiring an implementation plan before any code
changes (Part 15).

**Critical research finding, before any planning was written:** the named
Facebook page — the requested *primary* source — is completely
inaccessible. `WebFetch` against it returned only the page title, zero
posts; no available tool can authenticate to or render Facebook. The
Provincial Government article (supporting source) was also blocked
directly (403 from `davaodeoro.gov.ph`); everything attributed to it comes
from `WebSearch`'s own synthesis, not a primary read. Full inventory:
`docs/data-reference/ddopaa-2025-source-register.md` — the only concrete
things that survived corroboration (all `PARTIALLY_VERIFIED`, sourced from
search snippets + Scribd document previews, never `VERIFIED_OFFICIAL`):
meet opened January 17, 2025 at Maragusan Grandstand Arena, all 11
municipalities participated, sports touched across sources (Athletics,
Basketball incl. 3x3, Volleyball, Swimming, Gymnastics, Boxing), five real
delegation nicknames (Nabunturan "Black Mamba," Montevista "Blazing
Fighters," New Bataan "Rock Wreckers," Mawab "Pick Hammer," Maragusan
"Maroon Knights"), and a handful of team-level event outcomes. **One real
student-athlete's name surfaced during research (a boxing gold medalist)
and was deliberately never recorded anywhere** — the owner's standing
instruction is no real athlete name without explicit authorization,
regardless of whether it's already public; followed strictly even though
this one technically already appeared in a public post.

Presented this finding plus a Part 15 implementation plan and asked the
owner three pivotal questions rather than guessing: (1) given the Facebook
gap, proceed anyway, have the owner supply the post content directly, or
pause? → **proceed mostly-synthetic**, honestly labeled, nothing beyond the
short corroborated list ever claimed as verified; (2) provenance metadata:
new DB columns/tables, or documentation-only? → **documentation-only, no
schema changes** (matches this project's "avoid unnecessary complexity"
posture and the request's own "choose the simplest compatible approach"
instruction); (3) build all 16 parts in one pass, or break into WPs like
every other phase? → **break into WPs**.

Plan written accordingly: `docs/phases/ddopaa-2025-reference-dataset/`
(README + DESIGN-NOTES + CHECKLIST + WP1..7) — WP1 Meet/Venue/Sports
Catalog Setup → WP2 Standard Dataset (11 real delegations, 10–25 schools
each, 500–700 synthetic athletes) → WP3 Results & Medal Tally
Reconciliation (through the existing encode→validate flow and
`MedalTallyService`, no hardcoded tally) → WP4 Live Scoring Samples
(basketball/boxing/softball-baseball, each scheduled+live+completed) →
WP5 Demo & Load-Test Tier Wiring (reuses WP-06-04's
`PerformanceBenchmarkSeeder` for the load-test tier rather than duplicating
it) → WP6 Testing & Seeding Safety → WP7 Documentation & Completion
Review. Confirmed the existing schema needs zero changes — every structure
this needs (District/School/Delegation/Athlete/Sport/Event/Venue/
EventSchedule/EventMatch/EventResult/ResultPlacement/ScoringSession/
ScoreEvent) already exists, built across Phases 1–7 and the Division
initiative for exactly this shape of data. `docs/data-reference/
ddopaa-2025-source-register.md` already written (the Part 2 research
deliverable). Nothing else committed/pushed; execute one WP at a time on
owner instruction. Next: owner approval, then WP1.

## DdOPAA Work Package Log
- WP1 Meet, Venue & Sports Catalog Setup — done 2026-07-27. New
  `database/seeders/DdopaaReferenceSeeder.php` (flat file, `local`/
  `testing`-guarded like every existing sample seeder, not registered in
  `DatabaseSeeder`'s default chain — matches `PerformanceBenchmarkSeeder`'s
  precedent and Part 12's "separate commands" requirement). Creates
  "DdOPAA Meet 2025" (Active, published; `PARTIALLY_VERIFIED` start date
  2025-01-17 corroborated across 3 source-register fetches;
  `SYNTHETIC_DERIVED` end date, none was found). Sets nicknames on
  exactly the 5 real municipalities the source register corroborates
  (Nabunturan "Black Mamba," Montevista "Blazing Fighters," New Bataan
  "Rock Wreckers," Mawab "Pick Hammer," Maragusan "Maroon Knights") via
  a plain `District::where(...)->update()` — never creates new District
  rows, the other 6 municipalities deliberately untouched. Three venues
  (`PARTIALLY_VERIFIED` "Maragusan Grandstand Arena," 2 `SYNTHETIC_DEMO`
  supporting venues for indoor/aquatic events later WPs need). Added
  Boxing as a `Sport` row (already a supported live-scoring board type
  since Phase 7, but never in the seeded catalog) and 11 new `Event` rows
  across Basketball/Volleyball/Gymnastics/Swimming/Boxing — reused the
  one pre-existing "Basketball" (Boys) event from the earlier ad-hoc
  live-scoreboard-link demo rather than duplicating it. Every event is
  individually annotated `PARTIALLY_VERIFIED` (directly matches a
  corroborated source-register fact — e.g. "3x3 Basketball, Boys" since
  Montevista's win is on record) or `SYNTHETIC_DERIVED` (a realistic
  gender-paired counterpart or, for Swimming, a plausible baseline event
  — the sport's inclusion is corroborated, no specific event is). All
  12 events (11 new + 1 reused) attached to the meet. Verified end-to-end
  against the real `pmmsdb`, not just written and assumed correct: ran
  the seeder, confirmed every fact above via `tinker`, re-ran it a second
  time and confirmed identical counts (true idempotency, no duplicates).
  Dedicated automated test coverage deliberately deferred to WP6 (Testing
  & Seeding Safety), per the initiative's own plan — this WP's evidence
  is the direct verification above, matching how WP-06-04's seeder work
  was verified. Data left in place for WP2 onward to build on (unlike
  WP-06-04's benchmark seeder, which was cleaned up after one-off
  profiling — this is a multi-WP foundation, not a one-off test). Pest
  655/655 (unchanged — no test code touched), full gate green:
  Pint+PHPStan L7 (0 errors); frontend checks not re-run, no frontend
  file touched. App reconfirmed HTTP 200 at http://pmms.app. Not
  committed/pushed — next: WP2 Standard Dataset — Delegations, Schools,
  Athletes & Personnel, on owner instruction.
- WP2 Standard Dataset — done 2026-07-27. New
  `database/seeders/DdopaaStandardSeeder.php` (flat, `local`/`testing`-
  only, requires WP1's meet to already exist, not in the default seed
  chain). Registers all 11 real municipalities as approved delegations;
  177 schools (10–25 per municipality, `SYNTHETIC_DERIVED` DepEd-style
  names — explicitly not a verified roster, documented as such); 531
  synthetic athletes (within the requested 500–700 standard-tier range),
  synthetic Filipino names from fixed 20-name pools, no real names; 11
  coaches; 311 confirmed entries. Deterministic throughout (index/LRN-
  based selection, never `random_int`) specifically so idempotency holds
  — proven, not assumed: ran it twice, byte-identical counts both times
  (11/177/531/311). `guaranteedEntries()` ensures the 5 corroborated
  delegation/event pairs from the source register (Montevista→3x3
  Basketball Boys, Nabunturan→Volleyball Girls + Boxing Boys ×3,
  Mawab→Volleyball Girls, Maragusan→Volleyball Girls, New Bataan→Artistic
  Gymnastics Boys) actually have real entries ready for WP3 to place as
  winners, regardless of how the generic sex-matched distribution
  happened to land — verified present after both seeding runs.
  **Caught and fixed a real data-quality bug before finalizing, not
  after**: the first draft assigned each athlete's grade level
  independently of which school they were enrolled at, producing
  nonsense like a Grade 9 student "attending" an Elementary school (since
  `School.level` is just descriptive metadata, nothing in the app itself
  enforces this consistency, but it's an obvious tell in seed data meant
  to look realistic). Fixed by deriving grade level from the assigned
  school's own `level` (Elementary→3-6, Secondary→7-10, Integrated→either)
  instead of a school-independent formula; since `Athlete` firstOrCreate
  is keyed on LRN, the already-seeded bad rows wouldn't have self-
  corrected on a second run, so the WP2-scoped data (never WP1's
  meet/venues/events/nicknames) was explicitly cleared and reseeded fresh
  — verified 0 mismatched grade/school-level athletes afterward, and
  re-verified idempotency again post-fix. No cross-municipality school
  assignment (schools are always created directly under their own
  municipality's District, never reassigned). Dedicated automated test
  coverage remains deferred to WP6 per the plan; this WP's evidence is
  the direct `tinker` verification above (2 full seed runs, a
  targeted mismatch query, and the 6 guaranteed-entry checks). Pest
  655/655 (unchanged), full gate green: Pint+PHPStan L7 (0 errors);
  frontend checks not re-run, no frontend file touched. App reconfirmed
  HTTP 200. Not committed/pushed — next: WP3 Results & Medal Tally
  Reconciliation, on owner instruction.
- WP3 Results & Medal Tally Reconciliation — done 2026-07-27. New
  `database/seeders/DdopaaResultsSeeder.php` (flat, `local`/`testing`-
  only, requires WP1+WP2's meet and delegations to already exist, not in
  the default seed chain). Uses the app's own existing encode→validate
  flow exactly as `ResultController` does — `EventResult`/
  `ResultPlacement` rows only, no parallel "medal award" table;
  `MedalTallyService::standings()` derives the tally at read time,
  unchanged, per the initiative's Part-required flow (Event Result →
  Result Validated → Result Finalized → tally recalculated). Team events
  (`teamPlacements()`) rank by delegation, not individual entry — every
  teammate at a shared rank gets `is_tie=true`, so each roster member
  earns the medal individually in the tally, matching real multi-sport-
  meet medal counting. Individual events (`individualPlacements()`) rank
  entries directly, with a `sweep` option (Nabunturan Boxing Boys) where
  every entry from the winning delegation shares rank 1, approximating
  the source register's "4 golds" note against a catalog with only one
  non-weight-classed Boxing event. `KNOWN_WINNERS` (3x3 Basketball Boys→
  Montevista, Artistic Gymnastics Boys→New Bataan, Boxing Boys→
  Nabunturan/sweep) and `VOLLEYBALL_GIRLS_BRACKET` (Nabunturan, Mawab,
  Maragusan) encode exactly the `PARTIALLY_VERIFIED` facts from the
  source register; every other placement, and every "how did the rest of
  the field finish" detail even within a corroborated event, is
  `SYNTHETIC_DERIVED`/`SYNTHETIC_DEMO`, documented per-method. Produced
  14 validated results, 69 placements.
  **Caught and fixed three real bugs before finalizing, not after**:
  (1) Revisited WP2's `pickEvent()` — its fixed candidate-scan order let
  high-capacity events (Basketball, cap 12) absorb nearly all entries
  before low-capacity events (Swimming, Gymnastics, Boxing) got any,
  leaving 4 of 12 events with zero participants and nothing for WP3 to
  place; fixed by rotating the starting candidate index off the
  athlete's own LRN, required clearing and reseeding WP2's data again,
  verified 10 of 12 events multi-delegation afterward while the 6
  guaranteed entries and idempotency both still held. (2) Found WP2's
  elementary Athletics events were queried for athlete entries but never
  synced onto the meet itself (`$meet->events()->syncWithoutDetaching(...)`
  was missing), meaning 44 entries pointed at events the meet didn't
  actually offer — inconsistent with what `EntryController::store()`
  would ever allow; fixed additively, no data wipe needed. (3) A
  destructuring bug in `individualPlacements()` —
  `[$winner, , , $sweep] = $this->knownWinnerRow($event) ?? [...]` read
  `$winner` from index 0 (the event name string) instead of index 2 (the
  winning municipality) — silently broke winner-prioritization for
  Boxing and Gymnastics; it "accidentally" looked right for Gymnastics
  only because that event happened to have a single participating
  delegation, so any ordering put them first regardless, but Boxing
  (3 competing delegations) exposed it: Maragusan (lowest entry ID by
  chance) placed 1st instead of Nabunturan. Fixed to
  `[, , $winner, $sweep] = ...`, cleared and reseeded results. (4) After
  fixing #3, the fallback ranking's plain ascending-ID sort made
  Compostela (alphabetically first, lowest delegation ID) sweep ~19 golds
  — nearly every event with no corroborated winner — an obviously
  artificial-looking result; fixed by adding deterministic `rotated()`/
  `rotatedEntries()` helpers that rotate the sorted ID list by the
  event's own ID (never `random_int`, idempotency preserved), spreading
  wins realistically across municipalities. Verified via direct
  `pmmsdb` queries, not assumed correct from code review: re-ran the
  seeder three times post-fix, stable at 14 results/69 placements every
  time (true idempotency); spot-checked all 4 known winners correct
  (3x3 Basketball Boys=Montevista, Volleyball Girls bracket=Nabunturan/
  Mawab/Maragusan, Artistic Gymnastics Boys=New Bataan, Boxing
  Boys=Nabunturan with a 3-way rank-1 tie); reviewed the full
  `MedalTallyService` standings for realism (top New Bataan 8 golds down
  to Mawab/Maragusan 1-2 total, spread across ~10 municipalities, no
  single-municipality sweep); reconciled every municipality's tally
  total against the sum of its own schools' totals — 0 mismatches across
  all 10 municipalities with results — and confirmed all 69 placements
  trace to the 14 validated results with no orphans. Dedicated automated
  test coverage remains deferred to WP6 per the plan; this WP's evidence
  is the direct verification above. Full gate green: Pint clean,
  PHPStan L7 (0 errors), Pest 655/655 (3,305 assertions, unchanged — no
  test code touched), frontend checks not re-run (no frontend file
  touched). App reconfirmed HTTP 200 at http://pmms.app. Not committed/
  pushed — next: WP4 Live Scoring Samples (Basketball, Boxing,
  Softball/Baseball), on owner instruction.
- WP4 Live Scoring Samples (Basketball, Boxing, Softball/Baseball) —
  done 2026-07-27. New `database/seeders/DdopaaLiveScoringSeeder.php`
  (flat, `local`/`testing`-only, requires WP1+WP2's meet and delegations,
  not in the default seed chain). Mirrors the ad-hoc
  `SampleProvinceDemoSeeder::liveBasketballGame()` pattern exactly —
  same `ScoringSession`/`ScoreEvent` fields, same `firstOrCreate`/
  `forceFill` idempotency approach — generalized to 3 sports × 3 states
  (9 `EventMatch` rows total, each with its own `EventSchedule` slot so
  the Schedule page's live-link column has real state to show):
  Basketball (reused WP1's "Basketball" Girls team event), Boxing
  (reused WP1's "Boxing" Boys individual event — a live bout is a
  separate, provisional concept from the `EventResult` WP3 already
  validated for the same event; both coexist, same as the real app
  allows), and Softball (one new "Softball" Girls team event,
  `SYNTHETIC_DEMO` — WP1's catalog had no sport mapping to
  `ScoreboardType::SoftballBaseball`, which requires a sport literally
  named "Softball" or "Baseball"; both already existed unused in
  `SportsCatalogSeeder`). Per sport: one `Scheduled` match with no
  session; one match with an `in_progress` session (running score,
  correct sport-specific `sport_state` — team fouls for basketball, a
  partial round history for boxing, partial inning/count state for
  softball, each summing correctly to the running score); one
  `Completed` match (`match.status` explicitly forced, since the app
  itself never auto-transitions a match on session end — confirmed by
  reading `ScoringSessionController::end()`, which only ever touches the
  session) with an `ended` session — a full, internally consistent final
  state (3 complete boxing rounds summing to 29-28; 7 complete softball
  innings summing to 9-6). Every value `SYNTHETIC_DEMO`, documented as
  such — no source has real score/round/inning data for any match.
  Never creates or touches `EventResult`/`ResultPlacement` — verified,
  not assumed: counts stayed at 14/69 (WP3's exact numbers) before and
  after running this seeder. Verified via direct `pmmsdb` queries: 9
  matches / 6 sessions (3 in-progress + 3 ended; scheduled matches
  correctly have none) / 4 `ScoreEvent` rows / 9 `EventSchedule` slots,
  identical after a second run (idempotent); every completed match's
  round/inning breakdown hand-checked to sum to its final score (Boxing
  10+9+10=29 / 9+10+9=28; Softball 1+0+2+1+0+3+2=9 / 0+1+0+2+0+1+2=6).
  Dedicated automated test coverage remains deferred to WP6 per the
  plan; this WP's evidence is the direct verification above. Full gate
  green: Pint clean, PHPStan L7 (0 errors), Pest 655/655 (3,305
  assertions, unchanged — no test code touched); frontend checks not
  re-run, no frontend file touched. App reconfirmed HTTP 200 at
  http://pmms.app. Not committed/pushed — next: WP5 Demo & Load-Test
  Tier Wiring, on owner instruction.
- WP5 Demo & Load-Test Tier Wiring — done 2026-07-27. Three separate,
  clearly-named commands per the request's own Part 12 requirement
  (never one seeder with a tier flag). New
  `database/seeders/DdopaaDemoSeeder.php` (demo tier: calls WP1, then
  adds 3 municipalities × 6 athletes = 18 total — a quick-to-eyeball
  fraction of WP2's 500+ volume, own 942xxx LRN/school-code range so it
  never collides with WP2's 941xxx or WP-06-04's 950xxx). New
  `database/seeders/DdopaaStandardTierSeeder.php` (standard tier: a thin
  `$this->call()` orchestrator chaining WP1→WP2→WP3→WP4 in order, same
  pattern `DatabaseSeeder` itself already uses — not to be confused with
  WP2's own `DdopaaStandardSeeder`, which is just the athlete-volume
  ingredient, not the full tier). Load-test tier: confirmed
  `PerformanceBenchmarkSeeder` (WP-06-04) still works as-is and needs no
  changes — reran it directly (11 delegations / 88 schools / 1,320
  athletes, unchanged scale), left untouched per this WP's own Out of
  Scope.
  **Caught and fixed a real cross-run idempotency bug before finalizing,
  not after** — found by actually re-running the new standard-tier
  orchestrator twice in a row rather than assuming a single successful
  run proved it safe: `DdopaaStandardSeeder`'s (WP2) event-capacity
  simulation reset to empty on every invocation and replayed
  deterministically only as long as the meet's attached event catalog
  never changed between runs. Because the orchestrator runs WP4 (which
  adds a new "Softball" event) *after* WP2 every time, a second
  orchestrator run fed WP2 a bigger catalog than the first run saw,
  causing two compounding problems: already-entered athletes could pick
  up a second entry in the newly available event, and skipping those
  athletes on the guard-check shifted the local capacity simulation
  enough that *other*, previously-unentered athletes also drifted onto
  different events — confirmed entries grew 314 → 358 → 402 over three
  successive runs, nowhere near stable. Root-caused and fixed by making
  `$entryCounts` DB-backed instead of run-local: seeded from actual
  existing `Entry` rows at the start of `run()` (new
  `existingEntryCounts()` method) and gated by a new `alreadyEntered()`
  check so a previously-entered athlete is never re-evaluated, on top of
  a catalog that now always reflects true cumulative state rather than a
  from-scratch-per-run simulation. Applied the identical, smaller-scale
  fix to the new `DdopaaDemoSeeder` (same latent bug, same mechanism, own
  `existingEntryCounts()` scoped to one delegation) since it has the
  same generic-catalog + local-capacity-tracking shape. Verified by
  fully resetting the DdOPAA-scoped data (delegations through matches,
  including deleting and letting WP4 recreate the Softball event) and
  replaying the whole stack from a genuine clean slate: first pass
  reproduced WP3's exact previously-documented evidence (531 athletes /
  314 confirmed entries / 14 results / 69 placements), and three full
  standard-tier re-runs plus two full demo-tier re-runs afterward held
  perfectly stable at those same numbers (plus 18 stable demo entries on
  top, 332 total) — true idempotency, not just a single lucky run.
  Medal-tally reconciliation re-verified clean (0 municipality/school
  mismatches) after the fix. The 8 athletes who hold 2 confirmed entries
  is a separate, pre-existing, stable, and intentional characteristic of
  `guaranteedEntries()` (WP2) — it can layer a guaranteed entry onto an
  athlete the generic pass already entered elsewhere — not a regression
  from this fix. Noting for WP6: an automated idempotency test for the
  standard tier must run the *orchestrator* at least twice (not just
  WP2 alone with a frozen catalog) to actually exercise this fixed path.
  Full gate green: Pint clean, PHPStan L7 (0 errors), Pest 655/655
  (3,305 assertions, unchanged — no test code touched); frontend checks
  not re-run, no frontend file touched. App reconfirmed HTTP 200 at
  http://pmms.app. Not committed/pushed — next: WP6 Testing & Seeding
  Safety, on owner instruction.
- WP6 Testing & Seeding Safety — done 2026-07-27. New
  `tests/Feature/DdopaaReferenceDatasetTest.php` (16 tests), running the
  real seeder classes against the test database (sqlite `:memory:`,
  phpunit.xml) rather than reconstructing their output with factories,
  since the point is proving what the actual seeders produce — every
  invariant from the request's Part 13 list, plus the environment guard:
  all 11 real municipality delegations exist and approved; every
  athlete's school is within their own delegation's municipality; no
  school crosses municipalities even after a repeat run; no duplicate
  delegation per municipality even after a repeat run; every medal
  placement traces to a validated result and municipality totals equal
  their own schools' summed totals; an encoded-but-unvalidated result
  never appears in the tally (proven with a real extra encoded result
  injected via factory, not just by absence); live-scoring seeding never
  creates or touches `EventResult`/`ResultPlacement`; the standard-tier,
  demo-tier, and load-test-tier commands are each idempotent (run
  twice, identical counts); all 6 new seeder classes
  (`DdopaaReferenceSeeder`, `DdopaaStandardSeeder`, `DdopaaResultsSeeder`,
  `DdopaaLiveScoringSeeder`, `DdopaaDemoSeeder`, `DdopaaStandardTierSeeder`)
  individually refuse to run outside `local`/`testing` — each guard test
  seeds valid prerequisites first, then flips `app()['env']` to
  `production` and proves specifically the environment guard (not a
  missing-prerequisite early return) is what blocks it.
  **Caught and fixed a second real bug before finalizing** — this one
  invisible in every manual MySQL check done throughout WP4/WP5, only
  surfaced by actually running the new idempotency test against the
  project's real test database: `DdopaaLiveScoringSeeder`'s `slot()`
  method (WP4) queried `EventSchedule::firstOrNew(['scheduled_date' =>
  $date->toDateString(), ...])` — a bare `Y-m-d` string — but Eloquent's
  `date` cast serializes new rows through the query grammar's default
  datetime format (`Y-m-d H:i:s`) when saving, so the two never actually
  matched as strings. MySQL's native `DATE` column silently truncates
  the time part on INSERT, which is exactly why this stayed invisible
  across every earlier manual `tinker` re-run against the dev database;
  SQLite has no such column type, stores the full string verbatim, and
  a second orchestrator run in the Pest suite created a duplicate
  `EventSchedule`/`EventMatch` pair for all 9 matches instead of finding
  the existing ones (9 → 18). Fixed by replacing the raw-string
  `firstOrNew` key with an explicit `whereDate('scheduled_date', ...)`
  lookup — the Laravel helper built specifically for comparing only the
  date portion, portable across both engines — falling back to `new
  EventSchedule(...)` only when no match is found. Verified idempotent
  afterward in both places: the Pest suite (9 schedules/9 matches stable
  across two runs) and the real dev MySQL database (re-ran
  `DdopaaLiveScoringSeeder` directly, still 9/9, no regression from the
  fix). This is the second cross-database-engine lesson from this
  initiative (WP5's fix was cross-run, catalog-order; this one is
  cross-engine, date-serialization) — both were caught only because a
  seeder's own claimed idempotency was actually exercised against a
  different environment than the one it was written and eyeballed in,
  not assumed from a single successful manual run.
  Dedicated automated coverage was this WP's own purpose, so nothing
  further deferred. Full gate green: Pint clean, PHPStan L7 (0 errors),
  Pest 671/671 (655 existing + 16 new, 3,341 assertions total, all
  green — proves no regression); frontend checks not re-run, no
  frontend file touched. App reconfirmed HTTP 200 at http://pmms.app.
  Not committed/pushed — next: WP7 Documentation & Completion Review,
  on owner instruction.
- WP7 Documentation & Completion Review — done 2026-07-27. **Initiative
  complete (WP1–WP7).** Four new docs: `docs/data-reference/
  ddopaa-2025-reference-data.md` (what's actually in the dataset, by
  classification — nothing reaches `VERIFIED_OFFICIAL`, only a short
  `PARTIALLY_VERIFIED` list plus `SYNTHETIC_DERIVED`/`SYNTHETIC_DEMO`
  everywhere else), `docs/data-reference/ddopaa-2025-data-limitations.md`
  (the honest "what this dataset is not" — no verified medal tally, no
  verified champion, no verified schedule, no real athlete names, and
  why, expanding the source register's own section), `docs/testing/
  ddopaa-2025-demo-data-guide.md` (the three tier commands, expected
  counts, how to reset safely, prerequisites per seeder), and
  `docs/reports/ddopaa-2025-seed-data-completion.md` (the completion
  report — a per-WP verification table, all 4 bugs found and fixed
  during the initiative summarized in one place, every owner-approved
  scope deviation listed, final dataset counts, full gate results).
  Verified every WP1–WP6 deliverable actually exists on disk (not just
  trusted from their own completion reports) — all 6 seeder files, the
  WP6 test file, and the source register confirmed present via direct
  file check. Re-ran the full quality gate one final time, this time
  including the frontend checks every earlier WP in this initiative
  correctly skipped (no frontend file was touched until now, so there
  was nothing to re-check) — all green: Pint clean, PHPStan L7 (0
  errors), Pest 671/671 (3,341 assertions), ESLint 0 errors, Prettier
  clean, `tsc --noEmit` 0 errors, Vite build succeeded. App reconfirmed
  HTTP 200 at http://pmms.app. `README.md`'s status line updated from
  "Planned — pending owner approval" to "Complete." CHECKLIST.md now
  shows all 7 WPs checked. **Not committed, not pushed, no production
  seeder run** — every command executed throughout this initiative
  stayed `local`/`testing`-only, proven automatically by WP6's guard
  tests, not just by convention. Next: owner review of
  `docs/reports/ddopaa-2025-seed-data-completion.md`, then a commit/push
  decision — entirely the owner's call, not started here.
- **Post-completion fix, 2026-07-27 — Maco missing from the medal
  tally.** WP1–WP7 were committed (`fc06233`..`f874e91`) and pushed to
  `origin/main` on owner instruction; the owner then reviewed the
  seeded data ahead of a presentation and reported Maco missing from
  "Municipalities." Investigated rather than assumed: Maco's underlying
  data was fully present (approved delegation, 20 schools, 36 athletes,
  21 confirmed entries across 10 different events, all in events that
  *did* get a validated result) — the actual cause was
  `MedalTallyService::standings()` only ever lists a district that
  appears in at least one placement, and deterministic rotation had
  left Maco the one municipality with confirmed entries but zero
  top-3 finishes anywhere, so it silently had no row at all rather than
  a zero row. `database/seeders/DdopaaResultsSeeder.php` gained
  `guaranteeMunicipalityCoverage()`: for any municipality with confirmed
  entries but no placement, swap a bronze (rank 3) slot in one
  individual event they're entered in away from whichever delegation
  currently holds it — only when that donor has enough medals (>= 2)
  that losing one bronze can't drop them to zero themselves. Never
  touches the 4 `PARTIALLY_VERIFIED` corroborated events (all team or
  already-decided). Applied: Maco now shows 1 bronze (donor was
  Nabunturan, 11→10 total, nowhere near zero); all 11 municipalities
  now appear in the tally; `ResultPlacement` count unchanged at 69 (a
  swap, not an addition); reconciliation re-verified clean (0
  municipality/school mismatches, 0 unvalidated placements). Verified
  idempotent (re-ran twice, stable). Full gate green: Pint clean,
  PHPStan L7 (0 errors), Pest 671/671 including all 16 WP6 tests
  unchanged. App reconfirmed HTTP 200. **Not yet committed or pushed —
  this fix is new since the WP1–WP7 push**, pending owner instruction.

# Phase 8 — UI/UX Implementation and Visual Alignment
`docs/phases/phase-08-ui-ux-visual-alignment/` (16 WPs) — this directory,
its reference images (`docs/ui-ux/references/`), and `.ai/project-rules.md`/
`.ai/work-package-runner.md`/`.ai/ui-ux-rules.md` all predate this
conversation's visibility into them — created in a session not reflected
elsewhere in this log. Picked up here starting with WP-08-01 on owner
instruction ("Read: project-rules.md current-phase.md
work-package-runner.md ui-ux-rules.md Then read and implement:
WP-08-01...").

## Phase 8 (UI/UX) Work Package Log
- WP-08-01 Screenshot and Current UI Gap Assessment — done 2026-07-27.
  `docs/reports/phase-08/WP-08-01-completion.md` created — an
  evidence-based gap assessment comparing all 5 reference images
  against the actual current implementation. Chrome extension was
  unavailable this session (confirmed via `tabs_context_mcp`, tried
  twice), so the assessment reads the real source (React/TSX
  components, Tailwind classes, the theme's CSS custom properties, and
  the backend data each page actually receives) rather than comparing
  screenshots — flagged explicitly as a substitution, with a
  recommendation to re-run a real screenshot pass before WP-08-13/
  WP-08-15 once reconnected. Key findings: (1) the entire current theme
  is zero-chroma grayscale (`oklch(x 0 0)` on every token in
  `resources/css/app.css`, shadcn's unmodified default) — the root
  cause behind nearly every visual gap, which is exactly what WP-08-02
  is scoped to fix; (2) most dashboard/medal-tally gaps are visual only
  (missing colored stat cards, donut charts, icons) but a few are
  functional — no per-event status aggregate for an "Events Overview"
  donut, no points-based (Gold=3/Silver=2/Bronze=1) ranking system, no
  "vs. yesterday" delta tracking; (3) the Athlete Eligibility reference
  shows an automated rule-checking flow (age/grade/residency/duplicate
  checks, instant PASS/FAIL) but the real app has a fundamentally
  different, deliberate manual document-upload-and-human-review
  workflow (`EligibilityReview`'s own doc comment: decisions are
  "always made by a person") — flagged as needing an owner scoping
  decision before WP-08-06, not a restyle; (4) Basketball/Softball live
  scoreboard gaps are partly visual, partly `sport_state`-schema gaps
  (no quarter-by-quarter history, no team shooting/hitting stats, no
  per-athlete attribution — `ScoreEvent` already has enough history for
  a play-by-play feed though, that part is purely a missing display);
  (5) Athletics has **no existing live-scoreboard foundation at all** —
  `ScoreboardType` has no Athletics case, so this reference needs new
  backend modeling before any UI work, not a restyle, flagged for
  whoever scopes WP-08-11. No application code changed — assessment
  only, per this WP's own rules. `CHECKLIST.md` WP-08-01 checked off.
  Not committed/pushed. Not begun: WP-08-02, per this WP's explicit
  "do not begin the next work package" rule — next on owner
  instruction.
- WP-08-02 PMMS Design Tokens and Visual Standards — done 2026-07-27.
  Rather than guess a palette, sampled every new color directly from
  the 9 approved reference PNGs via a small Python/Pillow script
  (downsample, bucket pixel colors, filter anti-aliasing/low-saturation
  noise, convert sRGB→OKLCH) — two hues converged consistently across
  every reference: deep navy (hue≈258, sidebars) and vivid royal blue
  (hue≈263, buttons/badges/active states), plus gold (hue≈85) and
  green (hue≈145) from the medal-tally/eligibility references. The
  existing destructive red already matched the reference's "LIVE" red
  almost exactly (sampled hue 27–28 vs. the app's existing 27.325) —
  left unchanged rather than needlessly re-tuned. Updated
  `resources/css/app.css` (Tailwind 4 CSS-first config, no
  `tailwind.config.js`): `--primary`/`--sidebar`/`--sidebar-primary`/
  `--sidebar-accent` (+dark variants) replaced with the sampled
  palette; `--secondary`/`--muted`/`--accent`/`--border`/`--input`/
  `--ring` given only a subtle cool-blue tint (deliberately kept
  low-chroma — these back Radix/shadcn's pervasive subtle-hover states
  used by every dropdown/select/menu item, so a strong brand tint there
  would show up everywhere the references don't show it); two new
  semantic pairs added (`--success`/`--warning`, neither existed
  before — matches "ONGOING"/"UPCOMING" badges); three new medal-color
  pairs added (`--medal-gold`/`--medal-silver`/`--medal-bronze` — the
  rank-1/2/3 highlights that recur across Dashboard/Medal
  Tally/mobile-ranking, currently unstyled plain text everywhere);
  `--chart-1..5` remapped to the brand+status palette for the
  not-yet-built donut charts WP-08-01 flagged. **Fixed a real
  pre-existing bug found while auditing the tokens**:
  `--destructive-foreground` was literally the same value as
  `--destructive` in light mode (invisible text) and a different-but-
  still-wrong mid-red in dark mode — never visibly broken only because
  `Button`'s destructive variant hardcodes `text-white` instead of
  using the token, but the token itself was wrong; fixed to white in
  both modes. New `docs/ui-ux/design-tokens.md` documents every token,
  why accent/secondary/muted were deliberately left neutral, and lists
  (via grep, not fixed) pages already using raw Tailwind palette
  classes instead of semantic tokens, flagged for a later consistency
  WP. No `.tsx` file touched — this WP is token-values-only per its
  own scope (WP-08-03 onward applies them); every shadcn/ui primitive
  already reads these CSS variables, so buttons/badges/sidebar/focus-
  rings already pick up the new brand colors automatically without any
  component edit. Verified every new token round-trips OKLCH→sRGB
  in-gamut (Python check) before writing them, and `npm run build`
  compiled clean. Chrome extension still unavailable this session
  (checked 3 times) — could not get a live visual screenshot, flagged
  for a real visual check before WP-08-03 proceeds. Full gate green:
  Pint clean, PHPStan L7 (0 errors, neither touched — CSS-only change),
  Pest 671/671 unchanged, ESLint 0 errors, Prettier clean, tsc 0
  errors, Vite build succeeded. App reconfirmed HTTP 200.
  `CHECKLIST.md` WP-08-02 checked off. Not committed/pushed. Not begun:
  WP-08-03, per this WP's own rule — next on owner instruction.
- WP-08-03 Admin Application Shell and Navigation — done 2026-07-27.
  This WP's own listed reference images were the same generic
  live-scoreboard set copy-pasted onto WP-08-02 — not
  `admin-dashboard.png`, the only image that actually shows a shell —
  confirming WP-08-01's "templated docs" finding again; used the
  actual shell-showing references instead. Confirmed via grep first
  that no backend Pest test touches sidebar/header DOM (no frontend
  component tests in this project), so restructuring carried no
  regression risk to the test suite. Three structural fixes: (1)
  `Sidebar` was `variant="inset"` (floating/rounded/gapped) — every
  reference shows a flush full-height sidebar — changed to the default
  `variant="sidebar"`; (2) header had no date/time or visible identity
  and used `--sidebar-border` for its own border despite sitting in the
  light main-content panel, not the dark sidebar — rebuilt with a new
  `useClock` hook (30s refresh) and an avatar/name/role dropdown
  reusing the existing `UserMenuContent` unchanged, fixed border to
  `--border`; (3) the sidebar footer's `NavUser` became a redundant
  second identity menu once the header had one — replaced with a new
  `SidebarMeetCard` showing the real current meet (name/status/dates/
  venue, globally shared from `HandleInertiaRequests`, guarded to
  authenticated requests so public-portal guest loads never pay the
  extra query) instead of the reference's fictional "Stronger Together,
  Champions Forever!" tagline/illustration, which doesn't correspond to
  anything real in this app — `nav-user.tsx` deleted after confirming
  via grep it's unused anywhere else. Also added `role_label` to the
  shared `auth.user` payload (reusing `UserRole::label()`, existed on
  the backend enum, never surfaced to the frontend before) and renamed
  the nav section label "Platform"→"Main Navigation" to match every
  reference. Incidental fix while already in the file: `UserInfo`'s
  avatar fallback used hardcoded `bg-neutral-200`/`dark:bg-neutral-700`
  instead of WP-08-02's semantic `bg-muted`/`text-muted-foreground`
  tokens — fixed, not swept repo-wide (out of scope here). Deliberately
  did NOT add a notification bell — no notification concept (model,
  table, event) exists anywhere in the backend, and WP-08-01 explicitly
  deferred that decision to this WP; building one would be a new
  feature, and a non-functional bell would itself violate "do not
  hardcode screenshot values" by implying functionality that doesn't
  exist — documented as a deliberate omission, revisit only if a future
  WP explicitly scopes real notifications. No dashboard content
  touched (WP-08-04's scope). Chrome extension still unavailable this
  session (checked twice more) — no live screenshot possible, flagged
  again for a visual check before WP-08-04. Full gate green: Pint
  clean, PHPStan L7 (0 errors), Pest 671/671 unchanged (meaningful here
  since `HandleInertiaRequests` runs on every request), ESLint 0
  errors, Prettier clean, tsc 0 errors, Vite build succeeded. App
  reconfirmed HTTP 200. `CHECKLIST.md` WP-08-03 checked off. Not
  committed/pushed. Not begun: WP-08-04, per this WP's own rule — next
  on owner instruction.
- WP-08-04 Admin Dashboard Visual Implementation — done 2026-07-27. This
  WP's own listed reference images were again the wrong copy-pasted set
  (generic live-scoreboard/ranking images, not `admin-dashboard.png`) —
  same templated-doc issue WP-08-01/02/03 already flagged; used
  `admin-dashboard.png` instead. Four real gaps closed against it: (1)
  `StatCard` gained an optional `tone` prop (colored circular icon
  badges, backed by WP-08-02's tokens) — every other `StatCard` usage
  (e.g. `management/index.tsx`) untouched, opt-in only; (2) new
  `DashboardController::eventsOverview()` — a real, non-invented 3-way
  Completed/Ongoing/Upcoming breakdown from `EventResult`/
  `EventSchedule` (deliberately no "Cancelled" bucket — no such concept
  exists on `Event`, inventing one would hardcode a screenshot value),
  rendered as a new `EventsOverviewCard` segmented-bar widget with its
  own empty state; (3) medal-tally rank numbers replaced with a
  `RankBadge` using WP-08-02's `--medal-gold/silver/bronze` tokens; (4) a
  new quick-actions row of six shortcut tiles, all reused existing
  Wayfinder routes, no new routes/permissions. Two smaller real
  additions found already-available-but-unused: today's schedule rows
  now show an Upcoming/Ongoing/Completed badge derived client-side via
  `useClock` (reused from WP-08-03) against each slot's start/end time
  (lexicographic "HH:MM" comparison, no date parsing); recent-activity
  entries get per-action-prefix colored icons (`ActivityIcon`, generic
  fallback so a future audit action never needs this file touched).
  Dashboard also now surfaces the 3 most-recent published announcements
  (reusing the existing `Announcement` model and the existing
  `PublicAnnouncements` component already used on the public portal — no
  new backend or duplicated rendering). One cross-page addition scoped
  from the same "what's happening right now" reference intent: a "Watch
  live" link on the Schedule page — `ScheduleController::
  matchesForSlots()` exposes `match_id`/`is_live` per slot, scoped
  exactly like `MatchController::index()` (Viewers never receive the
  field; a Delegation Officer only sees their own delegation's matches),
  computed once per page load, not per-row; documented in
  docs/scheduling.md ("Live scoreboard link") and cross-referenced from
  docs/live-scoring.md. `SampleProvinceDemoSeeder` gained a re-seedable
  `liveBasketballGame()` helper (always repositions to "today") so the
  new link has real demo data without hand-walking Meets → Matches →
  Start scoring. No schema changes, no authorization changes — every
  widget is read-side over existing queries/relations plus the two new
  additive/scoped queries above, both mirroring authorization already
  proven by `MatchController`'s own tests rather than duplicating it.
  Pest 671/671 (5 new tests in ScheduleTest proving the live-link scoping:
  no-match, in-progress, ended-only, viewer-forbidden, delegation-
  officer-own-match-only), full gate green: Pint+PHPStan+ESLint+
  Prettier+tsc+build. Chrome extension still unavailable this session
  (re-checked via `tabs_context_mcp` immediately before writing the
  completion report) — no live screenshot possible, flagged again for a
  visual check before WP-08-05. Local Laragon MySQL/web services were
  not running this session (a first for this phase — WP-08-01/02/03 all
  recorded HTTP 200); `php artisan serve` surfaced a `PDOException`
  connecting to 127.0.0.1:3306, confirming the gap is "services not
  started" rather than an application bug (the Pest suite runs against
  SQLite and doesn't depend on this) — flagged for an owner follow-up to
  start Laragon and reconfirm HTTP 200 before WP-08-05, not treated as a
  blocker given every other gate passed.
  docs/reports/phase-08/WP-08-04-completion.md written; `CHECKLIST.md`
  WP-08-04 checked off. Not committed/pushed. Not begun: WP-08-05, per
  this WP's own rule — next on owner instruction.
- WP-08-05 Admin Medal Tally and Rankings UI — done 2026-07-27. This WP's
  own reference-image list was again the wrong copy-pasted set — used
  `admin-medal-tally.png` instead. Two of the app's own already-
  documented rules shaped this WP's design rather than following the
  reference literally: (1) `docs/medal-tally.md`'s "ordering is
  conventional: gold, then silver, then bronze, then name" is a tested,
  deliberate official-standings rule, not an oversight — even though the
  reference visually implies points-based ranking ("Ranking is based on:
  Gold (3 points)..."), a new `points` value (Gold×3/Silver×2/Bronze×1)
  was added as display-only (new column + its own separately labeled
  "Top by points" widget) without touching the tested rank order —
  proven by a new test where a single-gold district (fewer points) still
  outranks a two-silver district (more points); (2) the Post-Division
  refinement rule that school standings must never read as a competing
  standing meant the reference's "View Ranking By: Municipality/School"
  toggle was deliberately not built — both tables stay always-visible,
  district-first/official, school-below/reference-only, as already
  established. `MedalTallyService` gained (additive only, existing
  `standings()` behavior/signature-compatible, tested order unchanged):
  `points` on district rows; new `medalsBySport()` (same validated/
  filtered placement set, grouped by sport); new `recentMedals()`
  (gold/silver/bronze/total awarded in the last 24 hours from
  `event_results.validated_at`, real and computed at read time, no
  stored snapshot); a new `$ageDivision` filter reusing the existing
  `AgeDivision` enum — this is what the reference's "Division: All
  Divisions" dropdown actually maps to in real data (this app's
  `Division` model is one deployment-wide City/Province setting, not a
  filterable list; Event age division is the real, different, existing
  concept the reference means). All three methods share a new
  `basePlacements()` extraction instead of duplicating the validated-
  only/meet/sport `whereHas` chain three times. `TallyController`
  composes district totals, top-5-by-points, the sport breakdown, the
  recent-medals delta, and age-division options. `tally/index.tsx`
  rebuilt to match the reference: retitled "Medal Tally & Rankings"; new
  "Export report" button (the CSV download route already existed on the
  backend but was never linked from this page — only reachable via the
  printable report); new age-division filter; four summary `StatCard`s
  with a real 24-hour delta (omitted rather than shown as "+0" when
  there's no recent activity); a CSS conic-gradient "Medal distribution"
  donut — no charting library added, same dependency-isolation
  discipline as WP-08-04's events-overview bar; `RankBadge` extracted
  from `dashboard.tsx` into a shared `components/rank-badge.tsx` (its
  second use site) so the two pages don't drift; a Points column +
  explicit caption stating rank still follows gold/silver/bronze, not
  points; "Top by points" and "Medals by sport" widgets; a real-time-
  update `Alert` banner. `StatCard`'s `tone` prop gained `silver`/
  `bronze` variants (only had `gold` before). Deliberately not done: no
  municipality/school ranking toggle, no points-based re-ranking, no "As
  of Date" historical point-in-time filter (would need reconstructing a
  snapshot from history that doesn't exist for this purpose — a real
  feature, not a restyle), no changes to `reports/medal-tally.tsx`/
  `public/tally.tsx`/the dashboard's tally widget (WP-08-08's scope, not
  this WP's). Pest 676/676 (5 new tests in MedalTallyTest: points
  weighted 3/2/1 never reorder standings, age-division filter narrows
  correctly, an invalid age-division value is silently ignored,
  medalsBySport groups by sport, recentMedals excludes placements older
  than 24 hours), full gate green: Pint+PHPStan+ESLint+Prettier+tsc+
  build. Chrome extension still unavailable this session. Laragon's
  MySQL and Apache came up on their own this session (both were down at
  the end of WP-08-04) and the pmms.app vhost is correctly configured,
  but the running Apache process is still serving Laragon's own default
  landing page for it instead of routing to the vhost — needs a service
  restart to pick up the config. Deliberately did not restart Apache:
  the same instance also serves roughly a dozen other unrelated local
  projects already running on this machine, and restarting a shared
  service for one WP's optional visual check isn't a risk worth taking
  without asking first — flagged for the owner, not treated as a
  blocker given every other gate passed (same precedent WP-08-04 set).
  docs/medal-tally.md extended (points/by-sport/recent-medals/age-
  division sections); docs/reports/phase-08/WP-08-05-completion.md
  written; `CHECKLIST.md` WP-08-05 checked off. Not committed/pushed.
  Not begun: WP-08-06, per this WP's own rule — next on owner
  instruction. Note for whoever picks up WP-08-06: WP-08-01 flagged that
  reference as showing an automated PASS/FAIL rule-checking flow that
  conflicts with the app's real, deliberate manual document-review
  workflow — likely needs an owner scoping decision before
  implementation, same as this WP needed one for points-based ranking.
- WP-08-06 Athlete Eligibility Checker UI — done 2026-07-27. Confirmed
  the flagged conflict before writing any code: the reference shows a
  fully automated eligibility-rule engine (search athlete → pick sport/
  event/category → "Check Eligibility" → auto PASS/FAIL per rule →
  auto ELIGIBLE/INELIGIBLE verdict), directly contradicting
  docs/eligibility.md's explicit "PMMS records documents and human
  decisions — it never adjudicates eligibility... automated rules are
  policy-dependent and deferred," with automated rules/age adjudication/
  duplicate-entry checks named out of scope. Presented the owner three
  options before touching code: restyle the real manual-review queue
  only; build a new single-athlete lookup page (still no invented
  rules); or actually build the automated rule engine (reversing the
  documented decision). **Owner chose: restyle the real queue only.**
  No automated rules, no PASS/FAIL checklist, no auto-verdict, no QR
  scanning, no sport/event/category filters, no Print/Export PDF were
  built — none of that exists in this app's data model and none was
  invented to match the mockup. What WAS added, all real/computed: a
  name search (`EligibilityController` now uses the shared
  `SearchesAndPaginates` trait, searching `athlete.first_name`/
  `athlete.last_name` — the identical pattern `EntryController` already
  uses); three summary `StatCard`s (Pending review/Approved/Returned)
  computed once from a cloned base query *before* the status/search
  filters narrow it, so the totals don't shift when the list is
  filtered; each document's real upload date now shown next to its
  download link; status badges recolored with WP-08-02's semantic
  tokens (pending=warning, approved=success, returned=destructive).
  Extracted `reviewRow()`/`documentRow()` private methods with explicit
  array-shape docblocks out of the inline `->through()` closure — needed
  to resolve a real PHPStan/Larastan "Collection template is not
  covariant" false positive triggered by the nested `map()` inside
  `through()` (tried the nullsafe-`!==null` workaround from the Division
  initiative first; didn't resolve it here — extracting named methods
  with explicit docblocks did). Page retitled "Eligibility" → "Eligibility
  Review" (kept honest, not "Checker," since no automated checking was
  built). Reused the existing shared `SearchBar` component (already used
  by `athletes/index.tsx`) rather than building a new search input.
  docs/eligibility.md updated with a full "WP-08-06 visual alignment —
  restyle only, no automated rules" section recording the scoping
  decision and what was/wasn't built, so this doesn't need re-litigating
  later. Pest 678/678 (2 new tests in EligibilityTest: search by athlete
  name, summary counts reflect the whole scoped queue regardless of the
  status filter), full gate green: Pint+PHPStan+ESLint+Prettier+tsc+
  build. Chrome extension still unavailable this session. The pmms.app
  Apache vhost routing issue noted in WP-08-05 is still unresolved (not
  re-investigated this WP, same not-a-blocker status).
  docs/reports/phase-08/WP-08-06-completion.md written; `CHECKLIST.md`
  WP-08-06 checked off. Not committed/pushed. Not begun: WP-08-07, per
  this WP's own rule — next on owner instruction.
- WP-08-07 Public Portal Shell and Branding — done 2026-07-27. Its own
  reference-image list was again the wrong generic set;
  `public-medal-tally.png` is the only image that actually shows a
  public page, so it's what this WP's shell/nav/branding work was
  checked against (it doubles as WP-08-08's own reference too). The
  existing `public-layout.tsx` was genuinely minimal — logo + Sign-in/
  Dashboard button, no site nav connecting the portal home to a meet's
  pages at all. Only real routes got nav entries: the reference's News &
  Announcements/Galleries/About items don't correspond to any page in
  this app and were not built (would mean dead links or fabricated
  pages). Since Schedule/Results/Medal Tally are meet-scoped routes, not
  standalone pages, the header needs a meet to link into — added a new
  guest-only shared Inertia prop, `publicNav` (`HandleInertiaRequests`),
  the same "shell-level chrome shared once via middleware" pattern
  WP-08-03 established for the authenticated sidebar's `currentMeet`,
  mirrored here for guests: resolves the most recently started
  *published* meet, counts its active `ScoringSession`s for a "Live now"
  indicator (reusing `PortalController::liveMatches()`'s exact scoping
  as a `count()` instead of a full fetch), guarded to `$user === null`
  so authenticated loads never pay for it, `null` when nothing's
  published (header then shows only "Home"). `public-layout.tsx`
  rebuilt: nav renders from `publicNav`, active-link highlighting
  compares the current pathname (query stripped) against each item's
  exact route, and a "Live now" badge appears only when `liveCount > 0`
  — no indicator for nothing to indicate, same rule as WP-08-03's
  no-notification-bell decision — linking into the meet page's existing
  full "Live now" section (WP-07-08) rather than duplicating a live-
  match list inline in the header. New
  `resources/js/components/public-page-hero.tsx` — a reusable branded
  band built from the existing sidebar/primary tokens (WP-08-02, no new
  colors), applied to `public/home.tsx` this WP as proof-of-use;
  results/tally/scoreboard/meet pages weren't restyled with it (each
  page's own later WP's job, WP-08-08 for tally). Deliberately not
  built: a "Live now" dropdown with an inline match list (simplified to
  a link, avoiding duplicated rendering), a hamburger/Sheet mobile nav
  drawer (only up to 4 real nav items, so `PublicMeetNav`'s existing
  horizontal-scroll pattern was enough), a footer motto banner (the
  reference's "ONE MEET. ONE SPIRIT. ONE CHAMPION." is decorative copy
  with no real counterpart, not invented, same discipline as WP-08-03's
  fictional-tagline rejection), and a shell-wide announcement ticker
  (the existing `PublicAnnouncements` section already covers this;
  judged out of proportion to add a second, ticker-specific data path
  for a decorative element). docs/public-portal.md extended with a
  "Header nav & 'Live now'" section recording all of this. Pest 681/681
  (3 new tests in PublicPortalTest: `publicNav` resolves to the most
  recently started published meet with a correct live-match count —
  proven against one ended and one active session so only the active
  one counts; `publicNav` is null with no published meets;
  authenticated requests never receive it), full gate green:
  Pint+PHPStan+ESLint+Prettier+tsc+build. Chrome extension still
  unavailable this session. The pmms.app Apache vhost routing issue
  (WP-08-05/06) remains unresolved, not re-investigated this WP, same
  not-a-blocker status. docs/reports/phase-08/WP-08-07-completion.md
  written; `CHECKLIST.md` WP-08-07 checked off. Not committed/pushed.
  Not begun: WP-08-08, per this WP's own rule — next on owner
  instruction.
- WP-08-08 Public Medal Tally and Rankings Page — done 2026-07-27.
  `public-medal-tally.png` (already identified in WP-08-07's report as
  the one real public-facing reference) is the same reference WP-08-05
  already implemented against for the *internal* admin tally page, so
  this WP ports that same treatment to the public page rather than
  reinventing it — none of WP-08-05's additions (points, donut,
  by-sport, recent-medals delta) are more privacy-sensitive than the
  medal counts already shown publicly.
  `PortalController::tally()` gained `totals`/`topByPoints`/`bySport`/
  `recentMedals`/an `age_division` filter — all direct calls into the
  exact same `MedalTallyService` methods WP-08-05 already added; no new
  backend logic written. Extracted `tally/index.tsx`'s five widgets
  (`MedalDistributionCard`, `TopByPointsCard`, `MedalsBySportCard`,
  `MedalCells`/`MedalHeader` — `RankBadge` was already shared) into
  `resources/js/components/`, the same "shared rendering, independent
  props" pattern `live-score-display.tsx` established for live scoring
  in WP-07-08, so the internal and public tally pages render from one
  implementation instead of two that could drift; `PortalController`
  still builds its own minimal public-safe prop array, only the widget
  rendering is shared. `public/tally.tsx` rebuilt on these plus adopts
  `PublicPageHero` (WP-08-07) for its title band — the hero component's
  first real second use, as that WP's report anticipated. Found and
  fixed a real pre-existing duplication bug while in the file: WP-08-05
  had defined a local `pluralize()` in `tally/index.tsx` identical to
  `pluralizeAreaLabel()`, which already existed in `@/lib/utils` and was
  already used by `app-sidebar.tsx`/`registry/districts.tsx` — replaced
  the local copy with the existing shared one; also moved
  `recentDescription()` (the "+N in the last 24 hours" delta formatter)
  to `@/lib/utils` alongside it, now needed by both tally pages. Pest
  683/683 (2 new tests in PublicTallyTest: the page exposes totals/
  points/bySport/recentMedals correctly, the age-division filter narrows
  results the same way the internal page's does), full gate green:
  Pint+PHPStan+ESLint+Prettier+tsc+build. Chrome extension still
  unavailable this session. The pmms.app Apache vhost routing issue
  (WP-08-05/06/07) remains unresolved, not re-investigated this WP, same
  not-a-blocker status. docs/medal-tally.md and docs/public-portal.md
  extended; docs/reports/phase-08/WP-08-08-completion.md written;
  `CHECKLIST.md` WP-08-08 checked off. Not committed/pushed. Not begun:
  WP-08-09, per this WP's own rule — next on owner instruction.
- WP-08-09 Mobile Ranking and Medal Tally UI — done 2026-07-27. First WP
  in this phase whose own reference-image list was actually correct
  (`mobile-ranking-medal-tally.png`); its objective line was also
  unusually specific ("compact medal cards, delegation ranking table,
  sports strip, sport filter, safe-area spacing, and bottom
  navigation") — treated as the literal scope checklist. The
  reference's mobile shell moves site nav from the header into a fixed
  bottom tab bar — a genuinely different pattern from WP-08-07's
  horizontal header nav, not a restyle of it. New
  `resources/js/components/public-bottom-nav.tsx`: `sm:hidden`, fixed
  to the viewport bottom; the header nav is now `hidden sm:flex` — the
  two are complementary, never both visible. Reuses the same real
  `publicNav` destinations the header nav already resolves, plus a
  "Live" tab shown only when there's an actual live match (same "no
  indicator for nothing to indicate" rule as everywhere else this
  phase); shorter labels ("Ranking" vs. "Medal Tally") since five tabs
  share one row, same destination either way. Padded with
  `env(safe-area-inset-bottom)`; `<main>` gets matching bottom padding;
  footer credit line hidden below `sm:` since the bottom nav now serves
  that role. `public/tally.tsx` (this WP's specific page) picked up the
  rest: summary `StatCard`s go 4-across starting at `sm:` instead of
  `lg:`; the ranking table collapses to the top 8 rows by default with
  a "View full ranking (N total)" expand button — backend still returns
  every row regardless (proven by a new test with 10 districts), so
  expanding needs no extra request; the table's already-documented
  reference-only "Points" column is hidden below `sm:` to free up width;
  new `resources/js/components/sports-medal-strip.tsx` — a compact
  horizontally-scrollable icon-forward preview of the busiest 4 sports
  above the existing full `MedalsBySportCard` table (strip is a shorter
  preview, not a duplicate), with a "More sports" tile as a plain
  `#anchor` (not an Inertia `Link`, which would attempt a page
  navigation) scrolling to the full table — per-sport icons are purely
  decorative (Waves/Footprints/Swords/Dumbbell-fallback), no functional
  meaning, since this project's icon set has no sport-specific icons.
  Also added "As of {generatedAt}" to the public tally's info banner
  (same convention the internal admin tally already used) — this page
  never had a generated-at timestamp before. Deliberately not built: no
  "Public View ▾" role-switcher dropdown (the existing Sign in/
  Dashboard button already covers this, no real "view mode" concept
  exists); no restructuring of the official ranking table to show
  individual schools even though the reference's mockup pairs a school
  name with a municipality subtext there — would reopen the "school
  must never read as a competing standing" conflict WP-08-05 already
  resolved; no new "Live Scores" index page (the bottom nav's Live tab
  reuses the meet page's existing "Live now" section, same reasoning as
  WP-08-07's header badge); no changes to the internal admin tally page
  (out of this WP's public-mobile scope). Pest 684/684 (1 new test in
  PublicTallyTest: 10 districts all arrive in props and `generatedAt` is
  present, proving the mobile top-8 collapse is client-side display
  only), full gate green: Pint+PHPStan+ESLint+Prettier+tsc+build. Chrome
  extension still unavailable this session — flagged more prominently
  than usual given how much genuinely visual/responsive surface this WP
  touched (fixed bottom bar, safe-area padding, breakpoint changes). The
  pmms.app Apache vhost routing issue (WP-08-05/06/07/08) remains
  unresolved — checked again this session (MySQL/Apache both running,
  unlike WP-08-04's session, but still serving Laragon's own default
  page for the vhost), not re-investigated further, same not-a-blocker
  reasoning (shared instance serving ~a dozen other local projects).
  docs/public-portal.md extended with "Mobile bottom navigation" and
  "Mobile medal tally" sections; docs/reports/phase-08/
  WP-08-09-completion.md written; `CHECKLIST.md` WP-08-09 checked off.
  Not committed/pushed. Not begun: WP-08-10, per this WP's own rule —
  next on owner instruction.
- WP-08-10 Basketball Live Scoreboard UI — done 2026-07-27. References
  (`desktop-basketball-live-score.png` + mobile counterpart) were, for
  once, actually correctly listed. They show a far richer scoreboard
  than what's tracked: game clock, 24-second shot clock, timeouts,
  quarter-by-quarter breakdown, a play-by-play with player names/jersey
  numbers, full team shooting/rebounding/assist stats, and per-player
  "top performers" with photos. Checked what's real first: basketball
  `sport_state` is still just `{fouls_a, fouls_b}` (WP-07-04); no clock/
  shot-clock/timeout state exists anywhere; and critically, no scoring
  event anywhere in this app records which athlete did anything — a
  point is only ever attributed to a side, never a player, so per-player
  stats/top-performers are structurally impossible without a new
  feature. Presented the owner three options before writing code:
  restyle with real data only; restyle plus two cheap new trackers
  (timeouts, an operator-set clock value); or a full build (real
  per-player attribution, functioning clocks, derived box score).
  **Owner chose: restyle with real data only.** No clock, shot clock,
  timeouts, team stats, box score, or top performers were built. What
  WAS built, all real: a genuine play-by-play feed — new
  `ScoringSession::playByPlay()`/`describeEvent()`, included in
  `toLivePayload()` so every board type gets it for free, not just
  basketball, reconstructed by replaying every point/correction event in
  order (same `max(0,...)` floor the controller itself uses) since a
  single event's payload only records the one side it changed — capped
  at 30 events, newest first, `LiveScoreDisplay` shows the first 8 with
  a "View full play by play" expand (same collapse pattern WP-08-09
  established for the mobile ranking table); fouls rendered as dots
  (`FoulDots`) instead of a bare number — same real count, different
  rendering; real match metadata (sport/category/venue/scheduled_date,
  the latter two new — both controllers now eager-load `schedule.venue`)
  in a breadcrumb-style header replacing the plain title on both
  `scoring/show.tsx` and `public/scoreboard.tsx`; and a "disconnected"
  indicator — both pages already polled every 5 seconds (WP-07-01) but
  silently retried failures with no visible signal, closing a real
  pre-existing gap against this phase's own "support ... disconnected
  ... states" rule rather than new scope invented for this WP (after 2
  consecutive poll failures, `LiveScoreDisplay` shows a "Connection
  lost — retrying automatically" banner, cleared by any successful poll
  or Echo push). Reverb updates, 5-second polling, and the provisional-
  score badge were already real and unchanged (WP-07-01/02/08) —
  re-verified via tests, not rebuilt. Pest 687/687 (3 new tests: the
  play-by-play feed reconstructs correctly newest-first with correct
  per-row running scores across a point/foul/point sequence; both the
  internal and public scoreboard pages expose the new real metadata),
  full gate green: Pint+PHPStan+ESLint+Prettier+tsc+build. Chrome
  extension still unavailable this session. The pmms.app Apache vhost
  routing issue (WP-08-05 onward) remains unresolved, not
  re-investigated this WP, same not-a-blocker status. docs/live-
  scoring.md extended with a "Basketball scoreboard visual alignment
  (WP-08-10)" section recording the scoping decision and what was/
  wasn't built; docs/reports/phase-08/WP-08-10-completion.md written;
  `CHECKLIST.md` WP-08-10 checked off. Not committed/pushed. Not begun:
  WP-08-11, per this WP's own rule — next on owner instruction. Note for
  whoever picks up WP-08-11: WP-08-01 flagged that **Athletics has no
  existing live-scoreboard foundation at all** — `ScoreboardType` has no
  Athletics case, so that reference needs new backend modeling before
  any UI work, not a restyle — likely needs the same kind of owner
  scoping decision this WP and WP-08-06 both needed, probably even
  earlier (whether to build Athletics live scoring at all, not just how
  to style it).
- WP-08-11 Athletics Live Event UI — done 2026-07-27. Confirmed and
  extended WP-08-01's flag before writing code: `ScoreboardType` has no
  Athletics case, and more fundamentally, no scoring event anywhere in
  this app attributes a time/mark to an individual athlete mid-event —
  the whole `ScoringSession`/`EventMatch` model is built around two-
  sided team matches, which a multi-competitor race or field event
  doesn't fit at all; a bigger structural gap than WP-08-10's basketball
  one, not just a missing visual style. "Meet Records" (a wholly separate
  historical-records concept) doesn't exist in any form either.
  Presented the owner three options: a real shell using only what's real
  (schedule + medal totals + validated results once they exist, honest
  notice about no live tracking); new backend infrastructure for real
  per-athlete athletics results; or deferring the WP entirely. **Owner
  chose: real shell only.** New public route `/meets/{meet}/athletics` →
  `PortalController::athletics()` → `public/athletics.tsx`: for a
  selected day, every Athletics-sport `EventSchedule` slot with a real
  Upcoming/Ongoing/Completed status (the exact same time-window-vs-
  `now()` derivation `DashboardController::eventsOverview()` established
  in WP-08-04, reused not reinvented); once validated, an event's real
  top-3 placements with real marks (the same `EventResult`/
  `ResultPlacement` data `/meets/{meet}/results` already shows, filtered
  to Athletics, attached inline per event — an unvalidated/encoded
  result correctly shows nothing, proven by test); a medal-totals summary
  scoped to Athletics only via `MedalTallyService::standings($meetId,
  $athleticsSportId)`, summed; an explicit banner stating live per-
  athlete tracking isn't available yet. Linked from `/meets/{meet}` via
  a new "Athletics schedule and results" button shown only when the meet
  actually has Athletics events (`hasAthletics`, derived from the same
  schedule query `meet()` already runs, no extra query). Deliberately
  not built: live race clock/shot clock, per-athlete live position/time/
  gap, live field-event standings, meet records register, weather,
  live-updates ticker, any new `ScoringSession`/`ScoreboardType`
  infrastructure, and no `PublicMeetNav` tab (not one of that shared
  component's three fixed destinations, and not every meet has Athletics
  events — reached via a conditional link instead, same discovery
  pattern the live-scoreboard page already uses). Hit two real gotchas:
  the project's documented "new Inertia page needs a build before its
  first page-render test passes" issue (rebuilt, then all tests passed);
  and accidentally regenerated Wayfinder routes without `--with-form`
  while hand-adding the new route, briefly breaking every page using a
  route's `.form()` helper — caught immediately by `tsc --noEmit`,
  fixed by regenerating with `--with-form` (these generated directories
  are gitignored, so no diff was ever at risk). Pest 693/693 (6 new
  tests in PublicAthleticsTest covering guest access, sport filtering,
  upcoming/completed status derivation, real placements/marks, medal
  totals scoped to Athletics only, and unvalidated results never
  appearing), full gate green: Pint+PHPStan+ESLint+Prettier+tsc+build.
  Chrome extension still unavailable this session. The pmms.app Apache
  vhost routing issue (WP-08-05 onward) remains unresolved, not
  re-investigated this WP, same not-a-blocker status. docs/public-
  portal.md extended; docs/reports/phase-08/WP-08-11-completion.md
  written; `CHECKLIST.md` WP-08-11 checked off. Not committed/pushed.
  Not begun: WP-08-12, per this WP's own rule — next on owner
  instruction. Note for whoever picks this up: unlike Athletics,
  Softball/Baseball already has a real `ScoreboardType`/`sport_state`
  foundation (WP-07-06), so this WP should be closer in shape to
  WP-08-10's basketball restyle than to this WP's shell-only outcome —
  still worth re-checking what per-player data (if any) that reference
  expects before assuming so.
- WP-08-12 Softball and Baseball Live Scoreboard UI — done 2026-07-27.
  Checked the reference first as WP-08-11 flagged to: same shape of gap
  as basketball (WP-08-10) — a "Team Comparison" panel (hits/errors/
  walks/strikeouts/stolen bases/batting average/slugging %), per-player
  "Top Performers"/"Current Pitcher" panels, none tracked; the
  reference's baserunner diamond isn't real either (no baserunner model
  has ever existed, a deliberate WP-07-06 omission). Since the owner had
  already answered this exact structural question twice (WP-08-10,
  WP-08-11) with "real data only," and this WP is the same shape as
  WP-08-10's specifically (real `sport_state` core exists; reference
  wants extra per-player data that doesn't), applied that established
  answer directly rather than asking a third time. Built: a proper line-
  score table (`SoftballLineScore`) from the real `sport_state.innings`
  breakdown — deliberately not a fixed 7/9-inning grid, since no
  configured game length is tracked, so only innings that actually
  happened get a column; balls/strikes/outs as colored dot rows via a
  new generic `CountDots` (basketball's `FoulDots` generalized on its
  second use) with real caps matching WP-07-06's own auto-reset rules
  (3 balls/2 strikes/2 outs); real play-by-play descriptions for
  softball's own event types (`Count`/`InningRun`, previously falling
  through `describeEvent()`'s bare-label fallback) — deliberately no
  inferred "walk"/"strikeout" labels, since `count()`'s payload has no
  batter/side field to ground such an inference in. Found and fixed a
  real bug while extending this: `playByPlay()`'s running-score
  reconstruction (WP-08-10) only replayed point/correction deltas,
  silently ignoring `InningRun` and `RoundScore` — a softball or boxing
  play-by-play's displayed score would have stayed frozen at 0-0 all
  game. Not a regression against anything shipped (`playByPlay()` itself
  is uncommitted WP-08-10 work) but a real catch, found by a failing test
  while writing this WP's own coverage; fixed for both sports, and gave
  `RoundScore` a real description too while already in that method,
  closing boxing's last generic-fallback gap as a side effect. Match-
  header metadata and the disconnected indicator were already generic
  (not basketball-gated) from WP-08-10, so softball/baseball got them
  for free — reconfirmed via tests, not rebuilt. Pest 695/695 (2 new
  tests: softball play-by-play descriptions with correct reconstructed
  running scores; the same running-score fix proven for boxing's
  RoundScore), full gate green: Pint+PHPStan+ESLint+Prettier+tsc+build.
  Chrome extension still unavailable this session. The pmms.app Apache
  vhost routing issue (WP-08-05 onward) remains unresolved, not
  re-investigated this WP, same not-a-blocker status. docs/live-
  scoring.md extended; docs/reports/phase-08/WP-08-12-completion.md
  written; `CHECKLIST.md` WP-08-12 checked off. Not committed/pushed.
  Not begun: WP-08-13, per this WP's own rule — next on owner
  instruction. Note for whoever picks this up: WP-08-13's own title
  ("Shared Tables, Cards, Charts, Scoreboards, and Filters") suggests a
  consolidation/audit pass over components already built across WP-08-04
  through WP-08-12 (`StatCard`, `MedalDistributionCard`,
  `TopByPointsCard`, `MedalsBySportCard`, `RankBadge`, `CountDots`,
  `SoftballLineScore`, etc.) rather than new scoreboard work — worth
  confirming its actual scope against its reference images before
  assuming, same discipline every WP this phase has needed.
- WP-08-13 Shared Tables, Cards, Charts, Scoreboards, and Filters — done
  2026-07-27. Confirmed the prediction from WP-08-12's note: no
  reference image actually depicts "shared components" as a concept
  (back to the same wrong generic image list every WP starts with, this
  time with no sport-specific exception) — this WP is a consolidation/
  audit pass, not new visual work against a mockup. Delegated a
  read-only background audit across every page using a Select-based
  filter, the shared Table components, and StatCard grids; verified each
  finding by hand before acting, since several turned out correct-by-
  context rather than bugs: "bare overflow-x-auto without a border" on 5
  tables was correct — all five sit inside a Card or a `rounded-xl
  border` section that already provides one, adding a second would have
  been a double-border regression; "Rank/# column width varies" was
  correct — the header text itself varies ("Rank" vs "#"), so different
  widths are proportional, not inconsistent; single-filter pages missing
  the `flex flex-wrap gap-2` wrapper render pixel-identical with or
  without it, nothing to fix; two report pages' `gap-3 items-center`
  filter row was correct on one of them (mixes a date Input with a
  differently-sized text label, genuinely needs items-center) so left
  both alone rather than partially fixing a 4px difference with no other
  effect. Three real inconsistencies found and fixed:
  `incidents/index.tsx`'s status filter was `w-44` against every
  identical status-filter pattern elsewhere (`w-56`) — normalized; the
  medal-summary StatCard grid (Total Gold/Silver/Bronze/Medals, same
  four cards/same data) used `lg:grid-cols-4` on the internal admin
  tally page (WP-08-05) but `sm:grid-cols-4` on the two public pages
  built afterwards (WP-08-08/09) — the same widget looked different on a
  tablet-width screen depending on which page you were on; normalized
  `tally/index.tsx` to match the more deliberately-chosen public
  breakpoint; four `EmptyState` call sites (`accreditation/index.tsx`
  and `reports/delegation-roster.tsx`'s athlete/personnel panels)
  omitted a description, rendering visibly shorter than every sibling
  empty state on the same pages — added the exact wording `athletes/
  index.tsx`/`personnel/index.tsx` already established rather than
  inventing new phrasing. Reconfirmed (no change needed): dashboard's
  two distinct StatCard grids and management/index.tsx's matching one
  are a genuinely different widget category already consistent with
  each other; the donut/segmented-bar/bar-list chart family already
  shares one visual language despite being three different chart types,
  no further extraction needed; scoring/show.tsx and public/
  scoreboard.tsx both still render exclusively through the shared
  LiveScoreDisplay with no local reimplementation. New docs/ui-ux/
  shared-components.md catalogs every shared presentational component
  built across Phase 8 (file, originating WP, consuming pages) plus
  this audit's findings, so a future pass doesn't need to re-run it from
  scratch. No new tests needed (pure className/prop consistency fixes,
  no new behavior); existing coverage for every touched page
  (AccreditationTest, IncidentTest, MedalTallyTest, ReportTest) re-run
  and confirmed unaffected. Pest 695/695 unchanged, full gate green:
  Pint+PHPStan+ESLint+Prettier+tsc+build. Chrome extension still
  unavailable this session. The pmms.app Apache vhost routing issue
  (WP-08-05 onward) remains unresolved, not re-investigated this WP,
  same not-a-blocker status. docs/reports/phase-08/
  WP-08-13-completion.md written; `CHECKLIST.md` WP-08-13 checked off.
  Not committed/pushed. Not begun: WP-08-14, per this WP's own rule —
  next on owner instruction. Note for whoever picks this up: likely
  another cross-cutting pass rather than new-page work, similar in shape
  to this WP — worth confirming scope against its own reference images
  before assuming.
- WP-08-14 Responsive Mobile Tablet and Large Display Alignment — done
  2026-07-27. Confirmed the prediction: same generic wrong reference
  list, no image depicting "responsive alignment" — a second
  consolidation/audit pass, this time targeting responsive-breakpoint
  behavior (missing scroll wrappers, non-collapsing grids, fixed widths,
  touch targets, large-display space use, the 640-1023px tablet range)
  rather than cross-page component consistency (WP-08-13's scope).
  Delegated a second background audit; verified every finding by hand
  before acting, same discipline as WP-08-13 — several were correct-by-
  design: `division/edit.tsx`'s capped form width and `scoring/show.tsx`'s
  capped control-panel width are standard, correct readability practice,
  not wasted large-display space; dashboard's 3-column events-overview
  legend has short enough labels to not need a responsive prefix; all 52
  Table usages already have an overflow-x-auto ancestor. Two real issues
  fixed: `accreditation/cards.tsx`'s printable ID cards (336px fixed
  width, Tailwind v4 dynamic spacing) sat in a bare flex-wrap with no
  scroll wrapper — would overflow the page on phones under ~370px since
  flex-wrap wraps between items, not within one; added `max-w-full`
  alongside the fixed width so it shrinks to fit below 336px while
  staying fixed (better for its real print purpose) above that. Six
  widget-pair/sidebar grids (ranking-table+donut and top-by-points+
  medals-by-sport on both `tally/index.tsx` and `public/tally.tsx`;
  dashboard's schedule+companion-widget and recent-activity splits)
  jumped straight from single-column mobile to a split layout at `lg:`
  (1024px), leaving the whole 640-1023px tablet range single-column for
  content wide enough to benefit from splitting sooner — moved all six
  to `md:` (768px), verified safe first since every table involved
  already has its own overflow-x-auto and the sidebar widgets wrap
  gracefully rather than overflow at a narrower width. Also confirmed
  (not changed) WP-04-06's accepted 32px touch-target convention still
  applies unchanged to the two public pages added since that review
  (`public/scoreboard.tsx`, `public/athletics.tsx`) — extended
  docs/public-portal.md's accepted-deviations note to say so explicitly.
  No new tests needed (pure className/breakpoint changes, no new
  behavior); full suite re-run and confirmed unaffected. Pest 695/695
  unchanged, full gate green: Pint+PHPStan+ESLint+Prettier+tsc+build.
  Chrome extension still unavailable this session — flagged more
  pointedly than usual this time, since two consecutive WPs' worth of
  static-analysis-only responsive/consistency fixes have now
  accumulated with zero live viewport verification. The pmms.app Apache
  vhost routing issue (WP-08-05 onward) remains unresolved, not
  re-investigated this WP, same not-a-blocker status. docs/ui-ux/
  shared-components.md extended with a "Responsive breakpoint audit
  (WP-08-14)" section; docs/reports/phase-08/WP-08-14-completion.md
  written; `CHECKLIST.md` WP-08-14 checked off. Not committed/pushed.
  Not begun: WP-08-15, per this WP's own rule — next on owner
  instruction. Note for whoever picks this up: WP-08-15 is "Visual
  Regression and Accessibility Review" — its visual-regression component
  may itself need an owner scoping conversation about how to proceed
  given the Chrome extension has been unavailable for every WP in this
  entire phase, rather than assuming a workaround exists.
- WP-08-15 Visual Regression and Accessibility Review — done 2026-07-27.
  Raised the flagged question before writing anything: this project has
  no screenshot/visual-diff tooling of any kind, and the Chrome
  extension has been unavailable for every WP this phase, so there was
  no way to capture real screenshots to diff manually or automatically.
  Presented the owner two options: accessibility-only this session with
  visual regression documented as deferred, or installing a screenshot-
  testing tool and establishing a first baseline now (a genuine
  first-of-its-kind new dependency, comparable in weight to Phase 7's
  Reverb decision). **Owner chose: accessibility-only, visual regression
  deferred.** Accessibility audit deliberately scoped to everything new
  or significantly modified since the project's two prior dedicated
  accessibility passes (WP-04-06 for the original public portal,
  WP-07-03 for the original live-scoring UI) — i.e. every page/component
  built across WP-08-03 through WP-08-14. Delegated a background audit
  across 7 categories (icon-only elements, decorative-icon aria-hidden,
  live regions, heading order, color-only information, focus management,
  form labels); verified every finding by hand, same discipline as
  WP-08-13/14's audits — most categories came back already correct.
  Real gaps found and fixed: six decorative icons sitting directly next
  to their own visible text label, missing `aria-hidden="true"`
  (Download/Printer/Info in tally/index.tsx, Info in public/tally.tsx
  and public/athletics.tsx, Plus in eligibility/index.tsx) — a screen
  reader would otherwise announce the icon's implicit name redundantly
  alongside the text already doing that job. One finding examined and
  deliberately left unchanged: the play-by-play list (WP-08-10/12) is
  not an aria-live region — re-announcing every new play on every poll/
  Echo push would be disruptive noise for a screen-reader user during an
  active game, not a helpful update; the running score's existing
  aria-live="polite" remains the one audible live signal, confirmed as a
  deliberate, correct design choice rather than an oversight. Everything
  else checked (icon-only buttons, other decorative icons, heading
  order, color-only info, focus management on the new bottom nav/expand
  buttons, form labels on new search/filter controls) verified already
  sound, no changes needed. New docs/ui-ux/accessibility-review.md
  records the scope decision and the full audit findings (including
  everything verified sound) so a future pass doesn't need to re-check
  it. No new tests needed (pure aria-hidden attribute additions, no new
  behavior); full suite re-run and confirmed unaffected. Pest 695/695
  unchanged, full gate green: Pint+PHPStan+ESLint+Prettier+tsc+build.
  Chrome extension still unavailable this session — now three
  consecutive WPs (08-13/14/15) recommending a real device/browser QA
  pass before Phase 8 closes out. The pmms.app Apache vhost routing
  issue (WP-08-05 onward) remains unresolved, not re-investigated this
  WP, same not-a-blocker status. docs/reports/phase-08/
  WP-08-15-completion.md written; `CHECKLIST.md` WP-08-15 checked off.
  Not committed/pushed. Not begun: WP-08-16, per this WP's own rule —
  next on owner instruction. Note for whoever picks this up: WP-08-16
  ("Phase 8 Final Visual Acceptance") may itself need to open with the
  same question this WP opened with — how to reach "acceptance" honestly
  without ever having seen the app rendered this entire phase.
- WP-08-16 Phase 8 Final Visual Acceptance — done 2026-07-27. **This
  closes Phase 8 (all 16 WPs).** Not new UI work — the phase-closing
  compliance review, following the same template this project already
  used for Phase 5 and Phase 7's own closing reviews. New
  docs/phases/phase-08-ui-ux-visual-alignment/phase-8-compliance-review.md:
  architecture conformance table, result-integrity boundary re-
  verification (grepped every Phase 8-touched file with any EventResult/
  ResultPlacement relationship — zero write references, confirmed read-
  only throughout), authorization re-verification (zero Policy/Gate
  files touched all phase, confirmed via git diff --stat), a final full
  quality-gate run, and — specific to this phase — a table re-affirming
  all seven reference-vs-real-app scoping conflicts raised to the owner
  across the phase (WP-08-05 points-ranking, WP-08-06 automated-
  eligibility-rules, WP-08-09 school-ranking, WP-08-10 basketball clock/
  box-score, WP-08-11 athletics live-race data, WP-08-12 softball
  equivalent, WP-08-15 visual-regression tooling) are still implemented
  exactly as decided, not silently reverted or drifted. Verified before
  writing, not assumed: composer.json/lock and package.json/lock have
  zero diff across all 16 WPs (confirmed zero new dependencies added
  this phase); composer audit and npm audit both clean. Final gate:
  Pint PASS, PHPStan L7 PASS (0 errors), Pest PASS 695/695 (3,640
  assertions — 649 at Phase 7's close, +46 across this phase),
  ESLint/Prettier/tsc PASS, npm run build PASS. Re-checked both standing
  environment gaps one last time: Chrome extension still disconnected
  (every one of 16 WPs this phase had zero live browser verification —
  named as the one real gap in the review, honestly, not glossed over);
  pmms.app returns HTTP 200 but is still serving Laragon's own default
  placeholder page, not the app itself (vhost config is correct, the
  running Apache process just needs a restart — declined to do
  unilaterally, same reasoning as every WP since WP-08-05, a shared
  instance serving ~a dozen other local projects). Recommendation:
  Phase 8 is complete and internally consistent — real data used
  throughout, Phase 3's result-integrity core and every authorization
  rule completely untouched, zero new dependencies, green gate — but a
  real device/browser QA pass (or restored Chrome extension
  connectivity) is the recommended next step before treating the
  phase's visual work as fully signed off, ahead of any commit decision.
  docs/reports/phase-08/WP-08-16-completion.md written; `CHECKLIST.md`
  WP-08-16 checked off (**all 16 Phase 8 WPs now complete**). Not
  committed/pushed. Phase 9 — Post-Deployment Support is already
  scaffolded at docs/phases/phase-09-post-deployment-support/ and ready
  to pick up on owner instruction, alongside the owner's commit decision
  for the Phase 8 tree.
