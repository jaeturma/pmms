# Event Scheduling & Venue Assignment

WP-03-02. Manual scheduling of a meet's events into venue time slots, per the MVP
scope (no automated scheduling or optimization).

## Data model

`event_schedules` — `meet_id` (FK, cascade), `event_id` (FK, restrict), `venue_id`
(FK, restrict — this activates the WP-03-01 venue delete guard), `scheduled_date`,
`starts_at`/`ends_at` (time), optional `note` (≤255). One event may have several slots
(sessions/days). Indexed on `(venue_id, scheduled_date)` for the conflict check and
`(meet_id, scheduled_date)` for listing.

Times are normalized to `H:i:s` on write (`ScheduleRequest::slotData()`) so string
comparisons behave identically on MySQL and the SQLite test database.

## Rules (server-enforced in `ScheduleController`)

- Slots may only reference events attached to the meet (`meet_events`).
- Scheduling (create/update/delete) is allowed only while the meet is
  **registration-closed or active** — before that the event list may still change,
  after completion the schedule is historical record.
- Same-venue overlap on the same day is blocked with a validation message naming the
  conflicting event and time range; back-to-back slots (end == start) are allowed.
- Archived venues cannot receive new slots (`ScheduleRequest` exists-where-active).
- End time must be after start time.

## Authorization

The schedule is non-sensitive: readable by all authenticated roles. Mutations are
manager-only via the `role:admin,organizer` route group — one row in the matrix in
`docs/authorization.md`, swept by `AuthorizationMatrixTest`.

## Audit

`schedule.created|updated|deleted` via `AuditLogger`, with meet, event, venue, date,
and time range in context.

## UI

`resources/js/pages/schedule/index.tsx` — shared-component table sorted by day and
start time, filterable per meet, per venue, and per day (date input), searchable by
event name, paginated. Managers get a slot dialog with dependent meet → event selects
(only registration-closed/active meets are offered), active-venue select, date/time
inputs, and note. Sidebar entry "Schedule" after Venues, visible to all roles.

Printable schedule sheets arrive in WP-03-08.

## Live scoreboard link (Phase 8 addition)

A slot whose event has a match (`matches.event_schedule_id`) gets a "Live" column
entry — a link straight into that match's scoreboard (`/matches/{id}/scoreboard`),
with a red "Live" badge when the match currently has an in-progress or paused
session (mirrors `matches/index.tsx`'s own "Live" column). This lets a manager or
delegation officer jump from "what's on today" straight to "watch the game
happening right now" without a detour through the Matches page.

The link data is scoped exactly like `MatchController::index()`, not just readable
by "all roles" the way the rest of the schedule is: **Viewers never receive
`match_id`/`is_live` at all** (live scoring is forbidden to them regardless, so a
link that would just 403 is never shown), and a **Delegation Officer only sees it
for matches involving their own delegation's entries** — a match belonging to
another delegation shows no link, same as it would on their own Matches list.
`ScheduleController::matchesForSlots()` computes this once per page load, keyed by
`event_schedule_id`, rather than a per-row query.
