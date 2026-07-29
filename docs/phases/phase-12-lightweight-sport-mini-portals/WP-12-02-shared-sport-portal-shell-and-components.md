# WP-12-02 — Shared Sport-Portal Shell and Components

## Project
Provincial Meet Management System (PMMS) — Division Edition

## Phase
Phase 12 — Lightweight Sport Mini Portals

## Visual Direction
Arena's card-grid rhythm, applied to a broadcast-style live section and
compact game lists — reusing `LiveScoreDisplay`, `PublicPageHero`,
`StatCard`, `EmptyState`, `TeamLogo`, `sportIcon()` exactly.

## Required Reading
```text
.ai/project-rules.md
.ai/work-package-runner.md
docs/phases/phase-12-lightweight-sport-mini-portals/README.md
docs/phases/phase-12-lightweight-sport-mini-portals/DESIGN-NOTES.md
docs/phases/phase-12-lightweight-sport-mini-portals/INSPECTION-REPORT.md
docs/phases/phase-12-lightweight-sport-mini-portals/DATA-CONTRACT-MAP.md
resources/js/components/live-score-display.tsx
resources/js/components/sports-medal-strip.tsx
app/Http/Controllers/PortalController.php
```

## Rules
- Inspect the repository first.
- Build the reusable shell/components before implementing any single
  sport page — no duplicated per-sport pages (brief's own Step 3).
- One new read-only route/controller action shape
  (`GET /{sportSlug}`), resolving `Meet::published()->active()->first()`
  — additive only, no existing route/controller/page touched.
- Reuse `LiveScoreDisplay`/`EmptyState`/`PublicPageHero`/`StatCard`/
  `TeamLogo`/`sportIcon()` exactly — build new shared components only
  for what doesn't already exist (game-list cards, venue-info card,
  the sport-config source, the "not available yet" section state).
- Standings/Leading Scorers/Bracket render the resolved honest
  "not available yet" state (`DATA-CONTRACT-MAP.md` §D/E/F) — no new
  backend query for any of the three.
- No new dependency, no new migration.
- Run all quality checks.
- Update documentation.
- Do not commit or push.
- Do not begin the next work package.

## Exclusions
Any new team-standings/per-athlete-scoring/bracket-tree backend logic;
implementing any specific sport's actual page (that's WP-12-03);
`PublicBottomNav`/header `topNavItems` (not touched this WP — nav
integration is a smaller, later decision per `DESIGN-NOTES.md`).

## Objective
Build the reusable sport-portal shell: a `SportPortalConfig` source
(12 entries, one per sport, `scoringType`/`supportsStandings`/
`supportsLeadingScorers`/`supportsBracket` all honestly `false` for
Standings/Scorers/Bracket per the resolved decision), the shared
section components (Live Now, Today's/Completed/Upcoming Games,
Venue Information, and a generic "not available yet" state for the
other three), and the one new route/controller action resolving the
active meet + a sport by slug.

## Acceptance Criteria
- New route additive only, read-only, resolves the active published
  meet via the existing scope.
- No fabricated data anywhere — Standings/Leading Scorers/Bracket
  render the honest empty state, never invented numbers.
- No private/admin data exposed.
- Responsive and accessible; reduced-motion behavior works.
- Tests added (route resolution, no-active-meet state, unknown-slug
  404/empty state, public-safe fields).
- Full quality gate green.
- Documentation updated (`docs/public-portal.md`).
- No commit or push performed.

## Completion Report
Create:
```text
docs/reports/phase-12/WP-12-02-completion.md
```

Next:
```text
WP-12-03 — Basketball Reference Implementation
```
