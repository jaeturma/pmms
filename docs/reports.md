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
| Medal tally report (district/municipality standings, plus school reference, meet/sport filters) | `/reports/tally` | Every authenticated user (aggregates of validated results) | "Printable report" header action on the tally page |
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

**Division initiative:** the delegation roster report shows
`Delegation::registrantName()` (School or Municipality) — the delegation's
own identity, correctly. The per-entry "school" field on the event-entry
list and the per-placement "school" on the result sheet are sourced from
`athlete.school` — each entered/placed athlete's own home school. The
school-participation summary counts a school as participating if it has its
own delegation (City) **or** any athletes/personnel of its own under a
municipal delegation (Province) — not gated on the school having a direct
delegation row, which would otherwise hide every Province-deployment school.
See `docs/delegations.md`.

## Verified 2026 — Phase 6 (WP-06-01)

Re-verified all six reports (page, print layout, CSV export) against the app as
it stands after the Division initiative and Phase 7: `ReportController` uses
`athlete.school`/`Delegation::registrantName()`/`Division::current()->areaLabel()`
correctly everywhere, with no stale delegation-as-school-proxy references left.
All six report pages consistently use the shared `ReportActions` component
(print + CSV buttons, `print:hidden`), so print/export behavior is uniform
across the set. The medal tally report correctly reflects the district-first
ordering from the 2026-07-25 post-Division refinement (district/municipality
standings shown first, school table demoted to a "reference only" note).
Live scoring (Phase 7) correctly has no report of its own — a live session is
provisional by design and was never expected to produce one.
`tests/Feature/ReportTest.php` (18 tests) and
`tests/Feature/OperationsReportTest.php` (8 tests) still pass and still cover
current behavior. No defects found; no code changes were needed. Per owner
decision, CSV remains the only export format — native `.xlsx` export was
considered and explicitly dropped as unnecessary (`docs/phases/phase-06-reports-uat-deployment-turnover/README.md`).

Note: `reports/management` (Phase 5's Executive/Management Dashboards printable
report, `docs/management-dashboard.md`) is a separate report built on the same
`ReportActions` pattern but is out of this document's/this verification's scope
— it was never part of the WP-02-12/WP-03-08 inventory above.
