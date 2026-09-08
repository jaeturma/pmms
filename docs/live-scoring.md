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
infrastructure required. A development `.env` sets
`BROADCAST_CONNECTION=reverb` plus `REVERB_APP_ID`/`REVERB_APP_KEY`/
`REVERB_APP_SECRET`/`REVERB_HOST`/`REVERB_PORT`/`REVERB_SCHEME` and their
`VITE_REVERB_*` mirrors for the frontend Echo client. Running a Reverb
server (`php artisan reverb:start`) is an operational requirement for
real-time updates; it is not required for the app to function.

**Frontend realtime is gated on `VITE_REVERB_APP_KEY` being present at
build time** (`resources/js/pages/scoring/show.tsx` — `realtimeEnabled`).
`@laravel/echo-react` bakes that Vite var into the bundle, and its first
`useEcho()` call does `new Pusher(key)`, which **throws synchronously**
("You must pass your app key…") when the key is missing — and with no
error boundary that blanks the operator console. So `configureEcho()` and
the `useEcho` subscription (isolated in `<RealtimeScoreSync>`, rendered
only when `realtimeEnabled`) run only when a key was compiled in. A
production build with `BROADCAST_CONNECTION=log` and no `VITE_REVERB_*`
therefore runs the console on its polling baseline with **no Pusher
instantiation at all**. Rebuild whenever the broadcasting env changes.

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

All under `App\Http\Controllers\ScoringSessionController`. Every mutation
route below sits behind `role:admin,organizer,technical_official`
middleware, then the controller's own `canManage()` narrows that coarse
filter to who may actually manage *this* match's scoreboard: Admin (any
match), a Technical Official scoped to their own assigned sport
(`User::sports()`), or an Organizer holding an active Tournament
Secretary or Tournament ICT `MeetSportAssignment` for the match's
meet+sport — a plain Organizer, or a Technical Official/Tournament
Secretary/ICT outside their own assignment, still gets `403`.

