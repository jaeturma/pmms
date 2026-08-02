# Division Settings

The division's type determines who registers and competes as a delegation:
schools (or, in a future release, districts) in a **City** division;
**municipalities** in a **Province** division. Either way, individual
school-level standings remain visible — see `docs/medal-tally.md`.

This deployment defaults to **Province — Davao de Oro**, with its 11
municipalities seeded as `District` rows.

## Data model

- `divisions` — a single settings row, `type` (`App\Enums\DivisionType`:
  `city`|`province`) + `name`. Accessed via `Division::current()`, which
  creates a Province default on first access so the app always has one row
  (`firstOrCreate`, mirrors `AdminUserSeeder`'s idempotent-upsert pattern).
- **No new `Municipality` table.** A Province division's municipalities are
  the existing `districts` table — same model, same schools/tally rollup,
  only the *rendered label* changes (`Division::areaLabel()`: "District" for
  City, "Municipality" for Province). Code, relations, and audit log keys
  always say `District`; never "Municipality."
- `districts.nickname` (nullable) — a delegation's nickname (e.g. Maco →
  "Power Voltz") for banners/ID cards/public portal. All 11 municipalities'
  nicknames are now confirmed and seeded by `DivisionRegistrySeeder`;
  further edits happen via the district/municipality registry screen.
- `districts.congressional_district` (nullable string) — the municipality's
  legislative district ("First" or "Second"), seeded alongside the
  nickname. Reference data only — not currently surfaced in the registry
  UI or used by any grouping/authorization logic.
- `congressional_districts` (`First`, `Second`) + `districts.congressional_district_id`
  (nullable FK) — the real `CongressionalDistrict → has many District`
  relationship (`App\Models\CongressionalDistrict::districts()`,
  `District::congressionalDistrict()`), added
  2026-08-02 as WP-REALIGN-01 of the DdOPAA organizational realignment
  (`docs/reports/architecture/pmms-organizational-realignment-gap-assessment.md`).
  Deliberately **not** named `District` (that already means municipality
  throughout this codebase) — same naming discipline `SchoolDistrict`
  already established. Coexists with the plain `congressional_district`
  string column rather than replacing it; both are synced together by
  `DivisionRegistrySeeder` — see
  `docs/architecture/pmms-data-migration-plan.md` §6-7 for the
  coexistence/eventual-cutover plan. No registry UI yet.
- `school_districts` — the real DepEd school-district sub-unit (e.g. "Laak
  North"), a level *below* `districts`/municipality, added for the medal
  tally's "School standings" District column and the public landing page.
  This is still **not** a new `Municipality` table and doesn't reopen the
  decision above: `school_districts.district_id` points at the existing
  `districts` row it belongs to (the FK is named `district_id`, meaning
  "municipality," for the same reason `schools.district_id`/
  `delegations.district_id` already do), and delegation registration is
  completely untouched — a delegation still registers at the municipality
  (`districts`) level regardless of how many school districts it has.
  `schools.school_district_id` (nullable) is the one new link: a school may
  optionally belong to a specific school district within its municipality.
  A municipality with a single DepEd district (Mabini, Mawab, Montevista,
  New Bataan) gets no `SchoolDistrict` row at all — standings fall back to
  its own name. The other seven (Compostela, Laak, Maco, Maragusan,
  Monkayo, Nabunturan, Pantukan) are seeded with their real East/West or
  North/South split by `DivisionRegistrySeeder`; further changes happen via
  the districts registry (`registry/school-districts.tsx`,
  `SchoolDistrictController`). See `docs/medal-tally.md` for how the
  tally's District column uses this.

## Type lock

The type is foundational to how delegations register (Phase: municipal
delegations — see the follow-up work package). Once any delegation exists,
`Division::typeIsLocked()` is true and the type can no longer be changed —
enforced in `DivisionRequest` (the `type` validation rule is omitted
entirely when locked, so a submitted value is silently ignored rather than
erroring) and surfaced in the settings UI. Changing the type after
delegations exist would orphan or misclassify existing registrations.

## Sharing to the frontend

`HandleInertiaRequests::share()` includes a `division` prop
(`{ type, name, areaLabel }`) on every page — read, not admin-gated,
since any page may need to render the area label (e.g. "Municipality"
instead of "District" in the districts/municipalities registry and sidebar
nav). Editing is separate and admin-only.

## Authorization

Read: shared to every authenticated (and guest) page via Inertia. Edit:
`/division` is gated `can:administer` (admin-only), matching the audit log's
sensitivity — this is app-wide configuration, not routine registry data.

## Audit

`division.updated`, with the name and a `type: {from, to}` context — see
`docs/audit-trail.md`.

## UI

`resources/js/pages/division/edit.tsx` — a single settings form (name always
editable, type editable only when unlocked, with an explanatory alert when
locked). Sidebar entry "Division settings" in the admin-only nav group.
The district/municipality registry page (`registry/districts.tsx`) and its
sidebar nav label are area-label-aware throughout (title, empty state,
dialog copy, confirm dialogs) via the shared `division.areaLabel` prop and
the `pluralizeAreaLabel()` helper in `resources/js/lib/utils.ts`.

## Seeding

`database/seeders/DivisionRegistrySeeder.php` — the **real default
configuration** for this deployment (unconditional, all environments,
idempotent): a Province division named "Davao de Oro" and its 11
municipalities (Compostela, Laak, Mabini, Maco, Maragusan, Mawab, Monkayo,
Montevista, Nabunturan, New Bataan, Pantukan) as `District` rows, each with
a confirmed nickname and congressional district. Municipality *creation*
is create-only (an admin's archive/edit choices are never undone by
re-seeding), but nickname/congressional_district are treated as
authoritative facts and re-synced on every run.

## Division initiative — complete (WP1–WP7)

Municipal delegation registration (`docs/delegations.md` "Registering unit:
School or Municipality"), athlete/personnel home-school attribution
(`docs/athletes.md` "Home school"), and the medal tally's per-school grouping
(`docs/medal-tally.md`) are all built and re-keyed end-to-end: a Province
deployment's municipal delegation pools multiple schools, and every module —
entries, matches, results, reports, ID cards, the tally, the public portal —
correctly attributes an individual to their own school while the delegation
itself is still identified by its municipality. Demonstrated with seed data
during development (WP6, seeder since removed as sample/demo cleanup — see
`Ddopaa2026ShowcaseSeeder` for the dataset that remains). The officer-sees-whole-municipal-roster
authorization consequence is reviewed and documented as accepted/intended —
see `docs/delegations.md` "Officer roster scope" (WP7).

## School districts: confirmed vs. single-district municipalities

Seven municipalities have a confirmed East/West or North/South DepEd split,
seeded by `DivisionRegistrySeeder`: Compostela, Laak, Maco, Maragusan,
Monkayo, Nabunturan, Pantukan. The remaining four (Mabini, Mawab,
Montevista, New Bataan) have a single DepEd district and deliberately get
no `SchoolDistrict` row — a municipality with zero or one school district
shows its own name in the tally's District column; nothing needs to change
there if one of these four is later confirmed to split.

## Open item: City's "district competes" option

`DivisionType::City` is a valid, selectable enum case and the schema
(`delegations.district_id`) is ready for it, but City's own registration
choice — "schools *or* districts compete" — has no dedicated UI/validation
built; a City deployment today can only register school-rooted delegations
(the same behavior as before this initiative). This was described by the
product owner as a future option, not a specified requirement for the
current Davao de Oro (Province) deployment, and is deliberately not guessed
at. Revisit if/when a City deployment is actually needed.
