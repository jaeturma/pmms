# Coach & Official Registry

WP-02-07. Delegation personnel: coaches, assistant coaches, and chaperones.

## Data model

- `personnel` — `delegation_id` (FK restrict; delegations with personnel cannot be
  deleted), `school_id` (FK restrict — the person's own home school; same
  registration-time-only, delegation-constrained rule as athletes, see
  `docs/athletes.md` "Home school"), names, `role` (`App\Enums\PersonnelRole`),
  optional phone/email, optional photo via `FileUploadService` (same replace/cleanup
  lifecycle as athletes).
- `personnel_sport` — sport assignments for coaching roles only; syncing sports for a
  chaperone is refused, and demoting a coach to chaperone clears their assignments.
- `personnel.user_id` (nullable FK to `users`, added WP-REALIGN-05, 2026-08-02) — links
  this roster row to its own login account. Most rows have none; a login is only
  linked when a Coach account is deliberately set up. Not globally unique (a
  returning coach keeps one login across multiple meets, each with its own
  `Personnel` row), but unique per `(delegation_id, user_id)` — the same login can't
  be double-linked within one delegation. Kept out of `Fillable` on purpose — never
  settable through `PersonnelController::update()`'s general request input, only by
  trusted code. See `docs/authorization.md` and
  `docs/reports/architecture/pmms-organizational-realignment-gap-assessment.md` §10.

## Authorization & audit

Identical scoping to athletes (`PersonnelPolicy`): viewers excluded, officers and
coaches manage only their own delegation's personnel while it is an editable draft
with registration open, managers manage all — including, under a municipal
delegation, personnel attributed to any of its pooled schools (see
`docs/delegations.md` "Officer roster scope"). A Coach is scoped via
`Personnel.user_id` → `Delegation::hasCoach()`, not the `delegation_user` pivot an
officer uses. Photos served by record visibility. Audit actions:
`personnel.created|updated|sports_updated|deleted` (no per-view audit — personnel are
adults; athlete-level view auditing is a minors-data measure).

## UI

`personnel/index.tsx` — searchable, paginated registry with create/edit dialogs
(including photo upload via `_method: put` form spoofing), a sports checklist dialog for
coaching roles, and delete confirmation. Sidebar entry: Personnel.

## Out of scope (per WP)

Technical officials and officiating assignment (Phase 3), DepEd HR integration,
accreditation.

Coach login accounts (`App\Enums\UserRole::Coach`, `personnel.user_id`) were out of
scope at WP-02-07 but were built later, additively, in WP-REALIGN-05 (2026-08-02) —
see "Data model" and "Authorization & audit" above. No controller/UI exists yet for
*creating and linking* a Coach account to a roster row (that link is set by trusted
code only); once created and linked, a Coach's own day-to-day capabilities
(register athletes, upload eligibility documents, submit/withdraw entries) work
through the same `AthleteController`/`PersonnelController`/`EntryController`/
`EligibilityController` endpoints an officer already uses.
