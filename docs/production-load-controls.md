# Production Load Controls

System Administrator emergency levers for a live meet, aimed at cutting
unnecessary PHP/Laravel traffic while preserving critical ICT/Admin
operations. All application-level — nothing here needs an Nginx/Apache
config change, and nothing deletes scoring data or user accounts.

Everything is gated by `can:administer` (the `UserRole::Admin` gate) at
the route — hiding the frontend buttons is not the control.

## A. Public medal tally — no automatic polling

`resources/js/apps/portal/pages/portal/tally.tsx` performs **no** interval,
heartbeat, websocket fallback or `router.reload` timer. It makes only its
initial request. Medal data changes when the browser is refreshed or the
**"Refresh Tally"** button is clicked — a `router.reload({ only:
['categories','generatedAt'] })` of the current URL, so the selected
category (`?category=`) and the sport filter (`?sport_id=`) are preserved.
A synchronous `useRef` latch drops every click until the in-flight
request finishes, so repeated / double clicks fire exactly one request;
the `reloading` state just disables the button and spins the icon. The
existing signature-diff gate means a refresh that returns identical
numbers animates nothing.

`MedalTallyService` is unchanged — Overall = Elementary + Secondary only,
Paragames separate, Kickboxing excluded everywhere, repeated medal rows
aggregate exactly.

The internal `tally/index.tsx` ("Medal tally" sidebar page) never polled
and is untouched. `resources/js/pages/public/tally.tsx` is dead code (no
route renders it) and was left alone.

## B. Suspend all public live scoreboards

`system_settings.live_scoreboards_suspended` (bool). Flip it from the
Production Load Controls card, or `POST
/system/load-controls/{suspend,resume}-scoreboards`
(`LoadControlController`), each audited
(`load_control.scoreboards_{suspended,resumed}`).

`App\Http\Middleware\EnsurePublicScoreboardsActive` is mounted on **only**
these four public routes:

| Route | Suspended behaviour |
|---|---|
| `public.scoreboard` (`/meets/{m}/matches/{n}/scoreboard`) | renders `portal/scoreboard-suspended` — no scoreboard query |
| `public.scoreboard.poll` | `{ "suspended": true, "session": null }` — 200, no controller |
| `public.live-sport-portal` (`/live/{sport}`) | renders `portal/scoreboard-suspended` |
| `public.sport-portal.poll` (`/{sport}/poll`) | `{ "suspended": true, "liveNow": null, "otherLiveCount": 0 }` |

The middleware short-circuits **before** the controller, so a suspended
poll never builds `toLivePayload()`. The frontend polls
(`portal/scoreboard.tsx`, `portal/sport-portal.tsx`, `portal/live-sport.tsx`)
stop their interval the moment they see `suspended: true` and show the
notice — **they do not poll again to detect resume**; a viewer refreshes.
`/{sport}` (the sport hub page) is not suspended — only its Live Now poll
is — so schedule/results/standings keep working.

The read is a 60-second cached projection (`Setting::loadControls()`,
`pmms:load-controls`), busted by every writer, so the gate adds no
`system_settings` query per request once warm.

### ICT scoring is never affected

The authenticated scoring routes are a wholly separate tree under
`auth`/`verified` — `scoring.board` (`/matches/{n}/scoreboard`),
`scoring.show`, `scoring.start`, every `scoring-sessions/{s}/*`. The
suspend middleware is not on any of them. Suspension is a public
viewing / load-control mechanism only. Regression-tested:
`ProductionLoadControlsTest` "suspending public scoreboards does not
disable authenticated ICT scoring".

## C. Disconnect logged-in users

`session.driver` is `database` in production — sessions live in the
`sessions` table (`id, user_id, ip_address, user_agent, payload,
last_activity`), nowhere else. No Redis is used for sessions/cache/queue.

`LoadControlController`:

- **`disconnect-inactive`** — deletes `sessions` rows with a `user_id`
  whose `last_activity` is older than the inactivity timeout. Guest
  sessions (`user_id` null) and the acting administrator's own sessions
  are never touched.
- **`disconnect-non-admin`** — deletes every `sessions` row whose
  `user_id` is not a System Administrator (and not the acting session).

