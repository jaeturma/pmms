# Public Sport Portals — Implementation Summary

Phase 12 ("Lightweight Sport Mini Portals"), WP-12-01 through WP-12-08,
all executed one work package at a time on owner instruction, 2026-07-29
through 2026-07-30. Full WP-by-WP narrative: `.ai/current-phase.md`;
per-WP reports: `docs/reports/phase-12/`.

## Files created (across the whole phase)

- `app/Enums/SportPortalSlug.php` — 12-case slug ⇄ `Sport.name` enum,
  source of truth for routing and sport resolution.
- `resources/js/pages/public/sport-portal.tsx` — the one shared page
  serving all 12 routes.
- `resources/js/components/sport-portal-live-now.tsx`
  (`SportPortalLiveNowCard`), `sport-portal-game-list.tsx`
  (`SportPortalGameList`), `sport-portal-unavailable.tsx`
  (`SportPortalUnavailable`), `sport-portal-venue-info.tsx`
  (`SportPortalVenueInfo`) — the 4 new shared components.
- `resources/js/hooks/use-page-visible.ts` (`usePageVisible`) — visibility-
  aware polling primitive.
- `resources/js/config/sport-portals.ts` (`sportPortals`) — frontend
  presentational config for all 12 sports.
- `tests/Feature/PublicSportPortalTest.php` — all backend test coverage for
  this phase.
- `docs/phases/phase-12-lightweight-sport-mini-portals/` (README,
  DESIGN-NOTES, CHECKLIST, INSPECTION-REPORT, DATA-CONTRACT-MAP, 9 WP
  files, original brief kept verbatim under its renamed filename).
- `docs/reports/phase-12/` — one completion report per WP.
- `docs/public-sport-portals/` (this directory) — the brief's own §16
  required documentation set, written this WP.

## Files modified

- `app/Http/Controllers/PortalController.php` — additive only: 2 new public
  actions (`sportPortal`, `sportPortalPoll`) and their private helpers
  (`sportPortalData`, `individualEventSportPortalData`,
  `sportPortalLiveNow`, `sportPortalGameRow`), plus this WP's `canonicalUrl`
  addition to the existing `sportPortal()` response. No existing method's
  behavior was changed.
- `routes/web.php` — 2 new routes (`{sportSlug}`, `{sportSlug}/poll`),
  purely additive, both constrained by `whereIn(SportPortalSlug::values())`.
- `resources/js/lib/utils.ts` — added `pluralize()`/`capitalize()`
  (WP-12-04), reused by the sport-portal page's per-sport terminology.
- `docs/howtorun/ROADMAP.md`, `docs/public-portal.md` — updated to
  reference the new phase/routes.

## Components reused, not rebuilt

`LiveScoreDisplay` (full live board: score, running clock, `LiveBadge`,
fullscreen, Basketball fouls, Boxing rounds, Softball/Baseball innings),
`EmptyState`, `PublicPageHero`, `ScoringSession::toLivePayload()`/
`boardType()`, `ScoringSessionController::board()`'s "suggested labels"
pattern for pre-live competitor names, and the existing `animate-card-in`/
`--ease-premium`/`--duration-base` motion tokens. Zero new colors, zero new
`@theme` entries.

## Backend contracts preserved

Zero schema change (no migration in this phase). Zero change to scoring,
medal computation, eligibility, or authorization logic. Every new route is
read-only and additive. The two Athletics/Swimming-serving methods
(`athletics()`, `individualEventSportPortalData()`) read from the same
underlying `EventSchedule`/`EventResult` queries without altering
`athletics()` itself.

## Known limitations

- **Standings, Leading Scorers, and a real Tournament Bracket diagram are
  not available for any sport** — no team win/loss aggregation, no
  per-athlete point attribution in live scoring, no bracket-tree structure
  exists anywhere in this schema today (`DATA-CONTRACT-MAP.md` §D-F).
  Honest "not available yet" states render instead, per the brief's own
  "do not fabricate data" rule and the owner's explicit 2026-07-29
  decision. Building any of the three would require genuinely new backend
  work outside this phase's scope.
- Sport-portal routes are not yet linked from the header nav or
  `PublicBottomNav` — discoverable via the existing `/sports`/`/gallery`
  pages' cards only (`DESIGN-NOTES.md`); flagged as a default, not a final,
  owner-reviewed decision.
- No frontend-unit-testing infrastructure exists in this project, so
  visibility-aware polling and `<Head>` metadata rendering are verified by
  source inspection rather than an automated browser/unit test (see
  `testing-checklist.md`).

## Sport-specific exceptions

- **Athletics, Swimming**: no real `EventMatch` usage in this system;
  served by `individualEventSportPortalData()` reading `EventSchedule`/
  `EventResult` directly, with a `mark` field instead of a two-sided score
  and no fabricated "vs" line.
- **Boxing, Chess**: genuinely head-to-head; fit the generic
  `EventMatch`-based shape with zero functional change.
- **Basketball, Boxing, Softball/Baseball**: use their existing dedicated
  `ScoreboardType` live board; every other sport falls back to the
  established Generic side-score board (a Phase 7 design, not a Phase 12
  gap).

## Performance decisions

10-item cap on every game list; a single featured live match (plus an
"other live" count) rather than a full live feed; no embedded map (a
generated external map-search link instead); visibility-aware polling
(7s Live Now, 45s background game-list refresh, both paused while the
browser tab is hidden); zero new dependencies anywhere in the phase. Full
detail: `performance-strategy.md`.

## Test results (this WP, WP-12-08)

`canonicalUrl` assertions added to 3 existing test cases (the active-meet
case, the no-active-meet case, and the 9-case cross-sport isolation
dataset) rather than new standalone tests, since the prop is additive to
already-covered request paths. See
`docs/reports/phase-12/WP-12-08-completion.md` for the full gate run
(Pest count, PHPStan, ESLint/Prettier/tsc, build, audits).

## SEO metadata (this WP, WP-12-08)

Every sport route now has a real, distinct `<Head>` title
(`"{Sport} — {Meet}"`, or `"{Sport} | Provincial Meet"` when no meet is
active — matching every other public page's existing title convention,
rather than adopting the original brief's own literal `"{Sport} | DdOPAA
Live"` branding, which does not match this app's actual name/branding), a
real meta description built from the same real sport/meet data already on
the page (never fabricated), and an absolute canonical URL
(`route('public.sport-portal', $slug)`, resolved through `APP_URL`). No
social-preview (Open Graph/Twitter Card) metadata was added — the brief's
own §12 makes it conditional on "if already supported," and no page in this
entire application has ever set any social-preview metadata, so there is
nothing existing to extend; adding a full OG/Twitter Card system from
scratch is out of this WP's scope (and would need real preview images,
excluded by this WP's own rules).
