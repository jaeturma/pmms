# WP-12-06 — Performance and Visibility-Aware Polling

## Project
Provincial Meet Management System (PMMS) — Division Edition

## Phase
Phase 12 — Lightweight Sport Mini Portals

## Required Reading
```text
.ai/project-rules.md
.ai/work-package-runner.md
docs/phases/phase-12-lightweight-sport-mini-portals/README.md
docs/phases/phase-12-lightweight-sport-mini-portals/DESIGN-NOTES.md
resources/js/pages/public/tally.tsx
resources/js/pages/public/scoreboard.tsx
```

## Rules
- Inspect the repository first.
- Every existing poll in this app (`tally.tsx` kiosk, `scoreboard.tsx`)
  runs continuously with no pause-on-hidden-tab behavior — build a
  small, reusable `document.visibilitychange`-aware hook rather than a
  one-off for this phase alone (worth reusing later).
- Fetch only the currently open sport — no preloading other sports'
  data (brief's own §9).
- No new dependency (no polling library — Inertia's own `usePoll`/
  `router.reload` stays the mechanism).
- Run all quality checks.
- Update documentation.
- Do not commit or push.
- Do not begin the next work package.

## Exclusions
Any change to existing pages' own polling behavior (`tally.tsx`,
`scoreboard.tsx`) — this WP adds new, reusable polling behavior for
this phase's own pages, it does not retrofit existing ones.

## Objective
Implement the brief's own refresh-interval table (§9) per section
(Live Now 5-10s, Today's Games 30-60s, Completed/Upcoming/Standings/
Scorers/Bracket 60s-5min or cached) with visibility-aware pausing —
polling stops when the tab is hidden, resumes when visible, and only
runs at all when there's live data to justify it.

## Acceptance Criteria
- Polling pauses when the browser tab is hidden and resumes when
  visible (verifiable via a test simulating `visibilitychange`).
- No duplicate timers, no memory leak on unmount.
- No sport's data is fetched while viewing a different sport's page.
- Full quality gate green.
- Documentation updated.
- No commit or push performed.

## Completion Report
Create:
```text
docs/reports/phase-12/WP-12-06-completion.md
```

Next:
```text
WP-12-07 — Accessibility and Loading/Error-State Review
```
