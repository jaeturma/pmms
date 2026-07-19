# Tournament & Match Management

WP-03-04. Matches and heats per meet event, created and managed manually by managers —
no bracket generation or seeding automation, per MVP scope.

## Data model

- `matches` — `meet_id` (FK, cascade), `event_id` (FK, restrict), optional
  `event_schedule_id` (FK, null on delete; must belong to the same meet+event),
  `round_label` (e.g. "Heat 1", "Semifinal"), `sequence` (ordering within the
  event), `status`. The model is `App\Models\EventMatch` — `Match` is a PHP
  reserved word.
- `match_entries` — pivot to `entries`, unique per (match, entry) so an entry
  appears at most once per match. `entry_id` is restrict-on-delete, and
  `EntryController::destroy` refuses to delete entries that took part in a match.

## Status flow

`App\Enums\MatchStatus`: **scheduled → completed | walkover | cancelled**.
Scheduled is the only mutable state; the other three are terminal. Completed
matches become the anchor for results in WP-03-05. All transitions are audited
(`match.status_changed` with from/to).

## Participants (server-enforced)

- Drawn only from the event's **confirmed** entries of the same meet.
- One appearance per match (unique pivot); team events allow one entry per school
  per match, individual events allow several from the same school.
- Participants can only be changed while the match is scheduled.
- Replaced via `PUT /matches/{match}/participants` (sync semantics), audited
  `match.participants_updated`.

## Authorization

Mirrors entry visibility: managers see and manage everything
(`role:admin,organizer` for all mutations); delegation officers get read access
to matches involving their delegation only; viewers have none (the list reuses
`EntryPolicy::viewAny` — matches carry athlete names). See the matrix in
`docs/authorization.md`.

## Audit

`match.created|updated|deleted|status_changed|participants_updated` via
`AuditLogger`, with meet, event, round, and sequence in context.

## UI

`resources/js/pages/matches/index.tsx` — match list (sidebar entry after
Schedule) filterable by meet and event, showing round/sequence, linked schedule
slot, participant names with schools, and status. Managers get: match dialog with
dependent meet → event → slot selects, a participants dialog (checkbox list of
that event's confirmed entries), status ConfirmDialogs (Complete / Declare
walkover / Cancel), and delete.
