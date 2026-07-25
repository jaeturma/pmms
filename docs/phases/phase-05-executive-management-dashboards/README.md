# Phase 5 — Executive and Management Dashboards

**Status:** COMPLETE 2026-07-25 — all 8 WPs executed, WP-05-08 review COMPLIANT
(`phase-5-compliance-review.md`). Replaces the unreviewed generic-template draft that occupied this directory
before (see git history for this commit — the old content and its "why this
was wrong" note are preserved there): that draft invented DepEd job-title
roles (`Schools Division Superintendent`, `Committee Head`, etc.) that don't
exist in this codebase, and assumed "medal tally is delegation-based" /
"municipality as the official delegation," which collides with the Division
initiative's real model.

## Goal

Give Admin and Organizer — the two roles Phase 3's dashboard already treats as
"managers" (`Gate::manage-meet-data`) — a view **across meets and school
years**, not just the one currently-Active meet Phase 3's operations block
covers. Phase 3 answers "what does today look like for the meet that's
running right now"; Phase 5 answers "how is the program doing over time":
participation trends, registration/validation throughput, delegation and
school performance history, venue utilization.

## Grounding

- Real roles: `App\Enums\UserRole` — Admin, Organizer, Delegation Officer,
  Viewer (`docs/authorization.md`). Phase 5 dashboards are Admin/Organizer
  only, gated by the existing `manage-meet-data` gate (same gate that already
  scopes Phase 3's operational queues) — no new roles.
- Phase 3 baseline to reuse, not duplicate: `DashboardController`'s
  `operations` block (`docs/dashboard.md`) already covers the single Active
  meet in real time; Phase 5 does not re-show that data, it adds the
  cross-meet layer on top.
- Division initiative (`docs/division.md`, `docs/medal-tally.md`): every
  individual (athlete, personnel, entry, result, medal) is attributed to
  their own **school**; a delegation is a school (City) or a municipality
  (Province) depending on division type. Any Phase 5 aggregate that groups
  "by delegation" must say explicitly whether it means the registering unit
  or the individual's home school — the same distinction that took 7 work
  packages to get right internally.
- `MedalTallyService::standings()` is reused per-meet and aggregated across
  meets for the performance-history WP — not reimplemented.
- No `Committee` entity exists in this codebase (protests/incidents are the
  closest real concepts); no committee-specific dashboard is in scope.

## Principles

- Read-side only — no new mutable state beyond what operations already
  produce. Phase 5 adds queries and pages, not new source-of-truth tables
  (aggregates may be cached/materialized later if performance requires it,
  not before there's a real reason).
- Admin/Organizer only, reusing `manage-meet-data` — Delegation Officer and
  Viewer keep exactly what Phase 3 already gives them; nothing in Phase 5
  changes their access.
- MySQL remains the source of truth; every trend is computed from the same
  tables operations write, never a separate reporting store.
- Minors stay protected: cross-meet views aggregate (counts, trends,
  standings) — they do not add new athlete-level detail views beyond what
  already exists in the athlete registry.
- One work package at a time; nothing committed or pushed without owner
  instruction, matching every phase before this one.

## Work Packages

| WP | Title |
|---|---|
| WP-05-01 | Management Dashboard Foundation |
| WP-05-02 | Participation & Registration Trends |
| WP-05-03 | Operations Progress & Risk |
| WP-05-04 | Delegation & School Performance History |
| WP-05-05 | Venue Utilization |
| WP-05-06 | Management Reports & Export |
| WP-05-07 | Accessibility & Mobile Review |
| WP-05-08 | Phase 5 Review and Acceptance |

Sequence is strict: each WP assumes its predecessors.

## Visual Checkpoints

1. **After WP-05-02:** an Admin/Organizer opens the new management view and
   sees participation and registration progress trending across multiple
   meets, not just the currently-Active one.
2. **After WP-05-05:** the full cross-meet picture — operations risk,
   delegation/school performance history, venue utilization — is visible in
   one place.
3. **After WP-05-07:** the management dashboards are demonstrable on a phone.

## Exclusions (deferred or out of scope)

New roles or role-specific dashboards beyond Admin/Organizer; any `Committee`
entity; public-facing cross-meet analytics (product scope already defers this
for the portal, see `docs/public-portal.md`); predictive/AI features; anything
requiring a new mutable data store distinct from existing tables; Flutter.

## Completion

Phase 5 completes via WP-05-08 (full quality gate + compliance review),
mirroring WP-03-11/WP-04-11. The review report goes to this directory; the WP
log lives in `.ai/current-phase.md`.
