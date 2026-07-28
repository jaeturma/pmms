# Premium Experience Audit — WP-08.5-01

Phase 8.5 — PMMS Premium Sports Experience. Audit only; no code was
modified in this work package (per its own Objective).

## Method

Read every public-facing page and its shared components directly from the
current tree (not screenshots, not the WP-08 reference images), plus the
design tokens (`resources/css/app.css`), the build output, and the test
suite. Findings below cite real files/lines so later WPs (02–10) can act on
them without re-discovering the same ground. No PMMS data, route, model,
authorization rule, or validation was changed to produce this report.

## 1. Current inventory

**Public pages** (`resources/js/pages/public/`): `home.tsx` (single active
meet + competing-municipality grid, WP added 2026-07-27), `meet.tsx`
(schedule/live-now/venues), `results.tsx`, `tally.tsx`, `athletics.tsx`,
`scoreboard.tsx`.

**Shared public/scoreboard components**: `public-page-hero.tsx`,
`public-meet-nav.tsx`, `public-bottom-nav.tsx`, `public-announcements.tsx`,
`live-score-display.tsx` (redesigned 2026-07-27 with team logos, a
pause-aware running clock, and boxing red/blue corners), `team-logo.tsx`
(generated placeholder), `medal-table-parts.tsx`, `rank-badge.tsx`,
`stat-card.tsx`, `medal-distribution-card.tsx`, `top-by-points-card.tsx`,
`medals-by-sport-card.tsx`, `sports-medal-strip.tsx`.

**Layout**: `resources/js/layouts/public-layout.tsx` — header (logo, nav,
sign-in), `<main class="max-w-5xl">`, footer, `PublicBottomNav` on mobile.

**Design tokens**: `resources/css/app.css` — OKLCH tokens for
background/primary/secondary/muted/accent/destructive/success/warning,
plus dedicated `--medal-gold`/`--medal-silver`/`--medal-bronze` pairs and a
navy `--sidebar` set. `--primary` is a royal blue (`oklch(0.4 0.19 263)`),
comment-documented as "sampled from the approved reference images." No
`--font-*` beyond `--font-sans: 'Instrument Sans', ...` — no display/serif
pairing, no distinct scoreboard numeral font.

**No animation library**: `package.json` has `tw-animate-css` (a
Tailwind utility-class plugin) and nothing else — no Framer Motion, no
GSAP, no `react-spring`. Confirmed by dependency grep.

## 2. Public portal

- `public/home.tsx` — a `PublicPageHero` band (gradient, `from-sidebar
  to-primary`, `public-page-hero.tsx:17`), three CTA buttons, a responsive
  grid of municipality cards with `TeamLogo` placeholder badges. This is
  the strongest page visually today (redesigned last WP) but the hero is
  still a flat two-color gradient with no motion, no event imagery, no
  countdown-to-start treatment, and the same hero component is reused
  verbatim on `athletics.tsx` and `tally.tsx` with no page-specific
  identity (WP-08.5-03 scope: "hero and event branding").
- `public/meet.tsx` — **no `PublicPageHero`** at all; a bare `<h1>` +
  `<p>` (`meet.tsx:93-104`), the same plain pattern `results.tsx` and
  `scoreboard.tsx` use. This is the single biggest visual inconsistency
  in the portal: the page a visitor lands on immediately after the
  homepage (Schedule) looks like a different, older product than the
  homepage they just left.
- `PublicMeetNav` (`public-meet-nav.tsx`) is a 3-button row with no
  active-tab underline/indicator beyond a filled vs. outline button
  variant — functional but not "broadcast tab bar" quality.
