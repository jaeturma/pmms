# Current Phase
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

**Do not start Phase 6 from `docs/phases/phase-06-reports-uat-
deployment-turnover/` as-is** (owner confirmed 2026-07-25 not ready to plan
it yet). Checked 2026-07-25: unreviewed generic-template scaffolding that
Phase 5's directory also had (see "Phase 5" section below for how that got
fixed) — never written for or checked against this codebase; still carries
the "municipality as the official delegation" assumption (WP-06-01) that
collides with the Division initiative's real model. The same problem hit
`docs/phases/phase-07-live-scoring-enhancement/` too (invented a
"Tournament Manager" role, falsely claimed an existing "Reverb foundation")
— that one has now been corrected into a real plan (see "Phase 7" section).
Write a real plan for Phase 6 the same way Phase 4's, Phase 5's, and
Phase 7's were (git history `a7bde91`, and the Phase 5/Phase 7 planning
commits) when it's actually next — not adapted from this scaffolding.
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
COMPLETE 2026-07-26, all 3 WPs executed one at a time on owner instruction.
Review: docs/phases/phase-07-live-scoring-enhancement/
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
