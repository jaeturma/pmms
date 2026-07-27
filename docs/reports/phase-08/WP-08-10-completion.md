# WP-08-10 — Basketball Live Scoreboard UI

**Status:** Complete 2026-07-27. Per this WP's own rules, WP-08-11 has
not been started.

## Scoping decision (owner-directed)

This WP's references (`desktop-basketball-live-score.png` and
`mobile-basketball-live-score.png` — both correctly listed this time, a
rare exception in this phase) show a far richer scoreboard than what's
tracked: a running game clock, a 24-second shot clock, timeouts, a
quarter-by-quarter score breakdown, a play-by-play feed with player
names/jersey numbers, full team shooting/rebounding/assist stats, and
per-player "top performers" with photos.

Checked what's real before writing any code: `sport_state` for
basketball is still just `{fouls_a, fouls_b}` (WP-07-04). There is no
game clock, shot clock, or timeout tracking anywhere, and — the
critical gap — **no scoring event anywhere in this app records which
athlete did anything**; a point is only ever attributed to a side
(`a`/`b`), never a player. Per-player stats and "top performers" are
therefore structurally impossible without a new feature, not a restyle.

Presented the owner three options before writing code: (1) restyle with
real data only — score, fouls/bonus, period label, plus a genuine
play-by-play feed built from the existing `score_events` audit log; (2)
restyle plus two cheap new trackers matching the existing foul-tracking
pattern (timeouts, an operator-set clock display value); (3) a full
build — real per-player attribution, functioning clocks, derived box
score — a substantial new feature, not a restyle. **The owner chose
option 1.** Everything below reflects that; no clock, shot clock,
timeouts, team stats, box score, or top performers were built.

## What was found and built

**A genuine play-by-play feed** — new `ScoringSession::playByPlay()`
(and private `describeEvent()`), reconstructed from the existing
append-only `score_events` log. Included in `toLivePayload()`, so every
board type gets it for free, not just basketball. Since a single
`score_events` row only records the *one* side it changed, both running
scores at each point in the feed are reconstructed by replaying every
`point`/`correction` event in order with the same `max(0, ...)` floor
`ScoringSessionController::score()` itself applies — a real
reconstruction from real data, not a stored value. Capped at the 30 most
recent events, newest first. `LiveScoreDisplay` (the presentational
component already shared by the operator console and the public
scoreboard, WP-07-02/08) shows the first 8 with a "View full play by
play" expand button — the same collapse pattern WP-08-09 established for
the mobile ranking table.

**Fouls rendered as dots** (`FoulDots`) instead of a bare number — the
same real `fouls_a`/`fouls_b` count, just matching the reference's
visual language, capped/filled at `BASKETBALL_BONUS_THRESHOLD`.

**Real match metadata** added to both `scoring/show.tsx` (internal) and
`public/scoreboard.tsx` (public) page headers: sport name, gender+age-
division category, and — new — venue and scheduled date, sourced from
the match's own `EventSchedule`/`Venue` relations (both controllers now
eager-load `schedule.venue`). A breadcrumb-style header
("Basketball › Boys Secondary › Elimination Round") plus a "Live now"
badge replaces the plain title line, matching the reference's header
treatment. All real, existing fields — no new columns.

**A "disconnected" indicator** — both live-scoring pages already polled
every 5 seconds (WP-07-01) but silently retried failures with no visible
signal to the viewer. After 2 consecutive poll failures,
`LiveScoreDisplay` now shows a "Connection lost — retrying
automatically" banner; any successful poll or Echo push clears it. This
closes a real, pre-existing gap against this phase's own stated rule
("support ... disconnected ... states") rather than being new scope
invented for this WP.

**Reverb updates, the 5-second polling baseline, and the provisional-
score badge** were all already real and functioning (WP-07-01/02/08) —
re-verified via the existing/extended test suite, not rebuilt.

## What was deliberately NOT done

- **No game clock or shot clock** — no real timer state exists anywhere;
  a display-only countdown with no server sync would drift across
  viewers and imply functionality that doesn't exist.
- **No timeout tracking** — no real state exists (unlike fouls, which
  WP-07-04 already built the same way).
- **No team shooting/rebounding/assist/turnover/steal/block stats, no
  box score tab, no "top performers," no player photos, no videos tab**
  — none of this data is tracked; building it would mean adding
  per-athlete attribution to every scoring action, a real feature change
  to the scoring UX, not a restyle.
- **No changes to boxing or softball/baseball scoreboards** — this WP is
  scoped to basketball specifically, per its reference images (their own
  WPs, if any, are separate).

## Verification

- `npx tsc --noEmit` — 0 errors.
- Full quality gate green (below).
- **Could not get a live visual screenshot** — Claude in Chrome
  extension still disconnected this session.
- **Could not get a live HTTP check against http://pmms.app** — same
  unresolved Apache vhost-routing issue noted since WP-08-05; status
  unchanged, still not treated as a blocker.

## Test results

`vendor/bin/pest` — **687/687 passing**, 3,552 assertions (3 new tests:
`ScoringSessionTest` — the live payload includes a play-by-play feed
correctly reconstructed from a point, a foul, and another point,
newest-first with correct per-row running scores; the internal
scoreboard page exposes sport/category/round metadata; `PublicScoreboardTest`
— the public scoreboard exposes the same real metadata for guests).

## Quality results

| Check | Result |
|---|---|
| `vendor/bin/pint --test` | Passed |
| `vendor/bin/phpstan analyse` (level 7) | Passed, 0 errors |
| `vendor/bin/pest` | Passed, 687/687, 3,552 assertions |
| `npx eslint .` | Passed, 0 errors |
| `npm run format:check` | Passed |
| `npx tsc --noEmit` | Passed, 0 errors |
| `npm run build` | Passed |

## Files modified

- `app/Models/ScoringSession.php` — `playByPlay()`, `describeEvent()`,
  included in `toLivePayload()`
- `app/Http/Controllers/ScoringSessionController.php` — real match
  metadata (sport/category/venue/scheduled_date) on `board()`
- `app/Http/Controllers/PortalController.php` — same metadata on
  `scoreboard()`
- `resources/js/components/live-score-display.tsx` — play-by-play
  feed rendering with expand, `FoulDots`, disconnected banner
- `resources/js/pages/scoring/show.tsx` — breadcrumb header, poll-
  failure tracking wired to `disconnected`
- `resources/js/pages/public/scoreboard.tsx` — same breadcrumb header
  and poll-failure tracking
- `tests/Feature/ScoringSessionTest.php` — 2 new tests
- `tests/Feature/PublicScoreboardTest.php` — 1 new test
- `docs/live-scoring.md` — "Basketball scoreboard visual alignment
  (WP-08-10)" section recording the scoping decision and what was/
  wasn't built
- `docs/phases/phase-08-ui-ux-visual-alignment/CHECKLIST.md` — WP-08-10
  checked off

## Remaining issues

- Chrome extension still unavailable — recommend a real visual check
  before WP-08-11.
- The pmms.app Apache vhost routing issue (noted since WP-08-05) is
  still unresolved.

## Next

WP-08-11 — Athletics Live Event UI, on owner instruction (per this WP's
own rule: do not begin the next work package). Note for whoever picks
this up: WP-08-01 flagged that **Athletics has no existing live-
scoreboard foundation at all** — `ScoreboardType` has no Athletics case,
so that reference needs new backend modeling before any UI work, not a
restyle — likely needs the same kind of owner scoping decision this WP
and WP-08-06 both needed, probably at an even earlier stage (whether to
build Athletics live scoring at all, not just how to style it).
