# Data Integrity

The Administration > Data Integrity screen (`/administration/data-integrity`) lists broken relationships and incomplete reporting data. Opening the screen does not change records. Administrators and active central ICT members can inspect the meet; tournament ICT access is limited to assigned sports. Coaches cannot access the console.

For a broken sport roster, open its repair link and search existing athletes in the original delegation. Linking requires an explicit athlete selection and a reason. The server checks the original roster identity, delegation, level, gender, and duplicate sport membership before updating the link in a transaction.

Creating an athlete during repair requires a search within the previous 30 minutes, explicit confirmation, and canonical registration fields. Existing identities, including archived LRNs, cannot be duplicated. Creation and relinking are audited. Repair does not create Event Entries or rewrite historical results.

For command-line diagnostics, run `php artisan pmms:audit-data --meet=ID`, optionally with `--json`. Omitting the meet scans all records. The command is read-only; a successful exit means the scan ran, not that no issues were found.

Result placements display reporting completeness separately from their operational status. Missing athlete attribution, team roster details, or coaches are reporting concerns and do not themselves block result operation.

## Verification on 2026-09-06

The latest pending workspace changes were identified as this feature; the original task conversation was not available. The focused DataIntegrityTest suite passed all 9 tests (77 assertions), covering read-only diagnostics, missing athletes, historical serialization, explicit repair, duplicate prevention, authorization, and stale previews. The full backend suite also passed: 1,866 tests and 11,548 assertions. Pending PHP formatting and sidebar formatting were corrected; the dirty-file Pint check passes.

Ten frontend lint issues in the pending feature files were corrected. Targeted ESLint and Prettier checks pass.

Repository-wide verification is not yet clean: TypeScript reports errors in `components/delete-user.tsx` (missing generated `ProfileController.destroy`) and `pages/food/distribution.tsx` (numeric pagination parameter); PHPStan reports 1,397 errors across the repository. The full ESLint scan reports 326 errors and one warning before the targeted fixes, including generated files under storage. These results do not establish that all errors were introduced by this feature. The production build stalled during `wayfinder:generate --with-form`; attempts were stopped after several minutes without completion. No successful production build or browser verification is claimed. Full task sign-off remains pending broader validation and confirmation against the original request.
