# Live Scoring (Phase 7, WP-07-01..08)

Optional, provisional live scoring for a match in progress. **Never creates,
updates, or implies an `EventResult`/`ResultPlacement`** — the only path to
an official result is still Phase 3's encode→validate flow
(`docs/results.md`), completely untouched by this feature. Live scoring is
a spectator/operations layer on top of `App\Models\EventMatch`; ending a
session does not finalize anything, an Organizer still encodes the result
separately, same as if no live session had ever existed.

## First new dependency

Every phase through Phase 5 shipped with **zero dependencies added**. This
feature deliberately breaks that streak: `laravel/reverb` (WebSocket
broadcasting) plus `laravel-echo`/`pusher-js`/`@laravel/echo-react` on the
frontend, added per explicit owner approval 2026-07-25. To contain the
risk, Reverb is **additive only** — every write is also readable through a
plain polling GET endpoint, and the feature is proven by test to work
correctly with broadcasting entirely unconfigured. If Reverb isn't running
in a given environment, the feature still works, just without the
near-real-time push.

`BROADCAST_CONNECTION` defaults to `log` in `.env.example` (framework
default, deliberately unchanged, same convention as `DB_CONNECTION=sqlite`)
— a fresh setup gets polling-only live scoring with zero broadcasting
infrastructure required. `.env` (this deployment) sets
`BROADCAST_CONNECTION=reverb` plus `REVERB_APP_ID`/`REVERB_APP_KEY`/
`REVERB_APP_SECRET`/`REVERB_HOST`/`REVERB_PORT`/`REVERB_SCHEME` and their
`VITE_REVERB_*` mirrors for the frontend Echo client
(`resources/js/app.tsx`'s `configureEcho({ broadcaster: 'reverb' })`).
Running a Reverb server (`php artisan reverb:start`) is a new operational
requirement for real-time updates in production; it is not required for
the app to function.

## Data model

- `scoring_sessions` — one row per live session of a match (`match_id`,
  restrict on delete). `status` (`App\Enums\ScoringSessionStatus`:
  in_progress/paused/ended). `side_a_label`/`side_b_label` (free text,
  suggested from the match's own entries when starting a session in
  WP-07-02, but not a strict FK — a match can have more than two entries
  and this phase doesn't model bracket/team structure). `score_a`/
  `score_b` (unsigned int). `period_label`/`status_note` (free text,
  sport-agnostic — no sport-specific columns). `started_by`/`ended_by`,
  `started_at`/`ended_at`.
- `score_events` — append-only log (`App\Enums\ScoreEventType`:
  point/correction/period_change/note/paused/resumed/ended). No
  `updated_at` (`ScoreEvent::UPDATED_AT = null`) — this table is never
  updated, only appended to. `payload` is JSON, shape depends on `type`.
  This is both the audit trail and the mechanism a reconnecting client
  could use to catch up (no separate offline-sync system).
- `App\Enums\MatchStatus` is **not modified** — live-session state lives
  entirely in `scoring_sessions`, decoupled from match lifecycle status. A
  match's own status transition (Scheduled→Completed/Walkover/Cancelled)
  still happens via the existing `matches.status` endpoint, independent of
  whether a live session was used.
- Only one non-`ended` session per match is allowed (enforced in
  `ScoringSessionController::store()`).
- `sport_state` (nullable JSON, WP-07-04) — sport-specific structured data
  the generic columns above don't cover. Shape depends on the session's
  `board_type`; `null` for the generic board. Never stored as a separate
  column per sport — one flexible JSON column shared by every sport-specific
  scoreboard added to this phase.
- `board_type_override` (nullable string, WP-07-07) — set only when the
  operator explicitly forced the generic board at session start (see
  "Manual board-type override" below). `null` for every session that used
  the automatic, sport-derived board — the common case.

## Endpoints

All under `App\Http\Controllers\ScoringSessionController`:

| Route | Access |
|---|---|
| `GET /matches/{match}/scoring-session` (`scoring.show`) | Same rule as `Matches — list`: Admin/Organizer any match, Delegation Officer their own delegation's matches only, Viewer forbidden. Polling contract — returns the current (most recent) session or `null`. |
| `POST /matches/{match}/scoring-sessions` (`scoring.start`) | `role:admin,organizer` (same gate match create/update use). Only for a `Scheduled` match with no existing active session. |
| `PATCH /scoring-sessions/{session}/score` (`scoring.score`) | `role:admin,organizer`. `type` = `point` or `correction`; `correction` requires a `reason`. Score never goes below 0. |
| `PATCH /scoring-sessions/{session}/period` (`scoring.period`) | `role:admin,organizer`. Updates `period_label`/`status_note`. |
| `PATCH /scoring-sessions/{session}/pause` \| `/resume` (`scoring.pause` / `scoring.resume`) | `role:admin,organizer`. |
| `PATCH /scoring-sessions/{session}/end` (`scoring.end`) | `role:admin,organizer`. Sets the session `ended`; never touches `EventResult`. |
| `PATCH /scoring-sessions/{session}/foul` (`scoring.foul`, WP-07-04) | `role:admin,organizer`. Basketball board only — `422` for any other board type. `action` = `add` (with `side`) or `reset`; mutates `sport_state.fouls_a`/`fouls_b`. |
| `PATCH /scoring-sessions/{session}/round` (`scoring.round`, WP-07-05) | `role:admin,organizer`. Boxing board only — `422` for any other board type. `score_a`/`score_b` (0-10 each) append a round to `sport_state.rounds` and add to the session's running `score_a`/`score_b`. |
| `PATCH /scoring-sessions/{session}/count` (`scoring.count`, WP-07-06) | `role:admin,organizer`. Softball/Baseball board only — `422` for any other board type. `action` = `out` \| `ball` \| `strike` \| `reset_count`; advances `sport_state`'s outs/count/inning per the cascading rules below. |
| `PATCH /scoring-sessions/{session}/inning-run` (`scoring.inning-run`, WP-07-06) | `role:admin,organizer`. Softball/Baseball board only — `422` for any other board type. `side` + `runs` (1-20) add to the current inning's row in `sport_state.innings` and to the session's running `score_a`/`score_b`. |

`scoring.start` also accepts an optional `board_type` (WP-07-07) — see
"Manual board-type override" below.

Every mutation appends a `score_events` row, records an `AuditLogger` event
(`scoring.started`/`scored`/`corrected`/`period_changed`/`paused`/
`resumed`/`ended`), and broadcasts `App\Events\ScoreUpdated` on the private
channel `match.{matchId}.scoring` (`routes/channels.php` — authorization
mirrors the `Matches — list` rule exactly, not a new rule).

## UI (WP-07-02)

`GET /matches/{match}/scoreboard` (`scoring.board`) renders
`resources/js/pages/scoring/show.tsx` — one page, not two, for both the
operator console and the read-only display (same pattern as `matches/
index.tsx` unifying manager and viewer experience with conditional
controls, not two separate pages):

- **No active session, `canManage` and the match is `Scheduled`**: a
  "Start live scoring" form with side A/B label inputs, pre-filled from
  `suggestedLabels` (the two entries' school names, only suggested when
  the match has exactly two confirmed entries — team events with more
  than two, or matches with fewer, are left blank for manual entry, since
  this phase doesn't model bracket/team structure).
- **No active session, anyone else (or match not `Scheduled`)**: an
  `EmptyState`.
- **Active session**: large running score per side, status badge, period/
  status text. If `canManage` and the session isn't `ended`: +1/+2/+3
  quick-score buttons per side, a "Correct" dialog per side (delta +
  required reason), a period/status update form, pause/resume, and an
  End `ConfirmDialog` (its description reminds the operator the official
  result still needs encoding separately — ending live scoring is not the
  same action).
- **Full-screen mode**: a toggle button using the browser Fullscreen API
  on the scoreboard's own container (not the whole page) — large type,
  minimal chrome, for a laptop/tablet/TV/projector. Tracks
  `fullscreenchange` so Escape/browser-native exit is reflected correctly.
- **Live updates**: the page always polls `scoring.show` every 5 seconds
  (the baseline — matches WP-07-01's promise that Reverb is never
  required) and additionally subscribes via `useEcho` (`@laravel/
  echo-react`) to the `match.{id}.scoring` private channel for
  near-instant updates when Reverb is available. Both write into the same
  local state, so the page behaves identically either way.
- State-sync note: local `session` state is adjusted from the Inertia
  `session` prop **during render** (comparing against a tracked "last
  synced" value), not inside a `useEffect` — React's own recommended
  pattern for "adjust state when a prop changes," since setting state
  synchronously inside an effect triggers a lint error
  (`react-hooks/set-state-in-effect`) and an extra render.
- Linked from `matches/index.tsx`'s new always-visible "Live" column
  (not gated behind `canManage`, unlike the existing Actions column — a
  Delegation Officer should be able to watch their own delegation's match
  even though they can't operate scoring).

## Sport-specific scoreboards (WP-07-04)

Per owner instruction, the generic board (score + free-text period/status)
stays the default for every sport, and dedicated scoreboards are added
per sport as their own WP. Which board a session uses is never stored —
`ScoringSession::boardType()` derives it every time from the match's own
`event.sport.name` via `App\Enums\ScoreboardType::forSport()`, so a later
sport-catalog rename or edit is reflected immediately, with no backfill
needed. `toLivePayload()` exposes it as `board_type`, alongside
`sport_state`, so the frontend can pick the right controls without a
separate request.

- **Basketball** (`ScoreboardType::Basketball`, sport name "Basketball"):
  `sport_state` is `{fouls_a, fouls_b}` — running **team** fouls for the
  current quarter/period. Initialized to `{0, 0}` when a session starts for
  a Basketball match (`ScoringSessionController::store()`). The quarter
  itself reuses the existing generic `period_label` free-text field (e.g.
  "Q2") rather than a new structured field — fouls are reset by an explicit
  "Reset fouls" action (`scoring.foul` with `action: reset`) the operator
  triggers when a new quarter starts, not inferred automatically from a
  period-label change. A "Bonus" badge shows once a side's fouls reach
  `BASKETBALL_BONUS_THRESHOLD` (5, a documented convention, not a hard
  rule enforced elsewhere — a meet using different local rules can just
  ignore the badge). Every foul action is also a `score_events` row
  (`App\Enums\ScoreEventType::Foul`) and an `AuditLogger` event
  (`scoring.foul_recorded` / `scoring.fouls_reset`).
- **Boxing** (`ScoreboardType::Boxing`, sport name "Boxing", WP-07-05):
  `sport_state` is `{rounds: [{round, score_a, score_b}, ...]}` — a
  round-by-round history, 10-point-must style (each round's two scores are
  validated to `0..10`, not forced to include a `10` — a meet's own judging
  convention decides that, this app doesn't enforce it). Initialized to
  `{rounds: []}` when a session starts for a Boxing match. Recording a round
  (`scoring.round`, `add`-only — no `reset`, unlike fouls) appends to the
  history **and** adds both scores into the session's running `score_a`/
  `score_b`, so the main scoreboard's cumulative total is always the sum of
  every round judged so far; a mis-recorded round isn't edited in place —
  the operator corrects the running total the same way as any other board
  type, through the existing generic `scoring.score` correction endpoint
  (`type: correction`, reason required). The round number itself isn't
  operator-input — it's always `count(rounds) + 1`, so rounds can't be
  recorded out of order or duplicated under a wrong number. The round
  (as in "Round 3") reuses the existing generic `period_label` free-text
  field, same convention as basketball's quarter — no new structured field
  for it.
- **Softball/Baseball** (`ScoreboardType::SoftballBaseball`, sport name
  "Softball" or "Baseball", WP-07-06): `sport_state` is `{inning, half
  (top|bottom), outs, balls, strikes, innings: [{inning, runs_a, runs_b},
  ...]}`. Initialized to inning 1, top, all counters 0, empty `innings` when
  a session starts for either sport. Two endpoints, not one, since runs and
  the count/outs are independent concerns:
  - `scoring.count` (`out`/`ball`/`strike`/`reset_count`) advances the count
    via cascading rules that mirror the sport's own hard rules, the same
    way basketball's bonus badge and boxing's derived round number encode
    a rule rather than leave it to the operator: any out (direct, or the
    third strike) resets the count for the next batter; a **third** out
    additionally ends the half-inning (flips top↔bottom, and increments
    `inning` once bottom ends); a **fourth** ball resets the count (a walk
    — this app doesn't model baserunners, so no run is auto-added, the
    operator uses `inning-run` if a walk forces one in); `reset_count` is a
    manual correction for a new batter with no walk/strikeout.
  - `scoring.inning-run` (`side` + `runs`) finds-or-creates the current
    inning's row in `sport_state.innings` and adds to it **and** to the
    session's running `score_a`/`score_b` in the same request, so the
    linescore breakdown and the cumulative total can never disagree with
    each other. The inning number for a new row always comes from
    `sport_state.inning` (never operator-input), so a run always lands in
    the inning the count/outs engine says is current.
  - Both endpoints (and every prior sport-specific one) leave the generic
    `score` correction endpoint reachable too — a run recorded through it
    updates the total but not the innings breakdown, same accepted
    trade-off already documented for boxing's rounds; nothing is silently
    lost either way since every mutation is still an unconditional
    `score_events` row.
  - Inning label (e.g. "Inning 3") reuses the existing generic
    `period_label` field only if the operator chooses to set it — unlike
    basketball/boxing, the inning number is already visible from
    `sport_state.inning`/`half`, so this field is optional here, not the
    primary display.

Boxing and Softball/Baseball both reused Basketball's JSON column and
per-board `store()` initialization without any schema change — the
extension point WP-07-04 was built to prove out.

## Manual board-type override (WP-07-07)

The automatic, sport-derived board is always correct for a normal match —
but an exhibition bout, a mixed-rules friendly, or any match that doesn't
follow its sport's usual structure may not fit the sport-specific board's
assumptions (basketball fouls, boxing rounds, softball innings/outs). At
session start, the operator can force the plain generic board instead:
`ScoringSessionController::store()` accepts an optional `board_type` field,
validated to only ever equal `"generic"` (`Rule::in([ScoreboardType::
Generic->value])`) — there is deliberately no way to force a *sport-specific*
board onto a match of a different sport, only to opt out of one down to
generic. When set, it's stored in the new `board_type_override` column;
`ScoringSession::boardType()` checks it first and, if present, returns it
without even loading the match's sport — the override always wins over the
derived value, for the lifetime of that session. A generic-forced session
never gets `sport_state` initialized (`store()`'s per-board-type `match`
naturally returns `null` for `ScoreboardType::Generic`, same as any other
sport with no dedicated board), so it behaves exactly like a session for a
sport with no dedicated board at all.

`ScoringSessionController::board()` exposes `suggestedBoardType` — the
board that *would* be auto-selected, computed straight from the match's
sport, independent of whether a session exists yet — so the frontend knows
whether to show the override control at all. `scoring/show.tsx`'s "Start
live scoring" form only renders the "Use the generic scoreboard instead of
the automatic {board} board" checkbox when `suggestedBoardType !== 'generic'`
(showing it for an already-generic sport would be a meaningless no-op
control). The choice is a one-time decision at start, not something a
session can flip mid-way — changing board type after sport-specific state
already exists would orphan that state, so this is out of scope by design.
`scoring.started`'s audit context now also records the resolved
`board_type`, so an overridden session is traceable in the audit log even
though the operator's choice itself isn't a separate mutation-with-reason
like a score correction.

## Public exposure (WP-07-08)

Live scoring was internal-only through WP-07-07 — per owner instruction,
WP-07-08 extends the public portal (`docs/public-portal.md`, "Live
scoreboard") to a read-only, provisional view of a match's live session for
any published meet, no separate opt-in beyond the existing publish
decision. Full detail lives in that doc, not duplicated here; the
short version: same `Meet::published()` scope, polling only (no Reverb for
guests), and a shared `LiveScoreDisplay` presentational component so the
public and internal read-only rendering can never drift apart.

## Tests

`tests/Feature/ScoringSessionTest.php` — authorization (Delegation Officer
forbidden from another delegation's match, allowed for their own read-only
view, Viewer forbidden entirely, mutations forbidden for non-managers), a
full session lifecycle (start→score→correct→period change→pause→resume→
end) via the polling read endpoint **with broadcasting on the `null`
driver** (`phpunit.xml`'s test default) — proves the feature doesn't
hard-depend on Reverb, only one active session per match enforced,
ending a session leaves `EventResult`/`ResultPlacement` completely
untouched (explicit assertion), corrections require a reason and are
audited with the correct actor; the scoreboard page's own authorization
(guest redirect, Viewer forbidden, Delegation Officer own-match-only),
suggested side labels only appear for exactly two entries, and the page's
`session` prop reflects a score change made through the operator
endpoints (the polling contract, not a browser-only assertion); a
Basketball match's session initializes `board_type`/`sport_state`
correctly and a non-Basketball match's doesn't, team fouls increment the
correct side and reset zeroes both, the `scoring.foul` endpoint 422s for a
non-Basketball session, is forbidden for non-managers, and rejects a
mutation once the session has ended, and the scoreboard page exposes
`board_type`/`sport_state` for a Basketball match; a Boxing match's session
initializes an empty round history and the right `board_type`, recording
round scores appends to the history and sums into the running total
correctly across multiple rounds with correct round numbers, a round score
outside `0..10` is rejected, the `scoring.round` endpoint 422s for a
non-Boxing session, is forbidden for non-managers, rejects a mutation on an
ended session, and the scoreboard page exposes `board_type`/`sport_state`
for a Boxing match; a Softball or Baseball match's session both correctly
initialize the same `softball_baseball` board type and count/inning state,
recording a run appends to the current inning's row and sums into the
running total (a later inning starts its own row rather than merging),
three outs flips the half-inning and resets the count, the third out of a
bottom half also advances the inning number, a third strike is itself an
out, a fourth ball resets the count without recording an out,
`reset_count` only zeroes balls/strikes, both endpoints 422 for a
non-Softball/Baseball session, are forbidden for non-managers, reject a
mutation on an ended session, and the scoreboard page exposes
`board_type`/`sport_state` for a Softball match; a Basketball, Boxing, or
Softball match's session can each be forced to `generic` (`sport_state`
stays `null`) via the `board_type` override at start, a Basketball match
started without the override still gets the basketball board (regression
guard), the override rejects any value other than `"generic"` (e.g.
`"basketball"` on an unrelated sport's match), and the scoreboard page
exposes the auto-derived `suggestedBoardType` correctly both for a
Basketball match and for a match with no dedicated board, before any
session exists.
`tests/Feature/ResultTest.php` (pre-existing, unchanged) already proves the
Phase 3 encode→validate flow works with no live scoring session ever
created — Phase 7 adds no coupling for it to newly depend on.
`tests/Feature/PublicScoreboardTest.php` (WP-07-08) — guests can view the
public scoreboard for a published meet's match and unpublished meets 404,
a match that doesn't belong to the given meet 404s, the page exposes the
live session read-only (including sport-specific state) with `canManage`/
`suggestedLabels` structurally absent, the poll endpoint returns the same
payload and 404s the same way, and the public meet page's `liveMatches`
lists only matches with a currently active session, scoped to that meet.

## Reconnection and concurrent-operator behavior (WP-07-03)

- **Reverb stopping mid-session:** every write already lands in
  `scoring_sessions`/`score_events` before `broadcast()` is dispatched, and
  `ScoreUpdated` is a queued `ShouldBroadcast` event, so a stopped/unreachable
  Reverb server never blocks or fails the HTTP request. A client that was
  relying on the socket simply stops receiving pushes; the 5-second
  `scoring.show` poll (always running, not conditional on Echo) is what
  actually re-syncs it, picking up the latest `toLivePayload()` on its next
  tick — no reconnect handshake or client-side catch-up logic needed,
  consistent with the "no complex offline synchronization" principle in
  DESIGN-NOTES.
- **Concurrent operator tabs:** `score()`'s read-modify-write of
  `score_a`/`score_b` (`max(0, $session->{$column} + $delta)` then `save()`)
  is a plain last-write-wins update, not lock-guarded — two simultaneous
  corrections from two tabs can race on the running total. This is
  accepted, not a bug to fix: `score_events` is still an unconditional
  `ScoreEvent::create()` on every request, so no audit row is ever silently
  dropped even if the derived total's race means one write's delta doesn't
  land in the final number — an operator can always reconcile from the
  `score_events` log, and a session is provisional by definition (Phase 3's
  validated result is the one number that must never race).

## Accessibility (WP-07-03)

Swept `scoring/show.tsx` (operator console + read-only live display,
including full-screen mode) at phone/tablet/desktop widths, same checklist
as WP-04-06/WP-05-07: the bare `+1`/`+2`/`+3` quick-score buttons had no
accessible name distinguishing which side they scored for — fixed with an
`aria-label` naming the side and point count (e.g. "Add 1 point, Home");
the live score grid got `aria-live="polite"` + `aria-atomic="true"` so a
screen-reader user watching the read-only display is told when the score
changes, not just sighted users; the two-side quick-score control block
(previously a fixed `grid-cols-2`, tight enough on a narrow phone to risk
button wrapping/overflow) now stacks to one column below `sm:` and its
button rows wrap. Verified already sound: heading order (one `h1` via
`PageHeader`, no other headings — `CardTitle` is a styled `div`, same
convention as every other page), decorative icons already `aria-hidden`
(`Maximize2`/`Minimize2`/`Play`/`Pause`/`Square`, `EmptyState`'s icon), the
"No live session" empty state, and every form input already
`Label`-associated.
