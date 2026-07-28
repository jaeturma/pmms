# Visual Regression and Accessibility Review (WP-08-15)

## Scope decision: accessibility only, visual regression deferred

This WP's own title has two halves. Before writing any code, confirmed
with the owner how to handle the visual-regression half: this project
has **no screenshot/visual-diff tooling of any kind** (no Playwright, no
Percy/Chromatic, nothing), and the Claude in Chrome extension has been
unavailable for every single WP in this phase, so there was no way to
capture real screenshots to diff either manually or automatically.
Building visual-regression infrastructure now would be a genuine
first-of-its-kind new dependency for this project — comparable in
weight to Phase 7's Reverb decision, not something to fold silently into
one WP alongside an accessibility pass.

Presented two options: accessibility-only this session with visual
regression documented as deferred/blocked, versus installing a
screenshot-testing tool and establishing a first baseline now. **The
owner chose accessibility-only, visual regression deferred.**
Recommendation, not yet acted on: a manual visual QA pass (or, if the
owner wants automated coverage going forward, a scoped decision on
which tool to adopt) before Phase 8's final sign-off (WP-08-16).

## Accessibility audit scope

Two dedicated accessibility passes already happened earlier in this
project and are documented in place — WP-04-06 (`docs/public-portal.md`,
"Accessibility & mobile review") covered the original four public
portal pages plus `PublicLayout`/`PublicMeetNav`/`PublicAnnouncements`;
WP-07-03 (`docs/live-scoring.md`, "Accessibility") covered the original
live-scoring UI. This WP's audit deliberately scoped to everything
**new or significantly modified since those two reviews** — i.e. every
page/component built across WP-08-03 through WP-08-14: the admin shell,
dashboard, both tally pages, eligibility, the public portal shell/nav/
hero, the mobile bottom nav, the athletics page, and the extended live
scoreboard (play-by-play, `CountDots`, `SoftballLineScore`, the
disconnected banner, the new breadcrumb headers).

## Findings

**Real gaps found and fixed** — six decorative icons sitting directly
next to their own visible text label, missing `aria-hidden="true"`, so
a screen reader would announce the icon's implicit name ("download",
"printer", "info", "plus") redundantly alongside the text already doing
that job:

- `tally/index.tsx` — the `Download` icon in "Export report" and the
  `Printer` icon in "Print" (page-header actions), and the `Info` icon
  in the real-time-update `Alert`.
