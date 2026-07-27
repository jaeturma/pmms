# WP6 — Testing & Seeding Safety

## Purpose
Prove the whole dataset's integrity and safety automatically, not just by
inspection — every invariant the request's Part 13 lists, plus the
production-seeding guard every seeder in this initiative carries.

## Tasks
- Inspect the repository before making changes.
- Implement only the scope of this work package.
- Preserve architecture and coding standards.
- Update documentation where necessary.
- Run backend and frontend quality checks.
- Do not commit or push changes.

## Scope
New Pest tests covering:
- All 11 real municipality delegations exist and are approved for the
  DdOPAA meet.
- Every school belongs to exactly one municipality (`district_id` never
  crosses).
- Every athlete's `school_id` is within their own delegation's
  registering municipality.
- No cross-municipality school assignment.
- No duplicate active delegation per municipality per meet.
- Medal tally reconciliation: every medal traces to a validated
  placement; municipality totals equal summed school totals.
- Validated results only affect the tally; any encoded-but-unvalidated
  result seeded does not appear in it.
- Live scores never create or touch `EventResult`/`ResultPlacement`.
- Each new seeder is idempotent (running twice produces identical counts).
- Each new seeder refuses to run outside `local`/`testing`
  (`app()->environment()` guard, asserted directly, not just assumed from
  convention).

## Out of Scope
Manual UAT/browser testing (out of this initiative's scope per the
original request); performance/load testing (already covered by the
existing `PerformanceBenchmarkSeeder`/`docs/performance.md`, WP-06-04).

## Deliverables
- New Pest test file(s) covering the list above
- Completion report
- Git status summary

## Acceptance Criteria
- Repository inspected first.
- Every listed invariant has a passing, named test.
- Full existing suite still passes (no regression).
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
WP7 — Documentation & Completion Review
