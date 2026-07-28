# PMMS Premium Sports Design System — WP-08.5-02

Phase 8.5 — PMMS Premium Sports Experience. This is the token/primitive
foundation every later WP in this phase (03–09) builds on. It **extends**
the existing `docs/ui-ux/design-tokens.md` (Phase 8's brand/status/medal
colors) rather than replacing it — read that doc first for where the
color values came from; this one covers what's new for the premium
direction: gradients, score/timer typography, motion tokens, live
indicators, medal visuals, and the conventions later WPs (hero/branding,
broadcast scoreboards, mobile, motion, large-display, medal ceremony,
performance/accessibility polish) should follow.

Visual direction: Olympic Games + FIFA Tournament Center + NBA Game
Center + FIBA LiveStats + Apple-quality polish — a distinct PMMS
identity, not a copy of any of those brands' actual assets/layouts.

## Colors and gradients

No new brand colors were needed — Phase 8's tokens already gave PMMS deep
navy (`--sidebar`), royal blue (`--primary`), medal gold/silver/bronze,
and a live red (`--destructive`), exactly what
`.ai/skills/pmms-premium-design-system.md` calls for. What was missing
was a **named, reusable hero gradient** instead of the raw utility combo
repeated inline wherever a branded band appeared:

- `.bg-premium-hero` (`resources/css/app.css`, `@layer utilities`) —
  `bg-gradient-to-r from-sidebar to-primary`. `public-page-hero.tsx` now
  uses this instead of the inline classes it had before; any future
  branded band (medal ceremony backdrop, kiosk header) should use the
  same class rather than a new one-off gradient.

## Typography: score digits and timers

A live scoreboard's score and running clock are the two places PMMS most
needs "Apple-quality polish" — large, stable, unmistakable numerals. Three
new semantic utilities in `app.css` formalize what `live-score-display.tsx`
was already doing inline (repeated per call site before this WP):

| Class | Definition | Use |
|---|---|---|
| `.text-score` | `text-6xl font-bold tabular-nums` | A team's running score, normal view |
| `.text-score-lg` | `text-9xl font-bold tabular-nums` | Same, full-screen mode |
| `.text-clock` | `font-mono font-bold tabular-nums` (size applied separately, since the running clock uses two different sizes depending on `fullscreen`) | The running game clock |

`tabular-nums` is load-bearing on all three: without it, a score or clock
digit changing width (`1` → `11`, `9:59` → `10:00`) visibly reflows the
surrounding layout — already correct practice in this codebase (see
`docs/reports/phase-08-5/premium-experience-audit.md` §6), now just
centralized instead of repeated. No new webfont was added — Instrument
Sans already supports tabular figures, and introducing a second display
face was judged unnecessary risk/weight for what these three utilities
already solve; revisit only if a later WP finds a concrete visual gap a
font change would close.

## Motion tokens

Three duration tokens and one easing curve, added to `app.css`'s
`@theme` block for every later WP's card-entrance/tab-transition/skeleton
work (WP-08.5-06's actual scope) to read from instead of inventing new
values per component:

```css
--ease-premium: cubic-bezier(0.16, 1, 0.3, 1); /* gentle, decelerating — not bouncy */
--duration-fast: 150ms;
--duration-base: 250ms;
--duration-slow: 400ms;
```

One motion primitive is implemented now, not just tokenized, because
"live indicators" is this WP's own explicit deliverable:
`--animate-pulse-live` (a slow 2.4s opacity breathe, `@keyframes
pulse-live` in `app.css`) backs the new `LiveBadge` component below. It
is deliberately a slow opacity pulse, not a flash or scale bounce — the
motion guidelines explicitly warn against flashing.

**Reduced motion**: a global `@media (prefers-reduced-motion: reduce)`
block was added to `app.css` (not previously present anywhere in the
codebase — confirmed zero matches in the WP-08.5-01 audit) that forces
every animation/transition in the app to near-zero duration. This means
`--animate-pulse-live` and every future motion WP-08.5-06 adds are
reduced-motion-safe automatically, with no per-component `motion-reduce:`
variant needed. Later WPs should rely on this baseline rather than
re-implementing their own guard, unless a specific animation needs a
*different* reduced-motion fallback (e.g., an instant state-swap instead
of simply skipping the transition) — this baseline only handles the
default "just make it not move" case.

## Live indicators

New component: `resources/js/components/live-badge.tsx` — a small red
pill with the breathing dot described above plus a text label (default
"Live", but every call site passes the session's own real
`status_label` — no hardcoded/screenshot copy). The dot is `aria-hidden`
since the adjacent visible text already carries the meaning.

Applied to the two places in the current codebase that most legitimately
mean "this is live right now":

- `live-score-display.tsx`'s top status indicator, replacing the plain
  `Badge` only when `status === 'in_progress'` — paused/ended sessions
  keep the existing plain `Badge` (a static state doesn't need a live
  pulse).
- `public/meet.tsx`'s "Live now" list — each live match row now shows a
  `LiveBadge` with its real status label instead of a static `Radio`
  icon.

Deliberately **not** applied yet to `public-layout.tsx`'s header "Live
now" button (it shows a live-match *count*, a different, CTA-shaped
affordance, not a bare state pill) or `PublicBottomNav`'s "Live" tab —
left for whichever later WP (05 mobile, 09 polish) actually reworks
those specific components, so this WP stays scoped to establishing and
demonstrating the primitive, not re-touching every "live" mention in the
app.