Both `DELETE FROM sessions WHERE …` only — never cache, queues, locks or
any other store (no `FLUSHALL`/`FLUSHDB`). Audited as
`load_control.sessions_disconnected` with
`{administrator_id, mode, sessions_invalidated}`. The affected users are
forced to sign in again on their next request; no account or scoring data
is altered.

## D. Authenticated inactivity expiry

**Off by default** — `system_settings.authenticated_inactivity_expiry_enabled`
is `false`, so shipping this changes no session behaviour until a System
Administrator ticks *"Sign out inactive authenticated users"*. While
disabled the middleware is a pure pass-through (it does not even stamp the
activity marker).

Once enabled, `App\Http\Middleware\ExpireInactiveSessions` (appended to the
`web` group, runs after `StartSession`, before the Inertia share work):

- a genuine authenticated request stamps `last_activity_at` in the
  session;
- if the next genuine authenticated request arrives more than
  `Setting::inactivityTimeoutMinutes()` later, `Auth::guard('web')->logout()`
  (which cycles the remember token and forgets the recaller cookie, so
  "remember me" cannot silently re-authenticate), `session()->invalidate()`,
  `session()->regenerateToken()`, then redirect;
- **public scoreboard / sport-portal poll routes and the public medal
  tally are `PASSIVE_ROUTES`** — skipped entirely, so background polling
  can neither extend nor collapse an authenticated session.

Redirect shape — never a raw 500/422:

| Request kind | Response |
|---|---|
| Inertia (`X-Inertia`) | `409` + `X-Inertia-Location: /login` |
| Other AJAX/JSON | `401 { "message": "Your session expired due to inactivity. Please sign in again." }` |
| Normal browser | `302` to `/login` with a `status` flash of the same message |

There is **no heartbeat** — nothing keeps a session alive on its own. The
global `SESSION_LIFETIME` (120) is left as an outer ceiling; the effective
idle window is the DB setting, minimum 5 minutes
(`Setting::MIN_INACTIVITY_TIMEOUT_MINUTES`, enforced in both the request
validation and the accessor).

## E / H. Admin configuration

Extended on the existing canonical settings surface
(`system-settings/edit.tsx`, `can:administer`) — no new settings
mechanism:

- **"Sign out inactive authenticated users"** — a checkbox
  (`authenticated_inactivity_expiry_enabled`, default off) that switches
  the whole inactivity feature on, plus a **timeout (minutes)** number
  input validated server-side `min:5, max:720`. Both saved through the
  normal `system-settings.update` PUT; no asset rebuild needed.
- **Production Load Controls card** — status read-outs (Live Scoreboards
  ACTIVE/SUSPENDED, Medal Tally Auto Refresh DISABLED, Session Inactivity
  Timeout) and four confirm-gated action buttons: Suspend / Resume Live
  Scoreboards, Disconnect Inactive Users, Disconnect All Non-Admin Users.
  Each button POSTs to its dedicated `LoadControlController` endpoint via a
  `ConfirmDialog`.

## G. Scoreboard poll cost

`PortalController::scoreboardPoll()` / `sportPortalPoll()` now wrap their
payload in `Cache::remember(key, now()->addSecond(), …)`. The payload is
identical for every viewer of a match/sport, so **N concurrent viewers
cost one `toLivePayload()` build (~a dozen queries) per second, not N**.
Both poll routes also drop `HandleInertiaRequests` via `withoutMiddleware`
— they are pure JSON, and that middleware was running `divisions` /
`system_settings` / `meets` / a `scoring_sessions` count on every poll for
nothing. Frontend polls additionally stop after `MAX_POLL_FAILURES` (10)
consecutive errors and already pause on a hidden tab and when a
match/session has ended.

Reverb/Pusher is **not** required and not configured for this — polling
remains the baseline (see `docs/live-scoring.md`).

## Tests

`tests/Feature/ProductionLoadControlsTest.php` (27 cases) covers all of
J.1–J.17: no auto-poll, manual refresh + filter preservation, the
settings page exposing the load-control state, suspend/resume
authorization and behaviour, lightweight suspended poll doing no
scoreboard work, ICT scoring unaffected, no data deleted, disconnect
inactive/non-admin with audit + self-protection + unrelated-store
isolation, inactivity expiry **off by default**, then (once enabled)
inactivity logout / genuine-activity keep-alive / clean Inertia 409 /
passive-poll non-extension, and the ≥5-minute timeout validation.
