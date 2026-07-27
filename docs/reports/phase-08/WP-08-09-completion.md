# WP-08-09 — Mobile Ranking and Medal Tally UI

**Status:** Complete 2026-07-27. Per this WP's own rules, WP-08-10 has
not been started.

## Repository findings

Read the required files. This WP's own reference-image list was, for
once, actually correct — `mobile-ranking-medal-tally.png` genuinely is
this WP's reference (every prior WP in this phase had a wrong,
copy-pasted image list; this is the first exception).

The WP's objective line was also unusually specific compared to every
other WP so far: "compact medal cards, delegation ranking table, sports
strip, sport filter, safe-area spacing, and bottom navigation" — treated
as the literal scope checklist rather than inferring scope from the
image alone.

Read `public-layout.tsx`, `public/tally.tsx`, `public-meet-nav.tsx`, and
`docs/public-portal.md` before changing anything.

## What was found and built

The reference's mobile shell moves site navigation from the header into
a fixed **bottom tab bar** (Home/Schedule/Results/Live Scores/Ranking) —
a real, different navigation pattern from WP-08-07's horizontal header
nav, not a restyle of it. New `resources/js/components/public-bottom-
nav.tsx`: fixed to the viewport bottom, `sm:hidden`; the header's
horizontal nav (WP-08-07) is now `hidden sm:flex` — the two are
complementary, never both visible. Reuses the exact same real
destinations `publicNav` (WP-08-07) already resolves, plus a "Live" tab
shown only when there's an actual live match (same "no indicator for
nothing to indicate" rule already established). Labels are shorter
("Ranking" vs. the header's "Medal Tally") since five tabs share one
row — same destination, not a different page. Padded for
`env(safe-area-inset-bottom)` so it never sits under an iOS home
indicator; `<main>` gets matching bottom padding so content never hides
behind the fixed bar; the footer credit line is hidden below `sm:` since
the bottom nav now serves that role.

`public/tally.tsx` (this WP's specific page, per the reference) picked
up the rest of the objective's checklist:

1. **Compact medal cards** — the summary `StatCard` grid now goes
   4-across starting at `sm:` instead of `lg:`.
2. **Delegation ranking table** — collapses to the top 8 rows by default
   with a "View full ranking (N total)" expand button; the backend still
   returns every row regardless (verified by a new test with 10
   districts), so expanding needs no extra request. The table's "Points"
   column (already documented reference-only, non-authoritative) is
   hidden below `sm:` to free up width for the columns that matter more
   on a narrow screen.
3. **Sports strip** — new `resources/js/components/sports-medal-strip.tsx`,
   a compact horizontally-scrollable icon-forward preview of the busiest
   4 sports, sitting above the existing full `MedalsBySportCard` table
   (which stays as the complete breakdown — the strip is a shorter
   preview, not a duplicate). A "More sports" tile is a plain `#anchor`
   (not an Inertia `Link`, which would attempt a page navigation)
   scrolling to the full table.
4. **Sport filter** — already existed (WP-08-08); confirmed it still
   renders correctly alongside the new mobile layout, no change needed.
5. **Safe-area spacing** — see the bottom nav above.
6. **Bottom navigation** — see above.

Also added: the public tally now shows "As of {generatedAt}" in its
info banner (`now()->toDayDateTimeString()`, the same convention the
internal admin tally already used) — this page never had a
generated-at timestamp before this WP.

## What was deliberately NOT done

- **No "Public View ▾" role-switcher dropdown** — the existing Sign
  in/Dashboard button already serves that purpose; there's no real
  "view mode" concept in this app to switch between.
- **No restructuring of the official ranking table to show individual
  schools** — the reference's mockup pairs a school name with a
  municipality subtext directly in the "official ranking" table, but
  this app's real, documented, tested rule is that school standings
  must never read as a competing standing (WP-08-05 already resolved
  this exact conflict for the desktop page). The table still ranks
  municipalities.
- **No new "Live Scores" index page** — the bottom nav's "Live" tab
  reuses the meet page's existing "Live now" section (WP-07-08) rather
  than building a third place that renders live-match data, matching
  WP-08-07's own reasoning for its header badge.
- **No changes to the internal admin tally page** — this WP is scoped
  to the public mobile page specifically, per its reference image; the
  admin page's desktop layout (WP-08-05) is untouched.

## Verification

- `npx tsc --noEmit` — 0 errors.
- Full quality gate green (below).
- **Could not get a live visual/mobile-viewport screenshot** — Claude
  in Chrome extension still disconnected this session (re-checked via
  `tabs_context_mcp` before writing this report). This WP touches more
  genuinely visual/responsive surface than most in this phase (a new
  fixed bottom bar, safe-area padding, breakpoint changes), so a real
  device/DevTools mobile-viewport check is recommended before WP-08-10
  more than usual — flagged, not treated as a blocker given the rest of
  the gate passed.
- **Could not get a live HTTP check against http://pmms.app** — checked
  again this session: MySQL and Apache are both running (unlike
  WP-08-04's session), but the same unresolved vhost-routing issue from
  WP-08-05/06/07/08 persists — Apache still serves its own default
  Laragon landing page for `pmms.app` instead of routing to the vhost.
  Not re-investigated further this WP (same reasoning as before: a
  shared Apache instance serving ~a dozen other local projects, not
  worth restarting without asking).

## Test results

`vendor/bin/pest` — **684/684 passing**, 3,516 assertions (1 new test
in `PublicTallyTest`: with 10 districts, the page still returns all 10
in props and exposes `generatedAt` — proving the mobile "top 8" collapse
is a client-side display choice only, never a server-side truncation).

## Quality results

| Check | Result |
|---|---|
| `vendor/bin/pint --test` | Passed |
| `vendor/bin/phpstan analyse` (level 7) | Passed, 0 errors |
| `vendor/bin/pest` | Passed, 684/684, 3,516 assertions |
| `npx eslint .` | Passed, 0 errors |
| `npm run format:check` | Passed |
| `npx tsc --noEmit` | Passed, 0 errors |
| `npm run build` | Passed |

## Files created

- `resources/js/components/public-bottom-nav.tsx`
- `resources/js/components/sports-medal-strip.tsx`
- `docs/reports/phase-08/WP-08-09-completion.md` (this report)

## Files modified

- `app/Http/Controllers/PortalController.php` — `generatedAt` on
  `tally()`
- `resources/js/layouts/public-layout.tsx` — bottom nav wiring, header
  nav hidden below `sm:`, safe-area/footer adjustments
- `resources/js/pages/public/tally.tsx` — compact cards, ranking
  collapse, sports strip, `generatedAt`
- `tests/Feature/PublicTallyTest.php` — 1 new test
- `docs/public-portal.md` — "Mobile bottom navigation" and "Mobile
  medal tally" sections
- `docs/phases/phase-08-ui-ux-visual-alignment/CHECKLIST.md` — WP-08-09
  checked off

## Remaining issues

- Chrome extension still unavailable — a real mobile-viewport check is
  recommended before WP-08-10 more than usual, given how much
  responsive/visual surface this WP touched.
- The pmms.app Apache vhost routing issue (noted since WP-08-05) is
  still unresolved.

## Next

WP-08-10 — Basketball Live Scoreboard UI, on owner instruction (per
this WP's own rule: do not begin the next work package).
