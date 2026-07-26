# WP-06-01 — Reports & Print Verification

## Purpose
Confirm the six existing report pages (`docs/reports.md`,
`ReportController`) are still correct and complete after every phase that has
touched reporting since they were built (Division initiative's re-keying and
area-label changes, Phase 7's live scoring). This is a verification pass, not a
rebuild — the reports and their CSV exports already exist and print/export
today.

## Tasks
- Inspect the repository before making changes.
- Implement only the scope of this work package.
- Preserve architecture and coding standards.
- Update documentation where necessary.
- Run backend and frontend quality checks.
- Do not commit or push changes.

## Scope
- Walk each of the six reports (delegation roster, per-event entry list,
  school participation summary, official result sheet, medal tally, daily
  schedule) against current behavior: page render, print layout
  (`window.print()` + `@media print`), and CSV download, for each authorized
  role per `docs/reports.md`'s access table.
- Confirm every report reflects the Division initiative's final state
  correctly: `Delegation::registrantName()` used only for delegation-level
  identity, `athlete.school`/`personnel.school` used for individual
  attribution, `division.areaLabel` used instead of a hardcoded "District"
  label anywhere it appears.
- Confirm no report needs a new field or view because of Phase 7 (live
  scoring is provisional and correctly has no printable "official" report of
  its own — verify this is still true, not assumed).
- Re-run `tests/Feature/ReportTest.php` and
  `tests/Feature/OperationsReportTest.php` and confirm they still pass and
  still cover the current behavior; add a test only if a real gap is found.
- If a real defect is found (stale field, broken print layout, wrong area
  label, missing audit event), fix it — scoped to that specific defect, not a
  redesign.
- Update `docs/reports.md` with a short "Verified 2026 — Phase 6" note
  summarizing the pass, whether anything was fixed, and confirming CSV export
  remains the only export format (no `.xlsx`, per owner decision).

## Out of Scope
Adding `.xlsx` export (dropped, not deferred — see README); adding new report
types; changing print CSS conventions app-wide; any report UI redesign.

## Deliverables
- Any defect fixes found during verification (expected to be small or none)
- Updated `docs/reports.md` verification note
- Completion report
- Git status summary

## Acceptance Criteria
- Repository inspected first — every report actually exercised, not assumed
  correct from reading code alone.
- No unrelated features added.
- Tests and quality checks completed.
- Documentation updated.
- No secrets exposed.
- No commit or push performed.

## Completion Report
Include:
1. Repository findings (per-report verification results)
2. Files created
3. Files modified
4. Test results
5. Quality results
6. Remaining issues
7. Recommended next work package

Next:
WP-06-02 — Backup & Restore Baseline