| Route | Access |
|---|---|
| `GET /matches/{match}/scoring-session` (`scoring.show`) | Same rule as `Matches — list`: Admin/Organizer any match, Delegation Officer their own delegation's matches only, Viewer forbidden. Polling contract — returns the current (most recent) session or `null`. |
| `POST /matches/{match}/scoring-sessions` (`scoring.start`) | `canManage()` (above). Only for a `Scheduled` match with no existing active session. |
| `PATCH /scoring-sessions/{session}/score` (`scoring.score`) | `canManage()`. `type` = `point` or `correction`; `correction` requires a `reason`. Score never goes below 0. |
| `PATCH /scoring-sessions/{session}/period` (`scoring.period`) | `canManage()`. Updates `period_label`/`status_note`. |
| `PATCH /scoring-sessions/{session}/pause` \| `/resume` (`scoring.pause` / `scoring.resume`) | `canManage()`. |
| `PATCH /scoring-sessions/{session}/end` (`scoring.end`) | `canManage()`. Sets the session `ended`; never touches `EventResult`. |
| `PATCH /scoring-sessions/{session}/foul` (`scoring.foul`, WP-07-04) | `canManage()`. Basketball board only — `422` for any other board type. `action` = `add` (with `side`) or `reset`; mutates `sport_state.fouls_a`/`fouls_b`. |
| `PATCH /scoring-sessions/{session}/round` (`scoring.round`, WP-07-05) | `canManage()`. Boxing / combat-rounds only — `422` for any other board type. **Boxing** takes `cards` (one 10-point-must card per judge); **combat-rounds** takes a single `score_a`/`score_b` pair (0-10). See §"Boxing" below. |
| `PATCH /scoring-sessions/{session}/boxing-deduction` \| `/boxing-decision` | `canManage()`. Boxing board only. Referee point deductions / the official bout decision — see §"Boxing". |
| `PATCH /scoring-sessions/{session}/count` (`scoring.count`, WP-07-06) | `canManage()`. Softball/Baseball board only — `422` for any other board type. `action` = `out` \| `ball` \| `strike` \| `reset_count`; advances `sport_state`'s outs/count/inning per the cascading rules below. |
| `PATCH /scoring-sessions/{session}/inning-run` (`scoring.inning-run`, WP-07-06) | `canManage()`. Softball/Baseball board only — `422` for any other board type. `side` + `runs` (1-20) add to the current inning's row in `sport_state.innings` and to the session's running `score_a`/`score_b`. |

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
  even though they can't operate scoring). Also linked from the Schedule
  page (Phase 8 addition) — see `docs/scheduling.md` "Live scoreboard
  link" — for the same reason: someone looking at what's scheduled today
  shouldn't have to detour through Matches to find a game already in
  progress.

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
- **Boxing** (`ScoreboardType::Boxing`, sport name "Boxing", WP-07-05;
  judge scorecards added later): the clock/bell keys are shared with
  combat-rounds (`round_duration_seconds`, `rest_duration_seconds`,
  `total_rounds`, `clock_seconds`, `clock_updated_at`, `clock_phase`,
  `bell_sounded_at`). On top of those, boxing models the **5-judge
  10-point-must system**:
  - `judge_count` (3 or 5, default 5; locked once a round is scored),
    `judge_rounds: [{round, cards: [{judge, red, blue}, ...]}]` — every
    judge's own card for every completed round, `deductions_a` /
    `deductions_b` (referee point deductions, bout-wide), `decision`
    (see below), `show_live_judge_scores` (default `false`).
  - `scoring.round` for a **boxing** session takes `cards` (one per
    judge). Each card must be 10-point-must — exactly one side on 10, the
    other on 7/8/9; `10-10`, `9-9` and "no side on 10" are rejected
    (boxing has no drawn round). Combat-rounds still passes the single
    `score_a`/`score_b` pair to the same route.
  - Recording a round appends every card to `judge_rounds`, derives the
    round's **consensus line** (the modal winning corner + modal winning
    margin among the judges — never an average) into
    `rounds: [{round, score_a, score_b}]` and the running `score_a`/
    `score_b`, so the existing round table, play-by-play (a `JudgeRound`
    score event, reconstructed exactly like `RoundScore`) and "unofficial
    points total" keep working unchanged. Round number stays derived
    (`count(judge_rounds) + 1`), capped at `total_rounds`.
  - `scoring.boxing-deduction` (`side` + `points` 1-3, or `action:
    reset`) — bout-wide, subtracted from that corner's total on **every**
    judge's card when the decision is computed; never touches a raw round
    score. Logs a `Deduction` score event.
  - `decision` is computed from each judge's **full card** (round totals
    minus deductions), never the aggregate points: tally the judges'
    picks → `unanimous` (all agree) / `majority` (one card even, no card
    for the loser) / `split` / `draw`. `status: 'provisional'` until every
    scheduled round is scored, then `'final'`.
  - `scoring.boxing-decision` (`method`: `points` / `rsc` / `rsc_i` /
    `ko` / `dsq` / `wo` / `abd` / `nc`, plus `winner` and optional
    `note`) — an authorized ICT/Admin call that **overrides** the points
    computation for a referee/official stoppage; `method: points` with no
    winner clears the override and reverts to the computed result. Audit
    `scoring.decision_recorded`.
  - **Public disclosure**: unless `show_live_judge_scores` is on, the
    per-judge cards and the provisional decision are withheld from the
    **public** payload (`ScoringSession::toLivePayload()`) while the bout
    is live; the operator console always sees everything; full disclosure
    once the session ends. A manual RSC/KO/DSQ/WO decision is a public
    announcement and is never withheld (though its per-judge `tally.judges`
    breakdown is still stripped).
  - **Boards** (`resources/js/components/live-score-display.tsx` operator
    console, `resources/js/apps/portal/components/boxing-scoreboard.tsx`
    public) deliberately show **only the corners, the round/rest countdown
    and the decision** — not the running points total, the round-by-round
    cards or the deduction tallies. The bout is decided on the judges'
    cards and that stays with the scoring table; the operator's own
    control panel keeps every scoring tool. Red corner = solid red panel,
    blue corner = solid blue; the corner photo is the athlete's
    `sports_photo_url` (action photo), not the ID portrait
    (`ScoringSessionController::matchParticipants()` /
    `ScoringSession::athleteParticipants()`).
  - A mis-recorded round is still corrected the same way as any other
    board type, through `scoring.score` (`type: correction`); the round
    (as in "Round 3") reuses the generic `period_label` free-text field.
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