- Navigation depth: Home → Schedule/Results/Tally/Athletics are all
  present and correctly wired (`docs/public-portal.md`), but there is no
  persistent "event countdown," "next match," or "today's headline" strip
  a real sports-event site (the brief's Olympics/FIFA reference) would
  lead with.
- Announcements (`public-announcements.tsx`) are plain bordered list
  items — no visual priority/severity treatment, no dismiss/read state.

## 3. Live scoreboards

Strongest area after last WP's redesign
(`resources/js/components/live-score-display.tsx`):

- Two boxed team panels + a center clock/period panel, divided by
  `divide-x-2` lines inside a `rounded-2xl border-2` card
  (`live-score-display.tsx:520-620`) — already closer to a broadcast
  "bug" than a plain stat block.
- Basketball: team logo, fouls with dot indicators, bonus badge.
- Boxing: red/blue corner badges + tinted panel background + real boxer
  photo (`participants` prop, operator console only) + a bordered
  round-by-round table.
- Baseball/softball: team logo next to each row in the line-score table.
- A real, pause-aware running clock (`ScoringSession::
  activeElapsedSeconds()`, `app/Models/ScoringSession.php:151-179`) ticks
  locally between polls via a `key`-remount pattern
  (`live-score-display.tsx:196-260`), avoiding both the React Compiler's
  render-purity rule and the codebase's own documented
  `set-state-in-effect` pitfall.

Remaining gaps against "broadcast-like" (this phase's own
`pmms-live-scoreboard-experience` skill) and "restrained motion" (
`pmms-motion-guidelines` skill):

- **Zero motion anywhere in scoring**: a point scored, a foul recorded,
  or a round entered re-renders the score instantly with no highlight/
  pulse — the skill explicitly calls for "score highlight, live pulse"
  and this WP found none. `Badge` status pills (Live/Paused/Ended) are
  static color swaps, no pulsing "LIVE" treatment despite `Radio`/
  `WifiOff` icons already being used elsewhere for live indicators
  (`public/home.tsx`, `public/meet.tsx:141`).
- **No finalized-winner celebration** — when a session ends, the display
  just shows an "Ended" badge; no medal-ceremony-style resolution (this
  is explicitly WP-08.5-08 scope, confirming the gap here is expected and
  already scheduled).
- **No dedicated large-display/kiosk mode** — `fullscreen` exists (the
  browser Fullscreen API, `scoring/show.tsx:332-338`) and scales
  typography up, but there is no explicit "TV/LED wall" layout variant,
  no auto-refresh visual countdown, and no connection-status treatment
  designed for a screen with no one nearby to dismiss an error (see §7).
- **Generic-board matches** (no sport-specific treatment) still render
  through the same boxed layout minus sport extras — acceptable, but the
  center panel's clock is the only differentiator; a generic match reads
  visually flat next to a basketball/boxing/softball one.
- Play-by-play (`live-score-display.tsx:~660-690`) is a plain
  `divide-y` list — functional, not "broadcast ticker" styled.

## 4. Medal tally / rankings

- `public/tally.tsx` reuses the internal admin tally's shared widgets
  (`MedalDistributionCard`, `TopByPointsCard`, `MedalsBySportCard`,
  `RankBadge`) — consistent, but visually these are still shadcn `Card`s
  with default padding/border, not a "medal podium" or "Olympics medal
  table" presentation. `RankBadge` (`rank-badge.tsx`) is a plain 24px
  colored circle with a number — no crown/medal iconography for rank 1.
- The `MedalDistributionCard`'s "donut" is a CSS conic-gradient
  (documented in `docs/medal-tally.md` as a deliberate "no charting
  library" choice) — functionally fine, visually plain (no labels on the
  arcs, no hover/legend interaction).
- Mobile: the ranking table collapses to a top-8 preview with a "View
  full ranking" expand (`public/tally.tsx`, `RANKING_PREVIEW_COUNT = 8`)
  — a real, already-shipped mobile accommodation (WP-08-09), not a gap.

## 5. Mobile views

- `PublicBottomNav` (`public-bottom-nav.tsx`) is a clean, safe-area-aware
  fixed tab bar — solid foundation, but tab icons/labels have no active-
  tab motion (just a color swap) and no badge/dot for "live now" beyond
  the header's separate "Live now" button (which is `hidden sm:flex` —
  **on mobile there is no live-match indicator in the bottom nav at
  all**, only on the meet page's own "Live now" section). This is a real
  functional gap for a phone user browsing Home/Results who has no signal
  that a match is live right now without navigating into the specific
  meet page.
- Touch targets: `size="sm"` buttons (day-selector chips, meet-nav) are
  32px, below the 44px guideline — already an accepted, documented
  deviation (`docs/public-portal.md` "Accepted deviations"), not new.

## 6. Typography

- Single typeface (`Instrument Sans`) for everything, including the
  scoreboard's score numerals (`text-6xl`/`text-9xl font-bold tabular-nums`,
  `live-score-display.tsx:283-291`). `tabular-nums` is correctly applied
  everywhere a number changes in place (scores, clocks, medal counts) —
  good existing discipline. No distinct display/numeral face for the
  "big score" moments the premium direction calls for (NBA Game Center–
  style scoreboards typically pair a geometric/monospace numeral face
  with a separate display face for team names).
- Heading hierarchy is consistent (`Heading` component, `h1`→`h2`, no
  skipped levels — verified sound in the WP-04-06 accessibility sweep,
  `docs/public-portal.md`) but visually modest: `text-2xl`/`text-3xl` for
  page titles, no larger "hero" numeral treatment outside the scoreboard.

