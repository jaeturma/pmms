# WP-08-05 — Admin Medal Tally and Rankings UI

**Status:** Complete 2026-07-27. Per this WP's own rules, WP-08-06 has
not been started.

## Repository findings

Read the required files. This WP's own listed reference images were
again the wrong copy-pasted set (the same generic live-scoreboard/
ranking images used by WP-08-02/04) — same templated-doc issue
WP-08-01/02/03/04 already flagged. Used `admin-medal-tally.png` instead,
the one reference that actually shows this page.

Read `app/Services/MedalTallyService.php`, `TallyController.php`,
`tally/index.tsx`, `tests/Feature/MedalTallyTest.php`, and
`docs/medal-tally.md` before changing anything. Two rules already
documented there constrained this WP's design:

1. **"Ordering is conventional: gold, then silver, then bronze, then
   name"** — an explicit, tested, documented business rule (the app's
   official meet verdict), not an oversight. WP-08-01's gap assessment
   had flagged "no points-based (Gold=3/Silver=2/Bronze=1) ranking
   system" as a functional gap versus the reference, but the reference
   also visually implies points as the ranking basis
   ("Ranking is based on: Gold (3 points)..."). **Decision:** add points
   as a real, computed, display-only value (and its own separately
   labeled "Top by points" widget) without touching the existing rank
   order — silently swapping the app's documented official standings
   algorithm inside a visual-alignment WP would be a real governance
   change, not a restyle. Proven by a new test that a single-gold
   district (fewer points) still outranks a two-silver district (more
   points).
2. **"School standings are a secondary reference... there is no
   separate school-level winner"** (Post-Division refinement,
   `.ai/current-phase.md`). The reference's "View Ranking By:
   Municipality" dropdown implies toggling between competing rankings.
   **Decision:** did not build a school/municipality toggle — kept both
   tables always visible, district first (official), school below
   (reference-only), exactly as already established, rather than
   building something that could make school standings read as a
   competing rank.

## What was found and built

**Backend** (`MedalTallyService`, additive only — the existing
`standings()` behavior, signature-compatible via new optional
parameters, and its tested rank order are unchanged):

- `points` added to each district/municipality row (Gold×3 + Silver×2 +
  Bronze×1) — not added to school rows, since the admin table's Points
  column and the points-based "Top by" widget are both
  district/municipality-level, per the reference.
- New `medalsBySport()` — same validated/filtered placement set as
  `standings()`, grouped by sport instead, for the reference's
  "Medals by sport" table.
