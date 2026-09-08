# Sports Medal Awards Report

Route: `GET /reports/sports-medal-awards` (`reports.sports-medal-awards`). Optional parameters: `meet_id`, `sport_id`. Preview is the printable view; its buttons print/save PDF through the browser or download UTF-8 CSV. No server PDF or XLSX dependency was added; these are not established dependencies in composer.json. CSV uses the already-authorized preview payload, preserves repeated rows and quantities, and escapes spreadsheet formula prefixes.

## Files and integration

Added:
- `app/Http/Controllers/SportsMedalAwardsReportController.php`: request validation, meet/sport authorization, header and selector payload.
- `app/Services/SportsMedalAwardsReport.php`: canonical award retrieval, grouping, attribution, signatories.
- `resources/js/pages/reports/sports-medal-awards.tsx`: preview, print/save PDF, CSV.
- `tests/Feature/SportsMedalAwardsReportTest.php`: permissions, award data, missing relationships and query-count tests.
- `tests/frontend/sports-medal-awards.mjs`: null-safe React rendering and repeated-row checks.
- This document.

Modified: `routes/web.php`, `app/Http/Middleware/HandleInertiaRequests.php`, `resources/js/types/auth.ts`, `resources/js/components/app-sidebar.tsx`, `resources/css/app.css`.

Existing uncommitted Medal Tally changes from the preceding request are separate from this report. Generated Wayfinder files are regenerated with Artisan, never hand-edited.

## Canonical source and status

`PublicEventResults::withMedals(EventResult::query())` supplies real (non-demo), Official Results with positive canonical `medal_awards.tally_quantity`. The report reads `EventResult.medalAwards` snapshots created by `MedalAwardService`, not fixed medal fields, schedules, scores or reconstructed standings. It additionally requires each snapshot's `result_version` to equal the current result version and applies the tally's medal-producing match rule. Draft, Submitted, Validated, Returned, Reopened and Cancelled states are excluded from this new report, per its accepted/published-only specification. Existing Admin/ICT Medal Tally preview behavior is unaffected.

The existing result workflow owns the one-active-result constraint. The report does not create, replace, deduplicate or mutate Results. Every positive current award snapshot remains a separate row, including repeated medal types and repeated delegations. `tally_quantity` is the stored equivalent of the requested medal tally count; physical quantity is shown separately.

Kickboxing is included when it has actual accepted award snapshots. This is an award-detail report, not an official standings calculation. The existing `MedalTallyService` exclusion remains intact. Paragames likewise stays visibly grouped by sport rather than being added to an Overall tally.

## Authorization

Reuses `DavraaReportAccess::canManage`, `sportIds` and `sportOptions`, without that service's Event Secretariat view-only extension. Active Tournament ICT sport assignments constrain the selector and server request. Existing central ICT membership confers meet-wide sport eligibility, but only Admin can request All Sports. ICT defaults to its first authorized sport (and its sole sport when only one exists); explicit empty/all, zero, unauthorized sport and unauthorized meet requests cannot widen the scope. The server checks every request independently of sidebar visibility.

## Names, header and signatories

`ResultAttributionService::report()` supplies saved reporting athletes and coaches. Individual attribution falls back to the placed Entry's athlete. Delegation resolves from the canonical award, then placement, team entry or individual entry. Saved team athlete attribution is historical; current mutable team rosters and current coach assignments are not substituted for missing historical attribution, consistent with the existing attribution service's snapshot convention.

Header fields come directly from selected Meet: name, school_year, venue, starts_at, ends_at. Missing optional values are omitted. Meet has no configured organizing-office/logo field in the existing report architecture, so no organization or seal is invented.

The sport's active `MeetSportAssignment` Tournament Secretary and Tournament Manager are the tournament staffing source. Footer labels follow the existing DAVRAA Prepared By / Recommended By convention. Lead assignments are preferred, with deterministic ID ordering. Identity comes from linked Person.full_name, then User.name; position uses original_designation or the configured role label. Missing/orphaned identity leaves name and position blank. No speculative Approved/Noted names or signatures are generated. Each sport has its own footer in All Sports mode.

All optional relationships are null-safe. Orphaned placements retain the canonical award information. In Admin All Sports mode an unresolved sport/event remains a neutral placeholder; an unresolved event cannot be attributed to an ICT sport and is therefore excluded from that scoped request. Queries eager-load award attribution and staff; query-count regression coverage confirms additional award rows do not add queries.

## Validation and deployment

Automated verification commands:

```powershell
php artisan test --compact tests/Feature/SportsMedalAwardsReportTest.php tests/Feature/PublicResultsTest.php tests/Feature/MedalTallyTest.php
node tests/frontend/sports-medal-awards.mjs
npm run build
node node_modules/typescript/bin/tsc --noEmit
```

No migration is required. No registration, result, roster or production data is changed.

Verification outcome: all 8 new feature tests passed (81 assertions); existing MedalTallyTest and PublicResultsTest passed in the combined regression run. The React rendering test and production build passed. The full TypeScript check reports existing errors in untouched `resources/js/components/delete-user.tsx:58` and `resources/js/pages/food/distribution.tsx:450`. The in-app browser backend was unavailable, so interactive visual/print-dialog verification was not performed. Print markup was checked through component rendering, and print CSS hides controls, repeats table headers and separates sports into pages.

After reviewing and transferring the approved release, run the following from the prepared production release directory using the existing deployment process. These commands are documentation only and were NOT executed against production:

```sh
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan optimize:clear
php artisan optimize
php artisan route:list --path=reports/sports-medal-awards
```

Use the existing release switch/rollback procedure, then verify one scoped ICT account and Admin preview/print. No migration or seeding command is needed for this feature. Keep the previous release available for rollback. This change does not require a new worker or service.
