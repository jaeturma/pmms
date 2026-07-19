# Venue Registry

WP-03-01. Playing venues and facilities of the division — the reference registry event
scheduling (WP-03-02) builds on.

## Data model

`venues` — `name` (unique, ≤160), optional `address` (≤255), optional `notes` (≤500,
free text for facilities/capacity/contact person), `active`. Referenced by
`event_schedules` (WP-03-02) with restrict-on-delete.

## Lifecycle

Same archive-first pattern as the other registries:

- Archive/restore toggles `active`; archived venues stay visible in the registry with
  an "Archived" badge and are excluded from new schedule assignments (enforced by
  `ScheduleRequest`).
- Hard delete is guarded by `Venue::isInUse()` (any referencing `event_schedules`
  row); the controller refuses with an "archive it instead" toast, mirroring the
  district/school guards, and the FK is restrict-on-delete as a backstop.

## Authorization

Read: any authenticated, verified user. Mutations (store/update/archive/restore/
destroy): `role:admin,organizer` middleware — one row in the matrix in
`docs/authorization.md`, swept by `AuthorizationMatrixTest`. The page receives a
`canManage` flag; middleware remains the enforcement point.

## Audit

Every mutation is recorded via `AuditLogger`:
`venue.created|updated|archived|restored|deleted`, with the venue name in context.

## UI

`resources/js/pages/registry/venues.tsx` — shared-component registry table (SearchBar
over name/address, PaginationControls, dialog form, ConfirmDialog for archive/restore/
delete, EmptyState, status badges) with a sidebar entry after Meets.
