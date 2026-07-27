# WP5 — Demo & Load-Test Tier Wiring

## Purpose
Provide the three separate data volumes the request asks for — demo,
standard, load-test — each with its own clear, safe command, none of them
overwriting or duplicating the others.

## Tasks
- Inspect the repository before making changes.
- Implement only the scope of this work package.
- Preserve architecture and coding standards.
- Update documentation where necessary.
- Run backend and frontend quality checks.
- Do not commit or push changes.

## Scope
- **Demo tier**: a small, quick-to-eyeball addition (either extends
  `SampleProvinceDemoSeeder` or a new equally small seeder) — a handful
  of DdOPAA-flavored records, not the full WP2 standard-tier volume.
- **Standard tier**: confirm WP1–WP4's combined seeders run cleanly as
  their own artisan command, additive to an existing local database
  (never `migrate:fresh`, never deletes existing rows).
- **Load-test tier**: confirm `PerformanceBenchmarkSeeder` (WP-06-04)
  still serves this need as-is; optionally theme its labels to DdOPAA
  nicknames if that reads better, without changing its scale or
  mechanics.
- Document three distinct commands (Part 12's own requirement): adding
  reference data to an existing local database, building a clean
  demonstration database, generating optional load-test data.

## Out of Scope
Any change to `PerformanceBenchmarkSeeder`'s actual scale/mechanics;
automated scheduling of any of these seeders.

## Deliverables
- Demo-tier seeder (new or extended)
- Confirmed standard-tier and load-test-tier commands
- Completion report
- Git status summary

## Acceptance Criteria
- Repository inspected first.
- All three tiers are independently runnable and clearly named.
- No destructive commands anywhere in the documented flow.
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
WP6 — Testing & Seeding Safety
