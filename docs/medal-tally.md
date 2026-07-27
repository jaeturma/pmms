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

## Points (WP-08-05, display-only)

`MedalTallyService` also computes a **Gold=3/Silver=2/Bronze=1** weighted
`points` value per district/municipality row (`districts` only, not
`schools`) — real, derived from the same gold/silver/bronze counts, never
stored. **Points never change the official rank order** — `ordered()`'s
conventional gold-then-silver-then-bronze-then-name sort (above) is
untouched; `points` is shown as an extra column and powers a separate
"Top by points" widget, which is its own explicitly-labeled secondary cut
of the same data, not an alternate "rank." A district with more gold
medals always outranks one with more total points but fewer gold medals.

## Medals by sport and recent activity (WP-08-05)

- `MedalTallyService::medalsBySport()` — the same validated/filtered
  placement set as `standings()`, grouped by `entry.event.sport.name`
  instead of school/district. Ordered by total descending, then name.
- `MedalTallyService::recentMedals()` — gold/silver/bronze/total awarded
  in the last 24 hours (`event_results.validated_at >= now()->subHours(24)`),
  real and computed at read time like everything else here — not a
  stored snapshot, so it needs no cleanup job.
- Both accept the same `$meetId`/`$sportId`/`$ageDivision` filters as
  `standings()`.

## Age division filter (WP-08-05)

`standings()`, `medalsBySport()`, and `recentMedals()` all accept an
optional `$ageDivision` (`App\Enums\AgeDivision` value: `elementary` or
`secondary`), filtering placements by `entry.event.age_division`. An
unrecognized value from the query string is silently ignored
(`AgeDivision::tryFrom()` returns `null`), same defensive pattern as the
eligibility review queue's status filter.

## School standings' District column

`standings()`'s school rows carry two area fields: `municipality` (always
the school's district/municipality name — the grouping key the
municipality-level rollup above sums by, unchanged) and `district` (the
value the "School standings" table's District column actually renders).
`district` shows the school's specific `school_districts` row (e.g. "Laak
North") only when **both** are true: the school has one assigned
(`school_district_id` is set) **and** its municipality has more than one
active school district registered. Otherwise `district` falls back to the
municipality's own name — the common case today, since most municipalities
have zero school districts registered (see `docs/division.md`). This
disambiguation only affects the School standings column; the "Overall
ranking by {areaLabel}" table above it is unaffected — it still rolls up by
municipality exactly as before. The interactive tally pages
(`tally/index.tsx`, `public/tally.tsx`) and the printable report
(`reports/medal-tally.tsx`) all label the School standings column literally
"District", not `{areaLabel}`, since it's no longer always the same thing
as the municipality-level ranking's area. The CSV export
(`ReportController::downloadTallyReport()`) is the one exception — its
district and school rows share a single `$areaLabel`-headed column (a
flat-CSV structural constraint, not a data bug), so a school row under a
split municipality shows its finer district name (e.g. "Laak North") under
a column literally headed "Municipality".

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
district/municipality standings shown first as the official standings
(column/section labels follow `division.areaLabel`), with school standings
below as a reference-only table, with meet, sport, and (WP-08-05) age
division filters. The school table's own area column is always literally
labeled "District" (not area-label-driven, unlike the ranking table above
it) — see "School standings' District column" above. The printable tally report (`reports/medal-tally.tsx` + CSV —
district rows before school rows) and the dashboard's "Medal tally — top
five" widget (`DashboardController::operations()`, district-based) share the
same area-label-aware, district-first treatment, but not WP-08-05's widgets
— those remain internal-admin-only.

**The public portal (WP-08-08) now shares WP-08-05's widgets** (points
column, medal-distribution donut, top-by-points, medals-by-sport, the same
summary `StatCard`s with a 24-hour delta) — `PortalController::tally()`
calls the exact same `MedalTallyService::medalsBySport()`/`recentMedals()`
methods and the same `$ageDivision` filter as the internal page, since none
of this data is privacy-sensitive (aggregates only, same as the rest of the
tally). The five widget components themselves
(`MedalDistributionCard`/`TopByPointsCard`/`MedalsBySportCard`/
`MedalCells`+`MedalHeader`/`RankBadge`) are shared, purely presentational
components under `resources/js/components/` — extracted on their second use
(WP-08-08) so the internal and public pages can't drift apart, the same
"shared rendering, independent props" pattern `live-score-display.tsx`
already established for live scoring
(`PortalController` still builds its own minimal, public-safe prop array;
only the widget rendering is shared, never a prop set). `public/tally.tsx`
also adopts the branded `PublicPageHero` band (WP-08-07) for its title.

WP-08-05 added, matching `admin-medal-tally.png` (the reference this WP's
own doc pointed to the wrong image list for — same templated-doc issue
WP-08-01/02/03/04 already found, see their completion reports):

- Four summary `StatCard`s (Total Gold/Silver/Bronze/Medals) with a real
  "+N in the last 24 hours" delta from `recentMedals()` (omitted when 0,
  not shown as "+0").
- A CSS conic-gradient "Medal distribution" donut (gold/silver/bronze
  share of the total) — no charting library added, same "isolate new
  dependencies" caution as every prior phase; built the same way
  WP-08-04's events-overview bar was.
- `RankBadge` (colored circle, top-3 medal-toned) on the standings table,
  extracted from `dashboard.tsx` into `components/rank-badge.tsx` so both
  pages share one implementation instead of two copies drifting apart.
- A "Points" column on the district/municipality table and a "Top by
  points" widget (see above — display-only, doesn't reorder the table).
- A "Medals by sport" table.
- An `Alert` banner restating that the tally updates in real time as
  results are validated.
- "Export report" (the pre-existing CSV download route,
  `reports.tally.download`, not previously linked from this page) added
  next to the existing "Printable report" link.
