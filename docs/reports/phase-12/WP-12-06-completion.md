# WP-12-06 — Completion Report

Performance and Visibility-Aware Polling. Status: **done**.

## Repository findings

Confirmed every existing poll in this app (`tally.tsx`'s kiosk-mode
`usePoll`, `scoreboard.tsx`'s raw `setInterval`+`fetch`) runs
continuously with no pause-on-hidden-tab behavior — re-read both files
in full rather than assuming, per this WP's own rule to build a
reusable primitive "rather than a one-off for this phase alone."

Inspected Inertia's own `usePoll` hook source
(`node_modules/@inertiajs/react/dist/index.js`) to check whether its
returned `stop`/`start` functions were safe to call dynamically from a
`visibilitychange` listener: confirmed the underlying `router.poll()`
instance is created exactly once (`useEffect(() => {...}, [])`, empty
deps), so `stop`/`start` are safe, idempotent delegates to that one
instance regardless of how many times they're called — but their
*returned function references* are new on every render (not
memoized), which would make them awkward `useEffect` dependencies.
Chose instead to keep a single consistent pattern already proven
elsewhere in this phase (`SportPortalLiveNowCard`'s own raw
`setInterval`): a plain interval gated by a new `visible` boolean,
where the effect's own cleanup naturally clears the timer whenever
`visible` flips — no separate stop/start bookkeeping needed, and no
new dependency on `usePoll`'s specific control-function shape.

**Confirmed no frontend-unit-testing infrastructure exists in this
project at all** (`grep` of `package.json` for `vitest`/`jest`/
`testing-library`: zero matches) — this WP's own acceptance criterion
("verifiable via a test simulating visibilitychange") cannot be
satisfied with an automated test without adding a new dependency,
which this WP's own rules explicitly forbid. Resolved by verifying
correctness through direct source review instead, documented plainly
rather than silently skipped.

## Implementation

- `resources/js/hooks/use-page-visible.ts` — new, reusable
  `usePageVisible()` hook: a `document.visibilitychange` listener
  toggling a boolean, `useState`-initialized from the real current
  `document.visibilityState` (not just defaulting to `true`, so an
  already-hidden tab never gets a spurious first poll).
- `resources/js/components/sport-portal-live-now.tsx` — its existing
  7s poll effect now includes `visible` in its dependency array and
  bails out early when `false`; the effect's own cleanup (already
  present) does the actual pausing when `visible` flips to `false`, and
  a fresh interval starts again when it flips back to `true`. No other
  behavior in this component changed.
- `resources/js/pages/public/sport-portal.tsx` — new 45s background
  refresh (`BACKGROUND_REFRESH_INTERVAL_MS`) via `router.reload({
  only: ['todayGames', 'completedGames', 'upcomingGames', 'venues'] })`,
  gated by the same `usePageVisible()` hook and an additional `meet ===
  null` guard (no point polling a page with no active meet at all).
  One shared interval for all four props rather than four separate
  timers — simpler than the brief's own fully-granular per-section
  table (Today's Games 30-60s vs. Completed/Upcoming/Venues 60s-5min)
  without meaningfully hurting freshness for the slower-changing
  sections.
- No new dependency (Inertia's own `router.reload`, already used
  elsewhere in this app, is the only mechanism), no new migration.

## Tests

No new Pest tests — this WP's own behavior (browser tab visibility,
polling timers) is entirely frontend and has no backend prop to assert
on via an Inertia Feature test, the same category of limitation
WP-11-08 already hit for `topNavItems`. Re-ran the full existing suite
to confirm zero regression from touching two already-tested frontend
files.

## Quality gate

- Pest: **768/768** passed, 4,624 assertions — unchanged from
  WP-12-05's baseline (no backend touched, no new test possible for
  this WP's own frontend-only, untestable-in-this-project behavior).
- Pint: clean, no changes needed (no PHP touched this WP).
- PHPStan (level 7): 0 errors.
- ESLint (`--fix`): 0 issues, no files changed.
- `tsc --noEmit`: clean.
- Prettier: only the same 2 pre-existing, unrelated drifted registry
  files — this WP's own changes needed no reformatting.
- `npm run build`: clean.

## Documentation

- `docs/public-portal.md` — new "Visibility-aware polling added
  (WP-12-06)" paragraph, including the honest test-infrastructure
  limitation.
- `docs/phases/phase-12-lightweight-sport-mini-portals/CHECKLIST.md` —
  WP-12-06 checked off.

## Remaining issues

None found that require further work within this WP's own scope. The
lack of frontend-unit-testing infrastructure is a standing, project-
wide gap (not introduced by this WP) — worth flagging for whoever picks
up test-coverage improvements generally, but adding a framework
unilaterally to close one WP's own acceptance criterion would violate
this same WP's "no new dependency" rule, so it wasn't done.

## Git status

`git diff --stat` against `app`/`routes` is unchanged from WP-12-05
(0 new lines) — confirming this WP touched only frontend files:
one new hook (`use-page-visible.ts`) and edits to two already-untracked
files from earlier WPs (`sport-portal-live-now.tsx`, `sport-portal.tsx`).
No migration, no dependency. Not committed, per rule.

Next: **WP-12-07 — Accessibility and Loading/Error-State Review**,
awaiting owner instruction.
