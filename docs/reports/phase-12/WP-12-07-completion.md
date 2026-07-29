# WP-12-07 — Completion Report

Accessibility and Loading/Error-State Review. Status: **done**.

## Repository findings

Full sweep across all 4 sport-portal components (`sport-portal-game-
list.tsx`, `sport-portal-live-now.tsx`, `sport-portal-unavailable.tsx`,
`sport-portal-venue-info.tsx`) plus `sport-portal.tsx` itself:

- **Icons**: every icon usage (`CalendarDays`/`MapPin` in the game
  list, `Radio` in Live Now, `MapPin`/`Navigation` in venue info,
  `Medal`/`Network`/`Trophy` on the page) is either passed through
  `EmptyState` (whose own wrapper `aria-hidden="true"` already covers
  it — confirmed by reading the component, not assumed) or carries its
  own `aria-hidden="true"` directly — no new icon has been added since
  WP-12-03's original sweep, and none was missed.
- **Headings**: `PublicPageHero` renders the page's one `<h1>`; every
  section uses the shared `Heading` component's `<h2>` — unchanged, no
  skipped levels anywhere.
- **Landmarks**: every sport-portal page renders inside `PublicLayout`'s
  existing `<main>`, same as every other public page — nothing new
  needed per-page.
- **Focus/touch targets**: no custom-sized interactive element anywhere
  in this phase — every button/link reuses `Button`/`EmptyState`'s
  action slot/plain anchors already styled and previously measured; the
  venue "Directions" link matches the same inline-link sizing already
  used on the Contact page.
- **Reduced motion**: `animate-card-in` (the page's only motion class)
  is covered by the existing global `prefers-reduced-motion` reset, same
  as every other public page.
- **Color**: `git diff main --stat -- resources/css/app.css` is empty —
  confirmed zero color/contrast-relevant token changes anywhere across
  the whole phase (WP-12-02 through this WP), not assumed.

## Loading/empty/error-state verification

- Every game-list section (Today's/Completed/Upcoming) already has a
  real, sport-term-aware `EmptyState` (WP-12-04's terminology work).
- Standings/Leading Scorers/Bracket already show the honest
  `SportPortalUnavailable` state (WP-12-02/05).
- Live Now's existing `disconnected` banner (`pollFailures >= 2` fed
  into `LiveScoreDisplay`, the same established pattern
  `scoreboard.tsx` already proved) **is** this section's error state —
  auto-retrying every poll, no separate manual "retry" button needed,
  consistent with the one existing precedent for this exact situation.
- **Verified rather than assumed**: read `@inertiajs/core`'s actual
  compiled source to confirm `router.reload()`'s `onError` defaults to
  a no-op when not supplied (`options.onError || noop`) — the new
  WP-12-06 background game-list refresh therefore already fails
  silently and non-blockingly on its own, with no code change needed to
  satisfy "every section fails independently."

## One real fix considered and declined

Investigated wrapping the Live Now card's live/no-live swap in an
`aria-live` region so a screen reader announces the transition (e.g.,
a match starting or ending mid-poll). Found `LiveScoreDisplay` already
owns its own tightly-scoped `aria-live="polite" aria-atomic="true"`
region internally, and confirmed `scoreboard.tsx` — this codebase's
one existing precedent for exactly this same live/no-live swap — does
**not** wrap it in an additional outer live region either. Wrapping it
here would risk a genuine regression: a live region with
`aria-atomic="true"` re-announces its **entire** contents on every
internal mutation, so nesting one around `LiveScoreDisplay`'s own
already-`aria-live` score card would make every few-second score tick
re-read the whole card aloud — far noisier than the existing, correct,
internally-scoped announcement alone. Declined in favor of staying
consistent with the proven precedent; the change was made, tested for
this exact risk by re-reading `scoreboard.tsx`, and reverted rather
than shipped speculatively.

## Tests

No new tests — this WP made no net code change (the one candidate fix
was implemented, evaluated, and reverted within the same session,
confirmed via `git status`/`git diff` showing zero unintended residue).
Re-ran the full suite to confirm no regression from the edit-then-
revert cycle.

## Quality gate

- Pest: **768/768** passed, 4,624 assertions — unchanged from
  WP-12-06's baseline (no functional change this WP).
- Pint: clean, no changes needed.
- PHPStan (level 7): 0 errors.
- ESLint (`--fix`): 0 issues, no files changed.
- `tsc --noEmit`: clean.
- Prettier: only the same 2 pre-existing, unrelated drifted registry
  files.
- `npm run build`: clean.

## Documentation

- `docs/public-portal.md` — new "Accessibility and loading/error-state
  sweep (WP-12-07)" paragraph, including the considered-and-declined
  `aria-live` finding.
- `docs/phases/phase-12-lightweight-sport-mini-portals/CHECKLIST.md` —
  WP-12-07 checked off.

## Remaining issues

None found requiring a code change. The Chrome extension remains
unavailable this session (checked again) — every accessibility claim
above rests on source-level inspection (component structure, existing
established precedents) and the passing test suite, stated plainly
rather than overclaimed as verified with a real screen reader or at
real viewport widths.

## Git status

`git status`/`git diff --stat` confirm zero net change from this WP:
`app`/`routes` are byte-identical to WP-12-05/06's own reports; the one
frontend edit made during this WP's investigation (the `aria-live`
wrapper) was reverted before finishing, leaving `sport-portal-live-
now.tsx` identical to its WP-12-06 state. No migration, no dependency.
Not committed, per rule.

Next: **WP-12-08 — SEO Metadata and Required Documentation**, awaiting
owner instruction.