## Basketball scoreboard visual alignment (WP-08-10)

`docs/ui-ux/references/desktop-basketball-live-score.png` and its mobile
counterpart show a much richer scoreboard than this app tracks: a running
game clock, a 24-second shot clock, timeouts, a quarter-by-quarter score
breakdown, a play-by-play feed with player names/jersey numbers, full team
shooting/rebounding/assist stats, and per-player "top performers" with
photos. None of that exists here — `sport_state` for basketball is still
just `{fouls_a, fouls_b}` (WP-07-04), and **no scoring event anywhere in
this app records which athlete did anything** — a point is only ever
attributed to a side (`a`/`b`), never a player.

Presented the owner three options before writing code: restyle with real
data only; restyle plus a couple of cheap new trackers (timeouts, an
operator-set clock value); or a full build (real per-player attribution,
functioning clocks, derived box score). **Owner chose: restyle with real
data only.** No clock, shot clock, timeouts, team shooting/rebounding
stats, or per-player top performers were built — all omitted rather than
faked, same discipline as WP-08-06's eligibility-checker decision and
WP-08-09's ranking-table decision.

What **was** built, all real:

- **A genuine play-by-play feed**, reconstructed from the existing
  append-only `score_events` log — `ScoringSession::playByPlay()` (new),
  included in `toLivePayload()` so every board type gets it for free, not
  just basketball. Replays every `point`/`correction` event in order,
  applying the same `max(0, ...)` floor `ScoringSessionController::
  score()` itself uses, to reconstruct **both** sides' running scores at
  each point in time — a single event's payload only ever records the one
  side it changed, so this is a real reconstruction, not a stored value.
  `describeEvent()` formats a human-readable line per event type (point,
  correction with its reason, foul add/reset, period change, pause/
  resume/end) — no player names, since none are tracked. Capped at the 30
  most recent events (a feed, not a paginated archive), newest first;
  `LiveScoreDisplay` shows the first 8 with a "View full play by play"
  expand button, the same collapse pattern WP-08-09 established for the
  mobile ranking table.
- **Fouls rendered as dots** instead of a bare number (`FoulDots`,
  `resources/js/components/live-score-display.tsx`) — still the same real
  `fouls_a`/`fouls_b` count, just a different rendering, matching the
  reference's visual language.
- **Real match metadata** in the page header (both `scoring/show.tsx` and
  `public/scoreboard.tsx`): sport name, gender+age-division category, and
  — new — venue and scheduled date, sourced from the match's own
  `EventSchedule`/`Venue` (`ScoringSessionController::board()` and
  `PortalController::scoreboard()` both now eager-load `schedule.venue`).
  All real, existing fields; no new columns.
- **A "disconnected" indicator** — both live-scoring pages already polled
  every 5 seconds (WP-07-01) but silently retried failures with no visible
  signal; this WP adds one. After 2 consecutive poll failures,
  `LiveScoreDisplay` shows a "Connection lost — retrying automatically"
  banner; any successful poll or Echo push clears it immediately. This was
  a real pre-existing gap against this phase's own stated rule ("support
  ... disconnected ... states"), not new scope invented for this WP —
  every earlier live-scoring WP had the polling/Reverb mechanism itself,
  just no user-visible signal when it was actually failing.

Reverb updates, the 5-second polling baseline, and the provisional-score
badge on the public page were all already real and unchanged by this WP
(WP-07-01/02/08) — re-verified working via the existing test suite, not
rebuilt.

