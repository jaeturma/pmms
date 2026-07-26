# Performance & Load Verification (WP-06-04)

A focused query/page-performance review at this deployment's actual scale —
a single Division's meet (Davao de Oro, 11 municipalities) on one local
server — not a generic multi-tenant load target, and not a synthetic
load-testing framework this project has no infrastructure to run
continuously (k6/JMeter-style tooling is explicitly out of scope; see the
WP).

## Result: no N+1 or missing-index issues found

Every page profiled (below) executes a small, **bounded** number of
queries that does not grow with dataset size or pagination depth — no code
changes were needed. This mirrors WP-06-01's "verification pass, no
defects found" outcome: the codebase was already built with eager loading
and pagination applied consistently (Phase 2's `SearchesAndPaginates`
trait, `->with([...])` on every index/report page), so this WP's job was
to prove that holds at realistic volume, not to retrofit it.

## Benchmark dataset

`database/seeders/PerformanceBenchmarkSeeder.php` — new, **not** registered
in `DatabaseSeeder`'s default chain (run on demand only, see below), so it
never slows a normal `db:seed`/test setup. Distinct from
`SampleProvinceDemoSeeder`, which is deliberately kept small (3 athletes)
so its own "eyeball the Division feature" demonstration stays easy to
read — this seeder exists purely to generate volume to profile against.

Unlike every other `Sample*` seeder, it attaches its delegations to the
**real** 11 Davao de Oro municipalities (`DivisionRegistrySeeder`) instead
of inventing its own sample districts — the whole point is realistic query
shape against what this deployment will actually have on meet day.
Schools/athletes/the meet itself are still `"Sample "`-prefixed so the
benchmark data is always easy to identify and was never mistaken for real
SDO data.

Scale generated (one run): **11 municipal delegations, 88 schools (8 per
municipality), 1,320 athletes (15 per school), 2,640 individual-event
entries (2 per athlete), 8 validated event results with up to 10 recorded
placements each (80 placements total)** — bounded by the sports catalog's
16 Athletics track events (4 of the 16 gender/age-division buckets got no
entries under the seeder's deterministic 2-of-3-events-per-bucket
selection; not a defect, just how the fixed track-event catalog splits).

Run it yourself: `php artisan db:seed --class=PerformanceBenchmarkSeeder`
(idempotent — safe to re-run). **Clean up afterward** so it doesn't linger
in a shared dev database: delete the `"Sample Performance Benchmark Meet"`
row and cascade (delegations → athletes/entries → event
results/placements → the `"Sample … Benchmark School …"` rows), or restore
from a WP-06-02 backup taken before seeding. This review's own benchmark
run was removed the same way after profiling — the numbers below are real
measurements, not estimates.

## Profiling method

Every controller action was called directly (bypassing HTTP/middleware
overhead, which this review isn't concerned with) as an authenticated
admin, with `DB::enableQueryLog()` around each call — the same technique
Laravel's own N+1 detection relies on, chosen over adding
`barryvdh/laravel-debugbar` as a new dependency for a one-time check (per
the WP's own preference). Query **count** is the meaningful number here —
it is what would explode under a real N+1 as row counts grow; the
millisecond timings are supplementary (single local-machine run, not a
benchmark-grade average) and included for reference only.

| Page / action | Queries | Time |
|---|---|---|
| `MedalTallyService::standings()` — no filter (all meets ever) | 5 | 15ms |
| `MedalTallyService::standings()` — one meet | 5 | 7ms |
| Medal tally page (`TallyController::index`) | 7 | 13ms |
| Dashboard (`DashboardController::index`, incl. operations widgets) | 22 | 38ms |
| Management dashboard (`ManagementDashboardController::index`) | 31 | 38ms |
| Delegation roster report — one delegation (~120 athletes) | 4 | 31ms |
| Event entry list report — one event (~220 entries) | 4 | 91ms |
| School participation summary — all 88+ schools, `withCount` | 3 | 16ms |
| Official result sheet — one validated result | 9 | 9ms |
| Medal tally report (printable) | 5 | 7ms |
| Daily schedule sheet | 1 | 1ms |
| Schools index — no search | 4 | 8ms |
| Schools index — search term | 4 | 6ms |
| Delegations index | 9 | 13ms |
| Entries index — no filter (page 1 of ~2,640 rows) | 19 | 79ms |
| Entries index — filtered to one event | 19 | 63ms |
| Entries index — search term across ~2,640 rows | 19 | 93ms |
| Entries index — page 50 (deep offset) | 19 | 63ms |
| Entries index — page 175 (last page, ~2,640 rows) | 19 | 66ms |
| Athletes index — no search (1,320 rows) | 12 | 19ms |
| Athletes index — search term | 12 | 17ms |

**The Entries index rows are the key proof**: query count stays at exactly
**19** regardless of filter, search term, or page depth (page 1 through the
last page, 175 pages deep at the fixed 15-per-page size) — confirming
`SearchesAndPaginates` is genuinely bounding the query with `LIMIT`/
`OFFSET`, not loading the full ~2,640-row table and paginating in PHP.
Every other page shows the same pattern: query count is a function of how
many *independent widgets/relations* a page has (Entries' 19 comes from
its paginated list plus three separate filter-option queries — event
options, delegation options, athlete options for its create dialog — each
bounded on its own, not from per-row queries), never of total row count.

## Index verification

Every foreign key filtered or joined on by the pages above already carries
an index, confirmed by reading the migrations directly rather than
assuming Laravel's `foreignId()->constrained()` default holds everywhere:
`entries.delegation_id`/`athlete_id`/`event_id` (plus an explicit composite
`(delegation_id, event_id)` index backing the max-entries-per-delegation
check), `athletes.delegation_id`/`school_id`, `delegations.meet_id`/
`school_id`/`district_id`, `schools.district_id`, `event_results.meet_id`/
`status`, and `result_placements.event_result_id` with a composite
`(event_result_id, rank)` index. No missing-index fix was needed.

One theoretical (not actual) observation: `MedalTallyService::standings()`
filters `result_placements` by `rank IN (1,2,3)` without also constraining
`event_result_id` in that same clause, so the composite
`(event_result_id, rank)` index can't be seeked directly for that filter —
in principle a large-enough `result_placements` table could eventually
benefit from its own `rank` index. Not fixed here: at this deployment's
scale (a handful of meets, hundreds of placements per meet — the benchmark
run above used 80), a full scan of that column is sub-millisecond on
MySQL; adding an index now would be optimizing for a row count this
single-Division deployment won't reach for years, if ever. Worth
revisiting only if a future phase adds bulk historical-meet aggregation
across many seasons at once.

## Quality gate

Pest 650/650 (3,245 assertions — unchanged; this WP is a verification
pass, no application behavior changed), Pint + PHPStan L7 + ESLint +
Prettier + tsc strict all green. No migrations added.