- `public/tally.tsx` — the same `Info`-in-`Alert` icon.
- `public/athletics.tsx` — the same `Info`-in-`Alert` icon (the "live
  tracking isn't available" notice).
- `eligibility/index.tsx` — the `Plus` icon in "Upload document".

All six fixed identically: `aria-hidden="true"` added to the icon
element, no other change.

**Verified already sound, no change needed** (each checked directly
against the code, not assumed):

- **Icon-only interactive elements** — none exist in the new surface;
  every button/link that has an icon also has visible text (bottom-nav
  tabs, "View full play by play"/"View full ranking" expand buttons,
  the fullscreen toggle, day-picker chips, the "More sports" tile).
- **Other decorative icons** — `StatCard` icons, dashboard quick-action/
  activity icons, `EventsOverviewCard`'s legend dots, `CountDots`,
  `MedalDistributionCard`'s legend dots, `SportsMedalStrip` icons, and
  the `›` breadcrumb chevrons in `scoring/show.tsx`/`public/
  scoreboard.tsx` were all already correctly `aria-hidden="true"`.
- **Live-updating regions** — the disconnected-connection banner
  (`live-score-display.tsx`) correctly uses `role="status"`. The
  play-by-play list is deliberately **not** an `aria-live` region —
  re-announcing every new play on every poll/Echo push (potentially
  every few seconds during an active game) would be disruptive noise
  for a screen-reader user, not a helpful update; the running score's
  existing `aria-live="polite" aria-atomic="true"` remains the one
  audible live signal, which is the right level (the final number, not
  a scrolling feed). Confirmed as a deliberate design choice, not an
  oversight, and left unchanged.
- **Heading order** — clean single `h1` (via `PageHeader` or
  `PublicPageHero`) → flat `h2` siblings (plain `<h2>` or the `Heading`
  component, which always renders `h2` regardless of its `variant` prop)
  on every page checked (`dashboard.tsx`, `tally/index.tsx`,
  `public/tally.tsx`, `public/athletics.tsx`). `CardTitle` correctly
  renders a styled `div`, not a competing heading — the same convention
  WP-07-03 already established and documented.
- **Color-only information** — `CountDots` is always paired with the
  raw numeral at every call site (basketball fouls, softball balls/
  strikes/outs); `RankBadge` renders the rank number inside the toned
  circle, color reinforcing rather than carrying the meaning alone;
  `MedalDistributionCard`'s donut has a full text alternative via
  `role="img" aria-label="{gold} gold, {silver} silver, {bronze} bronze"`.
- **Focus management** — `PublicBottomNav` uses real Inertia `Link`
  elements, not click-handler `div`s; day-picker chips and the expand
  buttons are real `Button`/`Link` elements; all keyboard-focusable, all
  in natural tab order.
- **Form labels** — eligibility's search reuses the existing `SearchBar`
  component (already sets `aria-label` from its `placeholder` prop
  internally); every `Select` filter added across the tally/eligibility
  pages this phase carries an `aria-label` consistent with sibling
  filters established in earlier phases.

## What was deliberately NOT done

- **No visual regression tooling** — see the scope decision above.
- **No changes to the play-by-play list's live-region behavior** — a
  deliberate design choice, confirmed correct, not a gap to close.
- **No re-audit of pages already covered by WP-04-06/WP-07-03** — this
  pass targeted only what's new or changed since those two reviews, to
  avoid duplicating work already done and documented.

## Color contrast audit (WP-08.5-09)

Color contrast had never actually been *measured* anywhere in this
project before — every prior pass (this doc included, and
`docs/public-portal.md`'s WP-04-06 section) explicitly deferred it,
reasoning "same tokens already in place across Phases 1–3." WP-08.5-09's
own Objective names "contrast" directly, so this pass computed real WCAG
contrast ratios (OKLCH → linear sRGB → relative luminance → the standard
`(L1+0.05)/(L2+0.05)` ratio) for every color pairing introduced or
reused by Phase 8.5, rather than continuing to assume.

**Two real failures found and fixed**, both a "brand-hue text directly
on a light tint" pattern rather than the safer "solid brand-hue fill +
dedicated `-foreground` text" pattern already used correctly elsewhere
(`RankBadge`, `StatCard`'s tone map):

- `text-warning` on `bg-warning/10` (the "connection lost" banner,
  originally WP-08-10, reused by WP-08.5-04's public scoreboard and
  WP-08.5-07's tally kiosk mode) — measured **~2.1:1** in light mode,
  well under WCAG AA's 4.5:1 for text. The obvious-looking fix,
  `text-warning-foreground`, is worse: it measures **~1.05:1** in dark
  mode, because that token is designed to pair with a *solid*
  `bg-warning` fill, not a 10%-opacity tint over a dark page background.
  The actual fix: leave the message text at its inherited default
  `foreground` color (already the app's baseline body-text pairing) —
  measured **~18.3:1** (light) / **~16.5:1** (dark) against the same
  tinted background. Only the icon stays warning-colored (decorative,
  `aria-hidden`, no contrast requirement). Fixed in
  `resources/js/components/live-score-display.tsx` and
  `resources/js/pages/public/tally.tsx`.
- `text-medal-gold` on a near-white background (the new "Meet
  concluded" `Badge` on `public/home.tsx`'s closing-summary card,
  WP-08.5-08) — measured **~1.89:1**. Fixed by switching to the
  established-safe pairing, `bg-medal-gold text-medal-gold-foreground`
  (a solid gold fill with its dedicated dark foreground token) —
  measured **~8.5:1**.

**Verified already sound** (measured, not assumed): `LiveBadge`'s
`bg-destructive` fill with white text (**~4.76:1** light / **~10.1:1**
dark — passes AA, if only just, in light mode); `StatCard`'s
gold/silver/bronze tone map (`bg-medal-*/20` + the matching
`-foreground` token, **~14:1**) — the pattern every other medal-colored
component in this phase should keep following. The two remaining raw
`text-medal-gold` usages left unchanged
(`podium-display.tsx`'s and `home.tsx`'s Crown icons) are both
decorative and `aria-hidden` — no text-contrast requirement applies.

**Not fixed, flagged instead** — the same `text-warning`-on-tint pattern
also exists in three admin-only usages (`resources/js/components/
stat-card.tsx`'s `warning` tone, `resources/js/pages/dashboard.tsx`,
`resources/js/pages/eligibility/index.tsx`'s "pending" badge) — outside
this WP's public-portal scope, not touched, but worth a future pass
since the underlying token pairing is identically wrong there.