## Softball/baseball scoreboard visual alignment (WP-08-12)

`docs/ui-ux/references/desktop-softball-live-score.png` shows the same
shape of gap WP-08-10 found for basketball, at a similar scale: a "Team
Comparison" panel (hits, errors, walks, strikeouts, stolen bases, batting
average, slugging %), per-player "Top Performers" with photos and batting
lines, and a "Current Pitcher" panel with per-player pitching stats — none
of which exist. Worse than basketball's gap in one respect: the
reference's diamond graphic showing runners on base isn't real either —
`sport_state` for softball/baseball has never tracked a baserunner model
at all (documented as a deliberate omission back in WP-07-06: "a walk —
no baserunner model, so no run auto-added").

Given the owner had already answered this exact structural
question twice for WP-08-10 (basketball) and WP-08-11 (athletics) with
"restyle/build with real data only," and this WP is the same shape as
WP-08-10's (a real `sport_state` core already exists; the reference wants
additional per-player/team-comparison data that doesn't), this WP applied
that same established answer directly rather than asking a third time.
What was built, all real:

- **A proper line-score table** (`SoftballLineScore`,
  `live-score-display.tsx`) — innings as columns, R as the final column,
  from the real `sport_state.innings` breakdown. Deliberately **not** a
  fixed 7- or 9-inning grid the way the reference shows: this app doesn't
  track a configured game length, so only innings that have actually
  happened get a column, however many that is — inventing empty "-"
  placeholder columns for a fixed length would be a real, if small,
  fabrication.
- **Balls/strikes/outs as colored dot rows** instead of "Count 1-2 · 1
  Out" text, reusing a new generic `CountDots` (basketball's `FoulDots`
  generalized to take `max`/`colorClass`, since this is its second real
  use). Real caps, not decorative round numbers: 3 balls (the 4th is a
  walk, auto-resets), 2 strikes (the 3rd is itself an out, auto-resets),
  2 outs (the 3rd flips the half-inning, auto-resets) — the same
  business rules WP-07-06 already enforces server-side, just reflected
  here as the dot rows' real maximum.
- **Real play-by-play descriptions for softball's own event types** —
  `ScoringSession::describeEvent()` (WP-08-10) previously only handled
  the generic/basketball event types; `Count` and `InningRun` fell
  through to a bare type-label fallback ("Count", "Inning run") with no
  detail. Now describes them from their real payload: `InningRun` as
  "+N run(s) — {side} (Inning M)"; `Count` per action as "Ball (B-S)" /
  "Strike (B-S)" / "Out (N outs this half)" / "Count reset". Deliberately
  does **not** try to infer derived events like "walk" or "strikeout" —
  `count()`'s payload has no `side`/batter field at all, and inferring a
  walk from "the count reset to 0 after a ball action" would be a fragile
  guess rather than a fact actually recorded.
- **Fixed a real bug found while extending this**: `playByPlay()`'s
  running-score reconstruction (added in WP-08-10) only replayed
  `point`/`correction` deltas — it silently ignored `InningRun` (softball
  runs) and `RoundScore` (boxing rounds) entirely, so a softball or boxing
  play-by-play's displayed running score would have been stuck at 0-0
  throughout. Not a regression against anything shipped (`playByPlay()`
  itself is new, uncommitted WP-08-10 work), but worth recording since it
  would have shipped broken for two sports if not caught here. Fixed by
  also applying `InningRun`'s single-side runs and `RoundScore`'s
  simultaneous both-side deltas during replay; `RoundScore` also gained a
  real description ("Round N: {side A} X – Y {side B}") while already in
  that method, closing the last remaining generic-fallback gap for boxing
  too.

Match/scoreboard-page header metadata (sport/category/venue/scheduled
date) and the disconnected-polling indicator were both already built
generically in WP-08-10 (not gated to basketball), so softball/baseball
sessions already had them for free — reconfirmed via the existing test
suite, not rebuilt.

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

## Manual setup and participant override (blank-page fix)

### The blank page

