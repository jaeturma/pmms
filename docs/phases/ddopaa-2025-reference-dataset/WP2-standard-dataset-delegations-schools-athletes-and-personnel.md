# WP2 — Standard Dataset: Delegations, Schools, Athletes & Personnel

## Purpose
Populate the "standard development dataset" tier: all 11 real
municipalities registered as delegations, a realistic school roster under
each, and 500–700 synthetic athletes (plus personnel) entered into WP1's
events.

## Tasks
- Inspect the repository before making changes.
- Implement only the scope of this work package.
- Preserve architecture and coding standards.
- Update documentation where necessary.
- Run backend and frontend quality checks.
- Do not commit or push changes.

## Scope
- Register all 11 real municipalities as approved `Delegation`s for the
  DdOPAA meet (attach to the real `District` rows, not invented ones).
- Generate 10–25 schools per municipality (`SYNTHETIC_DERIVED` names
  following real DepEd naming conventions — explicitly not an actual
  verified roster; documented as such in WP7).
- Generate 500–700 synthetic athletes (synthetic Filipino names, no real
  names) distributed across schools/delegations, plus coaches/personnel,
  plus event entries (respecting sex/age-division matching and each
  event's `max_entries_per_delegation`, same rules `EntryController`
  already enforces for real registrations).
- Deliberately concentrate some entries in the five delegations with a
  `PARTIALLY_VERIFIED` nickname (Nabunturan, Montevista, New Bataan,
  Mawab, Maragusan) so WP3 can plausibly echo their real team-level wins.

## Out of Scope
Results/medals (WP3); live scoring (WP4); load-test-scale volume (WP5,
reuses the existing `PerformanceBenchmarkSeeder` instead).

## Deliverables
- New seeder file (delegations/schools/athletes/personnel/entries portion)
- Completion report
- Git status summary

## Acceptance Criteria
- Repository inspected first.
- All 11 municipalities represented; no cross-municipality school
  assignment.
- No real athlete names anywhere.
- Seeder is idempotent and `local`/`testing`-only.
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
WP3 — Results & Medal Tally Reconciliation
