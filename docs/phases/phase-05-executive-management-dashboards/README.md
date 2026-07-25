# Phase 5 — Executive and Management Dashboards

**Status: NOT STARTED — this directory is unreviewed generic template
scaffolding, not a reviewed plan.** It was generated the same way the
Phase 3 and Phase 4 directories were before those got reconciled against
shipped code (see their own README.md status notes and
`.ai/current-phase.md`) — except Phase 5 has no shipped code to reconcile
against yet, so nobody has checked this content. Two concrete inaccuracies
confirmed 2026-07-25, do not build against them without fixing first:

1. **Wrong role model.** WP-05-02 through WP-05-07 assume roles ("Schools
   Division Superintendent", "Assistant SDS", "Education Program
   Supervisor", "Sports Coordinator", "Secretariat", "Tournament Manager",
   "Committee Head") that do not exist anywhere in this codebase. The real
   roles are `App\Enums\UserRole`: Admin, Organizer, Delegation Officer,
   Viewer (`docs/authorization.md`). Any real Phase 5 plan needs to map
   dashboard needs onto these four roles, not invent new ones.
2. **"Municipality delegations... official Provincial Meet summaries" /
   "medal tally is delegation-based"-style assumptions** (see
   WP-05-01/08/09 and others) collide with the Division initiative
   (`docs/division.md`, `docs/medal-tally.md`): a delegation is a school
   OR a municipality depending on division type, but every individual
   (athlete, personnel, entry, result, medal) is attributed to their own
   **school**, never the delegation — that distinction took 7 dedicated
   work packages to get right and cost a real production-shaped bug in
   the original Phase 4 draft (`docs/phases/phase-04-responsive-public-
   portal/README.md`). A Phase 5 dashboard built on "aggregate by
   delegation" alone would reintroduce it.

Treat this directory as a rough idea list, not a spec, until it's
actually reviewed and rewritten for this codebase — the same way Phase 4's
real plan (git history `a7bde91`) was written specifically for this
project rather than reused from a template.

## Goal

Provide clear, role-based dashboards for DepEd Division Office managers and meet coordinators.

## Main Users

- Schools Division Superintendent
- Assistant Schools Division Superintendent
- Education Program Supervisors
- Sports Coordinator
- Secretariat
- Tournament Managers
- Committee Heads

## Dashboard Focus

- Participation
- Registration progress
- Validation progress
- Event progress
- Results completion
- Medal tally
- Delegation and school performance
- Committee status
- Venue usage
- Pending issues
- Basic operational risks

## Work Packages

- [WP-05-01 — Dashboard Data and Access Foundation](WP-05-01-dashboard-data-and-access-foundation.md)
- [WP-05-02 — Schools Division Superintendent Dashboard](WP-05-02-schools-division-superintendent-dashboard.md)
- [WP-05-03 — Assistant Schools Division Superintendent Dashboard](WP-05-03-assistant-schools-division-superintendent-dashboard.md)
- [WP-05-04 — Education Program Supervisor and Sports Coordinator Dashboard](WP-05-04-education-program-supervisor-and-sports-coordinator-dashboard.md)
- [WP-05-05 — Secretariat Dashboard](WP-05-05-secretariat-dashboard.md)
- [WP-05-06 — Tournament Manager Dashboard](WP-05-06-tournament-manager-dashboard.md)
- [WP-05-07 — Committee Head Dashboard](WP-05-07-committee-head-dashboard.md)
- [WP-05-08 — Delegation and School Performance Dashboard](WP-05-08-delegation-and-school-performance-dashboard.md)
- [WP-05-09 — Meet Progress and Operational Risk Dashboard](WP-05-09-meet-progress-and-operational-risk-dashboard.md)
- [WP-05-10 — Dashboard Reports and Export](WP-05-10-dashboard-reports-and-export.md)
- [WP-05-11 — Dashboard Accessibility and Mobile Review](WP-05-11-dashboard-accessibility-and-mobile-review.md)
- [WP-05-12 — Phase 5 Review and Acceptance](WP-05-12-phase-5-review-and-acceptance.md)

## Execution Rules

1. Run one work package at a time.
2. Inspect the repository first.
3. Use only validated data.
4. Keep dashboards simple and role-based.
5. Run tests and quality checks.
6. Create a completion report.
7. Do not commit or push unless explicitly instructed.
