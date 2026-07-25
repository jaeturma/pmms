# Live Scoring (Phase 7, WP-07-01/02)

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
endpoints (the polling contract, not a browser-only assertion).
`tests/Feature/ResultTest.php` (pre-existing, unchanged) already proves the
Phase 3 encode→validate flow works with no live scoring session ever
created — Phase 7 adds no coupling for it to newly depend on.

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
