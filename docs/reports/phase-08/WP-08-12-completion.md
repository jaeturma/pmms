# WP-08-12 — Softball and Baseball Live Scoreboard UI

**Status:** Complete 2026-07-27. Per this WP's own rules, WP-08-13 has
not been started.

## Scoping approach (applying an already-established owner decision)

This WP's reference (`desktop-softball-live-score.png`, correctly
listed) shows the same shape of gap WP-08-10 found for basketball, at a
similar scale: a "Team Comparison" panel (hits, errors, walks,
strikeouts, stolen bases, batting average, slugging %), per-player "Top
Performers" with photos, and a "Current Pitcher" panel with per-player
pitching stats — none of which exist. The reference's baserunner diamond
graphic isn't real either — `sport_state` for softball/baseball has
never tracked a baserunner model at all (a deliberate omission WP-07-06
already documented: "a walk — no baserunner model, so no run
auto-added").

The owner had already answered this exact structural question twice —
WP-08-10 (basketball) and WP-08-11 (athletics) both got "restyle/build
with real data only, don't fake what isn't tracked." This WP is the same
shape as WP-08-10's specifically: a real `sport_state` core already
exists (inning/half/outs/balls/strikes/line-score, WP-07-06), and the
reference wants additional per-player/team-comparison data on top that
doesn't exist. Given the pattern was already established twice, this WP
applied that same answer directly rather than asking a third time for a
structurally identical situation — verified this was the right read by
actually checking the reference and the real data model first (per
WP-08-11's own closing note to do exactly that), not by assuming.

## What was found and built

**A proper line-score table** (`SoftballLineScore`, new in
`live-score-display.tsx`) — innings as columns, R as the final column,
from the real `sport_state.innings` breakdown, replacing the previous
flat "Inn 1: 2-0, Inn 2: 0-1" list. Deliberately **not** a fixed 7- or
9-inning grid the way the reference shows: this app doesn't track a
configured game length anywhere, so only innings that have actually
happened get a column — a fixed-length grid with "-" placeholders for
unplayed innings would itself be a small fabrication.

**Balls/strikes/outs as colored dot rows** instead of "Count 1-2 · 1
Out" text — a new generic `CountDots` component (basketball's
`FoulDots` generalized to take `max`/`colorClass`, since this is its
second real use, extracted rather than duplicated). Real caps matching
the app's own already-enforced business rules (WP-07-06), not decorative
round numbers: 3 balls (4th is a walk, auto-resets to 0), 2 strikes (3rd
is itself an out, auto-resets), 2 outs (3rd flips the half-inning,
auto-resets).

**Real play-by-play descriptions for softball's own event types** —
`ScoringSession::describeEvent()` (built in WP-08-10) only handled the
generic/basketball event types before this WP; `Count` and `InningRun`
fell through to a bare type-label fallback ("Count", "Inning run") with
no detail. Now formats them from their real payload: `InningRun` as "+N
run(s) — {side} (Inning M)"; `Count` per action as "Ball (B-S)" /
"Strike (B-S)" / "Out (N outs this half)" / "Count reset". Deliberately
does not infer derived events like "walk" or "strikeout" — the `count()`
endpoint's payload has no batter/side field at all, so inferring one
from "the count reset to 0" would be a guess, not a recorded fact.

**Found and fixed a real bug while extending this**: `playByPlay()`'s
running-score reconstruction (added in WP-08-10) only replayed
`point`/`correction` deltas — it silently ignored `InningRun` (softball
runs) and `RoundScore` (boxing rounds) entirely, so a softball or boxing
play-by-play's displayed running score would have stayed frozen at 0-0
the whole game. Caught by a failing test while writing this WP's own
test coverage, not by inspection — a genuinely useful catch. Not a
regression against anything shipped (`playByPlay()` itself is
uncommitted WP-08-10 work), but worth recording since it would have
shipped broken for two sports otherwise. Fixed by applying `InningRun`'s
single-side runs and `RoundScore`'s simultaneous both-side deltas during
replay; gave `RoundScore` a real description too ("Round N: {side A} X –
Y {side B}") while already in that method, closing boxing's last
remaining generic-fallback gap as a side effect.

Match/scoreboard-page header metadata (sport/category/venue/scheduled
date) and the disconnected-polling indicator were both already built
generically in WP-08-10 (never gated to basketball specifically), so
softball/baseball sessions already had them for free — reconfirmed via
the test suite, not rebuilt.

## What was deliberately NOT done

- **No hits (H) or errors (E) tracking** — not tracked anywhere; the
  line-score table shows only R (runs), the one stat that's real.
- **No team comparison panel** (walks, strikeouts, stolen bases, batting
  average, slugging %) — none of this is tracked.
- **No per-player "Top Performers" or "Current Pitcher" panels** — no
  per-athlete attribution exists in scoring at all, same structural gap
  WP-08-10 found for basketball.
- **No baserunner/bases display** — no baserunner model exists; this is
  a real, pre-existing, documented gap (WP-07-06), not something this WP
  introduced or could restyle around.
- **No walk/strikeout inference in the play-by-play** — see above; only
  literal recorded facts are described.

## Verification

- `npx tsc --noEmit` — 0 errors.
- Full quality gate green (below).
- **Could not get a live visual screenshot** — Claude in Chrome
  extension still disconnected this session.
- **Could not get a live HTTP check against http://pmms.app** — same
  unresolved Apache vhost-routing issue noted since WP-08-05; status
  unchanged, still not treated as a blocker.

## Test results

`vendor/bin/pest` — **695/695 passing**, 3,640 assertions (2 new tests:
the play-by-play feed describes inning-run and count events correctly
from real payload data, newest-first, with correctly reconstructed
running scores after the `InningRun` fix; a second test proves the same
fix for boxing's `RoundScore` running-score reconstruction, which had
the identical latent bug).

## Quality results

| Check | Result |
|---|---|
| `vendor/bin/pint --test` | Passed |
| `vendor/bin/phpstan analyse` (level 7) | Passed, 0 errors |
| `vendor/bin/pest` | Passed, 695/695, 3,640 assertions |
| `npx eslint .` | Passed, 0 errors |
| `npm run format:check` | Passed |
| `npx tsc --noEmit` | Passed, 0 errors |
| `npm run build` | Passed |

## Files modified

- `app/Models/ScoringSession.php` — `InningRun`/`RoundScore` running-
  score reconstruction fix; `Count`/`InningRun`/`RoundScore` real
  descriptions in `describeEvent()`
- `resources/js/components/live-score-display.tsx` — `CountDots`
  (generalized from `FoulDots`), `SoftballLineScore`, restyled balls/
  strikes/outs
- `tests/Feature/ScoringSessionTest.php` — 2 new tests
- `docs/live-scoring.md` — "Softball/baseball scoreboard visual
  alignment (WP-08-12)" section recording the approach and the bug fix
- `docs/phases/phase-08-ui-ux-visual-alignment/CHECKLIST.md` — WP-08-12
  checked off

## Remaining issues

- Chrome extension still unavailable — recommend a real visual check
  before WP-08-13.
- The pmms.app Apache vhost routing issue (noted since WP-08-05) is
  still unresolved.

## Next

WP-08-13 — Shared Tables, Cards, Charts, Scoreboards, and Filters, on
owner instruction (per this WP's own rule: do not begin the next work
package). This WP's own title suggests it may be a consolidation/audit
pass over components already built across WP-08-04 through WP-08-12
(`StatCard`, `MedalDistributionCard`, `TopByPointsCard`,
`MedalsBySportCard`, `RankBadge`, `CountDots`, `SoftballLineScore`,
etc.) rather than new scoreboard work — worth confirming its actual
scope against its reference images before assuming, same discipline
every WP this phase has needed.
