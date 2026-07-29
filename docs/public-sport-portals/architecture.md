# Public Sport Portals — Architecture

Phase 12 ("Lightweight Sport Mini Portals") added 12 permanent, meet-agnostic
sport routes to the public portal: `/basketball`, `/volleyball`, `/baseball`,
`/softball`, `/football`, `/sepak-takraw`, `/badminton`, `/table-tennis`,
`/chess`, `/boxing`, `/athletics`, `/swimming`. Each shows the currently
active meet's Live Now, Today's/Completed/Upcoming Games, Venue Information,
and honest not-yet-available states for Standings/Leading Scorers/Tournament
Bracket for that one sport — one shared page and component system, not 12
duplicated pages. Full context: `docs/phases/phase-12-lightweight-sport-mini-portals/`
(README, DESIGN-NOTES, INSPECTION-REPORT, DATA-CONTRACT-MAP).

## Routing shape (a deliberate deviation)

Every other public route is meet-scoped: `/meets/{meet}/...`. Sport-portal
routes are bare top-level slugs with no meet ID: `/{sportSlug}` and
`/{sportSlug}/poll`. This resolves through the existing single-active-meet
concept (`Meet::published()->active()->first()`) — the same resolution
`PortalController::home()` already uses — so there is no new business
concept, just a route shape this app had never used before Phase 12. Both
routes are constrained by `->whereIn('sportSlug', SportPortalSlug::values())`,
so they can never intercept any other top-level route.

## Request flow

1. `routes/web.php` matches `{sportSlug}` against `App\Enums\SportPortalSlug`
   (12 cases, slug ⇄ real `Sport.name`).
2. `PortalController::sportPortal()` resolves the sport row and the active
   published meet, then calls `sportPortalData()`.
3. `sportPortalData()` branches once: Athletics and Swimming (no real
   `EventMatch` usage in this system) go through
   `individualEventSportPortalData()`, reading `EventSchedule`/`EventResult`
   directly; every other sport reads `EventMatch` + `ScoringSession`, the
   same shape `meet.tsx` already queries.
4. The page (`resources/js/pages/public/sport-portal.tsx`) renders Live Now,
   the three game lists, the three honest-unavailable sections, and Venue
   Information from those props.
5. `SportPortalLiveNowCard` polls `GET /{sportSlug}/poll` every 7s
   (`sportPortalPoll()`, a lighter JSON endpoint reusing the same live-match
   query); the page background-refreshes the game lists/venues every 45s via
   `router.reload({ only: [...] })`. Both pause while the browser tab is
   hidden (`usePageVisible()`).

## Components (new, Phase 12)

- `sport-portal-live-now.tsx` (`SportPortalLiveNowCard`) — wraps the
  pre-existing `LiveScoreDisplay` (Phase 7/8.5's full board: score, clock,
  `LiveBadge`, fullscreen, sport-specific state) with its own poll loop.
- `sport-portal-game-list.tsx` (`SportPortalGameList`) — a single list
  component reused for Today's/Completed/Upcoming, given a real winner
  derived at render time (`score_a <=> score_b`) rather than a stored field.
- `sport-portal-unavailable.tsx` (`SportPortalUnavailable`) — the honest
  "not available yet" state used by Standings/Leading Scorers/Bracket, for
  every sport.
- `sport-portal-venue-info.tsx` (`SportPortalVenueInfo`) — venue name/
  address/current-or-next-event, with a generated Google Maps search link
  (no stored geo field, no embedded map).
- `resources/js/hooks/use-page-visible.ts` (`usePageVisible`) — a
  `document.visibilitychange`-based boolean, the one new reusable primitive
  this phase introduced; gates both polls described above.
- `resources/js/config/sport-portals.ts` (`sportPortals`) — presentational
  config only (per-sport terminology, scoring type, `supportsStandings`/
  `supportsLeadingScorers`/`supportsBracket`, honestly `false` for every
  sport today). Mirrors `App\Enums\SportPortalSlug` by hand; the backend
  enum is the source of truth for which `Sport` row a slug resolves to.

## Reused, not rebuilt

`LiveScoreDisplay`, `EmptyState`, `PublicPageHero`, `LiveBadge`,
`ScoringSession::toLivePayload()`/`boardType()`, and
`ScoringSessionController::board()`'s "suggested labels" pattern for
pre-live competitor names — see `sport-configuration.md` for the full list
and `implementation-summary.md` for what was genuinely new per WP.

## Why Standings / Leading Scorers / Tournament Bracket are empty everywhere

Confirmed against real source, not assumed (`INSPECTION-REPORT.md` §5-6,
`DATA-CONTRACT-MAP.md` §D-F): `EventResult` has no `match_id` — medal
placements and match scores are two disconnected systems; every
`ScoreEvent` is side-level only, never attributing a point to an individual
athlete; `matches.round_label` is a free-text string with no bracket-tree
structure. Per the brief's own "do not fabricate data" rule, all three
sections render `SportPortalUnavailable` for every sport, this phase — the
same resolution WP-08-11 already chose for Athletics live-tracking. No new
schema, aggregation, or business logic was added to support any of the
three; see `data-contract-map.md` for the full field-by-field mapping.
