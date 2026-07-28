# WP-10-08 — Completion Report

Motion and Interaction Elevation Pass. Status: **done**.

## Repository findings

Read `resources/css/app.css`'s full motion-token section (Phase 8.5,
WP-08.5-02/06) first: `--ease-premium`, `--duration-fast|base|slow`,
and the composite `--animate-card-in`/`--animate-score-pop`/
`--animate-winner-in`/`--animate-pulse-live` tokens, plus the existing
global `prefers-reduced-motion` reset. Confirmed none of the tokens had
ever been used as *bare* transition utilities before this WP — every
prior consumer only used the composite `--animate-*` tokens.

**Real finding, caught by verifying compiled CSS rather than trusting
the class name would work**: built the app after writing
`duration-base` as a plain Tailwind utility class and grepped the
compiled CSS — `ease-premium` compiled correctly
(`.ease-premium{transition-timing-function:var(--ease-premium)}`), but
`duration-base` compiled to **nothing at all**. Tailwind v4 generates a
named utility from a custom `--ease-<name>` theme key, but does not do
the same for a custom `--duration-<name>` key — `duration-*` already
has an extensive built-in numeric scale, and a named theme key doesn't
hook into that utility generator the same way `ease-*`'s open-ended
namespace does. Had this shipped as originally written, every
"transition" this WP added would have been silently instant
(`transition-duration` defaulting to its initial `0s`) — motion that
looks and behaves identically to no motion, a much worse outcome than
an honestly-skipped feature, since it would have looked correct in the
source but done nothing in the browser. **Fixed** by switching every
occurrence to Tailwind v4's arbitrary-custom-property syntax,
`duration-(--duration-base)`, and re-verified in the rebuilt CSS:
`.duration-\(--duration-base\){transition-duration:
var(--duration-base)}` — confirmed present this time. This syntax also
stays in sync with the token automatically if `--duration-base`'s value
is ever changed later, unlike hardcoding the equivalent `duration-250`.

## Files modified

- `resources/js/layouts/public-layout.tsx`:
  - New `scrolled` state + a passive `scroll` event listener
    (`window.scrollY > 8`), applied only via `sm:shadow-md` — matching
    where the header is actually `sm:sticky` (WP-10-02); below `sm:`
    nothing changed.
  - Header gained `transition-shadow duration-(--duration-base)
    ease-premium` so the shadow fades in/out rather than popping.
  - Header nav `Button`s gained `duration-(--duration-base)
    ease-premium` in their own `className` — a page-scoped addition,
    the shared `Button` primitive itself untouched.
- `resources/js/pages/public/meet.tsx` — hover-lift on the schedule's
  per-venue cards.
- `resources/js/pages/public/results.tsx` — hover-lift on the per-event
  cards.
- `resources/js/pages/public/sports.tsx` — hover-lift on the sport
  cards.
- `resources/js/pages/public/home.tsx` — the municipality cards' prior
  bare `transition hover:shadow-md` (an untokened value predating this
  phase) upgraded to the same token-based hover-lift treatment, for
  consistency rather than leaving the one inconsistent card style on
  the portal.
- `resources/js/components/public-announcements.tsx` — hover-lift on
  the shared `<li>`, which necessarily affects both its consumers
  (`news.tsx`, this WP's own named target, and `home.tsx`'s
  announcement preview) — confirmed intentional, not an oversight.
- `docs/ui-ux/premium-design-system.md` — new "WP-10-08" section.
- `docs/phases/phase-10-premium-portal-redesign/CHECKLIST.md` — checked
  off.

All hover-lift instances use the identical class string
(`transition-[transform,box-shadow] duration-(--duration-base)
ease-premium hover:-translate-y-0.5 hover:shadow-md`) — one consistent
treatment across every card surface touched, not five slightly
different ones.

## Motion added

1. Shadow-on-scroll on the sticky public header (`sm:` and above only).
2. A subtle lift + shadow on hover for schedule/results/sports cards
   and the shared announcement list item (news + home preview).
3. A smooth hover-color fade on the header nav's links, replacing an
   instant snap.

No new `@keyframes` — every effect above is a `transition` utility, per
this WP's own exclusion against new animations without a genuine gap
(none was found).

## Reduced-motion verification

Checked by inspection, not assumed, per this WP's own rule: every class
added is either a `transition-*` utility or reads `transition-duration`
via `--duration-base`. `resources/css/app.css`'s existing global reset
sets `transition-duration: 0.01ms !important` on `*`, `::before`,
`::after` under `prefers-reduced-motion: reduce` — `!important` always
wins over a component-level `duration-(--duration-base)` class, so
every transition added this WP collapses to effectively instant
automatically, with zero per-component reduced-motion code needed. The
hover/scroll *end states* (shadow, lift, color) still apply — only the
animated transition between them is suppressed, which is the correct
reduced-motion behavior (remove motion, not remove the feature).

## Accessibility

No new colors, no new interactive elements, no change to focus order
or `aria-*` attributes anywhere. The hover-lift is a pure visual
affordance layered onto elements that were already interactive
(cards containing real links/buttons) — it doesn't itself add or
remove any interactive target.

## Tests

No test changes needed and none made — this WP is pure CSS/motion, with
zero data, prop, or route surface changed. Full suite reran to confirm
zero regressions.

## Quality gate

- Pint: **PASS** (no PHP touched this WP)
- PHPStan L7: **PASS** (0 errors)
- Pest: **PASS** — 714/714, 3,878 assertions (unchanged from WP-10-07)
- ESLint: **PASS** · `tsc --noEmit`: **PASS**
- `prettier --check` on every changed file: **PASS**
- `npm run build`: **PASS** — run twice: once to catch the
  `duration-base` compilation gap by inspecting the output CSS, once
  more after the fix to confirm the corrected utility actually compiles.

## Remaining issues

None blocking. Standing, previously-flagged gaps not addressed here
(out of this WP's scope): Chrome-extension live verification still
unavailable — this WP's motion effects in particular would benefit
from an eventual live look, flagged more pointedly than the routine
note since hover/scroll behavior is exactly the kind of thing static
source inspection can't fully confirm looks right; the `TeamLogo`
contrast finding remains queued for the closing WP-10-11.

## Documentation

- `docs/ui-ux/premium-design-system.md` — new "WP-10-08" section,
  including the `duration-base` finding in full.
- This completion report.
- Checklist updated.

## Git status

Working tree carries this WP's changes plus Phase 10's own accumulated
scaffold and WP-10-01 through WP-10-07 changes. **No commit, no push**
— per this WP's explicit rule and the standing project rule.

## Next work package

```text
WP-10-09 — Admin Shared-Component Visual Polish Pass
```

Not started — awaiting instruction to proceed.
