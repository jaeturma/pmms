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
