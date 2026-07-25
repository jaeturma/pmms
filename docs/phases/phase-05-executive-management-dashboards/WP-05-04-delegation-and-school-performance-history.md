# WP-05-04 — Delegation & School Performance History

## Purpose
Show medal-tally standings trending across meets/school years, per delegation
(registering unit) and per school (individual attribution) — the historical
counterpart to the live per-meet tally.

## Tasks
- Inspect the repository before making changes.
- Implement only the scope of this work package.
- Preserve architecture and coding standards.
- Update documentation where necessary.
- Run backend and frontend quality checks.
- Do not commit or push changes.

## Scope
- Calls `MedalTallyService::standings($meetId)` once per meet in scope and
  aggregates gold/silver/bronze/total across meets — does not reimplement
  tally derivation. District/municipality standings are the primary
  aggregate (matches the internal/public tally's district-first convention,
  see `docs/medal-tally.md`); school-level history is the reference table
  below it, same ordering convention.
- Per-school-year and all-time views, filterable via WP-05-01's foundation.
- Table on the management page; a delegation/school's row links to its
  registry entry.
- Tests: multi-meet fixture with known placements, aggregated totals correct
  across meets, district-first ordering matches the live tally's convention,
  authorization unchanged.

## Out of Scope
Live single-meet tally (already exists, `docs/medal-tally.md` — this WP
reuses it, does not modify it), venue data (WP-05-05), export (WP-05-06).

## Deliverables
- Updated source code
- Updated documentation
- Completion report
- Git status summary

## Acceptance Criteria
- Repository inspected first.
- No unrelated features added.
- Tests and quality checks completed.
- Documentation updated.
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
WP-05-05 — Venue Utilization