## 7. Motion

**This is the largest concrete gap found.** Cross-referenced against
`.ai/skills/pmms-motion-guidelines.md`'s explicit list (score highlight,
live pulse, card entrance, tab transition, loading skeleton, finalized-
winner celebration, reduced-motion support):

| Guideline item | Current state |
|---|---|
| Score highlight | Absent — score text swaps instantly |
| Live pulse | Absent — `Badge variant="destructive"` "Live now" is static |
| Card entrance | Absent — grids/lists render with no stagger/fade |
| Tab transition | Absent — `PublicMeetNav`/day-selector swap instantly |
| Loading skeleton | Absent — no loading state exists anywhere in the public portal; a slow request just shows stale content until the response lands |
| Finalized-winner celebration | Absent (correctly deferred to WP-08.5-08) |
| Reduced-motion support | **N/A today — there is no motion to reduce.** `grep` for `prefers-reduced-motion`/`motion-safe`/`motion-reduce` across `resources/js` and `resources/css` returns zero matches |

No animation dependency exists (`tw-animate-css` only, a utility-class
helper, not a runtime animation engine) — WP-08.5-06 starts from a clean
slate, not a refactor.

## 8. Spacing / premium polish

- Base spacing is shadcn/ui defaults throughout (`p-4`, `gap-4`,
  `rounded-xl` cards) — clean and consistent, but "compact SaaS admin"
  density rather than "premium spacious sports event" density. The
  `pmms-premium-design-system` skill calls for "premium spacing, clean
  white space" — current pages pack stat cards/tables at admin-dashboard
  tightness (`grid grid-cols-2 gap-4 sm:grid-cols-4`, the same pattern
  used on both the internal admin tally and the public one).
- Color usage leans heavily on `muted`/`border` grays; the branded
  `--primary` royal blue and `--medal-gold` mostly appear only on badges
  and the hero gradient, not as a broader visual identity thread (e.g.
  section dividers, active states, background tints).

## 9. Large-display readiness (TV / projector / LED wall / kiosk)

Cross-referenced against `.ai/skills/pmms-large-display-guidelines.md`:

- **High contrast**: partially met — the scoreboard's boxed panels and
  `tabular-nums` scores read well at distance; body text elsewhere
  (`text-sm text-muted-foreground`) is admin-density, not TV-safe.
- **Large text**: only the scoreboard's `fullscreen` mode scales up
  (`text-9xl` scores). No other public page has a "kiosk" or "TV" text
  scale — the medal tally, meet schedule, and home page would need a
  human standing close to a screen to read comfortably.
- **16:9 safe layout**: no explicit safe-margin/safe-area handling exists
  for a fixed-aspect display anywhere in the codebase — `fullscreen` mode
  centers content but doesn't guard against overscan on real TV hardware.
- **Auto-refresh**: the scoreboard already polls every 5s
  (`scoring/show.tsx:289-308`, `public/scoreboard.tsx:47-66`) and the
  medal tally is a normal page load (no auto-refresh) — a kiosk showing
  the tally unattended would go stale until someone reloads it.
- **Connection status**: exists on the scoreboard only (`WifiOff` banner
  after 2 failed polls, `live-score-display.tsx`) — no equivalent on the
  tally/schedule pages, which a kiosk is equally likely to display
  unattended.
