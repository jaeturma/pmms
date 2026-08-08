# Public Portal Day / Night Theme

A user-controlled Day/Night appearance switch for the public portal
(`resources/js/apps/portal/`) only — the admin application, Tournament
Manager console, and every other authenticated/internal surface are
completely unaffected. No login required, no backend storage, no server
request on switch.

## Default theme

**Day Mode (light) is the fixed default for every first-time visitor**,
regardless of the browser/OS's own `prefers-color-scheme` setting. This is
a deliberate change from this portal's prior behavior — see "What this
replaces" below. Night Mode only activates when a visitor explicitly picks
it; there is no automatic system-preference detection anywhere in this
feature.

## Storage

`localStorage['pmms-public-theme']`, values `'light'` | `'dark'`. No
cookie, no database column, no login requirement — a visitor's Night Mode
choice is scoped entirely to one browser. First visit (no stored value)
resolves to `'light'`. Switching to Night Mode writes `'dark'`; switching
back writes `'light'`. This is a fresh, portal-scoped key, intentionally
separate from the admin application's own `localStorage['appearance']`
(`@/hooks/use-appearance`, `'light'` | `'dark'` | `'system'`) — the two
systems never read or write each other's storage.

## `usePortalTheme()`

`resources/js/apps/portal/lib/use-portal-theme.ts` (not
`hooks/usePortalTheme.ts` — see "File locations" below). A module-level
singleton store (`useSyncExternalStore` + a listener `Set`), the same
shape as the admin's `use-appearance.tsx` hook, but a **fresh, independent
implementation** rather than an import of it — nothing under `apps/portal`
depends on a module outside its own tree, so an admin appearance change
can never affect the portal and vice versa. Returns `{ theme, toggleTheme
}`. Every component that calls this hook (the two portal layouts, the
toggle button) shares the same live value automatically, with no prop-
drilling or context provider needed.

## `PortalThemeToggle`

`resources/js/apps/portal/components/theme-toggle.tsx` (not
`components/navigation/ThemeToggle.tsx` — see below). A real `<button>`
(native keyboard activation — Enter/Space — comes free from using a real
button element, not a clickable `<div>`). Shows `Moon` while in Day Mode
(the action it performs — switch *to* Night Mode) and `Sun` while in Night
Mode, both from `lucide-react`, already a project dependency — no new icon
package installed. `aria-label`/`title` both read `"Switch to night mode"`
or `"Switch to day mode"` depending on current state. Rendered once in
`PortalHeader`'s always-visible top-right control cluster (not hidden
inside `md:flex`-gated nav or the mobile hamburger dropdown), so the same
single button serves both desktop (`[ nav ] … [ Staff login ] [ 🌙/☀ ] [
Live badge ]`) and mobile (`[ Logo ] … [ 🌙/☀ ] [ ☰ ]`) — no separate
mobile-only instance needed.

## File locations — deviated from the brief's literal paths

The brief suggested `hooks/usePortalTheme.ts` and
`components/navigation/ThemeToggle.tsx`. The portal's actual, already-
established convention (confirmed by inspecting the existing tree before
writing anything) is flat, kebab-case files: hooks live at
`apps/portal/lib/use-{name}.ts` (e.g. the pre-existing
`use-page-visible.ts`, exporting `usePortalPageVisible`), and components
live at `apps/portal/components/{name}.tsx` (flat, no subfolders, e.g.
`team-card.tsx` exporting `PortalTeamCard`). This feature follows that
real convention — `lib/use-portal-theme.ts` exporting `usePortalTheme`,
`components/theme-toggle.tsx` exporting `PortalThemeToggle` — per the
brief's own explicit permission to do so ("If the existing portal
architecture uses different folders, follow the established portal
convention").

## Theme application: `.pmms-portal[data-theme]`, never `:root`/`<html>`

