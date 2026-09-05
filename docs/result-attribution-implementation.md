# Direct Event Result attribution completion report

1. **Event type:** Reuses the canonical `events.is_team_event` / `Event.is_team_event`. No additional event-type column.

2. **Existing relationships:** Reuses Athlete -> sportRosterMemberships -> MeetSport, Delegation, TeamEntry/members, approved CoachAssignmentRequest scopes, active MeetSportAssignments, and Event Secretariat management-team membership. Athlete has soft deletion rather than a separate active flag. Candidate queries exclude soft-deleted athletes. Team Entry members require Event Entries and are mutable before finalization; result-specific ID pivots avoid making that entry architecture mandatory and preserve historical membership.

3. **Files changed:**
   - `app/Models/ResultPlacement.php`
   - `app/Services/ResultAttributionService.php` (new)
   - `app/Services/PublicEventResults.php`
   - `app/Http/Controllers/ResultAttributionController.php` (new)
   - `app/Http/Controllers/ResultWorkflowController.php`
   - `app/Http/Controllers/ResultController.php`
   - `app/Http/Controllers/ReportController.php`
   - `routes/web.php`
   - `database/migrations/2026_09_05_000001_add_result_attribution.php` (new)
   - `resources/js/components/result-attribution.tsx` (new)
   - `resources/js/pages/results/index.tsx`
   - `resources/js/pages/reports/result-sheet.tsx`
   - `tests/Feature/ResultAttributionTest.php` (new)
   - `tests/frontend/result-attribution.mjs` (new)
   - This report.

4. **Migration:** Additive nullable `result_placements.athlete_id`, `result_placement_athlete` and `result_placement_coach` pivots. Existing `team_entry_id` is reused as the source link. No athlete/user duplication, backfill, tally recalculation, or change to existing placement counts. Parent placement deletion cascades through its reporting pivots. Athlete/user relationships preserve soft-deleted identities for history. Migration has only been exercised by isolated SQLite tests, not applied to the application's database.

5. **Individual linking:** Optional searchable selector per placement during direct submission/editing. A separate reporting editor also works during review and after acceptance. Blank remains valid. Ordinary edits from older clients preserve omitted attribution when the delegation is unchanged.

6. **Candidate filtering:** The authenticated options endpoint requires current Meet, selected event, selected delegation and authorized actor. Athletes must match both their delegation and a canonical sport-roster membership for that Meet/Sport/delegation. No `athletes.sport_id` or confirmed-entry dependency. Changing event/delegation clears the UI selection; server validation independently rejects incompatible athlete IDs.

7. **Team roster:** Team events show roster controls instead of a single medal-athlete selector. Existing Team Entries can be imported even before confirmation; import copies athlete IDs into the placement pivot. Roster editing is independent of subsequent Team Entry membership changes. Existing athletes can be added/removed, and all roster links can be cleared. Empty/incomplete rosters remain valid.

8. **Coach permissions:** Approved coach scopes must match the exact delegation/event pair. Coaches can enrich submitted and accepted placements within that scope. The dedicated PATCH route permits reporting fields only and rejects medal/status/score fields. Other delegations and unauthorized events are denied. Primary and assistant coach relationships use approved assigned users; the existing application represents both through its Coach assignment system.

9. **ICT permissions:** Active Tournament ICT assignment plus existing event-access checks grants scoped candidate lookup and attribution editing before or after acceptance. An ICT role alone grants no access.

10. **Secretariat/Admin:** System Admin can correct attribution. Active central Event Secretariat membership and scoped Tournament Secretary assignments can inspect/correct through existing result-management scope. Acceptance/validation authorization remains in the existing workflow.

11. **Reports/public output:** Result list and printable result sheet show athlete names, roster players, coach names/roles, and informational completeness/counts. Result sheet includes document links under existing document permissions. CSV preserves its original first columns and appends Medal Count, Players, Coaches. Public individual results use linked athlete names and retain delegation fallback when blank. Public payloads do not include internal attribution IDs or private roster metadata. Historical roster membership is snapshotted as IDs; names remain relational, consistent with the existing architecture.

12. **Tally isolation:** Attribution updates only the placement's reporting relationships inside an audited transaction. They never call medal synchronization or modify result status, acceptance timestamps, version, rank, mark, delegation, or tally quantity. Regression tests compare complete award records before/after accepted attribution changes. Fifteen team players and two coaches still contribute Gold +1. Same-delegation Gold/Silver/Bronze remains supported.

13. **Tests added:** Seven Laravel feature tests combine optional individual/team submission and acceptance, current-sport/Meet/delegation filtering, deleted athletes, wrong-delegation rejection, repeated-delegation medals, coach/ICT/Admin/Secretariat scope, accepted-record invariance, audit logging, roster history, partial PATCH preservation, report coach output, public athlete names, and legacy direct-edit preservation. A standalone React render test checks seven UI assertions, including optional individual selection and absence of that selector for teams.

14. **Targeted results:** 64/64 result-focused tests passed. Expanded run including OperationsReportTest and PublicGalleryTest: 75/76 passed (888 assertions); only the unrelated gallery payload-contract test fails because the existing `sports.0.events` field is unexpected. The field is already present in HEAD. UI render test: 7/7 assertions passed. Changed-file ESLint, Pint and `git diff --check` passed.

15. **Full Laravel suite:** Final run pending. The preceding run had 1,826/1,828 passing; its CSV compatibility failure has been corrected and verified in the expanded targeted run. The other failure was the existing PublicGalleryTest contract mismatch.

16. **Frontend:** Final production build passed (32.22 seconds). TypeScript check reports two existing errors outside this feature: missing `destroy` route in `components/delete-user.tsx:58` and incompatible numeric filter in `pages/food/distribution.tsx:450`. No attribution-file TypeScript errors were reported.

17. **Production/data impact:** No deployment, production migration, or production data mutation. The application database still needs the additive migration through the normal release process before serving this updated code. Athlete, roster and coach links remain optional; submission and acceptance retain the current urgent direct-result rules.

