# Organization & School Registry

WP-02-02. Districts and schools of the Schools Division Office — the reference registry
later modules (delegations, athletes) build on.

## Data model

- `districts` — `name` (unique), `active`. A district has many schools.
- `schools` — `district_id` (FK, restrict on delete), `name` (unique per district),
  `school_id_code` (unique), `level` (`App\Enums\SchoolLevel`: elementary / secondary /
  integrated), optional `address`, `active`.

## Lifecycle

Records are **archived** (active flag), not deleted, once referenced:

- Districts with schools cannot be deleted — the destroy action refuses and suggests
  archiving. Empty districts may be hard-deleted.
- Schools are currently hard-deletable; the work package that first references schools
  (delegations, WP-02-05) must add the same referenced-guard.
- Archived districts disappear from the school form's district options; archived records
  stay visible in the registries with an "Archived" badge.

## Authorization

Read: any authenticated, verified user. Mutations (store/update/archive/restore/destroy):
`role:admin,organizer` middleware — see `docs/authorization.md`. The pages receive a
`canManage` flag and hide management controls for read-only roles; the middleware remains
the enforcement point.

## Audit

Every mutation is recorded via `AuditLogger`: `district.created|updated|archived|
restored|deleted` and `school.*` equivalents, with the record name in context.

## UI

`resources/js/pages/registry/districts.tsx` and `schools.tsx` — shared-component tables
with dialog forms (create/edit), ConfirmDialog for archive/restore/delete, EmptyState,
status badges, sidebar entries. Search/filter/pagination arrives phase-wide in WP-02-10;
until then the registries list all rows.

## Sample data

`SampleRegistrySeeder` (called from `DatabaseSeeder`) inserts two "Sample District — …"
districts with four "Sample …" schools, local/testing environments only — clearly
labeled, never production data.
