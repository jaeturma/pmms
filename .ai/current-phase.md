# Current Phase
Phase 3 — Provincial Meet Operations: PLANNED 2026-07-18 — pending owner approval.
Plan: docs/phases/phase-03-provincial-meet-operations/ (README + WP-03-01..11).
Eleven WPs: venues → scheduling → accreditation/IDs → matches → results encoding &
validation → medal tally → protests/incidents → reports → ops dashboard → audit/authz
review → compliance review. The pre-Phase-2 Phase 3 draft (13 boilerplate WPs, six of
which duplicated Phase 2 registration modules) was replaced by this plan.
Execute one work package at a time on owner instruction.

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
