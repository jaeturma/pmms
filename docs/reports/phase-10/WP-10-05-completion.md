# WP-10-05 — Completion Report

Live Scoreboard and Countdown Composition Refinement. Status: **done**.

## Repository findings

Read `live-score-display.tsx` in full first. Found the shared board
(`TeamPanel`/`CenterPanel`, the `rounded-2xl border-2` shell, the
gradient top strip, `.text-score`/`.text-clock` typography) was already
largely solved by Phase 8.5 — there was no genuine gap left in the
board's own internals to close, consistent with this WP's own Visual
Direction note ("already solved... this WP is board/card composition
and framing, not new typography or colors").

The real, genuine gap was one level up: `OpeningCountdown` — the card
shown before a live session starts — used a plain `rounded-2xl
border-2 bg-card` shell with no gradient accent, while the board it
hands off to the moment scoring starts uses that exact shell *plus* a
`h-1.5` gradient top strip. The two cards a visitor sees in immediate
sequence (countdown → live board) didn't visually match.

Grepped both shared components' consumers before touching anything:
`OpeningCountdown` has exactly one consumer (`public/scoreboard.tsx`,
both its kiosk and non-kiosk branches); `LiveScoreDisplay` has exactly
two (`public/scoreboard.tsx`, `scoring/show.tsx` — the operator
console), confirming this WP's own stated fact rather than assuming it.

## Files modified

- `resources/js/components/opening-countdown.tsx` — restructured to use
  the identical "boxed board" shell `LiveScoreDisplay` uses:
  `overflow-hidden rounded-2xl border-2 bg-card shadow-sm` wrapping a
  `h-1.5 bg-gradient-to-r from-sidebar to-primary` top strip, then the
  original content in an inner padded div. The clock typography
  (`.text-clock text-5xl sm:text-7xl`) and every icon/text element
  inside are byte-for-byte unchanged — only the outer container
  structure changed, per this WP's own exclusion against new score/
  clock styles.
- `resources/js/pages/public/scoreboard.tsx` — the non-fullscreen board
  wrapper's gap widened at `sm:` and above (mobile unchanged): `gap-4`
  → `gap-4 sm:gap-6`, giving more breathing room between the board and
  the sport-specific breakdown blocks (boxing round-by-round, softball
  line score, play-by-play) beneath it.
- `docs/ui-ux/premium-design-system.md` — new "WP-10-05" section.
- `docs/phases/phase-10-premium-portal-redesign/CHECKLIST.md` — checked
  off.

**Deliberately left unchanged**, per this WP's own explicit rules:
- `resources/js/components/live-score-display.tsx` — not modified at
  all. The board's own internals were already solved; no genuine gap
  was found there.
- `resources/js/pages/scoring/show.tsx` (the operator console) — not
  modified. Its own `gap-4` wrapper around `LiveScoreDisplay` stays as
  it is; the rule explicitly says the operator console must not
  visually drift toward the public portal's more spacious look, so it
  deliberately does not receive the same `sm:gap-6` treatment as the
  public page.
- `public/scoreboard.tsx`'s fullscreen branch (`gap-8`) and kiosk
  branch (also `gap-8`) — untouched. The rule says preserve full-screen
  mode exactly as it works today, and kiosk mode's spacing was already
  generous.

## Visual / frontend changes

The pre-match countdown card and the live scoreboard it's replaced by
now read as one consistent "premium broadcast board" family — same
shell, same gradient accent — instead of two differently-styled cards
in the same visual sequence. The public (non-kiosk, non-fullscreen)
scoreboard page has more breathing room between the board and its
sport-specific breakdown sections on larger screens.

## Reusable components

No new component. `OpeningCountdown` now reuses the exact gradient
class `LiveScoreDisplay`'s own top strip already used — no new color or
gradient definition anywhere.

## Large-display behavior

Kiosk mode (`?kiosk=1`) renders `OpeningCountdown` unchanged in
behavior — its call site in `scoreboard.tsx`'s kiosk branch wasn't
touched, and the component's own props/logic are identical; only its
internal container markup changed, so it displays the same real
`scheduled_start_at`-driven countdown as before, just inside the
matching gradient-topped shell. Auto-refresh, connection-status
messaging, and full-screen sizing in kiosk mode are all untouched
(Exclusions: no change to kiosk-mode data scoping or the polling/
disconnected mechanism).

## Accessibility

No new colors, and the gradient top strip is a decorative accent bar
with no text on it (same as the identical strip already used on the
live board, previously measured as decorative-only in earlier phases).
No change to any `aria-*` attribute, label, or live-region behavior in
either file. No new animation/transition, so no reduced-motion concern
applies here.

## Tests

No test changes needed and none made — neither file's props, data, or
route surface changed; grepped `tests/` and confirmed no test
references either component or page's class names. Full suite reran
after the build completed (an earlier concurrent-build/test run
produced a single flaky `ViteException` failure from a mid-write asset
manifest — a known race, not a code regression; a clean sequential
rerun passed 703/703) to confirm zero real regressions.

## Quality gate

- Pint: **PASS** (no PHP touched this WP)
- PHPStan L7: **PASS** (0 errors)
- Pest: **PASS** — 703/703, 3,720 assertions (unchanged from WP-10-04)
- ESLint: **PASS** · `tsc --noEmit`: **PASS**
- `prettier --check` on both changed frontend files: **PASS**
- `npm run build`: **PASS**

## Remaining issues

None blocking. Standing, previously-flagged gaps not addressed here
(out of this WP's scope): Chrome-extension live verification still
unavailable; the `TeamLogo` contrast finding remains queued for the
closing WP-10-11; medal tally layout is WP-10-06's scope.

## Documentation

- `docs/ui-ux/premium-design-system.md` — new "WP-10-05" section.
- This completion report.
- Checklist updated.

## Git status

Working tree carries this WP's changes plus Phase 10's own accumulated
scaffold and WP-10-01 through WP-10-04 changes. **No commit, no push**
— per this WP's explicit rule and the standing project rule.

## Next work package

```text
WP-10-06 — Medal Tally Layout Refinement
```

Not started — awaiting instruction to proceed.
