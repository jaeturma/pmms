# PMMS Design Tokens and Visual Standards

The color system established in `resources/css/app.css`'s `@theme` /
`:root` / `.dark` blocks (Tailwind 4's CSS-first config — there is no
separate `tailwind.config.js`). Every value below was sampled directly
from the approved reference images (`docs/ui-ux/references/`), not
guessed — see "How these were derived" at the end of this doc.

This WP (WP-08-02) only establishes the tokens themselves. No page
component was changed — that starts at WP-08-03. Any WP from here on
that touches visual styling should read from these tokens
(`bg-primary`, `text-medal-gold`, etc.) rather than hardcoding a raw
Tailwind color or a new one-off value.

## Brand colors

| Token | Light | Dark | Use |
|---|---|---|---|
| `--primary` / `bg-primary` | `#0538ab` (vivid royal blue) | `#477ae4` | Buttons, links, active states, the default `Badge` variant — anywhere the current theme used near-black before |
| `--sidebar` / `bg-sidebar` | `#011f4b` (deep navy) | `#01102b` | The app sidebar background — matches every admin reference image |
| `--sidebar-primary` | `#1548bc` | brighter blue | The active/selected nav item's highlight inside the dark sidebar |
| `--sidebar-accent` | `#17325b` | slightly lighter navy | Sidebar item hover state |

## Status colors (new)

Didn't exist before this WP — the previous theme only had
`primary`/`secondary`/`accent`/`destructive`. Added because the
reference images use distinct colors for "ONGOING," "UPCOMING," and
"LIVE" states that don't map to any existing token.

| Token | Value (light) | Matches reference badge |
|---|---|---|
| `--success` / `bg-success` | `#3a9742` (green) | "ONGOING", "Approved", "PASSED" |
| `--warning` / `bg-warning` | `#e99b2a` (amber) | "UPCOMING", "UP NEXT" |
| `--destructive` (unchanged) | `#e7000b` | "LIVE NOW" — already matched the reference red almost exactly before this WP, left as-is |

Each has a paired `-foreground` token for correctly-contrasting text
(`--success-foreground`, `--warning-foreground`), same pattern as the
existing `--primary-foreground` etc.

## Medal colors (new)

For the rank-1/2/3 medal icons and highlights that appear across the
Dashboard, Medal Tally (admin and public), and mobile ranking pages —
previously these pages rendered gold/silver/bronze as plain text with
no color at all.

| Token | Value (light) |
|---|---|
| `--medal-gold` / `bg-medal-gold` \| `text-medal-gold` | `#edb417` |
| `--medal-silver` | `#abaeb1` |
| `--medal-bronze` | `#ac704e` |

## One existing bug fixed

`--destructive-foreground` was set to the *same value* as
`--destructive` in light mode (`oklch(0.577 0.245 27.325)` for both) —
text in that color would have been invisible against a destructive
background. In dark mode it was a different but still-wrong mid-red
rather than a proper contrast color. Neither ever caused a visible bug
because `Button`'s `destructive` variant hardcodes `text-white` instead
of using the token — but the token itself was wrong. Fixed to
`oklch(0.985 0 0)` (white) in both modes, matching every other
`-foreground` pairing in the file.

## What was deliberately left alone

- `--accent`, `--secondary`, `--muted` stay close to their original
  neutral role (a very subtle cool-blue tint only) rather than being
  repurposed to a brand color. Reason: these three back Radix/shadcn's
  built-in hover/subtle-highlight states used pervasively (every
  dropdown item, select option, command palette row). Making them gold
  or navy would tint *every* incidental hover in the app, which the
  reference images don't show — gold and navy are used sparingly, for
  specific medal/brand moments only, backed by the new dedicated
  tokens above.
- Typography (`--font-sans: 'Instrument Sans'`) and `--radius`
  (`0.625rem`) are unchanged — both already read as a clean, modern
  sans-serif and a consistent rounded-card look consistent with the
  references; no evidenced gap here in WP-08-01's assessment.
- `--chart-1..5` were remapped to the brand+status palette (blue, gold,
  success, warning, destructive) so future donut/bar charts (Medal
  Distribution, Events Overview — both flagged as not-yet-built in
  WP-08-01) have a ready, cohesive 5-color set instead of the old
  generic Tailwind demo chart colors.

## Known pre-existing inconsistency (not fixed here, out of this WP's scope)

A handful of pages already use raw Tailwind palette classes
(`text-red-600`, `bg-blue-100`, etc.) instead of semantic tokens —
found via `grep` in `resources/js/pages/accreditation/`,
`resources/js/pages/auth/`, `resources/js/pages/settings/profile.tsx`,
and a few shared components (`app-header.tsx`, `nav-footer.tsx`,
`user-info.tsx`, `input-error.tsx`, `appearance-tabs.tsx`,
`delete-user.tsx`). None of these are in scope for this token-definition
WP (no reference image covers them) and none were touched — flagging
for whichever later WP does a consistency pass (likely WP-08-13,
"Shared Tables Cards Charts Scoreboards and Filters").

## How these were derived

Sampled programmatically from the 9 approved reference PNGs
(`docs/ui-ux/references/`): downsampled each image, bucketed pixel
colors, filtered for saturation/lightness to isolate real brand hues
from anti-aliasing noise, and converted the most frequent candidates
from sRGB to OKLCH. The dark-navy sidebar and vivid royal-blue
accent converged consistently across every admin and public reference
image (hue ≈ 258–264 across all samples); gold (hue ≈ 78–90) and
green (hue ≈ 144–148) were similarly consistent across the medal-tally
and eligibility-checker references. Verified round-trip (OKLCH → sRGB)
for every new token to confirm no out-of-gamut clipping before writing
them into `app.css`.
