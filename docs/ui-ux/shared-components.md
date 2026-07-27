# Shared Tables, Cards, Charts, Scoreboards, and Filters (WP-08-13)

An inventory of the reusable presentational components Phase 8 built up
across WP-08-04 through WP-08-12, plus the conventions this WP audited
and normalized. New pages should reuse these rather than re-implement
their own version.

## Components

| Component | File | First built | Used by |
|---|---|---|---|
| `StatCard` | `components/stat-card.tsx` | WP-08-04 | dashboard, tally (internal+public), athletics, eligibility, management |
| `RankBadge` | `components/rank-badge.tsx` | WP-08-05 | dashboard, tally (internal+public) |
| `MedalDistributionCard` | `components/medal-distribution-card.tsx` | WP-08-08 (extracted from WP-08-05) | tally (internal+public) |
| `TopByPointsCard` | `components/top-by-points-card.tsx` | WP-08-08 | tally (internal+public) |
| `MedalsBySportCard` | `components/medals-by-sport-card.tsx` | WP-08-08 | tally (internal+public) |
| `MedalCells`/`MedalHeader` | `components/medal-table-parts.tsx` | WP-08-08 | tally (internal+public) |
| `SportsMedalStrip` | `components/sports-medal-strip.tsx` | WP-08-09 | public tally |
| `PublicPageHero` | `components/public-page-hero.tsx` | WP-08-07 | public home, tally, athletics |
| `PublicBottomNav` | `components/public-bottom-nav.tsx` | WP-08-09 | public layout (mobile) |
| `PublicMeetNav` | `components/public-meet-nav.tsx` | WP-04-02 | public meet, results, tally |
| `LiveScoreDisplay` | `components/live-score-display.tsx` | WP-07-02, extended WP-08-10/12 | scoring console, public scoreboard |
| `CountDots` | `components/live-score-display.tsx` (internal) | WP-08-10 (as `FoulDots`), generalized WP-08-12 | basketball fouls, softball balls/strikes/outs |
| `SoftballLineScore` | `components/live-score-display.tsx` (internal) | WP-08-12 | softball/baseball scoreboard |
| `SearchBar` | `components/search-bar.tsx` | Phase 2 | every searchable registry page |
| `EmptyState` | `components/empty-state.tsx` | Phase 1 | every no-data path app-wide |

Every extraction above happened **on a component's second real use site**,
not preemptively — the project's own "no premature abstraction" rule.
`MedalDistributionCard`/`TopByPointsCard`/`MedalsBySportCard`/
`MedalCells`/`MedalHeader` all followed the exact "shared rendering,
independent props" pattern `LiveScoreDisplay` established first in
WP-07-02/WP-08-08's public-portal rule: a component can be shared, but a
public controller's prop array never reuses an internal page's.

## Audited conventions (WP-08-13)

A systematic pass over every page using a `Select`-based filter, the
shared `Table` components, and `StatCard` grids, looking for real visual
inconsistencies (not stylistic nitpicks with no visible effect). Most
flagged candidates turned out to be **correct-by-context** on closer
inspection — documented here so a future pass doesn't re-flag them:

- **Tables inside a `Card`** (`dashboard.tsx`'s today's-schedule/medal-
  tally-top-5 widgets, `MedalsBySportCard`) or inside their own
  `<section className="rounded-xl border">` (`results/index.tsx`,
  `public/results.tsx`'s per-event placement tables) correctly render
  their `<Table>` in a **bare** `overflow-x-auto` wrapper, no border —
  the ancestor already provides one. Only a table sitting directly in a
  plain `<section>`/`<div>` (most registry pages, the tally pages, etc.)
  needs its own `overflow-x-auto rounded-xl border` wrapper. Both are the
  same convention correctly applied twice, not an inconsistency.
- **"Rank"/"#" column width** varies (`w-10`/`w-12`/`w-16`) because the
  header *text* varies too — pages using the literal word "Rank" (4
  characters) correctly get more width than pages using "#" (1
  character). Not a bug.

Two real inconsistencies were found and fixed:

- **`incidents/index.tsx`'s status filter** was `w-44` while every other
  page's equivalent status filter (`protests/index.tsx`,
  `eligibility/index.tsx`, `results/index.tsx`) is `w-56` — same
  "All statuses" + short option list, no reason for the narrower
  trigger. Normalized to `w-56`.
