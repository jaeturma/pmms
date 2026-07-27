# WP3 — Results & Medal Tally Reconciliation

## Purpose
Give the dataset validated results and a resulting medal tally, entirely
through the app's existing encode→validate flow and `MedalTallyService` —
no hardcoded tally, no bypass of the real result-integrity pipeline.

## Tasks
- Inspect the repository before making changes.
- Implement only the scope of this work package.
- Preserve architecture and coding standards.
- Update documentation where necessary.
- Run backend and frontend quality checks.
- Do not commit or push changes.

## Scope
- For the events where a real winner is corroborated (3x3 Basketball
  Boys — Montevista; Women's Volleyball — Nabunturan; Men's Artistic
  Gymnastics — New Bataan; Boxing — Nabunturan; a Volleyball semifinal
  upset — Mawab over Maragusan), place the corresponding delegation's
  entry at rank 1 (`PARTIALLY_VERIFIED` winner), with a
  `SYNTHETIC_DERIVED` full placement list beneath it.
- Every other seeded event gets a fully `SYNTHETIC_DEMO` validated
  result: internally consistent placements (no duplicate ranks without
  the `is_tie` flag, only confirmed entries placed).
- Every result goes through `EventResult`/`ResultPlacement` exactly as
  `ResultController` already enforces — encoded, then validated, using
  the seeder's existing `forceFill()`-based pattern for guarded status
  fields (mirrors `SampleProvinceDemoSeeder::validatedResult()`).
- No separate "medal award" table or record — `MedalTallyService::
  standings()`'s existing derive-at-read-time behavior *is* the medal
  award step in the request's required flow; this WP does not add a
  parallel mechanism.

## Out of Scope
Live scoring (WP4, structurally separate from results already); any
result claimed as `VERIFIED_OFFICIAL` (nothing in this dataset qualifies).

## Deliverables
- New seeder file (results/placements portion)
- Completion report
- Git status summary

## Acceptance Criteria
- Repository inspected first.
- Every medal traces to a validated `ResultPlacement`; no orphan medals.
- Municipality totals equal their schools' summed totals.
- Only validated results affect the tally.
- Tests and quality checks completed.
- Documentation updated where relevant.
- No secrets exposed.
- No commit or push performed.

## Completion Report
Include:
1. Repository findings
2. Files created
3. Files modified
4. Test results
5. Quality results
6. Remaining issues
7. Recommended next work package

Next:
WP4 — Live Scoring Samples (Basketball, Boxing, Softball/Baseball)
