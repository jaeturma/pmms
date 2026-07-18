# Sports & Events Catalog

WP-02-03. Configurable sports and competition events — the reference catalog meet setup
(WP-02-04) and entries (WP-02-08) build on. Configuration over one-off code: variation by
gender, age division, team format, and entry caps lives in data, not code.

## Data model

- `sports` — `name` (unique), `active`. A sport has many events.
- `events` — `sport_id` (FK, restrict on delete), `name` (unique per sport + gender +
  age division), `gender` (`App\Enums\GenderCategory`: boys / girls / mixed),
  `age_division` (`App\Enums\AgeDivision`: elementary / secondary), `is_team_event`,
  `max_entries_per_delegation` (1–50, enforced at entry submission in WP-02-08),
  `active`.

## Lifecycle, authorization, audit

Identical to the school registry (`docs/registry.md`): archive instead of delete once
referenced (sports with events cannot be deleted; events become guarded when meets
reference them in WP-02-04), mutations behind `role:admin,organizer`, every change
audited (`sport.*` / `event.*` actions).

## UI

`resources/js/pages/catalog/sports.tsx` and `events.tsx` — same shared-component
pattern as the registries; the event form covers sport, gender, division, team flag, and
entry cap. Sidebar entries: Sports, Events.

## Seeder

`SportsCatalogSeeder` (called from `DatabaseSeeder`, idempotent) seeds 14 sports commonly
played at DepEd provincial meets plus 16 standard athletics track events (100/200/400m
dash and 4x100m relay × boys/girls × elementary/secondary). This is genuine reference
configuration, not sample data — the SDO adjusts it through the UI. The relay is seeded
as a team event with an entry cap of 1; individual events default to 2.
