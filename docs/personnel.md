# Coach & Official Registry

WP-02-07. Delegation personnel: coaches, assistant coaches, and chaperones.

## Data model

- `personnel` — `delegation_id` (FK restrict; delegations with personnel cannot be
  deleted), names, `role` (`App\Enums\PersonnelRole`), optional phone/email, optional
  photo via `FileUploadService` (same replace/cleanup lifecycle as athletes).
- `personnel_sport` — sport assignments for coaching roles only; syncing sports for a
  chaperone is refused, and demoting a coach to chaperone clears their assignments.

## Authorization & audit

Identical scoping to athletes (`PersonnelPolicy`): viewers excluded, officers manage
only their own delegation's personnel while it is an editable draft with registration
open, managers manage all. Photos served by record visibility. Audit actions:
`personnel.created|updated|sports_updated|deleted` (no per-view audit — personnel are
adults; athlete-level view auditing is a minors-data measure).

## UI

`personnel/index.tsx` — searchable, paginated registry with create/edit dialogs
(including photo upload via `_method: put` form spoofing), a sports checklist dialog for
coaching roles, and delete confirmation. Sidebar entry: Personnel.

## Out of scope (per WP)

Technical officials and officiating assignment (Phase 3), DepEd HR integration,
accreditation, user accounts for coaches.
