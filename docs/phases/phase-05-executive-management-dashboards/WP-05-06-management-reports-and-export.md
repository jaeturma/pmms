# WP-05-06 — Management Reports & Export

## Purpose
Let Admin/Organizer print or export the WP-05-02..05 widgets, mirroring the
existing internal report pattern (`docs/reports.md`) rather than inventing a
new one.

## Tasks
- Inspect the repository before making changes.
- Implement only the scope of this work package.
- Preserve architecture and coding standards.
- Update documentation where necessary.
- Run backend and frontend quality checks.
- Do not commit or push changes.

## Scope
- Printable report page(s) for the management dashboard, same `@media print`
  chrome-hiding pattern as every existing report (no PDF library).
- CSV export via `fputcsv` streams, same pattern as `ReportController`,
  audited (`report.*_exported`, matching the existing audit-action naming
  convention in `docs/audit-trail.md`).
- Linked from the management page ("Printable report" / download actions,
  same UX as the existing tally/roster reports).
- Tests: report renders with correct data, CSV download audited, forbidden
  for non-manager roles.

## Out of Scope
Any new widget (WP-05-02..05, already done); public-facing export (not in
scope for this phase, see README exclusions).

## Deliverables
- Updated source code
- Updated documentation (docs/reports.md extended)
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
WP-05-07 — Accessibility & Mobile Review