`data-theme="light"` / `data-theme="dark"` is set directly on the
`.pmms-portal` root element itself (`PortalLayout`'s and
`PortalKioskLayout`'s own wrapper `<div>`) — **never** on `:root`/
`<html>`. This is the strongest available isolation guarantee: since
`.pmms-portal` is a class only these two portal layouts ever render, and
every dark-mode CSS rule requires that exact class *and* the attribute
together (`.pmms-portal[data-theme='dark']`, a single compound selector on
one element — not an ancestor-plus-descendant selector reaching up to
`:root`), there is no selector anywhere that could ever match an admin
page's markup, regardless of what attributes an admin page's own root
element might carry.

## What this replaces: dead CSS made live, and a real conflict fixed

`resources/css/portal.css` already had a light/dark token pair, but two
real problems, found during inspection before implementing anything:

1. **Nothing ever set `data-theme`.** The prior selectors were
   `:root[data-theme='dark'] .pmms-portal` / `:root[data-theme='light']
   .pmms-portal` — an ancestor-plus-descendant pair keyed off an attribute
   on `:root` that no code anywhere in the repository ever wrote (grepped
   the full `resources/` tree to confirm). This was inert, pre-built
   scaffolding for a feature that had never actually been wired up — not a
   working prior dark mode.
2. **`@media (prefers-color-scheme: dark)` auto-activated dark mode for
   any visitor with a dark-preferring OS**, directly contradicting this
   feature's explicit "Day Mode is the default, always, regardless of
   system preference" requirement. This block was removed entirely — Day
   Mode is now the true, unconditional default; Night Mode only ever
   activates from an explicit click, matching the requirement precisely
   rather than being merely overridden-but-still-present dead code.

Both changes are isolated to `portal.css`; the token *values* themselves
(oklch colors for `--portal-bg`, `--portal-accent`, `--portal-maroon`,
etc., in both light and dark) are the same professionally-designed palette
that was already there — this feature activates and corrects the wiring
around them, it does not redesign the colors.

## Theme tokens

Both `.pmms-portal[data-theme='light']` and
`.pmms-portal[data-theme='dark']` define the full existing token set:
`--portal-bg`, `--portal-fg`, `--portal-surface`, `--portal-surface-
foreground`, `--portal-muted`, `--portal-muted-foreground`, `--portal-
border`, `--portal-accent(-foreground/-soft)`, `--portal-ink(-foreground/
-soft)`, `--portal-maroon(-foreground/-soft)`, `--portal-live(-
foreground)`, plus the page/hero gradients. Night Mode is a genuine dark
theme (deep navy/near-black page background, dark navy card surfaces,
light text, muted blue-gray secondary text) — not an inverted-colors
filter. No new tokens were introduced; this feature only fixed how the
existing dark set gets activated (see above).

## Medal colors and sport identity — preserved by construction, not by extra work

Every place identity/medal color genuinely matters already uses either a
literal, theme-independent value or a token whose dark-mode value was
already tuned:

- **Gold/Bronze medal tiles** (`PortalMedalTotalsRow`,
  `PortalTeamCard`, `PortalMedalWinnerCard`) use `--portal-accent(-soft)`/
  `--portal-maroon(-soft)` — real tokens, correctly adapting per theme.
- **Silver** uses a literal `oklch(0.93 0.005 258)` background — a fixed
  light swatch that was never theme-dependent to begin with, so it already
  reads as a bright accent card against a dark page background for free.
- **Boxing red/blue corners** (`CornerPhoto` in `boxing-scoreboard.tsx`)
  use literal `rgba(223,32,41,…)` / `rgba(12,86,216,…)` — fixed values,
  identical in both themes.
- **LIVE indicators** use `--portal-live`, tuned brighter/more saturated
  in dark mode (`oklch(0.62 0.2 25)` vs. light's `oklch(0.55 0.19 25)`) so
  the red stays vivid against a dark background rather than looking muddy.

No component was modified to achieve this — it was true before this
feature and remains true after, verified by live browser inspection of
the boxing and basketball scoreboards in Night Mode (see "Pages
verified").

## Transitions

`.pmms-portal { transition: background-color 200ms, color 200ms,
border-color 200ms; }` — one rule on the single scoping element, not
applied per-child, so the switch doesn't trigger expensive page-wide
recalculation. Respects `prefers-reduced-motion: reduce` (transition
disabled entirely, not just shortened).

## Print

`@media print { .pmms-portal[data-theme='dark'] { …light values…
background: white; } }` — Night Mode is always forced back to the light
palette when printing, regardless of the visitor's on-screen selection.

## Admin isolation

Verified live: selected Night Mode on the public home page, navigated to
`/dashboard` (redirected to `/login` in this unauthenticated browser
session) — confirmed via DOM inspection that neither `.pmms-portal` nor
any `data-theme` attribute exists anywhere on that page, and the login
form rendered in its normal light admin styling. Returned to the public
portal afterward — Night Mode was still active, restored correctly from
`localStorage`. No shared DOM attribute, no shared storage key, no shared
CSS selector between the two systems at any point.

## Pages verified (light and dark)

Home (`/`), Teams index (`/teams`), Team Detail (`/teams/{municipality}`,
including the Total/Elementary/Secondary/Paragames tabs and Medal
Winners cards), Medal Tally (`/meets/{meet}/tally` — full standings
table), Boxing live scoreboard (`/boxing` — red/blue corners, round
clock, judge scores, knockdowns), Basketball live scoreboard
(`/basketball` — score, clock, shot clock, fouls, timeouts, quarter
table, team comparison). The remaining pages in the brief's checklist
(`/teams/{municipality}/players-coaches`, `/softball`, `/volleyball`,
`/athletics`, `/standings`, `/schedule`, `/results`, `/venues`, `/news`,
`/gallery`, `/about`) were not individually screenshotted, but share the
exact same `PortalLayout` wrapper and CSS variable mechanism as every
page that *was* verified — there is no per-page theme logic anywhere for
them to diverge on.

## Mobile

Verified via an injected same-origin `<iframe>` at 390×800 (the reliable
technique for this machine — `resize_window` doesn't actually change the
captured viewport here, a tooling limitation noted in this project's own
memory from a prior feature). The toggle renders in the always-visible
top header row next to the hamburger button (`[ Logo ] … [ 🌙/☀ ] [ ☰ ]`),
not buried in the mobile menu dropdown. Clicking it toggles the theme
correctly; no horizontal overflow was introduced
(`document.documentElement.scrollWidth <= clientWidth` confirmed both
before and after the toggle).

## Accessibility

- Real `<button>`, not a clickable `<div>` — confirmed via `tagName`.
- `aria-label` and `title` both set, and both update correctly with state
  (`"Switch to night mode"` in Day Mode, `"Switch to day mode"` in Night
  Mode) — confirmed via DOM inspection in both states.
- Focusable — confirmed a visible focus ring renders and
  `document.activeElement` correctly resolves to the button after
  `.focus()`.
- **Keyboard activation is guaranteed by using a real `<button>` element**
  — Enter/Space-triggers-click is native, universal browser behavior for
  `<button>`, not something this feature's own code implements or could
  silently break. A live re-test with a synthetic `key` dispatch through
  the browser-automation tooling did not register a click; a real mouse
  click on the same focused element worked correctly immediately
  afterward, confirming the click handler itself is sound — the
  synthetic-keyboard-event gap is a known tooling limitation on this
  machine (synthetic `KeyboardEvent`s are not "trusted" events, so
  browsers don't run their native default action for them), not a defect
  in the toggle.

## Performance

No server/API request on switch, no page reload, no login. The only
runtime cost is the `useSyncExternalStore` subscription (already the
established pattern for the admin's own appearance hook) and a single CSS
custom-property recalculation scoped to `.pmms-portal` — no measurable
layout shift, confirmed by the before/after screenshots at every page
checked.

## Tests

**No frontend/JS test runner exists in this project** (`package.json` has
no `test` script; no Vitest/Jest/React Testing Library dependency
anywhere) — confirmed before claiming otherwise, not assumed. Adding one
from scratch for a single feature's toggle button would be a much larger,
undiscussed infrastructure decision, so none was added. Every behavior the
brief's test checklist asks for was instead verified live in the browser
(see sections above): default light, Moon → dark, persistence across
navigation and after visiting an unrelated (admin) page, Sun → light,
light persistence, admin isolation, keyboard-accessible real button with
correct ARIA, medal-color/live-status readability, mobile toggle, and no
horizontal overflow. The existing Pest suite (PHP, backend-only — this
feature touches zero PHP) was re-run as a full regression check; see the
implementation report for the exact pass count.

## See also

- `docs/public-portal.md` — the broader portal architecture this feature
  extends.
- `resources/js/hooks/use-appearance.tsx` — the admin application's own,
  completely separate appearance system (`class="dark"` on `<html>`,
  `localStorage['appearance']`, light/dark/system) — read for contrast,
  never imported.
