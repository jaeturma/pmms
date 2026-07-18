# Meet Setup & Lifecycle

WP-02-04. The meet record every registration module hangs off.

## Data model

- `meets` — `name` (unique), `school_year` (`YYYY-YYYY`), `starts_at`/`ends_at` dates,
  optional `venue`, `status` (`App\Enums\MeetStatus`, default `draft`, **not** mass
  assignable — transitions only via the status endpoint).
- `meet_events` — pivot selecting which catalog events run in a meet (meet cascade,
  event restrict on delete; the events catalog refuses to delete an event a meet uses).

## Lifecycle

```
draft → registration_open → registration_closed → active → completed
                 ↑__________________|
```

Transitions are whitelisted in `MeetStatus::allowedTransitions()` — the single source of
truth; the controller rejects anything else and the UI only renders allowed actions
(labels from `MeetStatus::actionLabel()`). One pragmatic loop: closed registration may
reopen, because SDO deadline extensions are routine. Every transition is audited with
`from`/`to` context (`meet.status_changed`). Only draft meets can be deleted.

## Registration-window hook

`$meet->isRegistrationOpen()` — the delegation (WP-02-05) and entry (WP-02-08) modules
must consult this before accepting registrations or entries.

## Authorization & audit

Same pattern as the registries: reads for all authenticated users; store/update/status/
events/destroy behind `role:admin,organizer`; audit actions `meet.created|updated|
status_changed|events_updated|deleted`.

## UI

`resources/js/pages/meets/index.tsx` — meet table with status badges, create/edit
dialog, per-meet event checklist dialog (syncs the pivot), transition buttons with
ConfirmDialogs, draft-only delete. Sidebar entry: Meets. The dashboard shows a
current-meet card (latest non-completed meet — real data, no placeholders).
