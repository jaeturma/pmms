# WP-08-15 — Visual Regression and Accessibility Review

**Status:** Complete 2026-07-27. Per this WP's own rules, WP-08-16 has
not been started.

## Scope decision (owner-directed)

Same generic wrong reference list as every prior consolidation WP, no
image relevant to "visual regression" or "accessibility review" as
concepts. But unlike WP-08-13/14, this WP's own title names a real
capability gap, not just a missing mockup: this project has **no
screenshot/visual-diff tooling of any kind**, and the Claude in Chrome
extension has been unavailable for every WP in this entire phase, so
there was no way to capture real screenshots to diff — manually or
automatically.

Presented the owner two options before writing anything: accessibility-
only this session with visual regression documented as deferred, versus
installing a screenshot-testing tool and establishing a first baseline
now (a genuine first-of-its-kind new dependency for this project,
comparable in weight to Phase 7's Reverb decision). **The owner chose
accessibility-only, visual regression deferred.**

## What was done

**Accessibility audit**, scoped deliberately to everything new or
significantly modified since the project's two prior dedicated
accessibility passes (WP-04-06 for the original public portal,
WP-07-03 for the original live-scoring UI) — i.e. every page/component
built across WP-08-03 through WP-08-14: the admin shell, dashboard,
both tally pages, eligibility, the public portal shell/nav/hero, the
mobile bottom nav, the athletics page, and the extended live scoreboard.

Delegated a background audit across 7 categories (icon-only interactive
elements, decorative icons missing `aria-hidden`, live-updating
regions, heading order, color-only information, focus management, form
labels). Verified every finding by hand before acting — most of the 7
categories came back clean (already correct), matching the pattern
established by WP-08-13/14's audits.

**Real gaps found and fixed** — six decorative icons sitting directly
next to their own visible text label, missing `aria-hidden="true"`
(the icon's implicit name would otherwise be announced redundantly
alongside the text already doing that job): the `Download`/`Printer`
icons in `tally/index.tsx`'s "Export report"/"Print" actions; the
`Info` icon in the real-time-update `Alert` on `tally/index.tsx`,
`public/tally.tsx`, and `public/athletics.tsx`; and the `Plus` icon in
`eligibility/index.tsx`'s "Upload document" button. All six fixed
identically, no other change.

**Verified already sound** (each checked directly against the code):
no icon-only interactive elements exist in the audited surface (every
icon has a visible text label alongside it); every other decorative
icon across `StatCard`, dashboard's quick-actions/activity icons,
`EventsOverviewCard`, `CountDots`, `MedalDistributionCard`,
`SportsMedalStrip`, and the scoreboard breadcrumb chevrons was already
`aria-hidden="true"`; the disconnected-connection banner correctly uses
`role="status"`; heading order is clean (one `h1`, flat `h2` siblings,
`CardTitle` correctly not competing as a heading) on every page
checked; no information is conveyed by color alone anywhere audited
(`CountDots` always shows the raw number, `RankBadge` shows the rank
digit, the medal-distribution donut has a full text alternative via
`role="img" aria-label`); the mobile bottom nav and every new expand/
day-picker control use real focusable `Link`/`Button` elements; every
new search/filter control already carries an `aria-label`.

One finding examined and deliberately left unchanged: the play-by-play
list (WP-08-10/12) is not an `aria-live` region. Re-announcing every new
play on every poll/Echo push — potentially every few seconds during an
active game — would be disruptive noise for a screen-reader user, not a
helpful update. The running score's existing `aria-live="polite"
aria-atomic="true"` remains the one audible live signal, which is the
right level of announcement. Confirmed as a deliberate, correct design
choice, not an oversight.

New `docs/ui-ux/accessibility-review.md` records the scope decision, the
audit's full findings (including everything verified sound, so a future
pass doesn't need to re-check it), and the six fixes.

## What was deliberately NOT done

- **No visual-regression tooling installed** — the owner's explicit
  choice; see the scope decision above.
- **No changes to the play-by-play list's live-region behavior** — a
  deliberate, correct design choice, not a gap.
- **No re-audit of pages already covered by WP-04-06/WP-07-03** — this
  pass targeted only what's new or changed since those two reviews.

## Verification

- `npx tsc --noEmit` — 0 errors.
- Full quality gate green (below).
- All six fixes are pure `aria-hidden="true"` attribute additions with
  no logic changes, so no new Pest tests were needed.
- **Could not perform live screen-reader or visual verification** —
  Claude in Chrome extension still disconnected this session (this is
  precisely the constraint that drove the scope decision above). All
  findings and fixes are static code-level accessibility analysis, not
  a rendered/assistive-technology check.
- **Could not get a live HTTP check against http://pmms.app** — same
  unresolved Apache vhost-routing issue noted since WP-08-05; status
  unchanged, still not treated as a blocker.

## Test results

`vendor/bin/pest` — **695/695 passing**, 3,640 assertions (no new tests
— pure `aria-hidden` attribute additions with no new behavior to cover;
full suite re-run and confirmed unaffected).

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

## Files created

- `docs/ui-ux/accessibility-review.md`
- `docs/reports/phase-08/WP-08-15-completion.md` (this report)

## Files modified

- `resources/js/pages/tally/index.tsx` — 3 `aria-hidden` additions
  (Download, Printer, Info)
- `resources/js/pages/public/tally.tsx` — 1 `aria-hidden` addition (Info)
- `resources/js/pages/public/athletics.tsx` — 1 `aria-hidden` addition
  (Info)
- `resources/js/pages/eligibility/index.tsx` — 1 `aria-hidden` addition
  (Plus)
- `docs/phases/phase-08-ui-ux-visual-alignment/CHECKLIST.md` — WP-08-15
  checked off

## Remaining issues

- Chrome extension still unavailable — this is now the standing
  recommendation across three consecutive WPs (08-13/14/15): a real
  device/browser QA pass (visual, responsive, and assistive-technology)
  before WP-08-16 finalizes Phase 8 acceptance.
- Visual regression tooling remains undecided/deferred, per the owner's
  choice this WP — worth a dedicated scoping conversation if the owner
  wants automated coverage going forward, separate from Phase 8's
  closeout.
- The pmms.app Apache vhost routing issue (noted since WP-08-05) is
  still unresolved.

## Next

WP-08-16 — Phase 8 Final Visual Acceptance, on owner instruction (per
this WP's own rule: do not begin the next work package). Given the
accumulated live-verification gap flagged above, that WP may itself
need to open with the same question WP-08-15 opened with: how to reach
"acceptance" honestly without ever having seen the app rendered this
entire phase.
