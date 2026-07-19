# Medal Tally & Rankings

WP-03-06. Medal standings per school and per district, computed **only from
validated results** (`event_results.status = validated`), per DESIGN-NOTES.

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

## Authorization

Aggregates only — non-sensitive. The tally page is readable by **every
authenticated role**; there are no mutations and no audit requirement.

## UI

`resources/js/pages/tally/index.tsx` (sidebar "Medal tally", all roles) — school
and district standings tables on shared components with meet and sport filters.
The printable tally report ships with the operations reports in WP-03-08; the
public portal is Phase 4.
