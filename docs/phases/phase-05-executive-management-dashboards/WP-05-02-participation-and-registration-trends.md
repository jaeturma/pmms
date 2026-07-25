# WP-05-02 — Participation & Registration Trends

## Purpose
Show Admin/Organizer how participation and registration are trending across
meets and school years — the first real cross-meet widget on the WP-05-01
foundation.

## Tasks
- Inspect the repository before making changes.
- Implement only the scope of this work package.
- Preserve architecture and coding standards.
- Update documentation where necessary.
- Run backend and frontend quality checks.
- Do not commit or push changes.

## Scope
- Per-meet, then across-meets: delegation counts by status (draft/submitted/
  approved), athlete/personnel counts, entry counts. Ordered by meet
  `starts_at`/`school_year`.
- Be explicit about the delegation-vs-school distinction (DESIGN-NOTES): this
  WP counts **delegations** (registering units) and **individuals** (their
  own school) as separate rows/columns, never conflated.
- Simple table + summary cards on the management page (`ui/table`,
  `StatCard`) — no charting library; a sparkline-free trend is fine as a
  table with a meet-by-meet column, consistent with "limited charts" and "no
  new dependencies" across every prior phase.
- Filter by school-year (from WP-05-01's foundation).
- Tests: multi-meet fixture, counts correct per meet and in aggregate,
  delegation vs individual counts don't get mixed up, authorization
  unchanged from WP-05-01.

## Out of Scope
Operations/risk (WP-05-03), medal/performance history (WP-05-04), venue data
(WP-05-05), export (WP-05-06).

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
WP-05-03 — Operations Progress & Risk
