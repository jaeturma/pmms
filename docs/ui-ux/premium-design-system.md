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

## WP-10-01 — Arena Reference Audit and Composition Mapping

Phase 10's opening WP, playing the same role WP-08.5-01 played for
Phase 8.5: no code, a real comparison between the reference and the
actual current codebase, so every later WP in this phase points back to
one shared vocabulary instead of re-deriving it. The reference
(uicookies.com's "Arena" template) was fetched and studied directly
during planning (`WebFetch`, not guessed at from its name) — a football
club site: full-bleed photographic hero with an overlay tagline, a
sticky nav with a ticket-purchase CTA, a monospace live countdown, W/D/L
form badges, structured match/player/news cards at consistent aspect
ratios, a three-column footer, and generous grid-based vertical rhythm.

**Verified against the real current files, not assumed from memory**:
`resources/js/layouts/public-layout.tsx`'s header is `<header
className="border-b">` — static, not sticky. Its footer is one line,
`hidden ... sm:block` (invisible on mobile entirely): `"PMMS Division
Edition — DepEd Schools Division Office"`, no columns, no links.
`resources/js/pages/public/meet.tsx`'s schedule renders as `<Table>`
rows grouped by venue, not individual cards.

| Arena element | PMMS today | Status | Closes in |
|---|---|---|---|
| Full-bleed photo hero + overlay tagline | `.bg-premium-hero` gradient + `PublicPageHero` (title/description/meta band) | Structurally equivalent; no imagery layer (deliberate — no photo pipeline exists, stock photography excluded by the owner); spacing not yet generous | WP-10-03 |
| Sticky nav + CTA button | `PublicLayout`'s header — static `border-b`, a "Live now"/"Sign in" button cluster doubles as the closest thing to a CTA | Real gap: not sticky | WP-10-02 |
| Monospace live countdown | `OpeningCountdown` (`.text-clock`, `tabular-nums`) | **Already fully equivalent** (Phase 8.5-08) | No gap — reuse as-is |
| W/D/L form badges | No PMMS equivalent | **Not applicable** — PMMS has no "team form"/streak concept; a provincial meet's live status (`LiveBadge`/`RankBadge`) already covers the real analogous need | Not in scope anywhere |
| Structured match cards (date/teams/kickoff/venue/CTA) | Schedule = table rows (`meet.tsx`); results = a real podium per event (`PodiumDisplay`, Phase 8.5-08) | Partial — results already card-like; schedule is dense-table, not card-like | WP-10-04 |
| News cards (image + headline + date + tags) | `PublicAnnouncements` (title/body/date only, no image/tag fields) | Partial — content model has no image/tag fields; a card treatment of what data exists is the real target, not a literal Arena news-card clone | WP-10-07 |
| Player cards | No PMMS equivalent | **Not applicable** — athlete profiles are deliberately never public (`docs/public-portal.md`'s privacy boundary); out of scope permanently, not just this phase | Not in scope anywhere |
| Three-column footer | One line of static text, hidden on mobile | Real gap | WP-10-02 |
| Grid-based card systems, consistent aspect ratios | Present in places (home's municipality grid, highlights row) but not consistent across every page | Partial | WP-10-03/04/06 |
| Live score/stat elements | `LiveScoreDisplay` — score, clock, fouls, boxing rounds, softball innings, all already built (Phase 7/8.5) | **Already substantial** — this phase only refines surrounding composition, not the data/typography | WP-10-05 |
| Photography/stadium imagery | None | **Deliberately excluded** — no photo pipeline exists (the same reason Gallery was deferred); not attempted anywhere this phase | Not in scope anywhere |
| Generous vertical rhythm | `gap-6`/`gap-8` already present at page level (Phase 8.5-02/03), tighter (`gap-2`–`gap-4`) at some section/card level | Partial | WP-10-03/04/06 |
| Sports/News/Contact as real nav destinations | Only Schedule/Results/Medal Tally exist as public destinations today | Real gap (Gallery excluded, see Phase 10's own README) | WP-10-07 |

**What this confirms about the rest of the phase's own plan**: the
"real gap" column above maps one-to-one with the WP breakdown already
written in `docs/phases/phase-10-premium-portal-redesign/README.md` —
no new WP is needed, and no planned WP turns out to be unnecessary. The
"not applicable" rows (team form, player cards, stadium photography)
are permanent exclusions rooted in what PMMS actually is (no athlete
profile pages, no photo pipeline, no "form" concept), not gaps to close
later.

## Files touched by WP-10-01

- `docs/ui-ux/premium-design-system.md` — this section (no other file;
  audit/documentation only, per this WP's own scope).

No route, controller, model, migration, component, or test file was
touched.

## WP-10-02 — Public Shell Rebuild: Sticky Nav and Real Footer

Closes the two gap rows the WP-10-01 mapping flagged for this WP:
`public-layout.tsx`'s header was static, and its footer was one
invisible-on-mobile line with nothing to elevate.

**Sticky header.** `<header>` gained `bg-background sm:sticky sm:top-0
sm:z-40` — sticky only at `sm:` and above, matching `PublicBottomNav`'s
own `sm:hidden` breakpoint exactly, so the two never compete for screen
space: below `sm:` the header scrolls away as before and the fixed
bottom tab bar owns navigation; at `sm:` and above the header now
tracks scroll and the bottom bar is absent. No scroll-triggered shadow
was added here — that's WP-10-08's own scope ("sticky-nav shadow-on-
scroll"), not this WP's.

**Real footer.** New `resources/js/components/public-footer.tsx`
(`PublicFooter`) replaces the old one-line `<footer>` with a three-
column layout — brand, a "Current Meet" column (meet name + venue +
school year, each conditionally rendered so nothing invented shows when
data is missing), and a "Quick Links" column reusing the same
`topNavItems` array the header nav already builds (so header and footer
navigation can never drift apart). Per the phase's resolved footer/
Contact decision, there is no office-contact section — PMMS stores no
division-office address/phone/email anywhere, and none was invented.
Kept the prior `hidden ... sm:block` breakpoint (mobile still sees only
the bottom tab bar for navigation, matching the original footer's own
mobile-hidden decision — not a new choice, a preserved one).

**Backend**: `HandleInertiaRequests::publicNav()` gained two additive
fields, `venue` and `schoolYear`, sourced from the same already-scoped
`Meet` the method already resolves — no new query, no new controller
method. Confirmed additive-safe by reading `PublicPortalTest` first:
its two existing `publicNav` assertions use positive `where()` checks,
never a `missing()`/strict-shape assertion (the kind of contract
WP-08.5-08 broke against `meetSummary()` and had to revert) — safe to
extend without the same course-correction.

## Files touched by WP-10-02

- `app/Http/Middleware/HandleInertiaRequests.php` — `publicNav()` gains
  `venue`/`schoolYear`.
- `resources/js/types/global.d.ts` — `publicNav` shared-prop type
  extended to match.
- `resources/js/components/public-footer.tsx` — new, `PublicFooter`.
- `resources/js/layouts/public-layout.tsx` — sticky header, footer swap.
- `tests/Feature/PublicPortalTest.php` — two new assertions on the
  existing `publicNav` test, covering the new fields.

No new routes, migrations, dependencies, or color tokens.

## WP-10-03 — Home Hero and Landing Composition Elevation

Widened the vertical rhythm of `public/home.tsx` per the phase's already-
resolved hero decision ("keep the exact gradient, just give it more
breathing room") — spacing only, no new color or background treatment.

**`PublicPageHero`** (shared by `home.tsx`, `tally.tsx`, `athletics.tsx`)
gained more internal padding: `py-8` → `py-10 sm:py-14` (horizontal
padding and the gradient itself untouched). Verified on all three
consumers per this WP's own rule — `tally.tsx`'s kiosk mode
(`?kiosk=1`) doesn't use this component at all (it renders its own
inline banner), so it's unaffected; the normal (non-kiosk) `tally.tsx`
and `athletics.tsx` renders both simply get a taller hero band, same as
`home.tsx` — consistent, not selectively applied.

**`home.tsx`** itself widened its own section gaps, `sm:` and above
only (mobile stays exactly as before, so phone scroll length doesn't
grow): outer wrapper `gap-8` → `gap-8 sm:gap-12` (and the no-active-
meet empty state's own wrapper `gap-6` → `gap-6 sm:gap-8`, for the same
"more room on larger screens" reasoning); the current-leaders/upcoming-
events/latest-result highlights row `gap-4` → `gap-4 sm:gap-6`; the
competing-municipalities section wrapper `gap-4` → `gap-4 sm:gap-6`,
its card grid `gap-4` → `gap-4 sm:gap-5`, and each municipality card's
own padding `p-4` → `p-4 sm:p-5`.

No data, prop, or business-logic change — `meet`/`currentLeaders`/
`upcomingEvents`/`latestResult`/`closingSummary`/`municipalities` all
render exactly as before, just with more room between them.

## Files touched by WP-10-03

- `resources/js/components/public-page-hero.tsx` — padding only.
- `resources/js/pages/public/home.tsx` — gap/padding utility changes
  only, no structural or data changes.

No new routes, migrations, dependencies, colors, or components.

## WP-10-04 — Schedule and Results Layout Rhythm

Closed the exact gap WP-10-01's mapping flagged: `public/meet.tsx`'s
schedule rendered as bare `<Table>` rows grouped by venue, with no
card presentation, unlike `public/results.tsx`'s per-event blocks
(already a real card since WP-08.5-08: `border-b p-4` header +
`PodiumDisplay` + a bare `overflow-x-auto` table body).

**`meet.tsx`'s schedule** now uses that exact same card shape per
venue group: `rounded-xl border` on the `<section>`, a `border-b p-4`
header (venue name + a `MapPin` icon, already imported/used elsewhere
on this page — no new import), then a bare `overflow-x-auto` table
body with no border of its own, since the ancestor already supplies
one — the precise "table inside a card" convention
`docs/ui-ux/shared-components.md`'s own audit already documented.
Replaces the previous mismatched pattern where the border lived on an
inner `<div>` around just the table while the venue heading sat
outside it, unbordered.

**Inter-card rhythm** widened at `sm:` and above on both pages (mobile
unchanged): the schedule's per-venue card list `gap-2` → `gap-4
sm:gap-6`; `results.tsx`'s per-event card list `gap-4` → `gap-4
sm:gap-6` (same target as `meet.tsx`'s, so both pages read at the same
"generous rhythm between cards" scale). Deliberately did **not** add
per-card stagger delays (unlike `home.tsx`'s fixed 3-card highlights
row) — both lists are unbounded in length (could be 20+ venues or
events), where a per-item `animationDelay` scales badly; the existing
single fade-in for the whole list (`animate-card-in` on the list
wrapper) is the more scalable, already-established convention and was
left as-is.

**`meet.tsx`'s "Venues" info-card list** (a separate, smaller section
below the schedule) got a matching proportional bump for visual
consistency with the schedule cards above it: `gap-2` → `gap-3
sm:gap-4`, each card's `p-3` → `p-4`.

No change to any data, filter, route, or the day-selector's behavior —
`venuesForDay`/`venueGuide`/`results`/`filters`/`sportOptions` all
render exactly the same values as before, just inside a consistent
card shape with more room between them.

## Files touched by WP-10-04

- `resources/js/pages/public/meet.tsx` — schedule card restructure,
  gap increases (schedule list, Venues list).
- `resources/js/pages/public/results.tsx` — gap increase only (per-event
  card shape was already correct from WP-08.5-08, untouched).

## WP-10-05 — Live Scoreboard and Countdown Composition Refinement

`LiveScoreDisplay` itself (team panels, center clock "bug", score
typography) was already largely solved by Phase 8.5 — this WP found
the real remaining gap was one level up: how generously the *consumer*
pages frame the board, and how visually related the pre-match
`OpeningCountdown` card reads next to the live board it hands off to.

**`OpeningCountdown`** gained the same "boxed board" shell
`LiveScoreDisplay` itself uses — `overflow-hidden rounded-2xl border-2
bg-card shadow-sm` with a `h-1.5` gradient top strip
(`bg-gradient-to-r from-sidebar to-primary`, the identical class the
live board's own top strip already uses) — so the countdown card and
the board it's replaced by the moment scoring starts read as one
consistent premium-broadcast family, not two unrelated card styles.
Only container structure changed; the clock typography (`.text-clock`)
and every text/icon element inside are byte-for-byte untouched, per
this WP's own exclusion. `OpeningCountdown` has exactly one consumer
(`public/scoreboard.tsx`, non-kiosk and kiosk both) — confirmed by
grep, so this is a fully contained change.

**`public/scoreboard.tsx`'s** non-fullscreen board wrapper gap widened
at `sm:` and above (mobile unchanged): `gap-4` → `gap-4 sm:gap-6` —
more breathing room between the board and the sport-specific breakdown
blocks beneath it (boxing round-by-round, softball line score, play by
play). **Fullscreen and kiosk mode's own spacing were deliberately left
untouched** — this WP's own rule says preserve full-screen mode exactly
as it works today, and kiosk mode's wrapper was already `gap-8`,
already generous.

**The operator console (`scoring/show.tsx`) was deliberately left
untouched** — neither `LiveScoreDisplay` itself nor `show.tsx`'s own
`gap-4` wrapper changed. This is the WP's own explicit rule, not an
oversight: the operator console must stay operator-dense and must not
visually drift toward the public portal's more spacious look. Verified
both `LiveScoreDisplay` consumers directly: `show.tsx` renders
unaffected (the shared component itself is untouched), and
`scoreboard.tsx` gets the intended, contained elevation.

## Files touched by WP-10-05

- `resources/js/components/opening-countdown.tsx` — restructured into
  the board's own "boxed" shell; no typography/color change.
- `resources/js/pages/public/scoreboard.tsx` — one gap-utility change
  (non-fullscreen board wrapper only).

No new routes, migrations, dependencies, colors, or components.
`live-score-display.tsx` and `scoring/show.tsx` (the operator console)
were read and verified but not modified.

## WP-10-06 — Medal Tally Layout Refinement

Elevated `public/tally.tsx`'s composition only — rankings stay folded
into this page (no separate `/rankings` route, per the phase's already-
resolved decision), and the official gold→silver→bronze ranking
computation/order and medal color tokens are byte-for-byte untouched.

**Spacing widened at `sm:`/`md:` and above** (mobile unchanged): the
outer page wrapper `gap-6` → `gap-6 sm:gap-8`; the data wrapper
(stat cards through school standings) `gap-6` → `gap-6 sm:gap-8`; the
stat-card row `gap-4` → `gap-4 sm:gap-5`; the ranking-table/medal-
distribution-card grid `gap-4` → `gap-4 md:gap-6`; the top-by-points/
medals-by-sport grid `gap-4` → `gap-4 md:gap-6`.

**The "Overall ranking" table** — the page's headline element and the
Objective's explicit "large, spacious ranking presentation" target —
gained `className="text-base"` on its own `<Table>`, one step up from
the app-wide default `text-sm`. This reuses the exact technique the
page's own kiosk branch already established (`<Table className="text-
lg">`, "every cell inherits the larger size") at a more modest step
suited to a normal browser width, not a new pattern. `MedalCells`/
`MedalHeader` (shared with the admin equivalent) have no font-size of
their own, so they inherit correctly; `RankBadge` declares its own
fixed `text-xs`/`size-6`, so it's unaffected either way — the same
combination already proven safe by the kiosk table.

**School standings** (explicitly labeled "Reference only" on the page)
was deliberately left at the ordinary table size — this WP elevates the
*official* ranking, not every table on the page equally.

**`tally/index.tsx` (admin) confirmed unaffected** — it's a structurally
separate file with its own independent `gap-4`/`gap-6` values; only
`public/tally.tsx`'s own JSX changed, and none of the shared components
(`MedalDistributionCard`/`TopByPointsCard`/`MedalsBySportCard`/
`MedalCells`/`MedalHeader`/`RankBadge`) were modified, so the admin page
inherits nothing from this WP.

**Kiosk mode was not touched** — its own separate branch (large-text
table, 30s poll, connection-status banner) already met the "large,
spacious" bar and this WP's own rule preserves it exactly as it works
today.

## Files touched by WP-10-06

- `resources/js/pages/public/tally.tsx` — spacing/gap increases plus one
  `className="text-base"` on the Overall ranking table. No other file.

No new routes, migrations, dependencies, colors, or components. No
change to ranking computation, medal colors, or kiosk mode.

## WP-10-07 — New Public Pages: Sports, News, Contact

The phase's only backend-touching WP — three new read-only routes/
controller actions, kept minimal per this WP's own rule, all scoped
through `Meet::published()` exactly like every other public route.

**Sports** (`/meets/{meet}/sports`) — a card grid of the sports actually
contested in this meet (`Meet::events()`, the real `meet_events` pivot,
grouped by `sport_id` in PHP after one eager-loaded query — no N+1),
each card showing a real event count and linking straight into
`/results`/`/tally` pre-filtered by `sport_id` (both routes already
accept that param — a real integration, not a static dead-end list).
Reused `sportIcon()` from `sports-medal-strip.tsx` (exported for this
purpose, decorative-only, `aria-hidden`) rather than duplicating the
icon-matching logic.

**News** (`/meets/{meet}/news`) — the full, paginated list of this
meet's published announcements (`Announcement::published()`, 10/page,
the standard `paginate()->withQueryString()->through()` chain
`AnnouncementController::index()` already established), reusing the
shared `PublicAnnouncements` component unchanged — this page is the
home page's 5-item preview's own "see all" destination, not a new
rendering.

**Contact** (`/meets/{meet}/contact`) — meet/venue info plus quick
links to every other portal page. Reuses `meetSummary()` exactly, zero
new query beyond the standard `Meet::published()` lookup. **No office-
contact section** — per the phase's already-resolved decision, nothing
was invented; a new test (`PublicContactTest`) asserts `missing()` on
`contact_email`/`contact_phone`/`office_address` to keep that decision
enforced, not just documented.

**Navigation**: all three land in `public-layout.tsx`'s header nav
array only — which the footer's quick-links column already reuses
verbatim (WP-10-02), so extending one list updates both surfaces at
once. `PublicBottomNav` was deliberately not touched, preserving its
tuned item count, per the phase's resolved decision. `PublicMeetNav`
(the Schedule/Results/Medal Tally sub-nav) was also deliberately not
extended — these three pages aren't "meet sections" in that same
sense, and extending that 3-item component wasn't part of the resolved
scope.

## Files touched by WP-10-07

- `routes/web.php` — 3 new guest routes (`public.sports`/`public.news`/
  `public.contact`), same `throttle:60,1` group and `whereNumber`
  convention as every existing public route.
- `app/Http/Controllers/PortalController.php` — 3 new public actions
  (`sports()`, `news()`, `contact()`).
- `resources/js/pages/public/{sports,news,contact}.tsx` — new pages.
- `resources/js/components/sports-medal-strip.tsx` — `sportIcon()`
  exported (was private to the file); no behavior change for its
  existing caller.
- `resources/js/layouts/public-layout.tsx` — header nav array extended
  with the 3 new destinations.
- `tests/Feature/Public{Sports,News,Contact}Test.php` — new, covering
  publication scoping/404, real data, pagination, and public-safe
  fields, matching the existing `PublicResultsTest`/
  `PublicAthleticsTest` conventions.
- `docs/public-portal.md` — 3 new page entries, header-nav update note.

No migrations, no new dependency, no color token change. Wayfinder's
Vite plugin auto-generated the new `resources/js/routes/public/*`
route helpers on `npm run build` — no manual step needed.

## WP-10-08 — Motion and Interaction Elevation Pass

A small, additive layer of plain-CSS micro-interactions, all reading
`--ease-premium`/`--duration-base` — the first WP to actually consume
these two tokens as bare transition utilities rather than only inside
the composite `--animate-*` tokens Phase 8.5 already used them in.

**Real finding, caught by verifying compiled CSS rather than assuming
it worked**: `duration-base` as a bare Tailwind utility class compiles
to **nothing** — Tailwind v4 generates a named utility from a custom
`--ease-<name>` theme key (confirmed: `ease-premium` compiles to
`transition-timing-function: var(--ease-premium)`), but does **not**
do the same for a custom `--duration-<name>` key the same way, since
`duration-*` already has an extensive built-in numeric scale and a
named theme key doesn't hook into its utility generator the same way.
Had this shipped as written, every "transition" added by this WP would
have been silently instant (`transition-duration` defaulting to `0s`)
— motion that looks identical to no motion at all, not merely a subtle
one. Fixed by using Tailwind v4's arbitrary-custom-property syntax,
`duration-(--duration-base)`, which compiles correctly (verified again
in the rebuilt CSS: `.duration-\(--duration-base\){transition-duration:
var(--duration-base)}`) and stays in sync with the token if it's ever
changed, rather than hardcoding `duration-250`. Worth remembering for
any future custom `@theme` extension: verify a new theme-key namespace
actually generates the expected bare utility by checking compiled CSS,
don't assume every `--<namespace>-<name>` key behaves like every other.

**Shadow-on-scroll for the sticky header** (`public-layout.tsx`): a
small `useState`/`useEffect` scroll listener toggles `scrolled`
(`window.scrollY > 8`), applying `sm:shadow-md` only at the breakpoint
where the header is actually `sticky` (WP-10-02) — below `sm:` the
header just scrolls away normally, so there's nothing to elevate. The
header already carries `transition-shadow duration-(--duration-base)
ease-premium`, so the shadow fades in/out rather than popping. The
listener only ever flips a boolean; the motion itself is plain CSS.

**Hover-lift on cards**: `transition-[transform,box-shadow]
duration-(--duration-base) ease-premium hover:-translate-y-0.5
hover:shadow-md` applied to: `meet.tsx`'s schedule venue cards,
`results.tsx`'s per-event cards, `sports.tsx`'s sport cards, and
`PublicAnnouncements`'s shared `<li>` (used by both `news.tsx`, this
WP's own target, and `home.tsx`'s announcement preview — the same
component necessarily affects both, confirmed intentional, not an
oversight). `home.tsx`'s municipality cards already had a bare
`transition hover:shadow-md` (an untokened value from an earlier WP)
— upgraded to the same token-based treatment for consistency rather
than left as the one inconsistent card style on the portal.

**Nav-link hover transition**: the header nav's `Button`s gained
`duration-(--duration-base) ease-premium` via their own `className`
prop — a page-scoped addition, not a change to the shared `Button`
primitive (which stays untouched, so no other button anywhere else in
the app is affected). Previously, `ghost`/`secondary`'s hover color
change snapped instantly (same missing-duration issue as the finding
above, just via the *default* Tailwind utility rather than a custom
one) — now it fades in smoothly.

**Reduced-motion, verified per new class, not assumed**: every class
added is either a `transition-*` utility or reads `transition-duration`
via `--duration-base` — the existing global reset
(`resources/css/app.css`) sets `transition-duration: 0.01ms !important`
on `*`/`::before`/`::after` under `prefers-reduced-motion: reduce`,
which overrides all of them uniformly (`!important` always wins).
`scroll-behavior: auto !important` in that same reset also means the
browser's own smooth-scroll (if any page ever added one) would be
disabled too, though this WP didn't add any scroll-behavior itself. No
new `@keyframes` were added — every effect here is a `transition`
utility, not an animation, per this WP's own exclusion.

## Files touched by WP-10-08

- `resources/js/layouts/public-layout.tsx` — scroll listener, header
  shadow-on-scroll, nav-button transition tokens.
- `resources/js/pages/public/{meet,results,sports,home}.tsx` — hover-lift
  on their respective cards.
- `resources/js/components/public-announcements.tsx` — hover-lift on
  its shared `<li>`.

No new routes, migrations, dependencies, colors, `@keyframes`, or
components. No test changes — pure CSS/motion, no data/prop/route
surface changed.

## WP-10-09 — Admin Shared-Component Visual Polish Pass

Elevated exactly the two shared components that had real, safe room to
improve, out of the five named in this WP's own rule — and documented
the other three as deliberately checked-and-left-alone, not an
oversight.

**`PageHeader`** (34 usages across the admin app) — title
`text-xl` → `text-2xl`, `space-y-0.5` → `space-y-1`. Now matches the
public portal's own `text-2xl font-semibold tracking-tight` h1 scale
(`meet.tsx`/`results.tsx`/`scoreboard.tsx` all use it) — a consistent,
more confident type scale shared across the whole app, without
touching color, background, or layout, so the admin shell stays
visually distinct from the public portal exactly as this phase's own
hard constraint requires.

**`EmptyState`** (40 usages, admin **and** both public portal pages —
confirmed by grep before touching it, since a change here ripples wider
than the admin-only components) — padding `p-10` → `p-12`, icon circle
`size-12` → `size-14`, icon `size-6` → `size-7`, `mb-4` → `mb-5`, title
weight `font-medium` → `font-semibold`. A more spacious, premium
no-data state everywhere it already appears, matching the "large,
spacious" feel WP-10-06 already established for the medal tally page.

**`SearchBar`, `ConfirmDialog`, `PaginationControls`** — audited, left
unchanged. `SearchBar` is a compact, functional filter form; a registry
page benefits from density here, not extra spaciousness — widening it
would work against its actual job. `ConfirmDialog` already reuses
`ui/dialog.tsx`'s shared `DialogTitle` (`text-lg font-semibold`,
confirmed by reading the primitive) — a reasonable size already, and
that primitive itself is out of this WP's blast radius (used by every
dialog in the entire app, not just admin resource pages).
`PaginationControls` is already minimal and correct; adding internal
margin/border risked double-spacing against the ~18 existing call
sites' own wrapper spacing, which this WP's own rule (spot-check 4–5
pages, don't audit all ~20) explicitly can't fully verify is safe.

**The `*FormDialog` convention** (each resource page's own local
`XxxFormDialog`, e.g. `AnnouncementFormDialog`) was reviewed as a
representative sample, not touched — it's a repeated JSX *shape*
across ~15-20 individual page files, not one shared component file;
this WP's own rule restricts changes to the five named shared files
and explicitly forbids touching individual resource pages "unless a
genuine one-off gap is found." None was found — every sampled
`*FormDialog` already composes cleanly from `ui/dialog.tsx`'s standard
primitives.

**Spot-checked 4 representative pages** (not all ~20, per this WP's own
rule) after the change: `registry/schools.tsx` (table-heavy, uses all
four touched-or-audited components in one page), `announcements/index.tsx`
(dialog-form page), `division/edit.tsx` (full-page-form), and
`reports/medal-tally.tsx` (print-relevant). Confirmed `resources/css/
app.css`'s `@media print` block only targets sidebar-shell selectors
(`[data-slot='sidebar']` etc.) — it never touches `PageHeader`/
`EmptyState`'s own classes, so this WP's typography/spacing changes
have zero interaction with the print layout.

## Files touched by WP-10-09

- `resources/js/components/page-header.tsx` — title size, spacing.
- `resources/js/components/empty-state.tsx` — padding, icon size,
  title weight.

No other file modified — `search-bar.tsx`, `confirm-dialog.tsx`,
`pagination-controls.tsx`, every individual resource page, and every
`*FormDialog` were read/audited but deliberately left unchanged. No new
routes, migrations, dependencies, colors, or components.

## WP-10-10 — Admin Sidebar and Dashboard Visual Polish

Spacing/typography/accent polish only, exactly per this WP's own rule
— the sidebar primitive (`ui/sidebar.tsx`) and the dashboard's data/
logic are both untouched; only `app-sidebar.tsx`, `nav-main.tsx`, and
`dashboard.tsx`'s own JSX (className overrides, never the primitives'
own default classes) changed.

**Sidebar** (`app-sidebar.tsx`/`nav-main.tsx`): `SidebarHeader`/
`SidebarFooter` padding `p-2` → `p-3` (a touch more room around the
logo and the meet-context card); the nav group's own wrapper `px-2
py-0` → `px-2 py-1`; `SidebarGroupLabel` gained `font-semibold`
(reads slightly more confident, still `text-xs uppercase tracking-wide`
from the primitive); `SidebarMenu`'s item gap `gap-1` → `gap-1.5`. All
four are className overrides passed into the shared primitive's own
`className` prop (twMerge-safe, confirmed each is a plain non-arbitrary
Tailwind utility so no dedup risk) — the primitive file itself was
never edited.

**Considered and deliberately declined**: an active-nav-item left-
border accent (reusing `--sidebar-primary`). The primitive's collapsed-
icon mode forces `p-2!` (`!important`) on `SidebarMenuButton` in that
state, which would clobber a one-sided padding compensation needed to
keep content aligned when adding a border — a real edge case that
would either misalign the icon in collapsed mode or require touching
the shared primitive's own icon-mode override (out of this WP's
declared scope: "do not restructure it"). Skipped rather than shipped
half-right.

**Dashboard** (`dashboard.tsx`): `QuickActions`'/`MeetOperations`'/
"Recent Activity"'s section headings `text-base font-medium` →
`font-semibold` (one consistent decision across all three, not three
separate ones — the objective names Quick Actions/Meet Operations
explicitly; Recent Activity sits on the same page and would have been
the one visibly inconsistent heading weight left behind otherwise).
`QuickActions`' button grid `gap-3` → `gap-3 sm:gap-4`, each button's
`py-4` → `py-5`. `MeetOperations`' own section gap `gap-4` → `gap-4
sm:gap-6`; its queues `StatCard` grid `gap-4` → `gap-4 sm:gap-5`; its
schedule/tally two-card grid `gap-4` → `gap-4 md:gap-6`.
`EventsOverviewCard`'s `CardContent` `space-y-4` → `space-y-5`, its
progress bar `h-2` → `h-2.5`, its segment-legend `dl` gap `gap-2` →
`gap-3`. No charting library added — the hand-rolled div-bar approach
is simply given more breathing room, per this WP's own exclusion.

**Admin-vs-public distinctness, explicitly verified rather than
assumed**: read `.bg-premium-hero`'s actual definition
(`resources/css/app.css`) — `@apply bg-gradient-to-r from-sidebar to-
primary`. The public hero's gradient *starts* from the same `--sidebar`
navy the admin sidebar fills with (a deliberate, pre-existing Phase
8.5-02 choice to reuse brand tokens, not something this WP introduced
or should "fix"). What actually keeps the two surfaces visually
distinct is structural, not color-exclusive: the sidebar is a narrow,
persistent icon+label rail with no gradient anywhere on it; the public
hero is a full-width top-of-page gradient band with large white
headline text, appearing only once per public page, never as a
persistent shell. No public page has a sidebar; no admin page has a
gradient hero. This WP's own edits added zero color and zero gradient
anywhere, so this distinction is unchanged by this WP — confirmed, not
just assumed.

## Files touched by WP-10-10

- `resources/js/components/app-sidebar.tsx` — header/footer padding.
- `resources/js/components/nav-main.tsx` — group padding, label weight,
  menu gap.
- `resources/js/pages/dashboard.tsx` — section heading weights, widget
  internal spacing (QuickActions, MeetOperations, EventsOverviewCard,
  Recent Activity).

No new routes, migrations, dependencies, colors, or components.
`ui/sidebar.tsx` (the shared primitive) was read but not modified.

## WP-10-11 — Accessibility, Contrast, Responsive Review, and Phase Compliance Review

Phase-closing verification pass, not new visual work — see
`docs/phases/phase-10-premium-portal-redesign/phase-10-compliance-
review.md` for the full architecture-conformance table, per-WP
deliverable re-verification, quality gate, diff-scope confirmation, and
findings/recommendation.

**The one real, substantive fix this WP made**: `TeamLogo`'s 8-color
palette, flagged as unaudited since WP-10-01's own planning, measured
at real WCAG contrast for the first time — all 8 Tailwind 500/600-weight
colors failed AA's 4.5:1 with the component's hardcoded `text-white`
(ranging 2.15:1–4.40:1). `aria-hidden="true"` does not exempt this from
1.4.3 (it only removes the element from the assistive-tech tree, not
from what a sighted low-vision user visually perceives). Fixed by
moving every hue to its own 700-weight — the first uniform tier where
all eight measure ≥4.5:1 (5.05:1–7.29:1) — same hue angles, just
darker, so each municipality keeps a distinct color. See the compliance
review's own contrast tables for the full before/after measurements.

Reduced-motion coverage (WP-10-08's additions), responsive behavior
(the 3 new pages + rebuilt shell), and admin-vs-public distinctness
(WP-10-10's claim) were all re-verified, not re-assumed — see the
compliance review for each. Full gate green: **714/714 tests, 3,878
assertions**; `composer audit`/`npm audit --omit=dev` both clean.

## Files touched by WP-10-11

- `resources/js/components/team-logo.tsx` — palette fixed to 700-weight,
  the fix documented inline in the component's own doc comment.
- `docs/phases/phase-10-premium-portal-redesign/phase-10-compliance-
  review.md` — new, the phase-closing review.
- `docs/phases/phase-10-premium-portal-redesign/CHECKLIST.md` — checked
  off (all 11 WPs now complete).

This closes Phase 10 — Premium Portal Redesign. Awaiting owner review of
the compliance report, then a commit/push decision for the whole Phase
10 tree.

## WP-11-01 — Public Portal Completion Gap Audit

Phase 11's opening WP, closing the gap between the owner's original
Arena brief (which lists Gallery, Rankings, About, FAQs, Search, and 404
among the pages to redesign) and what Phase 10 actually shipped
(deferred Gallery and a separate Rankings route; never built About/
FAQs/Search; 404 untouched beyond its already-correct WP-04-06
functional fix).

**Re-fetched the Arena reference directly** (`WebFetch`,
uicookies.com/demo/theme/arena/, 2026-07-29) rather than trusting Phase
10's notes from memory. Confirmed the real finding this WP exists to
record: **the reference has no separate Gallery, About, FAQ, Search, or
404 page at all.** It is a single-page template — every header/footer
nav item (`Fixtures`, `Results`, `Squad`, `News`, `Membership`, `Buy
Tickets`) is an in-page anchor (`#section`), not a distinct URL. This
matches WP-10-01's own mapping, which never listed those five page
types because they were never present to catalogue. Consequently, this
phase cannot clone a literal Arena "gallery page" or "FAQ page" — it
applies Arena's general design *language* (card grid at a consistent
aspect ratio, generous grid rhythm, structured section headers, the
three-column footer WP-10-02 already built) to each new page instead,
exactly as Phase 10 already did for News/Sports/Contact (also not
literal Arena pages).

| Target page | Arena language applied | PMMS today | Status | Closes in |
|---|---|---|---|---|
| Rankings | Card/table rhythm (same as Medal Tally's existing table) | Ranking data lives only inside `tally.tsx`'s "Overall ranking" table, no standalone route | Real gap — data exists (`MedalTallyService::standings()`), only a new destination is needed | WP-11-02 |
| Gallery | Structured card grid, consistent aspect ratio | No `Photo`/media model anywhere in the schema; no gallery route | Real gap, but bounded — no real photography exists to show; resolved as sport-identity tiles from real `Meet::events()` data, not fabricated photos | WP-11-03 |
| About | Structured section header + info card (same shape as Contact) | `Division::current()` (`name`/`type`/`areaLabel()`) and `meetSummary()` both exist and are unused for this purpose; no about route | Real gap — real data exists, just not assembled into a page | WP-11-04 |
| FAQs | Structured section rhythm, accordion list | No FAQ content or route anywhere | Real gap — needs new (real-data-grounded) content, not just a wiring job | WP-11-05 |
| Search | Card-grid grouped results | No search route; individual pages (`results.tsx`, `news.tsx`) each have their own narrow filter, no cross-content search | Real gap — must be built to the exact same privacy boundary every other public route already enforces | WP-11-06 |
| 404 | Spacing/typography rhythm only | `error.tsx` already functionally correct (WP-04-06: `PublicLayout`, "Back to portal home" link) | Partial — visual pass only, no functional gap | WP-11-07 |

No row in this table is "not applicable" the way three of WP-10-01's
rows were (team-form badges, player cards, stadium photography all had
no real PMMS analogue at all) — every target page here has a real,
buildable PMMS equivalent, just not yet built. The Migration Plan this
WP produces is therefore unchanged from README.md's own WP-11-02
through WP-11-09 breakdown: each row above maps one-to-one to an
existing later WP, confirming the phase's plan needs no adjustment
before WP-11-02 starts.

This closes WP-11-01 — docs-only, zero code changes. See
`docs/reports/phase-11/WP-11-01-completion.md` for the full report.

No new routes, migrations, dependencies, colors, or components.
