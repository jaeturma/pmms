# Rosters & Printable Lists

WP-02-12 (registration reports) + WP-03-08 (operations reports). Report pages with
print support and CSV export, built on the shared component patterns (PageHeader,
tables, `ReportActions`).

## Reports

| Report | Route | Access | Linked from |
|---|---|---|---|
| Delegation roster (athletes + personnel) | `/reports/delegations/{delegation}/roster` | Managers; assigned officers (`DelegationPolicy::viewRoster`) | "Roster" row action on the delegations page |
| Per-event entry list (withdrawn excluded) | `/reports/events/{event}/entries` | Managers all rows; officers their own delegation's rows; viewers 403 (`EntryPolicy::viewAny`) | "Event list" header action on the entries page when an event filter is active |
| School participation summary (aggregate counts, optional meet filter) | `/reports/participation` | Every authenticated user (aggregates only, no minor data) | "Participation" header action on the schools page |
| Official result sheet (validated results only, with validator identity and date; 404 for unvalidated) | `/reports/results/{result}` | Every authenticated user — validated results are official outcomes | "Sheet" action on each validated result card |
| Medal tally report (school + district standings, meet/sport filters) | `/reports/tally` | Every authenticated user (aggregates of validated results) | "Printable report" header action on the tally page |
| Daily schedule sheet (one day, grouped by venue; date defaults to today) | `/reports/schedule` | Every authenticated user (schedule is non-sensitive) | "Daily sheet" header action on the schedule page |

## Printing

No PDF library. Each report page is a regular Inertia page; the Print button calls
`window.print()`. `resources/css/app.css` has an `@media print` block that hides the
app chrome (`[data-slot='sidebar']`, the inset header) and flattens the main panel;
interactive controls on report pages carry `print:hidden`.

## CSV export

Each report has a `…/download` route streaming a CSV (`ReportController::csv()`,
`fputcsv` over `php://output`). Exports are role-scoped exactly like their pages and
audited as sensitive exports: `report.roster_exported` (subject: delegation),
`report.event_entries_exported` (subject: event), `report.participation_exported`,
`report.result_sheet_exported` (subject: result), `report.tally_exported`,
`report.schedule_exported`.

## Tests

`tests/Feature/ReportTest.php` — access scoping per role (page + CSV), export audit
records, withdrawn-entry exclusion, participation counts and meet filter.
`tests/Feature/OperationsReportTest.php` — validated-only rule (result sheet 404 for
encoded results, tally counts validated only), per-venue day grouping and time
ordering on the schedule sheet, and export audits for all three WP-03-08 reports.
