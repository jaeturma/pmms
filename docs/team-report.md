# Team Report

## Route and changed files

`GET /reports/team`, named `reports.team`, is Admin only. Query parameters: optional `meet_id` and `delegation_id`. Initial preview asks for one Team; changing Meet clears the Team selection. The selected delegation must belong to that Meet. Invalid or cross-meet IDs return 404; non-Admin requests return 403 before filter validation. The sidebar item is visible only to Admin under Reporting. There is no All Teams mode.

Added:
- `app/Http/Controllers/TeamReportController.php`: Admin authorization, selectors, Meet header, preview response.
- `app/Services/TeamReport.php`: empty-group removal and quantity-based summary over shared canonical report data.
- `resources/js/pages/reports/team-report.tsx`: selectors, preview, summary, winning events, Print / Save PDF.
- `tests/Feature/TeamReportTest.php` and `tests/frontend/team-report.mjs`.
- This document.

Modified:
- `app/Services/SportsMedalAwardsReport.php`: optional delegation filter, applied to qualifying awards and eager-loaded award rows before grouping.
- `routes/web.php`: Team Report route.
- `resources/js/components/app-sidebar.tsx`: Admin Reporting item.

The existing `medal-awards-report` print styles are reused. No generated Wayfinder file was hand-edited. Earlier uncommitted Medal Tally and Sports Medal Awards work remains separate from the Team Report change.

## Canonical source and filtering

Reuses `SportsMedalAwardsReport::build(meet, null, delegationId)`, which uses `PublicEventResults::withMedals()` and canonical `medal_awards` snapshots from `MedalAwardService`. Only real, Official Results, positive tally quantities, current result versions and medal-producing matches qualify. Draft, Submitted, Validated, Returned, Reopened, Cancelled and stale snapshots are excluded. Existing workflow constraints own one active Medal Result per Event; this report does not mutate or reconstruct Results.

Both the result-existence query and loaded medal rows require the exact selected `medal_awards.delegation_id`. Award ownership is determined from the canonical snapshot, not roster membership, standings, schedules, participation, athlete school or a similarly named Team. Other Team rows cannot leak into an otherwise shared winning Event. Remaining awards are grouped by Sport and Event using the shared report service; empty events and sports are removed before rendering.

Dynamic/repeated medal rows remain separate. Each summary bucket sums `tally_count`, the report representation of stored `medal_awards.tally_quantity`, and Total sums those awarded quantities. A Gold row with quantity 2 contributes Gold +2 and Total +2. Physical quantity is not used as a tally quantity.

This is an award-detail scope, consistent with Sports Medal Awards: accepted Kickboxing awards are included when present. The official standings tally still excludes Kickboxing; Team Report does not claim its award-detail summary is an Overall standings total. Paragames awards remain under their own sport. No additional filters were introduced.

## Missing data, header and signatories

Athlete names use the shared saved result attribution and individual Entry-athlete fallback. Saved team reporting athletes are used; current mutable rosters are not substituted for missing historical attribution. Missing athlete references leave the award and count intact, displaying a neutral dash. Coach details are not displayed.

Known Team awards remain reportable with missing Sport/Event relations, using neutral labels. Broken optional placement relations retain canonical medal/count information. A missing/deleted requested Meet or delegation produces a controlled 404 rather than a null dereference. An unnamed delegation is identified neutrally by its stored ID.

Header uses selected Meet name, school_year, venue and date range; absent optional fields are omitted. The current Meet report architecture has no configured organization/seal fields, so none are invented. Default Meet lookup is read-only.

Signatories reuse the shared sport staffing resolution: active Tournament Secretary / Tournament Manager assignments, Person then User identity, configured designation or role label. Prepared by / Recommended by match the existing convention. Missing/orphaned/incomplete staff leaves name and position blank and never blocks the report.

## Print, performance and verification

Preview and browser Print / Save PDF are supported. Existing print styles hide selectors/buttons and navigation, repeat table headers, and avoid splitting rows/signatories. Sports flow compactly within one Team report. No PDF library or XLSX dependency is introduced.

The delegation filter executes before grouping; only that Team's awards are loaded. Eager-loaded attribution and staffing avoid per-award queries. A query-count regression test verifies additional award rows do not add queries.

Verification commands:

```sh
php artisan test --compact tests/Feature/TeamReportTest.php tests/Feature/SportsMedalAwardsReportTest.php
node tests/frontend/team-report.mjs
node tests/frontend/sports-medal-awards.mjs
npm run build
```

No migration is required. No medal, registration, roster or production data is changed.

Verification outcome: 20 Team Report / Sports Medal Awards feature tests passed (133 assertions). Both React rendering tests passed, including empty selection, zero-medal Team, repeated medal rows and missing labels. The production build passed and regenerated Wayfinder through Artisan. Interactive browser/print-dialog verification was not performed; the browser backend was unavailable in this session. Existing unrelated full-project TypeScript errors recorded during Sports Medal Awards work remain outside this change.

## Safe production deployment commands ? not executed

After review and transferring the approved code into the prepared release directory, run these commands from that directory using the existing deployment process:

```sh
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan optimize:clear
php artisan optimize
php artisan route:list --path=reports/team
```

No migration or seeding command is required. Use the existing release-switch/rollback procedure, retain the previous release, and verify Admin preview/print plus ICT denial after the release switch. No new worker or service is needed. No production deployment was performed.