`MatchRosterPlayer::groupBySide()` built every roster line with
`$player->entry->athlete->fullName()` — no null guard. Deleting an athlete
from the registry is a soft delete (`Athlete` uses `SoftDeletes`;
`AthleteDeletionService`), but the `Entry` and `match_roster_players` rows
it leaves behind are hard rows with no `SoftDeletes` of their own, so
`entry->athlete` becomes `null` under the global scope while the roster row
still lists the player. The `fullName()` on `null` was a PHP fatal, which
Apache/mod_fcgid surfaces as *"Premature end of script headers: index.php"*
— a blank page on the internal board's first render (`board()` →
`toLivePayload()` → `onCourtPayload()`), on the 5-second `scoring.show`
poll, on the substitution modal (`match-roster.show`), and on the **public**
scoreboard and its poll. Dev never hit it because dev data has no
soft-deleted athletes sitting on a roster.

The fix is null-safe, not `withTrashed()`-everywhere:

- `MatchRosterPlayer::groupBySide()` reads `$player->entry?->athlete?->
  fullName() ?? __('Data incomplete')` and the same for the photo.
- `payloadForMatch()` / `payloadForIds()` eager-load the athlete
  `withTrashed()` **for display only** — a soft-deleted athlete still shows
  their real name in historical roster display, but a genuinely missing
  link shows `Data incomplete`.
- `MatchRosterController`: `canManage()` now also recognises the base
  `TournamentICT` / `TournamentManager` / `TournamentSecretary` roles (an
  ICT running the board was getting a 403 on the substitution modal);
  `store()` refuses an entry whose athlete is soft-deleted (a deleted
  entity never regains operational authority); `destroy()` audit context
  and `eligibleAthletes()` are null-safe.

No `withTrashed()` global scope, no `catch (\Throwable) { return success }`.

### Always-available manual setup

A Tournament ICT must be able to open the board and start it with the
factual minimum even when relational data is incomplete. `startSession()`
already accepts free-text `side_a_label` / `side_b_label` when fewer than
two authoritative participants exist, `board_type => generic`, and a
two-Delegation pick for a team match with no Team Entries — that path is
unchanged. Added:

- `board()` sends `meetDelegationOptions` (every active Delegation in the
  meet) and, for individual events, `athleteOptions` (athletes carrying an
  Entry for the event, `withTrashed` for the label, flagged `unlinked`) —
  a `<datalist>` the operator can pull a Side A / Side B name from. A
  soft-deleted athlete is a display suggestion only, never selectable as an
  operational athlete link.
- The frontend sends `manual_setup: true` when it rendered the free-text
  inputs. It changes nothing that is persisted — it only lets the operator
  console carry a *"Manual scoreboard setup"* remark.
- `board()` also sends `dataIssues: string[]` — non-fatal competition-data
  problems it detected (an event with no linked sport, entries whose
  athlete is missing/soft-deleted). Every relationship access in `board()`
  is null-safe (`$match->meet?->name ?? …`, `$event?->sport?->name ?? …`,
  `ScoreboardType::forSport($sport?->name)`), so a genuinely orphaned
  match warns and stays runnable from a manual setup rather than 500-ing.
  The console shows the list as a dismissible amber banner.

### Participant override (§6)

When the generated participants exist but are wrong or conflict with what
is actually competing, an authorized operator may **override the
scoreboard's operational participant fields**:

- At start: `startSession()` accepts `override_participants: true` (+
  optional `override_reason`, `side_a_athlete_id` / `side_b_athlete_id`).
  It skips the "assigned participants are authoritative" label overwrite
  and the automatic roster extraction — the operator's typed Side A / Side
  B stand.
- After start: `PATCH scoring-sessions/{session}/participants`
  (`scoring.participants`) sets `side_a_label` / `side_b_label` and
  optional display-only athlete/delegation ids on a running session.

