# Medal Tally & Rankings

WP-03-06. Medal standings per district/municipality and per school, computed
**only from validated results** (`event_results.status = validated`), per
DESIGN-NOTES. District/municipality standings are the official verdict for a
meet; school standings are a secondary reference showing which school each
medal came from — there is no separate school-level "winner."

## Derivation

`App\Services\MedalTallyService::standings(?meetId, ?sportId)` derives the tally
**at read time** — there is no stored tally table to drift out of sync, so a
correction to a validated result (which reopens it to encoded) removes its
medals from the tally automatically, and re-validation restores them.

- Rank 1 → gold, rank 2 → silver, rank 3 → bronze; ranks above 3 are ignored.
- Tied ranks (the `is_tie` flag from WP-03-05) each count — shared medals.
- Ordering is conventional: gold, then silver, then bronze, then name.
- District standings sum their schools' medals.
- Filters: per meet and per sport (via the placement's entry → event → sport).

## Division initiative: school-level grouping

Standings are grouped by `placement.entry.athlete.school_id` — the placed
athlete's own home school — never the delegation's. This is what makes a
municipal (Province) delegation work correctly: one delegation can pool
athletes from several schools, and each school still gets its own row with
its own medal count; the district/municipality rollup (unchanged mechanism —
it sums the already-computed school rows by district name) then correctly
totals them back into one municipality row. Under a City deployment this is
behaviorally identical to grouping by the delegation's school, since there
delegation and school are always 1:1. See `docs/delegations.md`.

## Authorization

Aggregates only — non-sensitive. The tally page is readable by **every
authenticated role**; there are no mutations and no audit requirement.

## UI

`resources/js/pages/tally/index.tsx` (sidebar "Medal tally", all roles) —
district/municipality standings shown first as the official standings, with
school standings below as a reference-only table (column/section labels
follow `division.areaLabel`), with meet and sport filters. The printable
tally report (`reports/medal-tally.tsx` + CSV — district rows before school
rows) and the public portal (`public/tally.tsx`) and the dashboard's "Medal
tally — top five" widget (`DashboardController::operations()`, district-based)
share the same area-label-aware, district-first treatment.
