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
  "Tigers") for banners/ID cards/public portal. Seeded where known, editable
  via the district/municipality registry screen otherwise.

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
idempotent via `firstOrCreate`, unlike the local/testing-only
`SampleRegistrySeeder`): a Province division named "Davao de Oro" and its 11
municipalities (Compostela, Laak, Mabini, Maco, Maragusan, Mawab, Monkayo,
Montevista, Nabunturan, New Bataan, Pantukan) as `District` rows. Only
Maco's nickname ("Tigers") is confirmed; the rest are left blank for the
admin to fill in via the registry screen.

## Division initiative — complete (WP1–WP7)

Municipal delegation registration (`docs/delegations.md` "Registering unit:
School or Municipality"), athlete/personnel home-school attribution
(`docs/athletes.md` "Home school"), and the medal tally's per-school grouping
(`docs/medal-tally.md`) are all built and re-keyed end-to-end: a Province
deployment's municipal delegation pools multiple schools, and every module —
entries, matches, results, reports, ID cards, the tally, the public portal —
correctly attributes an individual to their own school while the delegation
itself is still identified by its municipality. Demonstrated with seed data
via `SampleProvinceDemoSeeder` (WP6). The officer-sees-whole-municipal-roster
authorization consequence is reviewed and documented as accepted/intended —
see `docs/delegations.md` "Officer roster scope" (WP7).

## Open item: City's "district competes" option

`DivisionType::City` is a valid, selectable enum case and the schema
(`delegations.district_id`) is ready for it, but City's own registration
choice — "schools *or* districts compete" — has no dedicated UI/validation
built; a City deployment today can only register school-rooted delegations
(the same behavior as before this initiative). This was described by the
product owner as a future option, not a specified requirement for the
current Davao de Oro (Province) deployment, and is deliberately not guessed
at. Revisit if/when a City deployment is actually needed.
