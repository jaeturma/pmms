# WP-08-02 — PMMS Design Tokens and Visual Standards

**Status:** Complete 2026-07-27. Per this WP's own rules, WP-08-03 has
not been started.

## Repository findings

Read `.ai/project-rules.md`, `.ai/current-phase.md`,
`.ai/work-package-runner.md`, `.ai/ui-ux-rules.md`, this phase's
`README.md`, and this WP's own doc. Confirmed WP-08-01's own finding
before touching anything: the entire theme in `resources/css/app.css`
was `oklch(x 0 0)` — zero chroma on every single token, shadcn/ui's
unmodified grayscale default, no PMMS brand color anywhere. Confirmed
Tailwind 4's CSS-first config is in use (no `tailwind.config.js`
exists) — `app.css`'s `@theme`/`:root`/`.dark` blocks are the single
source of truth for every color token in the app.

## What was built

Rather than guess a palette, every new color was **sampled directly
from the 9 approved reference images**
(`docs/ui-ux/references/*.png`) using a small Python/Pillow script:
downsample each image, bucket pixel colors, filter out low-saturation/
near-white/near-black noise (anti-aliasing), and convert the most
frequent remaining candidates from sRGB to OKLCH. Two hues converged
consistently across every reference — a deep navy (`hue ≈ 258`, used
for sidebars/headers) and a vivid royal blue (`hue ≈ 263`, used for
buttons/active states/badges) — plus a gold (`hue ≈ 85`) and a green
(`hue ≈ 145`) that appeared consistently across the medal-tally and
eligibility-checker references. The existing (unchanged) destructive
red already matched the reference's "LIVE" red almost exactly
(sampled hue 27–28 vs. the app's existing 27.325) — left as-is rather
than needlessly re-tuned.

`resources/css/app.css` changes:
- `--primary`/`--sidebar`/`--sidebar-primary`/`--sidebar-accent` and
  their dark-mode equivalents replaced with the sampled navy/blue
  palette.
- `--secondary`/`--muted`/`--accent`/`--border`/`--input`/`--ring`
  given a subtle cool-blue tint (kept low-chroma deliberately — see
  "What was deliberately left alone" in the new design-tokens doc;
  these back Radix/shadcn's pervasive subtle-hover states, so making
  them a strong brand color would tint every dropdown/select hover in
  the app, which no reference image shows).
- Two new semantic token pairs added to the `@theme` mapping and both
  `:root`/`.dark`: `--success`/`--success-foreground` (green — matches
  "ONGOING"/"Approved"/"PASSED" badges) and `--warning`/
  `--warning-foreground` (amber — matches "UPCOMING"/"UP NEXT"
  badges). Neither existed before; the previous theme only had
  primary/secondary/accent/destructive.
- Three new medal-color token pairs:
  `--medal-gold`/`--medal-silver`/`--medal-bronze` (each with a
  matching `-foreground`), for the rank-1/2/3 highlights that recur
  across the Dashboard, admin Medal Tally, and public/mobile ranking
  pages — currently rendered as plain unstyled text everywhere.
- `--chart-1..5` remapped from Tailwind's generic demo chart colors to
  the new brand+status palette (blue/gold/success/warning/destructive),
  giving the not-yet-built Medal Distribution and Events Overview
  donut charts (flagged in WP-08-01) a cohesive palette to use when
  they're built.
- **Fixed a real pre-existing bug**: `--destructive-foreground` was
  set to the exact same value as `--destructive` in light mode (text
  in that color would be invisible against a destructive background)
  and to a different-but-still-wrong mid-red in dark mode. Never
  visibly broken because `Button`'s `destructive` variant hardcodes
  `text-white` instead of using the token, but the token itself was
  wrong regardless. Fixed to white in both modes.

New file: `docs/ui-ux/design-tokens.md` — documents every token above,
why `--accent`/`--secondary`/`--muted` were deliberately left neutral,
what was deliberately left unchanged (typography, radius), and a
`grep`-found list of pages already using raw Tailwind palette classes
instead of tokens (flagged for a later consistency pass, not fixed
here — out of this WP's scope, no reference image covers them).

## What was NOT done (correctly, per this WP's scope)

No `.tsx` page or component was touched. This WP establishes the
token *values* only — applying them to the sidebar, dashboard, medal
tally, etc. is WP-08-03 onward. Every existing shadcn/ui primitive
(`Button`, `Badge`, `Card`, `Sidebar`, ...) already reads from these
CSS variables, so the new brand colors already apply automatically
wherever those primitives are used (default button color, default
badge color, sidebar background, focus rings) without any component
edit — this is the intended effect of a pure token-layer change, not
an accident.

## Verification

- **OKLCH → sRGB round-trip check** (Python script, not just visual
  guessing): every new token converts to an in-gamut, sensible color —
  primary `#0538ab`, sidebar `#011f4b`, success `#3a9742`, warning
  `#e99b2a`, medal-gold `#edb417`, medal-silver `#abaeb1`, medal-bronze
  `#ac704e`. No new token clips out of sRGB gamut (the one out-of-gamut
  value found, `--destructive`, is the pre-existing unchanged value,
  not something this WP introduced).
- `npm run build` — succeeded, confirms the new `@theme` block and
  CSS custom properties compile cleanly.
- **Could not get a live visual screenshot** — Claude in Chrome
  extension was disconnected for this entire WP (checked three times:
  before starting, mid-work, and after the build). Recommend a real
  visual check (open `http://pmms.app` and eyeball the sidebar/badges)
  before WP-08-03 builds on top of these tokens, or ask me to check
  once the extension reconnects.

## Test results

`php artisan test` — **671/671 passing**, 3,341 assertions, unchanged
from before this WP (a pure CSS-token change cannot affect backend
behavior; ran anyway per project rules to confirm no regression).

## Quality results

| Check | Result |
|---|---|
| `vendor/bin/pint --test` | Passed (no PHP touched) |
| `vendor/bin/phpstan analyse` (level 7) | Passed, 0 errors (no PHP touched) |
| `php artisan test` | Passed, 671/671 |
| `npx eslint .` | Passed, 0 errors |
| `npm run format:check` | Passed |
| `npx tsc --noEmit` | Passed, 0 errors |
| `npm run build` | Passed |

## Files created

- `docs/ui-ux/design-tokens.md`
- `docs/reports/phase-08/WP-08-02-completion.md` (this report)

## Files modified

- `resources/css/app.css` — new/changed color tokens, see above
- `docs/phases/phase-08-ui-ux-visual-alignment/CHECKLIST.md` (WP-08-02
  checked off)

## Remaining issues

- Chrome extension still unavailable — recommend a real visual pass
  before WP-08-03 proceeds, or the next time it's available.
- A handful of existing pages use raw Tailwind palette classes instead
  of semantic tokens (listed in `docs/ui-ux/design-tokens.md`) — not
  fixed here, flagged for a later consistency-focused WP.

## Next

WP-08-03 — Admin Application Shell and Navigation, on owner
instruction (per this WP's own rule: do not begin the next work
package).