**Update (WP-08.5-04)**: the two remaining ad hoc `Badge
variant="destructive"` "Live now" pills — the breadcrumb badge on the
operator console (`scoring/show.tsx`) and the equivalent one on the
public scoreboard (`public/scoreboard.tsx`) — now use `LiveBadge` too.
Both sat outside `live-score-display.tsx` (a page-level breadcrumb, not
the shared board component), which is why WP-08.5-02 didn't already
catch them; every "this is live right now" indicator in the app is now
one primitive.

## Last-updated time

New (WP-08.5-04): `LiveScoreDisplay` accepts an optional `lastUpdatedAt`
prop (`number | null`, a `Date.now()` timestamp) and renders "Updated
just now" / "Updated Xs ago" next to the full-screen button, ticking up
via the same remount-a-counter-from-zero technique `RunningClock`
already uses (`key={at}`, so no render ever reads the wall clock
directly — this file's own documented React Compiler purity constraint).
Both consumers (`scoring/show.tsx`'s operator console and
`public/scoreboard.tsx`) now track a `lastUpdatedAt` state value, bumped
on every successful poll response and — for the operator console only —
every Echo `score.updated` push. While the connection is down (the
existing `disconnected` banner, `pollFailures >= 2`), the caller stops
bumping the timestamp, so the "ago" figure keeps climbing — a second,
continuously-worsening signal alongside the static warning banner,
rather than a value that could misleadingly reset.

## Medal visuals

`RankBadge` (`resources/js/components/rank-badge.tsx`, shared by the
admin dashboard and both tally pages) now renders a small crown icon for
rank 1 instead of the bare numeral "1" — the gold/silver/bronze color
tones (Phase 8's existing `--medal-*` tokens) are unchanged, this is
purely the rank-1 icon treatment. The crown is `aria-hidden`; the visible
color/position still carries the meaning, this is reinforcement not new
information. Ranks 2–3 are unchanged (their medal-toned numeral already
reads correctly).

Full podium/medal-ceremony visuals (first/second/third place staging,
confetti-equivalent finalized-winner treatment) are explicitly
WP-08.5-08's scope, not this one's — this WP only upgrades the one
shared numeral component every medal table already uses.

## Elevation, spacing, radius

- **Radius**: unchanged (`--radius: 0.625rem`) — already consistent
  across the app, no gap found.
- **Elevation**: no new shadow tokens were added. Tailwind's existing
  `shadow-sm`/`shadow-md`/`shadow-lg`/`shadow-xl` utilities already cover
  the range needed; the convention (documented here rather than
  re-tokenized) is: resting cards `shadow-sm` (the shadcn `Card`
  default), the scoreboard's "broadcast bug" uses a `border-2` instead of
  a shadow (already established last session,
  `live-score-display.tsx`), and any future modal/ceremony overlay should
  use `shadow-xl`. No component was changed to enforce this — it's a
  documented convention for later WPs, since no current inconsistency was
  found worth fixing here.
- **Spacing**: the WP-08.5-01 audit flagged current public pages as
  "compact admin density" rather than "premium spacious." This WP does
  not change any page's spacing — that's page-level work for WP-08.5-03
  (hero/branding) and WP-08.5-05 (mobile), which should favor `gap-6`/
  `p-6`+ on public "event" pages over the `gap-4`/`p-4` admin default,
  per the premium design system skill's "premium spacing, clean white
  space" guidance. **Update (WP-08.5-03)**: applied to the portal home
  (`public/home.tsx`), which already used `gap-8` at the page level and
  keeps that; the new highlights row uses `gap-4` between its three
  cards, which is the correct card-gutter spacing (not the page-section
  gap this note was about) — no change needed there.

## Tables, cards, tabs, filters

No structural changes in this WP — documenting the existing system as
the baseline later WPs style from:

- **Tables**: the shared `Table`/`TableHeader`/`TableBody`/`TableCell`
  primitives (`components/ui/table.tsx`), used identically by the medal
  tally, schedule, and results pages. `MedalCells`/`MedalHeader`
  (`medal-table-parts.tsx`) are the medal-specific extraction — reuse
  these rather than hand-rolling another gold/silver/bronze/total header.
- **Cards**: shadcn `Card` throughout; `StatCard` (`stat-card.tsx`) is
  the one specialized card variant (colored icon badge + big number),
  already used across the admin dashboard, public athletics page, and
  public tally.
- **Tabs**: `PublicMeetNav` (Schedule/Results/Medal tally) is filled-vs-
  outline buttons, not a real underline-indicator tab bar — no shadcn
  `Tabs` primitive is installed in this project yet
  (`resources/js/components/ui/` has no `tabs.tsx`). Flagged, not fixed,
  here: a true tab component is a reasonable candidate for WP-08.5-03 if
  that WP's hero/branding work touches navigation, but adding an unused
  primitive now (with no consumer) would be scope creep for this WP.
- **Filters**: the `Select`-based sport/age-division filters on the tally
  pages are the existing pattern (`public/tally.tsx`, `tally/index.tsx`)
  — no gap found, no change made.

## Mobile safe areas

Already correctly handled in one place —
`PublicBottomNav`'s `pb-[calc(0.25rem+env(safe-area-inset-bottom))]`
(`public-bottom-nav.tsx`) — documented here as the convention rather than
extracted into a new utility class, since it has exactly one consumer
today. If a second consumer appears (e.g. a kiosk-mode footer,
WP-08.5-07), extract a `.pb-safe`/`.pt-safe` utility at that point rather
than duplicating the `calc()` expression a second time.

## Large-display scaling

No kiosk/TV route exists yet (WP-08.5-07's scope, confirmed empty in the
WP-08.5-01 audit). The scaling *pattern* already proven and worth
reusing is the scoreboard's own `fullscreen` mode: content-driven size
steps (`.text-score` → `.text-score-lg`, `text-3xl` → `text-6xl` clock)
rather than a single global zoom, so text stays crisp instead of
blurrily scaled. WP-08.5-07 should extend this same "explicit large
step" pattern to a dedicated kiosk layout rather than introducing CSS
zoom/transform-based scaling, and must preserve the audit's confirmed
constraint: large-display views carry zero private data and zero
operator controls, the same boundary already correctly enforced on every
public page today.

## Files touched by this WP

- `resources/css/app.css` — motion tokens, `pulse-live` keyframe, global
  reduced-motion reset, `.bg-premium-hero`/`.text-score`/`.text-score-lg`/
  `.text-clock` utilities.
- `resources/js/components/live-badge.tsx` — new.
- `resources/js/components/rank-badge.tsx` — crown icon for rank 1.
- `resources/js/components/public-page-hero.tsx` — uses `.bg-premium-hero`.
- `resources/js/components/live-score-display.tsx` — uses the new score/
  clock utilities; status badge swaps to `LiveBadge` when in progress.
- `resources/js/pages/public/meet.tsx` — "Live now" rows use `LiveBadge`.

No route, controller, model, migration, authorization rule, or test
fixture was touched — this WP is presentational tokens/components only,
per its own scope.

## WP-08.5-04 — Broadcast-Style Live Scoreboards

Upgraded `LiveScoreDisplay` (shared by `scoring/show.tsx` and
`public/scoreboard.tsx`) against the Objective's checklist. Most items
already existed from the prior session's scoreboard redesign (team
logos, running clock, boxing corners — see git history around `d824cc9`)
and WP-08.5-02 (`LiveBadge`, score/clock typography): full-screen mode,
live indicators, polling fallback, and disconnected states were all
already present. Two real gaps were found and closed (see "Live
indicators" and "Last-updated time" above): the operator console's and
public scoreboard's own breadcrumb-level "Live now" badges were still
the pre-premium plain `Badge`, not `LiveBadge`; and no page displayed
when its data was last refreshed at all.

Two Objective items are **deliberately not built here**, both re-
confirmed rather than silently skipped:

- **TV/LED layouts** — this phase already has a dedicated WP for it
  (WP-08.5-07 — Large Display LED Wall and Kiosk Modes), and this doc's
  own "Large-display scaling" section above already defers to that WP by
  name. Building a kiosk/TV-specific layout here would duplicate that
  WP's scope. What this WP *did* verify: the existing `fullscreen` mode's
  content-driven size-step pattern (`.text-score` → `.text-score-lg`)
  still reads at high contrast with the new `LastUpdated` text and
  `LiveBadge` additions — nothing added here works against a future
  large-display mode built on the same pattern.
- **Athletics scoreboard** — does not exist as live data anywhere in this
  codebase, confirmed again here the same way WP-08-11 originally
  confirmed it: `App\Enums\ScoreboardType` has no Athletics case, and no
  scoring event ever attributes a time or mark to an individual athlete
  mid-race. `public/athletics.tsx` is a real schedule/results shell, not
  a live scoreboard, and stays that way — inventing a live athletics
  board here would mean either hardcoding placeholder data (excluded by
  this WP's own rules) or a genuine new feature (excluded — "no
  unrelated backend redesign").

## Files touched by WP-08.5-04

- `resources/js/components/live-score-display.tsx` — new `LastUpdated`/
  `TickingLastUpdated` sub-components and `lastUpdatedAt` prop.
- `resources/js/pages/scoring/show.tsx` — tracks `lastUpdatedAt` state
  (bumped on poll success and Echo push), passes it to
  `LiveScoreDisplay`; breadcrumb "Live now" badge now `LiveBadge`; unused
  `Badge` import removed.
- `resources/js/pages/public/scoreboard.tsx` — same `lastUpdatedAt`
  tracking (poll success only, no Echo for guests) and `LiveBadge` swap;
  the separate "provisional, not the official result" `Badge` is
  unchanged.

No route, controller, model, migration, authorization rule, or test
fixture was touched.

## WP-08.5-05 — Premium Mobile Sports Experience

Checked the Objective's checklist ("bottom navigation, mobile medal
tally, rankings, schedule, results, compact scoreboards, touch-friendly
filters, safe areas, reduced density, and fast loading") against the
actual mobile chrome rather than rebuilding it. Bottom navigation
(WP-08-09), the mobile-safe-area padding on it (`env(safe-area-inset-
bottom)`), the collapsed ranking preview on `public/tally.tsx`
(WP-08-09), the responsive breakpoint pass across every public page
(WP-08-14), and every public page's `gap-6`+ page-level spacing (already
"reduced density" per the WP-08.5-02 guidance — re-confirmed here rather
than re-applied, since it was already in place on every public page, not
just the home page WP-08.5-03 touched) were all already real and needed
no change.

Two real gaps found and closed:

- **Compact scoreboards**: `.text-score`/`.text-score-lg` were a flat
  `text-6xl`/`text-9xl` regardless of viewport width — a three-digit
  score at 60px/128px font-size overflows a phone-width team panel (the
  scoreboard's `grid-cols-[1fr_auto_1fr]` divides the viewport three
  ways). Both utilities now step up at `sm:` (640px) instead, restoring
  the original size for tablet/desktop/broadcast displays where there's
  room. The fullscreen running clock's size classes in `CenterPanel`
  (`live-score-display.tsx`) got the same `sm:` step, since that
  `auto`-width center column would otherwise squeeze the two score
  panels even further on a phone.
- **Mobile live indicators**: `PublicLayout`'s header "Live now" button
  and `PublicBottomNav`'s "Live" tab were the two spots WP-08.5-02
  explicitly named as left for "whichever later WP (05 mobile, 09
  polish) actually reworks those specific components" — this is that WP.
  The header button now renders `LiveBadge` (a single pulsing "Live now
  · N" pill) instead of a static icon+text+count `Badge` combo. The
  bottom nav's "Live" tab keeps its plain `Radio` icon (so the five tabs
  stay visually uniform) but gains a small pulsing dot overlay — a new
  optional `live?: boolean` on `BottomNavItem`, reusing the same
  `animate-pulse-live` keyframe rather than a second one-off animation.
  The dot is `aria-hidden`; the tab's own visible "Live" label already
  carries the meaning.

Touch-friendly filters were checked, not re-designed: the public
`Select` filter triggers are already 36px (`data-[size=default]`),
already above the 32px baseline this app accepted for `size="sm"`
buttons (WP-08-14, `docs/public-portal.md`'s "Accepted deviations" note,
extended here rather than re-litigated). Fast loading was checked via
the actual build output, not assumed: no new dependency was added this
WP; `public-layout.tsx`'s chunk grew from 75.27 kB to 75.39 kB gzip
(the new `LiveBadge` import) and `live-score-display.tsx`'s from 15.29
kB to 15.30 kB gzip — both negligible; the one real bundle-size concern
in this codebase (`wayfinder-*.js`, 346.48 kB / 109.08 kB gzip) is
unchanged and remains WP-08.5-09's concern, not this WP's.

## Files touched by WP-08.5-05

- `resources/css/app.css` — `.text-score`/`.text-score-lg` gained a
  `sm:` responsive step.
- `resources/js/components/live-score-display.tsx` — `CenterPanel`'s
  fullscreen clock size classes gained the matching `sm:` step.
- `resources/js/components/public-bottom-nav.tsx` — new optional
  `live?: boolean` on `BottomNavItem`; renders a pulsing dot on the
  icon when set.
- `resources/js/layouts/public-layout.tsx` — the "Live" bottom-nav item
  now passes `live: true`; the header's "Live now" button now renders
  `LiveBadge` instead of an ad hoc icon/text/count combo; unused `Badge`
  import removed.
- `docs/public-portal.md` — extended the "Accepted deviations" note.

No route, controller, model, migration, authorization rule, or test
fixture was touched.

## WP-08.5-06 — Motion and Interaction System

Implemented the Objective's six items directly, reading the motion
tokens WP-08.5-02 already tokenized-but-didn't-consume
(`--ease-premium`, `--duration-fast/base/slow`) rather than inventing new
values. Three new `@theme` animation entries in `app.css`, each with its
own `@keyframes`, all automatically reduced-motion-safe via the existing
global reset (WP-08.5-02) — no per-component guard needed, same as
`--animate-pulse-live`.

- **Score-change emphasis** — `--animate-score-pop` (a brief
  scale-to-1.08-and-back). `TeamPanel`'s score `<p>`
  (`live-score-display.tsx`) now has `key={score}`, so it remounts (and
  replays the pop) on every real score change — the same remount-key
  technique `RunningClock`/`LastUpdated` already use in this same file.
  Plays once on first mount too, which reads fine as part of the board's
  own entrance.
- **Live pulse** — already existed (`LiveBadge`, WP-08.5-02); nothing to
  do here.
- **Tab transitions** — this app has no client-side `Tabs` primitive;
  its closest equivalent is `PublicMeetNav` (Schedule/Results/Medal
  tally — full Inertia visits to different page components, which
  React remounts on its own) and the day-selector nav on
  `public/meet.tsx`/`public/athletics.tsx` (a visit to the *same* page
  component with a different query string, which React does **not**
  remount on its own). New `--animate-card-in` (fade + 8px rise) is
  applied to: the day-dependent content on both day-selector pages,
  wrapped in a `key={selectedDay}` div so it replays on every day
  switch; and the main content block of `public/results.tsx` and
  `public/tally.tsx`, which — because they're different page components
  from whichever page linked to them — mount fresh (and thus animate)
  every time a visitor arrives via `PublicMeetNav`.
- **Loading skeletons** — new `resources/js/components/public-loading-
  skeleton.tsx` (`PublicLoadingSkeleton`, wraps the existing shadcn
  `Skeleton` primitive that was already in the codebase but unused on
  any public page). Applied to `public/results.tsx` and
  `public/tally.tsx`'s filter-triggered `router.get` calls: both now
  track a `loading` state via `onStart`/`onFinish`, showing the skeleton
  in place of the real content while a filtered visit is in flight —
  real network-round-trip loading, not simulated. Bonus effect: since
  the skeleton and the real content are different element types at the
  same position, swapping back from skeleton to content is itself a
  fresh mount, so `--animate-card-in` on the real-content wrapper
  replays after every filter change too, not just on first page load.
- **Card entrances** — `--animate-card-in` applied to `public/home.tsx`'s
  three highlights cards (staggered 0/80/160ms) and its competing-
  municipalities grid tiles (staggered, capped at 12 items' worth of
  delay so a large grid doesn't grow an ever-longer tail).
- **Winner celebration only after finalization** — new
  `--animate-winner-in` (fade + scale-bounce-in), gated by a new opt-in
  `celebrate?: boolean` prop on `RankBadge`
  (`resources/js/components/rank-badge.tsx`) rather than the component
  guessing from context — the caller must vouch the data is a
  validated/finalized placement. Applied to `public/results.tsx` (which
  also gained `RankBadge` itself, replacing a bare rank numeral, for
  visual consistency with the tally pages' medal language — this page is
  validated-only data, structurally incapable of showing a live/
  provisional score) and `public/home.tsx`'s "Latest official result"
  card (same guarantee, via `PortalController::latestResult()`, which
  only ever queries `ResultStatus::Validated`). Deliberately **not**
  applied to the tally pages' or dashboard's `RankBadge` usage — those
  represent an ever-shifting overall standings leaderboard, not a
  specific decided event outcome, so "just finalized" framing doesn't
  fit; and deliberately not the full podium/medal-ceremony staging
  (multi-place staging, confetti-equivalent) — that remains WP-08.5-08's
  own dedicated scope, this is only the shared numeral component's
  one-time entrance pop.

`public/athletics.tsx`'s per-slot top-3 placements were considered for
the same `RankBadge` treatment but left as plain inline text — that
list's layout (`"1. Athlete (School) — mark"` on one line) doesn't have
room for a circular badge without more layout rework than this WP's
scope justified; it did get the day-switch `--animate-card-in` treatment
alongside `public/meet.tsx`.

## Files touched by WP-08.5-06

- `resources/css/app.css` — three new `@theme` animation entries
  (`--animate-card-in`, `--animate-score-pop`, `--animate-winner-in`)
  and their `@keyframes`.
- `resources/js/components/live-score-display.tsx` — `TeamPanel`'s score
  element gained `key={score}` and `animate-score-pop`.
- `resources/js/components/rank-badge.tsx` — new optional
  `celebrate?: boolean` prop.
- `resources/js/components/public-loading-skeleton.tsx` — new.
- `resources/js/pages/public/home.tsx` — `animate-card-in` on the
  highlights cards and municipality tiles; `RankBadge`'s
  `celebrate` on the latest-result card.
- `resources/js/pages/public/meet.tsx` — day-dependent schedule content
  wrapped in a `key={selectedDay}` `animate-card-in` div.
- `resources/js/pages/public/athletics.tsx` — same day-switch wrapper.
- `resources/js/pages/public/results.tsx` — `loading` state +
  `PublicLoadingSkeleton`; plain rank numerals replaced with
  `RankBadge celebrate`; `animate-card-in` on the results list.
- `resources/js/pages/public/tally.tsx` — `loading` state +
  `PublicLoadingSkeleton`; `animate-card-in` on the main content block.

No route, controller, model, migration, authorization rule, or test
fixture was touched — this WP is frontend-only.

## WP-08.5-07 — Large Display LED Wall and Kiosk Modes

Every prior WP in this phase deferred its own large-display work to this
one by name (WP-08.5-02's "Large-display scaling" section, WP-08.5-04's
"TV/LED layouts" scoping note, WP-08.5-06's "no kiosk/TV route exists
yet"). No new route or backend change was needed — `?kiosk=1` on the two
existing public pages that already carry continuously-relevant real data
(`public/scoreboard.tsx`, `public/tally.tsx`) switches their rendering,
reusing the exact same controller action and props the normal page
already gets.

**Layout resolution**: Inertia's `layout` option (`resources/js/app.tsx`)
receives both the page component name and the full page object
(confirmed by reading `@inertiajs/react`'s compiled source, not
assumed), so a `?kiosk=1` visit to either eligible page name now
resolves to a new `KioskLayout` (`resources/js/layouts/kiosk-layout.tsx`)
instead of `PublicLayout` — a bare full-viewport shell with none of
`PublicLayout`'s header, footer, sign-in/dashboard button, or bottom tab
bar. A new `useKioskMode()` hook (`resources/js/hooks/use-kiosk-mode.ts`)
gives page components the same `?kiosk=1` check via `usePage()`, for the
page-level rendering branch below the layout swap.

**Scoreboard kiosk mode**: reuses the existing `fullscreen` styling
(`.text-score-lg`, the wider board, WP-08.5-02/05) but now forced on
permanently rather than toggled via the browser Fullscreen API — an
unattended TV doesn't have anyone to click a toggle, and actual
`requestFullscreen()` calls require a user gesture browsers won't grant
automatically anyway (a real display running in kiosk mode is expected
to already be in an OS/browser-level full-screen kiosk configuration
outside this app's control). `LiveScoreDisplay` gained two new optional
props to support this: `showFullscreenToggle` (hides the toggle button
entirely) and `maxWidthClassName` (kiosk passes a much wider cap,
`max-w-[1600px]`, than the normal `max-w-5xl`, so the board fills more of
a real 16:9 frame instead of leaving wide black margins). The existing
5-second polling, `disconnected` banner, and `lastUpdatedAt` readout
(WP-08.5-04) needed no changes — they already are the "auto-refresh" and
"connection status" this Objective asks for. A new "Kiosk / TV mode"
link on the normal scoreboard page (next to "Back to schedule") makes
the mode discoverable rather than requiring a hand-typed URL.

**Medal tally kiosk mode**: this page had no live polling at all before
this WP (a plain per-visit Inertia render) — a genuine LED-wall display
needs fresh standings without anyone touching it, so kiosk mode adds a
30-second auto-refresh via Inertia's own `usePoll` hook (confirmed this
exists in the installed `@inertiajs/react` version rather than
hand-rolling a `setInterval` + `router.reload`). A poll re-fetches every
prop including the server-computed `generatedAt`, so that alone is the
"last refreshed" readout — no separate client-side timestamp needed;
only poll failures are tracked client-side, for the same
`disconnected`-style connection-status banner pattern the scoreboard
already uses. The kiosk board itself drops the filters, `PublicMeetNav`,
and the secondary sections (medals by sport, school standings) — a big
screen should show one thing at a glance, the full official ranking (not
capped at the phone-oriented `RANKING_PREVIEW_COUNT`), at a larger text
size (`text-lg` set once on `<Table>` itself, inherited by every cell
including the shared `MedalCells`/`MedalHeader` — no per-component
change needed there). Same "Kiosk / TV mode" discoverability link added
next to the filters, preserving whatever sport/age-division filter was
active when switching into kiosk view (a deliberate choice — the visitor
already chose that filter, kiosk mode is a display change, not a filter
reset).

**Why not a dedicated new route/page instead of a query flag**: a
`?kiosk=1` toggle on the existing routes needed zero backend changes
(no new controller method, no new authorization surface, nothing to add
to `Meet::published()`'s scoping) and can never drift out of sync with
the normal page's data, since it's the literal same props. A dedicated
route would have been a heavier, harder-to-justify change for what is
purely a presentational mode of already-public, already-correct data.

**16:9 / safe margins**: no fixed `aspect-ratio` box was used — actual
physical displays vary (a 16:10 projector, a portrait kiosk panel), and
forcing one would letterbox on anything else. "Safe margins" instead
means generous, responsive padding (`p-6 sm:p-10 lg:p-16`) so content
never touches the frame edge, matching the broadcast "title-safe area"
convention without hardcoding a specific screen shape.

**No private/admin data**: both kiosk views reuse the exact same
public-safe props the normal page already receives (`Meet::published()`-
scoped, no athlete photos, no operator controls) — there was no new data
to check, since nothing new was added to either controller action.

## Files touched by WP-08.5-07

- `resources/js/app.tsx` — `layout` resolver now checks for `?kiosk=1`
  on the two eligible page names before falling through to the normal
  name-based switch.
- `resources/js/layouts/kiosk-layout.tsx` — new.
- `resources/js/hooks/use-kiosk-mode.ts` — new.
- `resources/js/components/live-score-display.tsx` — new optional
  `showFullscreenToggle`/`maxWidthClassName` props.
- `resources/js/pages/public/scoreboard.tsx` — kiosk render branch;
  "Kiosk / TV mode" link on the normal page.
- `resources/js/pages/public/tally.tsx` — kiosk render branch (own
  30s `usePoll`, full untruncated ranking, larger table text); "Kiosk /
  TV mode" link on the normal page.

No route, controller, model, migration, authorization rule, or test
fixture was touched — this WP is frontend-only.

## WP-08.5-08 — Medal Ceremony and Event Presentation

Checked the Objective's list ("podium, athlete/team, delegation, school
origin, event title, champion delegation, opening countdown, closing
summary") against the existing pages rather than inventing a new route.
Three of the four "views" already had a natural, real-data-backed home:
`public/results.tsx` (podium/delegation/champion), `public/scoreboard.tsx`
(opening countdown, gated to before a session starts), and
`public/home.tsx` (closing summary, gated to a meet's real terminal
`Completed` status, `App\Enums\MeetStatus`) — all reusing their existing
controller action and route, with small additive backend fields rather
than a redesign.

**Podium / champion delegation**: `PortalController::results()` and
`latestResult()` both gained a `delegation` field per placement (the
placed athlete's school's municipality, `entry.athlete.school.district`
— already eager-loaded the same way elsewhere, e.g.
`MedalTallyService`). New `PodiumDisplay`
(`resources/js/components/podium-display.tsx`) renders ranks 1–3 in the
conventional silver-gold-bronze left-to-right staging (gold tallest,
center), each showing the athlete, school, and delegation, plus a
"Champion delegation: {district}" callout above the podium sourced from
the real rank-1 placement — never hardcoded. `public/results.tsx` now
shows this per event instead of a flat rank-1-first table; any placement
beyond rank 3 (an event can record more than three) still gets a plain
table below the podium, now with a `delegation` column too.
`public/home.tsx`'s "Latest official result" card was upgraded to the
same `PodiumDisplay` for visual consistency, since it's the same kind of
finalized-result presentation at a smaller scale.

**Opening countdown**: `PortalController::scoreboard()` gained
`scheduled_start_at` (an ISO 8601 instant combining the match's real
schedule slot date + start time, `null` if unscheduled) alongside the
existing `scheduled_date`. New `OpeningCountdown`
(`resources/js/components/opening-countdown.tsx`) ticks down to that
instant and renders nothing once it's reached — shown only in the
`session === null` empty state on both the normal and kiosk scoreboard
views, so it can never appear once real live-score data exists (the
live-score authority rule is structurally preserved: this component and
`LiveScoreDisplay` are mutually exclusive branches of the same `if`).
Ticks via a `now` state value updated once a second inside a
`useEffect`, not `Date.now()` read during render — the same purity
constraint `live-score-display.tsx` already documents for its own
ticking components, solved here with an absolute target timestamp
instead of that file's relative remount-key technique (there's no
server-provided "elapsed" base for a countdown, only a fixed target
instant).

**Closing summary**: new `PortalController::closingSummary()` — `null`
unless `$meet->status === MeetStatus::Completed` (the real terminal
lifecycle state, not inferred from dates or a heuristic), otherwise the
real champion district (top of the same `standings()` ranking `tally()`
and `currentLeaders()` already use) and its final gold/silver/bronze/
total counts. `public/home.tsx` shows this as a gold-tinted "Meet
concluded" card, positioned right after the hero — real data, no
placeholder copy, and it simply doesn't render at all for every ongoing
meet (which is every meet in this codebase's real data today; verified
by inspection, not by adding a redundant guard the type system doesn't
need).

**Deliberately not built**: a kiosk-mode ("`?kiosk=1`") variant of the
podium/ceremony view — WP-08.5-07 already established the kiosk-mode
pattern for two pages; extending it to a third here would be re-opening
that WP's scope rather than this one's. `public/athletics.tsx`'s
per-slot top-3 placements were reconsidered for `PodiumDisplay` (same
question WP-08.5-06 raised) and still left as plain text — that page
lists an entire day's events at once, and a full podium per slot would
make the page very long; `results.tsx` (one card per event, already the
right granularity) remains the natural home for the podium treatment.

## Files touched by WP-08.5-08

- `app/Http/Controllers/PortalController.php` — `results()` and
  `latestResult()` gained a `delegation` field per placement (plus
  `mark`/`is_tie` on `latestResult()`'s, matching `results()`'s
  existing shape); `scoreboard()` gained `scheduled_start_at`; new
  `closingSummary()` private method, wired into `home()`.
- `resources/js/components/podium-display.tsx` — new.
- `resources/js/components/opening-countdown.tsx` — new.
- `resources/js/components/rank-badge.tsx` — `medalToneClasses` exported
  for `PodiumDisplay` to reuse the same gold/silver/bronze color mapping.
- `resources/js/pages/public/results.tsx` — podium + delegation column
  on the "other placements" table.
- `resources/js/pages/public/home.tsx` — `PodiumDisplay` on the latest-
  result card; new closing-summary card.
- `resources/js/pages/public/scoreboard.tsx` — `OpeningCountdown` in
  both the normal and kiosk empty states.
- `tests/Feature/PublicResultsTest.php` — the "no sensitive or internal
  fields" placement-shape assertion updated to include the new,
  deliberate `delegation` field (a strict schema check; needed updating
  as part of this WP's own intended change, not a workaround).

No new route, migration, or authorization rule — every change extends
an existing controller action's payload. `PortalController::home()`'s
shared `meetSummary()` helper was deliberately **not** touched (a raw
`status` field was tried, then reverted after finding an existing test
asserting `missing('status')` on it, WP-04-06 — `closingSummary()` is a
separate, purpose-built prop instead, so that established public-safe-
fields contract on the shared helper stays intact).

## WP-08.5-09 — Public Portal Performance and Accessibility Polish

Checked every item in the Objective's list against the actual code
rather than assuming any of them were fine — the biggest surprise was
that one of this phase's own repeatedly-flagged "concerns" turned out
not to be one.

**Bundle usage — corrected a running assumption.** Every WP-08.5-02
through 08 report flagged `wayfinder-*.js` (~346.8 kB / ~109.2 kB gzip)
as a bundle-size concern "for WP-08.5-09," based on its name suggesting
route-helper bloat. Actually inspecting the built chunk's contents this
WP found that's wrong: the bulk of it is `es-toolkit` (a lodash-style
utility library), which is `@inertiajs/core`'s own internal dependency
— required by every single Inertia page, admin or public, to run
Inertia itself, not something this app's route generation controls.
Confirmed via the build manifest that both `public/home.tsx` and the
admin `dashboard.tsx` import the exact same chunk, which is expected and
correct: it's a genuinely shared framework dependency, downloaded once
per browser session and reused (cached) across every subsequent page
visit, not re-fetched per page. There is no real optimization available
here without changing the Inertia version itself, which is out of
scope. This flag is now closed — a real investigation with an honest
"not actually a problem" conclusion, not a shrug.

**A real, own-code performance bug found and fixed**: `home()` was
calling `MedalTallyService::standings()` **twice** in the same request
— once from `currentLeaders()` (WP-08.5-03) and once from
`closingSummary()` (WP-08.5-08), each independently re-querying and
re-grouping validated placements. `home()` now computes the district
standings once and passes the resulting collection into both helpers.
This is different from — and a real bug, unlike — the codebase's
existing practice of each *separate public page* computing its own
`standings()` once per its own request (`tally()`, `athletics()`);
calling it twice *within one request* was pure waste, not an accepted
pattern.

**Image optimization / lazy loading**: no real image ever reaches the
public portal to optimize — `TeamLogo` is a CSS-only generated-initials
badge (no `<img>` at all), and the only real `<img>` in the whole
scoreboard component (`live-score-display.tsx`'s boxing corner photo) is
gated behind a `participants` prop only the internal operator console
ever passes (confirmed by grep — no public page passes it), per
`docs/public-portal.md`'s privacy baseline. Route-level lazy loading is
already fully in place: Vite/Inertia code-splits every page into its own
chunk automatically (confirmed via the build manifest), and `lucide-
react` icons are already tree-shaken to individual per-icon chunks.
Considered Inertia's deferred-props feature for `tally.tsx`'s heavier
secondary sections (medals by sport, school standings) — not
introduced: it's never been used anywhere in this codebase, and adding
it for the first time (new frontend fallback UI, new test
implications) reads as a feature addition, not a "polish" pass; the
per-page code-splitting already in place is the real, already-satisfied
lazy-loading story for this phase.

**Reverb fallback**: structurally already guaranteed on the public side
— confirmed via grep that no public page imports `useEcho`/
`@laravel/echo-react` at all; every public live view is polling-only
by design (WP-07-08), so there is nothing to "fall back" from.

**Caching**: considered adding explicit `Cache-Control: no-store`
headers to the polling endpoints (`scoreboard/poll`, the tally kiosk
reload) as defense-in-depth against an intermediary cache serving stale
scores — not added. This deployment is a single Laragon machine with no
CDN or reverse proxy in front of it (`docs/deployment.md`'s WP-06-07
decision), so the risk this would guard against doesn't exist today;
adding response headers with zero precedent anywhere in this codebase
for a threat model that doesn't apply would be speculative hardening,
not a real fix. Static asset caching is already handled correctly at
the build level (Vite's content-hashed filenames, confirmed in the
manifest) — web-server cache-header configuration is infrastructure,
not application code, and out of scope the same way the Apache vhost
question has been every WP since WP-08-05.

**Keyboard support / touch targets**: re-checked for anything new since
WP-08-14/WP-08.5-05's reviews — found nothing new needing a change.
Every interactive element added across WP-08.5-02 through 08
(`LiveBadge`-wrapped links, the "Kiosk / TV mode" buttons, `Select`
filters) is a real `Button`/`Link`/Radix `Select`, already keyboard-
operable and already at or above the app's accepted 32px touch-target
baseline. `PodiumDisplay`/`OpeningCountdown` have no interactive
elements at all (pure display).

**Contrast**: see `docs/ui-ux/accessibility-review.md`'s new "Color
contrast audit" section — the real substance of this WP's accessibility
half. Two real failures found and fixed (a warning-banner text color,
a medal-gold badge), both measured with actual WCAG ratios rather than
eyeballed.

**Reduced motion**: re-confirmed all of WP-08.5-06's animations and this
WP's own `OpeningCountdown` inherit the existing global
`prefers-reduced-motion` reset automatically — `OpeningCountdown` has no
CSS animation at all (its digits update via React state, like
`RunningClock`), so there was nothing to guard in the first place.

**Privacy**: re-confirmed via grep — zero `photo_url`/`participants`
usage anywhere under `resources/js/pages/public/` or in the two new
Phase 8.5-08 components (`podium-display.tsx`, `opening-countdown.tsx`).

**Graceful failures**: `OpeningCountdown` hardened against a malformed
`startsAt` (a `Number.isNaN` guard — unreachable with real data today,
since the backend only ever sends a real ISO 8601 string or `null`, but
a parse failure would otherwise render "NaN:NaN" instead of nothing).
Also stopped its `setInterval` once the countdown reaches zero, instead
of ticking forever for as long as the parent keeps the component
mounted (e.g. an unattended kiosk sitting on a match page past its
scheduled start with scoring still not begun) — a real, if minor, waste
this WP's own "performance polish" scope should catch.

## Files touched by WP-08.5-09

- `app/Http/Controllers/PortalController.php` — `home()` computes
  district standings once, shared by `currentLeaders()`/
  `closingSummary()` (both signatures changed to accept the pre-computed
  `Collection` instead of calling `MedalTallyService` themselves).
- `resources/js/components/live-score-display.tsx` — disconnected-
  banner message text no longer sets `text-warning` (contrast fix); icon
  keeps the warning color.
- `resources/js/pages/public/tally.tsx` — same contrast fix on the
  kiosk connection-status banner.
- `resources/js/pages/public/home.tsx` — closing-summary "Meet
  concluded" badge switched from `text-medal-gold` to
  `bg-medal-gold text-medal-gold-foreground` (contrast fix).
- `resources/js/components/opening-countdown.tsx` — `Number.isNaN`
  guard; the ticking interval now stops itself once the target instant
  has passed.
- `docs/ui-ux/accessibility-review.md` — new "Color contrast audit"
  section.
- `docs/public-portal.md` — updated the "Accepted deviations" note to
  point at the new contrast audit instead of saying contrast was never
  checked.

No new route, migration, authorization rule, or test fixture — every
change is either a controller-internal efficiency fix (same output
shape) or a presentational contrast/robustness fix.
