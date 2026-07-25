# WP-05-05 — Venue Utilization

## Purpose
Show how venues are used across meets — slot counts and scheduled hours per
venue — closing out the cross-meet data widgets before reports/export and
the closing review.

## Tasks
- Inspect the repository before making changes.
- Implement only the scope of this work package.
- Preserve architecture and coding standards.
- Update documentation where necessary.
- Run backend and frontend quality checks.
- Do not commit or push changes.

## Scope
- Per venue, across meets in scope: scheduled slot count, total scheduled
  hours, distinct meets/events using it — derived from `EventSchedule`,
  no new tables.
- Table on the management page, filterable via WP-05-01's foundation, each
  row linking to the venue's registry entry.
- Tests: multi-meet/multi-venue fixture, counts and hours correct,
  authorization unchanged.

## Out of Scope
Venue registry CRUD (already exists, `docs/venues.md`), participation/
operations/performance widgets (WP-05-02..04, already done), export
(WP-05-06).

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
WP-05-06 — Management Reports & Export