Both record provenance in `sport_state['participants']` — **no migration**,
per §11: `mode` (`manual_setup` | `override`), `by` / `by_name` / `at`,
`reason`, `previous` (the labels it replaced), and the chosen `side_a` /
`side_b`. `ScoringSession::toLivePayload()` **strips that key from every
payload** and, only for the internal console (`operational: true`), adds
`operational_remarks` (terse notices like *"Participants overridden by
Tournament ICT"*) and `participant_provenance`. The public scoreboard sees
the manual labels as ordinary side names and nothing else. Each override
also writes an `AuditLog` (`scoring.participants_overridden` /
`scoring.participants_manual`, with previous + new) and a `ScoreEvent`
(`note` type) so it shows in the match's own play-by-play history.

Nothing in the registration domain is touched: no Athlete,
SportRosterMember, Entry, Confirmed Entry, Team Entry, Coach assignment or
Schedule is created or updated by a manual setup or an override.
`meetAthleteRule()` keeps an optional athlete link inside the match's own
Meet.

Tests: `tests/Feature/LiveScoreboardManualSetupTest.php` (19 cases —
soft-deleted roster athlete, missing schedule/entry, team + individual
manual naming, optional athlete link, override at start and after start,
audit logging, single/idempotent session creation, generic fallback,
standard workflow unchanged, out-of-sport operator blocked, public labels,
provenance never public, scoring mutations after manual start).

### Roster: load a team, or type a player in

The "Manage roster" (pre-start) and "Substitute" (in-game) modals both run
off `match-roster.show` / `match-roster.store`. Two additions let an
operator build a roster when the match's own registration wiring is
missing or broken:

- **Load a team's athletes.** `GET /matches/{match}/roster` accepts
  `a_delegation_id` / `b_delegation_id`. When given, that side's
  `eligibleAthletes` becomes *every Confirmed Entry that Delegation holds
  for this event* (minus whoever is already rostered) — real registration
  data, independent of whether a Team Entry or representative entries
  exist. The response also carries `teamOptions` (every active Meet
  Delegation) and `selectedDelegations` (what each side resolves to now, an
  explicit pick or the derived Team Entry / representative Delegation) so
  the console can render the picker with its current state. `store()`
  accepts a matching `delegation_id`: the operator's assertion of the
  side's team, which lets a Confirmed Entry be rostered even with no match
  linkage to derive the side from (the entry must still really be that
  Delegation's).

- **Add a player by name.** `store()` accepts `manual_name` in place of
  `entry_id`. The row is written with a **null `entry_id`** and the
  hand-typed name (`match_roster_players.manual_name`, migration
  `2026_09_08_120000`; `entry_id` is now nullable, the
  `(match_id, entry_id)` unique index still blocks rostering the same
  Entry twice — MySQL treats each NULL as distinct). A manual player is a
  first-class roster row: same 15-per-side cap, can be sent on court, and
  can be attributed points/fouls — `rosterPlayerName()` /
  `MatchRosterPlayer::groupBySide()` fall back
  `entry?->athlete?->fullName() → manual_name → "Data incomplete"`, so the
  name flows into the append-only play-by-play and the on-court payload.
  Audited as `match_roster.added` with `manual: true`.

Nothing in the registration domain is created or changed — a manual player
exists only on `match_roster_players`.

Tests: `tests/Feature/MatchRosterTest.php` (load-a-team pool, hand-typed
player + 15-cap, asserted-delegation accept/reject, manual player in the
payload and scored in play-by-play).

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
initializes the judge-scorecard state and the right `board_type`, a round
stores every judge's card verbatim and derives the consensus line + running
total, cards that aren't 10-point-must (`10-10`, `9-9`, no side on 10, an
over-wide margin) are rejected, referee deductions shift every judge card in
the decision without touching a raw score, unanimous / majority / split
decisions are computed from each judge's card, an authorized official can
override the points result with an RSC/KO decision (and a later round score
doesn't overwrite it), the public payload withholds live judge cards until
the bout ends unless disclosure is on, the deduction/decision endpoints are
boxing-only and manager-only, and the manual participant fallback still
starts a bout with no entries; a Softball or Baseball match's session both correctly
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
