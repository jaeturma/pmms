# DdOPAA 2025 Demo Data Guide

How to seed the DdOPAA 2025 reference dataset in a local dev environment,
at any of the three tiers WP5 wired up, and how to reset safely. All
commands below are `local`/`testing` only — every seeder involved opens
with `if (! app()->environment(['local', 'testing'])) return;` and
refuses to run anywhere else (proven, not just asserted by convention,
in `tests/Feature/DdopaaReferenceDatasetTest.php`).

**This is seed data only.** None of these commands touch production,
run a destructive database command, or delete existing rows — every
seeder here uses `firstOrCreate`/`firstOrNew`, never `migrate:fresh`.

## The three tiers

| Tier | Command | What you get |
|---|---|---|
| **Demo** | `php artisan db:seed --class=DdopaaDemoSeeder` | The meet, venue, and sports catalog (WP1) plus a small, quick-to-eyeball roster: 3 municipalities × 6 athletes = 18 athletes, 3 schools, up to 18 confirmed entries. No results, no live-scoring samples. Good for a fast UI walkthrough of the roster/entry pages. |
| **Standard** | `php artisan db:seed --class=DdopaaStandardTierSeeder` | The full realistic dataset: WP1 (catalog) → WP2 (531 athletes across 177 schools, 11 delegations) → WP3 (14 validated results, 69 placements) → WP4 (9 live-scoring sample matches). This is what the initiative's own visual checkpoints (Delegations/Schools/Athletes pages, Medal Tally, Schedule's "Live" column) are meant to be checked against. |
| **Load-test** | `php artisan db:seed --class=PerformanceBenchmarkSeeder` | WP-06-04's existing full-scale benchmark dataset, reused unchanged: 11 real municipalities, 88 schools, 1,320 athletes, under its own "Sample Performance Benchmark Meet" — for query/page-performance profiling, not for resembling the real DdOPAA meet. See `docs/performance.md`. |

Each is its own command — there is no single seeder with a tier flag,
matching the request's own requirement to keep the three tiers separate
and clearly named.

## Expected record counts (standard tier, freshly seeded)

| Table | Count |
|---|---|
| Delegations | 11 |
| Schools | 177 |
| Athletes | 531 |
| Confirmed entries | 314 |
| Validated results | 14 |
| Result placements | 69 |
| Live-scoring matches | 9 |

Layering the demo tier on top adds 3 schools, 18 athletes, and up to 18
more confirmed entries (332 total) — the demo tier's athletes use their
own `942xxx` LRN/school-code range, and the standard tier's use `941xxx`,
so the two never collide even if both are seeded in the same database.

## Running more than one tier in the same database

Safe and additive — every seeder is idempotent
(`firstOrCreate`/`firstOrNew` throughout) and none of them delete
anything. A realistic sequence:

```
php artisan db:seed --class=DdopaaStandardTierSeeder
php artisan db:seed --class=DdopaaDemoSeeder
```

Running any command a second time (even the whole standard tier again)
reproduces the same counts — proven directly in
`tests/Feature/DdopaaReferenceDatasetTest.php`, which runs each tier
twice and asserts identical row counts both times.

## Resetting the DdOPAA data

There is no dedicated "unseed" command — this is intentionally the same
posture as every other sample seeder in this project. To start over
locally, clear just the DdOPAA-scoped rows (never `migrate:fresh`, never
touch anything outside this initiative's own data) in FK-safe order:

1. `ScoreEvent` → `ScoringSession` → `EventMatch` → `EventSchedule` (for
   matches/schedules under the "DdOPAA Meet 2025" meet)
2. `ResultPlacement` → `EventResult` (for that meet)
3. `Entry` → `Personnel` → `Athlete` (for that meet's delegations)
4. `School` (filter by `school_id_code like '941%'` or `'942%'` — never
   delete schools by municipality alone, since `PerformanceBenchmarkSeeder`
   also creates schools under the same real districts)
5. `Delegation` (for that meet)

Then re-run whichever tier command you want. The meet, venues, and
sports catalog (WP1) are left in place across a reset like this — they
are meant to be permanent, idempotent foundations that every tier builds
on, the same way `DivisionRegistrySeeder`/`SportsCatalogSeeder` are.

## What each seeder needs to already exist

- `DdopaaReferenceSeeder` (WP1): only the real 11 `District` rows
  (`DivisionRegistrySeeder`) and the sports catalog
  (`SportsCatalogSeeder`) — both already run by default in
  `DatabaseSeeder`.
- `DdopaaStandardSeeder` (WP2), `DdopaaResultsSeeder` (WP3),
  `DdopaaLiveScoringSeeder` (WP4): the DdOPAA meet must already exist
  (WP1); WP3 and WP4 additionally need at least one delegation to exist
  (WP2, or the smaller `DdopaaDemoSeeder`). Each returns early,
  idempotently, if its prerequisite is missing — nothing errors, it just
  does nothing.
- `DdopaaDemoSeeder`, `DdopaaStandardTierSeeder`: self-contained — each
  calls `DdopaaReferenceSeeder` itself first.
- `PerformanceBenchmarkSeeder`: self-contained — calls
  `DivisionRegistrySeeder`/`SportsCatalogSeeder` itself first.

## Automated proof

`tests/Feature/DdopaaReferenceDatasetTest.php` (WP6) is the automated
version of everything above: municipality/delegation integrity, medal
tally reconciliation, live-scoring's non-interference with
`EventResult`, idempotency of all three tier commands, and the
environment guard on all 6 seeder classes. Run it directly with:

```
php artisan test --filter=DdopaaReferenceDatasetTest
```
