# WP-03-08 — Operations Reports & Printables

## Purpose
Complete this work package for the PMMS Division Edition. Keep implementation practical
for a Schools Division Office. Build only what is required for a maintainable
production-quality system.

## Tasks
- Inspect the repository before making changes.
- Implement only the scope of this work package.
- Preserve architecture and coding standards.
- Update documentation where necessary.
- Run backend and frontend quality checks.
- Do not commit or push changes.

## Scope
- Three reports on the established WP-02-12 pattern (print CSS + audited CSV, shared
  `ReportActions`): official result sheet per event (validated results only, with
  validator and validation date), medal tally report (per school and per district),
  and daily schedule sheet (per day, grouped by venue).
- Role scoping consistent with sources: result sheets and tally follow their pages'
  visibility (validated data, all roles); schedule sheet all roles; every CSV export
  audited (`report.*_exported`).
- Reports linked from the results, tally, and schedule pages via the shared
  PageHeader pattern.
- Tests: report data correctness (validated-only), scoping, export auditing.

## Out of Scope
Certificates, post-event summary narrative reports, public portal, emailed/scheduled
reports.

## Deliverables
- Updated source code
- Updated documentation (docs/reports.md additions)
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
WP-03-09 — Meet Operations Dashboard
