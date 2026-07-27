# DdOPAA 2025 Reference Dataset — Design Notes

## Classification convention used throughout every WP

Every seeder method's doc comment states which of the four classes its
records belong to — this is the *entire* provenance mechanism (owner
decision: documentation-only, no schema changes):

- `VERIFIED_OFFICIAL` — not used anywhere in this dataset. Nothing
  cleared this bar (see the source register).
- `PARTIALLY_VERIFIED` — the short corroborated list in `README.md`'s
  Grounding section only: meet date/venue/host, the 11 municipalities'
  participation, the sport list, the five real delegation nicknames, and
  the handful of team-level event outcomes. Used sparingly and only where
  it directly matches a register entry.
- `SYNTHETIC_DERIVED` — realistic records generated to complete a
  structure a verified fact only partially describes (e.g. a full
  32-school roster when only "11 municipalities participated" is
  verified; a full bracket when only the eventual winner is known).
- `SYNTHETIC_DEMO` — pure demonstration data with no reference basis at
  all (most individual athletes, most schools, most matches).

## WP1 — Meet, Venue & Sports Catalog Setup

- New `Meet`: name "DdOPAA Meet 2025," `school_year` "2024-2025,"
  `starts_at`/`ends_at` bracketing January 17, 2025 (`PARTIALLY_VERIFIED`
  start date; end date `SYNTHETIC_DERIVED` — multi-day meets of this
  scale typically run about a week, but no verified closing date exists).
  Published + Active, matching how `SampleProvinceDemoSeeder` treats its
  own demo meet.
- New `Venue`: "Maragusan Grandstand Arena" (`PARTIALLY_VERIFIED` name).
  Additional venues for other sports (gym, pool, etc.) are
  `SYNTHETIC_DEMO` — no source names any of them.
- District nicknames: update only the five real ones found
  (`District::firstOrCreate`'s existing idempotent pattern from
  `DivisionRegistrySeeder` — this WP only sets `nickname` on rows that
  already exist from that seeder, never creates new District rows for
  the real 11). The other six municipalities keep whatever nickname they
  already have (usually none) — **never invent a nickname for them**.