- **No private data / no admin controls**: verified sound — every public
  controller builds its own minimal, public-safe prop array
  (`docs/public-portal.md`'s binding rule), and `canManage`/operator
  controls are structurally absent from public pages (proven by existing
  tests, e.g. `PublicScoreboardTest`'s `missing('canManage')` assertions).
  **No gap found here** — this constraint is already correctly enforced
  and should stay a hard boundary for WP-08.5-07, not something to relax
  for a kiosk's sake.
- No dedicated kiosk/LED-wall route or layout variant exists at all today
  — WP-08.5-07 is starting from zero, not adapting an existing mode.

## 10. Accessibility

Built on top of the real WP-04-06 accessibility sweep already on record
(`docs/public-portal.md` "Accessibility & mobile review") plus this
audit's own spot-check of the newest code (home page, scoreboard):

- Decorative icons are consistently `aria-hidden="true"` in every file
  reviewed this pass (`public/home.tsx`, `live-score-display.tsx`,
  `public/meet.tsx`, `public/athletics.tsx`).
- The scoreboard's score grid already carries `aria-live="polite"
  aria-atomic="true"` (`live-score-display.tsx:~505`) and the running
  clock has an explicit `aria-label="Running time"` — good existing
  practice this WP should preserve, not rebuild.
- `TeamLogo`'s generated initials badge is `aria-hidden="true"`
  (`team-logo.tsx`) since the adjacent visible team-name text already
  carries the same information — correct, no redundant announcement.
- Color contrast was not re-audited this pass (per the existing
  documented policy of relying on the shared design tokens); no new
  color was introduced by this WP's audit, so no new risk was found.
- **New gap this audit surfaces**: none of the "Live" badges/pulses that
  WP-08.5-06 will add have a plan yet for a non-flashing equivalent —
  the motion guidelines' "avoid flashing" rule needs to be a hard
  constraint on that WP's actual implementation, not just this doc's
  intent.

## 11. Performance

From the current production build (`npm run build`, most recent run):

- `wayfinder-*.js` — **346.48 kB (109.08 kB gzip)** — by far the largest
  single chunk shipped, and it is Wayfinder's generated route-helper
  bundle, loaded broadly rather than split per-page. This is the single
  biggest performance lever available to later WPs (route helpers are
  used across both admin and public pages, so splitting is nontrivial,
  but worth flagging for WP-08.5-09's scope).
- `public-layout-*.js` — 75.27 kB (22.23 kB gzip) — the shared public
  shell; reasonable for a shell chunk but worth re-checking once
  WP-08.5-03/04's hero and scoreboard work add weight to it.
- `live-score-display-*.js` — 14.67 kB (5.15 kB gzip) after this WP's
  predecessor redesign — still lean.
- `home-*.js` — 6.51 kB (2.65 kB gzip) — lean.
- No public page loads a real image asset today (`TeamLogo` is CSS/DOM-
  generated, not an `<img>`; athlete photos only ever render on the
  authenticated accreditation page) — meaning the portal currently pays
  zero image-decode cost, a genuine performance asset worth preserving
  deliberately as later WPs consider hero imagery (WP-08.5-03) or medal
  ceremony visuals (WP-08.5-08): any real image introduced should be
  lazy-loaded and sized deliberately, not simply dropped in.
- Polling cadence (5s) on the scoreboard is unchanged and already proven
  to work standalone without Reverb (`docs/live-scoring.md`) — no
  regression risk found.

## 12. Gap summary, mapped to the remaining Phase 8.5 work packages

| Area | Severity | Owning WP |
|---|---|---|
| No motion system anywhere (score highlight, live pulse, transitions, skeletons) | High | WP-08.5-06 |
| `meet.tsx`/`results.tsx`/`scoreboard.tsx` lack `PublicPageHero` — inconsistent with `home.tsx`/`tally.tsx`/`athletics.tsx` | High | WP-08.5-03 |
| No kiosk/TV/LED-wall layout variant or safe-area handling | High | WP-08.5-07 |
| No finalized-winner / medal-ceremony presentation | Medium (already scheduled) | WP-08.5-08 |
| Mobile bottom nav has no "live now" indicator | Medium | WP-08.5-05 |
| `wayfinder-*.js` 346 kB chunk; no perf/skeleton work yet | Medium | WP-08.5-09 |
| Medal tally lacks podium/medal-forward visual treatment | Medium | WP-08.5-02 / WP-08.5-08 |
| No distinct display/numeral typography for scores vs. body text | Low–Medium | WP-08.5-02 |
| Compact/admin-density spacing vs. "premium spacious" direction | Low–Medium | WP-08.5-02 |
| Generic-board scoreboard matches look flatter than sport-specific ones | Low | WP-08.5-04 |

## 13. What must not change (re-confirmed, not just assumed)

Verified directly against the current code, not just the docs describing
it:

- `Meet::published()`/`Meet::scopeActive()` remain the only public data
  gates (`app/Models/Meet.php`) — untouched by this audit.
- Live scores stay provisional; only `EventResult`/`ResultPlacement`
  (Phase 3's encode→validate path) are authoritative — `ScoringSession`
  never writes either, confirmed still true by the existing test suite
  (`ResultTest`'s "a match can be finalized ... no live scoring session
  was ever started" and `ScoringSessionTest`'s "ending a session never
  touches EventResult").
- District/Municipality is still the official delegation; School is the
  athlete's own origin — confirmed unchanged in
  `MedalTallyService::standings()` and `docs/medal-tally.md`.
- Every public controller still builds its own minimal, public-safe prop
  array — no internal page's props are reused publicly anywhere in the
  code reviewed this pass.

## Non-goals confirmed out of scope for this audit

No Flutter, AI prediction, SaaS multi-tenant work, Regional/National
tier, proprietary broadcast hardware integration, sponsor billing, or
backend redesign was found necessary or was considered — consistent with
this phase's stated Exclusions.
