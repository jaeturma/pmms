# Current Phase
Phase 4 — Responsive Public Portal: PLANNED 2026-07-19 — pending owner approval.
Plan: docs/phases/phase-04-responsive-public-portal/ (README + WP-04-01..07).
Seven WPs: portal foundation & publication controls → public schedule/venues →
public results (validated only) → public medal tally → announcements →
accessibility/mobile review → compliance review. Replaces the stale pre-Phase-2
draft (11 boilerplate WPs on a municipality-delegation model that was never
built, plus public athlete profiles the product scope explicitly defers).
Grounded in product-scope §9: published schedules, validated results, medal
tally, announcements — nothing else public; publication is an explicit audited
manager decision; athlete identity on the portal is name+school+placement only.
Execute one work package at a time on owner instruction.

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