- **The medal-summary `StatCard` grid** (Total Gold/Silver/Bronze/Medals
  — the same four cards, same data shape) used a different responsive
  breakpoint on the internal admin tally page (`lg:grid-cols-4`, WP-08-05)
  than the two public pages that copied the pattern afterwards
  (`sm:grid-cols-4`, WP-08-08/WP-08-09-era `public/tally.tsx` and
  `public/athletics.tsx`) — the same widget looked different on a
  tablet-width screen depending on which page you were on. Normalized
  `tally/index.tsx` to `sm:grid-cols-4` to match the two (more recent,
  more deliberately chosen — see WP-08-09's report) public pages.
- **Four `EmptyState` call sites** (`accreditation/index.tsx`'s athlete/
  personnel panels, `reports/delegation-roster.tsx`'s athlete/personnel
  panels) omitted a `description`, rendering visibly shorter/plainer
  than every sibling empty state on the same pages. Added the same
  wording `athletes/index.tsx`/`personnel/index.tsx` already established
  ("Registered athletes will appear here." / "Registered coaches and
  chaperones will appear here.").

**Deliberately left alone**: `dashboard.tsx`'s two different `StatCard`
grids (`sm:grid-cols-2 lg:grid-cols-4` for operational queues,
`md:grid-cols-3` for the main stat row) and `management/index.tsx`'s
matching queue-stat grid — these are a genuinely different widget
category (operational counts, not medal cards) already consistent with
each other; forcing them into the medal-card breakpoint would be wrong,
not a fix. `MedalDistributionCard`'s donut, `dashboard.tsx`'s
events-overview segmented bar, and `TopByPointsCard`'s proportional bar
list already share the same visual language (`h-2 w-full overflow-hidden
rounded-full bg-muted` tracks, `size-2 rounded-full` legend dots) despite
being three different chart types — no further extraction needed, since
forcing a donut and a horizontal bar into one shared component would be
cosmetic DRY, not a real improvement. A handful of single-filter pages
(`protests/index.tsx`, `public/results.tsx`, `public/athletics.tsx`)
render their lone `Select` without the `flex flex-wrap gap-2` wrapper
most multi-filter pages use — left alone, since a single flex child
renders pixel-identical with or without the wrapper.

`scoring/show.tsx` and `public/scoreboard.tsx` were reconfirmed to both
render exclusively through `LiveScoreDisplay` with no local
reimplementation of any score-table, count-dot, or line-score logic —
the "one shared component" architecture WP-07-02/WP-08-10/WP-08-12 built
is holding.

## Responsive breakpoint audit (WP-08-14)

Same reference-list situation as WP-08-13 — no image actually depicts
"responsive alignment" as a concept, confirming this is another
consolidation pass, not new visual work. A second background audit
targeted responsive-behavior specifics WP-08-13's audit didn't cover:
missing horizontal-scroll wrappers, non-collapsing grids, fixed widths
that don't shrink, touch targets, large-display space usage, and the
tablet (640–1023px) range specifically. Each finding was verified by
hand before acting, same discipline as WP-08-13:

**False positives / correct-by-design, verified and left alone:**

- **All 52 `Table` usages already have an `overflow-x-auto` ancestor** —
  no missing scroll wrapper anywhere.
- **`division/edit.tsx`'s unconditional `max-w-lg` form width** — flagged
  as a "large-display" concern, but capping a simple settings form's
  width for readability is standard, correct UX practice (the shared
  `settings/layout.tsx` does the same via its own `max-w-xl` content
  section) — not wasted space, deliberate.
- **`scoring/show.tsx`'s `max-w-2xl` operator control panel** — same
  reasoning, a centered control widget, not a data table that needs full
  width.
- **`dashboard.tsx`'s events-overview 3-column legend** (`grid-cols-3`,
  no responsive prefix) — the three labels ("Completed"/"Ongoing"/
  "Upcoming") are short enough to fit a ~100px column on even the
  narrowest supported phone width without wrapping; not a real risk.

**Real issues, fixed:**

- **`accreditation/cards.tsx`'s printable ID cards** (`w-84 shrink-0` —
  336px fixed, Tailwind v4's dynamic spacing scale) sat in a bare `flex
  flex-wrap` container with no scroll wrapper — on a phone narrower than
  ~370px this would overflow the page horizontally, since `flex-wrap`
  wraps between items, it doesn't shrink an individual oversized one.
  Added `max-w-full` alongside the fixed width so a card shrinks to fit
  its container below 336px, keeping the fixed 336px (better for the
  print layout this page is really for) at every wider size.
- **Six widget-pair/sidebar grids jumped straight from a single mobile
  column to a split layout at `lg:` (1024px)**, leaving the entire
  640–1023px tablet range single-column for content that's genuinely
  wide enough to benefit from splitting sooner: the ranking-table +
  medal-distribution-donut split and the top-by-points + medals-by-sport
  pair (both `tally/index.tsx` and `public/tally.tsx` — the same layout,
  built in the same WPs), and the dashboard's today's-schedule +
  companion-widget pair and recent-activity split. All six moved from
  `lg:` to `md:` (768px). Verified safe before changing: every table
  involved already has its own `overflow-x-auto` (so a tight tablet
  column can still scroll instead of breaking), and the sidebar widgets
  (`MedalDistributionCard`, `TopByPointsCard`) are simple text/dot
  content that wraps gracefully rather than overflowing at a narrower
  width.
- **Touch-target inventory extended, not changed**: confirmed the
  `size="sm"` (32px) convention WP-04-06 already accepted for the public
  portal still applies unchanged to the two pages added since that
  review (`public/scoreboard.tsx`, WP-07-08; `public/athletics.tsx`,
  WP-08-11) — same pattern, not a new gap; `docs/public-portal.md`'s
  accepted-deviations note now says so explicitly instead of leaving it
  implicit.
