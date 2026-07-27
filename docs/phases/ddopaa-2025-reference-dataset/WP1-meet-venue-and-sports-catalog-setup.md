# WP1 — Meet, Venue & Sports Catalog Setup

## Purpose
Lay the foundation every later WP builds on: a DdOPAA Meet 2025 record, the
venue(s) it needs, and a sports/events catalog matching what's actually
corroborated (plus what the standard tier will need), attached to the meet.

## Tasks
- Inspect the repository before making changes.
- Implement only the scope of this work package.
- Preserve architecture and coding standards.
- Update documentation where necessary.
- Run backend and frontend quality checks.
- Do not commit or push changes.

## Scope
- New seeder (flat file, e.g. `DdopaaReferenceSeeder.php`, local/testing
  environment guarded like every existing sample seeder): creates the
  "DdOPAA Meet 2025" `Meet` (Active, published, `PARTIALLY_VERIFIED` start
  date of 2025-01-17, `SYNTHETIC_DERIVED` end date).
- New `Venue`: "Maragusan Grandstand Arena" (`PARTIALLY_VERIFIED`), plus
  any additional venues the standard/live-scoring tiers need
  (`SYNTHETIC_DEMO`, clearly named e.g. "Sample Gymnasium — DdOPAA").
- Update the five real municipalities' `District.nickname` where
  corroborated (Nabunturan "Black Mamba," Montevista "Blazing Fighters,"
  New Bataan "Rock Wreckers," Mawab "Pick Hammer," Maragusan "Maroon
  Knights") — via `District::where('name', ...)->first()`, never creating
  new District rows (the real 11 already exist via
  `DivisionRegistrySeeder`). Leave the other six untouched.
- Confirm/add sports to the catalog: Athletics, Basketball, Volleyball,
  Swimming, Gymnastics already exist via `SportsCatalogSeeder`; add
  Boxing if not present. Add a 3x3 Basketball event under Basketball.
- Attach every event this dataset will use to the DdOPAA meet.

## Out of Scope
Delegations, schools, athletes (WP2); results (WP3); live scoring (WP4).

## Deliverables
- New seeder file (meet/venue/catalog portion)
- Completion report
- Git status summary

## Acceptance Criteria
- Repository inspected first.
- Seeder is idempotent and `local`/`testing`-only.
- No unrelated features added.
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
WP2 — Standard Dataset — Delegations, Schools, Athletes & Personnel