- New `recentMedals()` — gold/silver/bronze/total awarded in the last 24
  hours, computed from `event_results.validated_at`, for the reference's
  "+N from yesterday" stat-card deltas (worded "+N in the last 24 hours"
  instead, since it's a rolling window, not a calendar-day comparison —
  avoids overclaiming precision the data doesn't have).
- New `$ageDivision` filter parameter on all three methods, reusing the
  existing `App\Enums\AgeDivision` (Elementary/Secondary) — this is what
  the reference's "Division: All Divisions" dropdown actually maps to in
  real PMMS data (this app's `Division` model is a single deployment-wide
  City/Province setting, not a filterable list — the reference's filter
  is Event age division, a real, different, existing concept). Both
  filters (sport, age division) reuse the shared `basePlacements()`
  query, extracted from the original inline query to avoid duplicating
  the validated-only/meet/sport `whereHas` chain three times.
- `TallyController::index()` composes: district totals (summed from the
  district rows), top-5-by-points (sorted server-side), the sport
  breakdown, the recent-medals delta, and the age-division options —
  same "controller composes, service computes" split already
  established.

**Frontend** (`tally/index.tsx`):

- Page header retitled "Medal Tally & Rankings" (was "Medal tally") to
  match the reference; added an "Export report" button (the CSV download
  route, `reports.tally.download`, already existed on the backend but
  was only reachable via the printable report page — now linked
  directly, next to the existing "Printable report" button, matching the
  reference's Export/Print pair).
- Age division filter added alongside the existing meet/sport filters.
- Four summary `StatCard`s (Total Gold/Silver/Bronze/Medals) with the
  real 24-hour delta as a description line (omitted, not shown as
  "+0", when there's no recent activity).
- `StatCard`'s `tone` prop (WP-08-04) gained `'silver'`/`'bronze'`
  variants — it only had `gold` before, since WP-08-04 never needed the
  other two medal colors on a stat card.
- A CSS conic-gradient "Medal distribution" donut (gold/silver/bronze
  share of the total, with an accessible `role="img"` label) — no
  charting library added; built the same way WP-08-04's events-overview
  segmented bar was, to keep this project's "isolate new dependencies"
  discipline (recharts or similar would be a new, unauthorized
  dependency for one widget).
- `RankBadge` (colored circle, medal-toned for the top 3) extracted from
  `dashboard.tsx` into `resources/js/components/rank-badge.tsx` — this
  is its second use site, so sharing it now stops two copies drifting
  apart; `dashboard.tsx` updated to import the shared version, no
  behavior change there.
- A "Points" column on the district/municipality table, with an explicit
  caption stating rank order follows gold/silver/bronze, not points.
- "Top by points" widget (bar list, top 5) and "Medals by sport" table
  (reuses the page's existing `MedalCells`/`MedalHeader` helpers).
- An `Alert` banner restating the real-time-update note the reference
  shows.

## What was deliberately NOT done

- **No "View Ranking By" municipality/school toggle** — see rule #2
  above. Both tables stay visible, district-first/official,
  school-below/reference-only, unchanged from the existing convention.
- **No points-based re-ranking of the official standings** — see rule #1
  above; points is additive display only.
- **No "As of Date" historical point-in-time filter** — the reference
  shows a date/time picker implying "what did the tally look like at
  this moment in the past," which would require reconstructing a
  point-in-time snapshot from `score_events`/`validated_at` history that
  doesn't exist for this purpose anywhere in the app. A real feature, not
  a restyle; out of this WP's scope.
- **No changes to `reports/medal-tally.tsx`, `public/tally.tsx`, or the
  dashboard's tally widget** — this WP is scoped to the internal admin
  page (`tally/index.tsx`); the public/printable pages are WP-08-08's
  scope.
- **No repo-wide sweep** of other `StatCard` call sites for the new
  silver/bronze tones — additive-only change, nothing else needed them.

## Verification

- `npx tsc --noEmit` — 0 errors.
- Full quality gate green (below).
- **Could not get a live visual screenshot** — Claude in Chrome
  extension still disconnected this session.
- **Could not get a live HTTP check against http://pmms.app** — this
  session, Laragon's MySQL and Apache came up on their own (both were
  down at the end of WP-08-04), and the Apache vhost for pmms.app
  (`D:/lara/etc/apache2/sites-enabled/auto.pmms.app.conf`) is correctly
  configured. But the running Apache process is still serving
  Laragon's own default landing page for `pmms.app` requests instead of
  routing to the vhost — it needs a service restart to pick up the
  config. Deliberately did not restart it: this Apache instance also
  serves roughly a dozen other unrelated local projects
  (`sites-enabled/` lists `acst.app`, `aims.app`, `booker.app`,
  `hris.app`, and others) already running on this machine, and
  restarting a shared service to satisfy one WP's optional visual check
  isn't a risk worth taking without asking first. Flagged for the owner
  to restart Laragon's services when convenient; not treated as a
  blocker given every other gate passed, matching WP-08-04's own
  precedent for this exact situation.

## Test results

`vendor/bin/pest` — **676/676 passing**, 3,418 assertions (5 new tests
in `MedalTallyTest`: points are weighted 3/2/1 and never reorder the
official standings — proven with a single-gold district still outranking
a two-silver district; the age-division filter narrows results
correctly; an invalid age-division value is silently ignored rather than
erroring; `medalsBySport()` groups placements by their event's sport;
`recentMedals()` only counts placements validated within the last 24
hours, excluding an older one). `PublicTallyTest` re-verified unaffected
(22/22 across both files).

## Quality results

| Check | Result |
|---|---|
| `vendor/bin/pint --test` | Passed |
| `vendor/bin/phpstan analyse` (level 7) | Passed, 0 errors |
| `vendor/bin/pest` | Passed, 676/676, 3,418 assertions |
| `npx eslint .` | Passed, 0 errors |
| `npm run format:check` | Passed |
| `npx tsc --noEmit` | Passed, 0 errors |
| `npm run build` | Passed |

## Files created

- `resources/js/components/rank-badge.tsx` (extracted from
  `dashboard.tsx`)
- `docs/reports/phase-08/WP-08-05-completion.md` (this report)

## Files modified

- `app/Services/MedalTallyService.php` — `points` on district rows,
  `medalsBySport()`, `recentMedals()`, `$ageDivision` filter, extracted
  `basePlacements()`
- `app/Http/Controllers/TallyController.php` — wires the above,
  `totals`, `topByPoints`, `ageDivisionOptions`
- `resources/js/pages/tally/index.tsx` — full visual/functional rebuild
  per the reference (see above)
- `resources/js/pages/dashboard.tsx` — `RankBadge` now imported from the
  shared component instead of defined locally
- `resources/js/components/stat-card.tsx` — `silver`/`bronze` tones
- `tests/Feature/MedalTallyTest.php` — 5 new tests
- `docs/medal-tally.md` — points/by-sport/recent-medals/age-division
  sections
- `docs/phases/phase-08-ui-ux-visual-alignment/CHECKLIST.md` — WP-08-05
  checked off

## Remaining issues

- Chrome extension still unavailable — recommend a real visual check
  before WP-08-06.
- Apache/MySQL are running but the pmms.app vhost isn't currently being
  served (needs a Laragon service restart) — recommend the owner
  restart it and reconfirm HTTP 200 before WP-08-06, since this affects
  every page, not just this WP's.

## Next

WP-08-06 — Athlete Eligibility Checker UI, on owner instruction (per
this WP's own rule: do not begin the next work package). Note: WP-08-01
flagged that reference as showing an automated PASS/FAIL rule-checking
flow that conflicts with the app's real, deliberate manual
document-review workflow — likely needs an owner scoping decision before
implementation, same as this WP needed one for points-based ranking.