- Sports/events: confirm `SportsCatalogSeeder`'s existing Basketball,
  Volleyball, Swimming, Athletics, Gymnastics are present (they already
  are, per Phase 2's catalog); add Boxing as a `Sport` row if not already
  present (it's a supported live-scoring board type per Phase 7 but was
  never added to the seeded catalog list). Add a 3x3 Basketball event
  variant under the existing Basketball sport (`PARTIALLY_VERIFIED` sport
  choice, `SYNTHETIC_DERIVED` specific gender/age-division breakdown,
  since the source only confirms "3x3 Basketball (Boys)" won by one
  team, not the full event program).
- Attach all catalog events actually used by this dataset to the DdOPAA
  meet via `$meet->events()->syncWithoutDetaching(...)`, mirroring every
  existing seeder's own pattern.

## WP2 — Standard Dataset

- Register all 11 real municipalities as approved delegations for the
  DdOPAA meet (`Delegation::firstOrCreate` keyed on `meet_id`+
  `district_id`, mirroring `PerformanceBenchmarkSeeder`'s exact pattern —
  attach to the *real* District rows this time, not invented sample
  districts, since this dataset's whole point is resembling the real
  province).
- Schools: 10–25 per municipality (request's own suggested range),
  `SYNTHETIC_DERIVED` names following real Philippine DepEd naming
  conventions (e.g. "{Municipality} National High School," "{Municipality}
  Central Elementary School," numbered "{Municipality} Elementary School
  {N}") — **explicitly not claimed as an actual roster of real schools**,
  since no verified school list was found for any municipality. Said
  plainly in `ddopaa-2025-data-limitations.md` (WP7).
- Athletes/personnel: 500–700 synthetic athletes total (request's
  "standard" tier), synthetic Filipino names, distributed across
  municipalities/schools, entries into the events attached in WP1. No
  real names anywhere (confirmed exclusion, see Grounding).
- A handful of athletes/entries are deliberately associated with the five
  `PARTIALLY_VERIFIED` delegation nicknames' municipalities (Nabunturan,
  Montevista, New Bataan, Mawab, Maragusan) so WP3's results can
  plausibly echo the real team-level outcomes without claiming
  individual-athlete accuracy.

## WP3 — Results & Medal Tally Reconciliation

- Uses the **existing, unmodified** encode→validate flow
  (`docs/results.md`) — this initiative adds data through it, never
  bypasses or duplicates it. No hardcoded medal tally table; `Medal
  Award Created` in the request's own required flow *is*
  `MedalTallyService::standings()`'s existing derive-at-read-time
  behavior — there is no separate "medal award" record to create, by
  design (`docs/medal-tally.md`), and this WP does not invent one.
- For the five events where a real winner is known (row 4–6 of the
  source register), the corresponding delegation's entry is placed rank
  1 — `PARTIALLY_VERIFIED` winner, `SYNTHETIC_DERIVED` full placement
  list beneath it (silver/bronze aren't verified for any of these).
- Every other event result is `SYNTHETIC_DEMO`, generated to be
  internally consistent (no duplicate ranks without ties, only confirmed
  entries placed, respects each event's `max_entries_per_delegation`).
- Reconciliation tests (WP6) prove: every gold/silver/bronze in the
  tally traces to a validated `ResultPlacement`; no medal exists without
  one; municipality totals equal the sum of their schools' totals
  (already guaranteed by `MedalTallyService`'s existing mechanism, but
  proven here against this dataset's actual scale, not just unit-tested
  against small fixtures).

## WP4 — Live Scoring Samples

Three sports, three states each, using `ScoringSession`/`ScoreEvent`
exactly as Phase 7 built them — no new columns, no new states:

- **Basketball**: one `Scheduled` match (no session yet), one
  `in_progress` session (running score, `sport_state.fouls_a/b`, a
  `period_label` like "Q3"), one `ended` session on a `Completed` match.
- **Boxing**: one `Scheduled` match, one `in_progress` session with a
  partial `sport_state.rounds` history, one `ended` session with a full
  round history summed into the final score.
- **Softball/Baseball**: one `Scheduled` match, one `in_progress` session
  with partial `sport_state` (inning/half/outs/balls/strikes/innings), one
  `ended` session with a complete innings breakdown.

All `SYNTHETIC_DEMO` — no source has any DdOPAA 2025 boxing or softball
data at all, and the basketball fragments found (team names, no scores)
aren't specific enough to seed a real score from. Every session stays
what it already structurally is: provisional, never touching
`EventResult`/`ResultPlacement` (Phase 7's own core guarantee, unmodified
here). This reuses the Schedule page's live-link column added the session
before this initiative (`docs/scheduling.md` "Live scoreboard link").

## WP5 — Demo & Load-Test Tier Wiring

- **Demo tier**: either a small addition to `SampleProvinceDemoSeeder` or
  a new, equally small `DdopaaDemoSeeder` — a handful of DdOPAA-flavored
  records for a quick UI walkthrough, same scale as the existing sample
  data (a few athletes, one or two events), not the full 500–700 tier.
- **Standard tier**: WP2's output, run via its own artisan command,
  additive to whatever's already in the local database (never
  `migrate:fresh`).
- **Load-test tier**: `PerformanceBenchmarkSeeder` (WP-06-04) already
  provides this at realistic scale — reused as-is, not duplicated.
  Optionally themed to DdOPAA nicknames if that's wanted, but functionally
  unchanged.
- Three separate, clearly-named artisan commands/seeder classes per the
  request's own Part 12 requirement — never one seeder that tries to be
  all three tiers via a flag.

## WP6 — Testing & Seeding Safety

Automated tests (new, added to the existing Pest suite, no new testing
framework):

- All 11 municipality delegations exist and are approved for the DdOPAA
  meet.
- Every school belongs to exactly one municipality; no cross-municipality
  school assignment (a school's `district_id` never changes after
  seeding).
- Every athlete's `school_id` belongs to a school within their own
  delegation's registering municipality (already an app-level invariant
  enforced by `AthleteRequest::withValidator()` — this WP's test proves
  the *seeded* data actually satisfies it, not just that the rule
  exists).
- No duplicate active (non-withdrawn) delegation per municipality per
  meet (the existing `unique(meet_id, district_id)` constraint already
  guarantees this at the DB level — test proves the seeder never
  violates it, i.e. is truly idempotent).
- Medal tally reconciliation: every medal traces to a validated
  placement; municipality totals equal their schools' summed totals.
- Only validated results affect the tally; encoded-but-unvalidated
  results seeded (if any) do not appear in it.
- Live scores never appear in `EventResult`/`ResultPlacement` — reuses
  Phase 7's own existing assertion pattern, proven again at this
  dataset's scale.
- Seeder idempotency: running any of the three tier seeders twice
  produces identical row counts the second time (no duplicates).
- Production seeding protection: every new seeder opens with the same
  `if (! app()->environment(['local', 'testing'])) return;` guard every
  existing sample seeder already uses — a test asserts this explicitly
  for each new seeder class, not just by convention.

## WP7 — Documentation & Completion Review

- `docs/data-reference/ddopaa-2025-reference-data.md` — what's actually
  in the dataset, organized by classification.
- `docs/data-reference/ddopaa-2025-data-limitations.md` — the honest
  version of "what this dataset is not," expanding the source register's
  own "What this register does NOT support" section into a standalone
  reference.
- `docs/testing/ddopaa-2025-demo-data-guide.md` — how to run each tier's
  seeder, expected record counts, how to reset/re-seed safely.
- `docs/reports/ddopaa-2025-seed-data-completion.md` — the completion
  report, same evidence-based format every phase's final WP produces.
- Full quality gate (Pint, PHPStan, Pest, ESLint, Prettier, tsc, build)
  and a closing verdict, mirroring every phase's own WP-closing pattern.
