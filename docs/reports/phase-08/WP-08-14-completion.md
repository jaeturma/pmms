# WP-08-14 — Responsive Mobile Tablet and Large Display Alignment

**Status:** Complete 2026-07-27. Per this WP's own rules, WP-08-15 has
not been started.

## Scope interpretation

Same situation as WP-08-13: this WP's own reference-image list is the
same generic wrong set every other WP starts with, with no image
actually depicting "responsive alignment" as a concept. Confirms the
prediction from WP-08-13's report — this is a second consolidation/audit
pass, not new visual work against a mockup, this time targeting
responsive-breakpoint behavior specifically (mobile/tablet/large
display) rather than cross-page component consistency.

## What was done

Delegated a second read-only background audit (Explore agent), scoped
to responsive-specific concerns WP-08-13's audit didn't cover: missing
horizontal-scroll wrappers on tables, non-collapsing multi-column grids,
fixed pixel widths that don't shrink, touch-target sizing on public
pages, large-display space usage in the (unconstrained-width) admin app,
and the 640–1023px tablet range specifically. Every finding was verified
by hand before acting — several turned out correct-by-design, same
discipline as WP-08-13.

**Verified correct, left alone:**

- All 52 `Table` usages across the app already have an `overflow-x-auto`
  ancestor — no missing scroll wrapper anywhere.
- `division/edit.tsx`'s unconditional `max-w-lg` form width, flagged as
  "wasting space on large displays" — capping a simple settings form's
  width for readability is standard, correct practice; the shared
  `settings/layout.tsx` does the same via its own `max-w-xl` content
  section. Not a bug.
- `scoring/show.tsx`'s `max-w-2xl` operator control panel — same
  reasoning, a centered control widget, not a table needing full width.
- `dashboard.tsx`'s 3-column events-overview legend (no responsive
  prefix) — the three short labels ("Completed"/"Ongoing"/"Upcoming")
  fit a ~100px column without wrapping even on the narrowest supported
  phone; not a real risk despite having no breakpoint variant.

**Real issues, fixed:**

1. `accreditation/cards.tsx`'s printable ID cards (`w-84`, 336px fixed)
   sat in a bare `flex flex-wrap` container with no scroll wrapper — on
   a phone narrower than ~370px this would overflow the page
   horizontally (`flex-wrap` wraps between items, it doesn't shrink an
   individual oversized one). Added `max-w-full` alongside the fixed
   width so a card shrinks to fit below 336px, while staying at the
   fixed 336px (better for what this page is really for — printing) at
   every wider size.
2. Six widget-pair/sidebar grids jumped straight from a single mobile
   column to a split layout at `lg:` (1024px), leaving the entire
   640–1023px tablet range single-column: the ranking-table +
   medal-distribution-donut split and the top-by-points + medals-by-sport
   pair (both `tally/index.tsx` and `public/tally.tsx`), and the
   dashboard's today's-schedule + companion-widget pair and
   recent-activity split. Moved all six from `lg:` to `md:` (768px).
   Verified safe first: every table involved already has its own
   `overflow-x-auto` (so a tight tablet column scrolls instead of
   breaking), and the sidebar widgets are simple text/dot content that
   wraps gracefully rather than overflowing at a narrower width.
3. Confirmed (not changed) the `size="sm"` (32px) touch-target
   convention WP-04-06 already accepted for the public portal still
   applies unchanged to the two pages added since that review
   (`public/scoreboard.tsx`, `public/athletics.tsx`) — updated
   `docs/public-portal.md`'s accepted-deviations note to say so
   explicitly instead of leaving newer pages unaddressed.

`docs/ui-ux/shared-components.md` (WP-08-13) extended with a
"Responsive breakpoint audit (WP-08-14)" section recording all of the
above, so a future pass has this ground already covered.

## What was deliberately NOT done

- **No changes to the false-positive findings** — each verified
  correct-by-design before being left alone.
- **No forced widening of intentionally-capped form widths** (e.g.
  `division/edit.tsx`) — narrow forms on wide monitors is correct
  design, not a responsiveness gap.
- **No new components or layout restructuring** — every fix is a
  `className` breakpoint-prefix or width-constraint change; nothing
  required new markup.
- **No live-viewport verification** — see Verification below.

## Verification

- `npx tsc --noEmit` — 0 errors.
- Full quality gate green (below).
- All fixes are pure `className` changes with no backend or logic
  changes, so no new Pest tests were needed; the full suite re-run
  confirmed unaffected.
- **Could not get a live visual/responsive-viewport check** — Claude in
  Chrome extension still disconnected this session, so no actual
  DevTools device-emulation pass was possible; all reasoning above is
  static analysis of the Tailwind classes and component structure, not
  a rendered check. Flagged, as with every prior WP this phase, as the
  standing recommendation before final acceptance (WP-08-16).
- **Could not get a live HTTP check against http://pmms.app** — same
  unresolved Apache vhost-routing issue noted since WP-08-05; status
  unchanged, still not treated as a blocker.

## Test results

`vendor/bin/pest` — **695/695 passing**, 3,640 assertions (no new tests
— pure presentational/responsive `className` fixes with no new
behavior to cover).

## Quality results

| Check | Result |
|---|---|
| `vendor/bin/pint --test` | Passed |
| `vendor/bin/phpstan analyse` (level 7) | Passed, 0 errors |
| `vendor/bin/pest` | Passed, 695/695, 3,640 assertions |
| `npx eslint .` | Passed, 0 errors |
| `npm run format:check` | Passed |
| `npx tsc --noEmit` | Passed, 0 errors |
| `npm run build` | Passed |

## Files modified

- `resources/js/pages/accreditation/cards.tsx` — `max-w-full` safety net
  on fixed-width printable cards
- `resources/js/pages/tally/index.tsx` — 2 grids moved `lg:` → `md:`
- `resources/js/pages/public/tally.tsx` — 2 grids moved `lg:` → `md:`
- `resources/js/pages/dashboard.tsx` — 2 grids moved `lg:` → `md:`
- `docs/public-portal.md` — accepted touch-target deviation note
  extended to newer pages
- `docs/ui-ux/shared-components.md` — "Responsive breakpoint audit
  (WP-08-14)" section
- `docs/phases/phase-08-ui-ux-visual-alignment/CHECKLIST.md` — WP-08-14
  checked off

## Remaining issues

- Chrome extension still unavailable — a real device/viewport check
  remains the standing recommendation before WP-08-15/16 finalize
  acceptance, more so now that two WPs' worth of static-analysis-only
  responsive/consistency fixes have accumulated without any live
  verification.
- The pmms.app Apache vhost routing issue (noted since WP-08-05) is
  still unresolved.

## Next

WP-08-15 — Visual Regression and Accessibility Review, on owner
instruction (per this WP's own rule: do not begin the next work
package). Given the Chrome extension has been unavailable for every WP
in this phase, that WP's "visual regression" component may itself need
an owner scoping conversation about how to proceed without live
screenshot capability — worth raising early rather than assuming a
workaround exists.
