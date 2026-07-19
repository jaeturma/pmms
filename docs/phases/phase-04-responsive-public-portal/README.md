# Phase 4 — Responsive Public Portal

**Status:** Planned 2026-07-19 — pending owner approval. Execution has not started.
Replaces the pre-Phase-2 draft: its eleven boilerplate WPs assumed a
municipality-based delegation model that was never built (Division Edition
delegations are per school), and its public delegation directory and athlete
profile pages exceed the product scope, which explicitly defers public athlete
profiles.

## Goal

Give parents, schools, and the public a mobile-first, no-login window into the
meet: published schedules, validated results, the live medal tally, and official
announcements — nothing else. Publication is an explicit, audited manager
decision; until a meet is published, the portal shows nothing about it. After
Phase 4, meet-day information reaches the public from the same database the
operations side writes to, with zero manual re-publishing of data.

## Grounding

- `docs/00-product/product-scope.md` §9 (Public Portal Scope): **in scope** —
  published schedules, published results, medal tally, public
  advisories/announcements; **deferred** — athlete profile pages, cross-meet
  analytics, public API; **out of scope** — medical data, unvalidated/working
  results, personally sensitive information.
- Phase 3 DESIGN-NOTES: "Public publication belongs to Phase 4"; results affect
  the public view only after validation.
- Phase 3 as-built baseline to reuse: `MeetStatus`, `EventSchedule` (day/venue
  grouping already proven in the daily sheet), validated `EventResult` +
  `ResultPlacement`, `MedalTallyService` (derived, validated-only),
  `AuditLogger`, the shared component library, and the print/report patterns.

## Principles

- Simple, presentable, responsive, maintainable, secure — no enterprise complexity.
- **Publication is opt-in and audited**: a manager publishes a meet; unpublishing
  is immediate; both are audit events.
- **Validated results only, always**: the portal can never show encoded/working
  data — server-enforced, not filtered in the UI.
- **Minors stay protected**: public pages carry athlete name, school, and
  placement only — never birthdate, LRN, grade, photos, or documents; public
  controllers build their own minimal prop sets and never reuse internal ones.
- Public routes need no authentication and use a separate public layout (no app
  sidebar, no session-bound chrome).
- Reuse Phase 1–3 foundations before writing anything new; MySQL remains the
  source of truth.
- One work package at a time; nothing committed or pushed without owner instruction.

## Work Packages

| WP | Title |
|---|---|
| WP-04-01 | Public Portal Foundation & Publication Controls |
| WP-04-02 | Public Schedule & Venue Guide |
| WP-04-03 | Public Results |
| WP-04-04 | Public Medal Tally |
| WP-04-05 | Announcements |
| WP-04-06 | Accessibility & Mobile Review |
| WP-04-07 | Phase 4 Compliance Review |

Sequence is strict: each WP assumes its predecessors. Seven WPs — the phase is
deliberately smaller than Phases 2–3 because it is read-side over data the
operations phases already produce.

## Visual Checkpoints

1. **After WP-04-02:** a visitor with no account opens http://pmms.app on a phone
   and browses a published meet's schedule by day and venue.
2. **After WP-04-04:** validated results and the live medal tally are publicly
   visible; unvalidated results and unpublished meets remain invisible — the
   publication boundary demonstrable end-to-end.
3. **After WP-04-06:** announcements plus the accessibility/mobile polish
   complete the public-facing demo.

## Exclusions (deferred or out of scope)

Public athlete/team profile pages, delegation/school directory, public search
across entities, cross-meet analytics and history, public API/exports, QR/RFID,
livestream or scoreboard integration, public self-registration of any kind,
push/email notifications, offline-first PWA behavior, Flutter, AI, multi-tenancy.

## Completion

Phase 4 completes via WP-04-07 (full quality gate + compliance review), mirroring
WP-03-11. The review report goes to this directory; the WP log lives in
`.ai/current-phase.md`.
