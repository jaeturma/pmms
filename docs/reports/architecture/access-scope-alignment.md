# Access scope alignment

## 1. Current behavior

PMMS had strong delegation scoping for Coaches and Delegation Officers and policy protection for athlete profiles/documents. Tournament visibility, however, commonly resolved only a `sport_id`. Several shared lists and report routes could therefore expose records outside a narrower sport-category assignment. Official result lists and schedule exports also had historical meet-wide behavior.

## 2. Target behavior

Effective access is `role/capability + active assignment + resource + action`. Tournament personnel see all delegations only inside assigned sport/event scope. Coaches require both approved delegation and event scope. DSAC sees all current-meet athletes only for eligibility work.

## 3. Already aligned

- Coach athlete and entry queries require ownership/approved delegation and event assignments.
- Athlete photos and eligibility documents authorize the parent athlete/review.
- Result workflow distinguishes tournament, technical-official, and Event Secretariat actions.
- Central ICT permissions are represented separately from Tournament ICT assignments.

## 4. Over-permissive areas

- Tournament query scopes collapsed category assignments into whole-sport IDs.
- Schedules, matches, official results, reports, and some result documents could use broader visibility than the assignment.
- Coach queues were sport-scoped but did not consistently apply a category/event boundary.

## 5. Under-permissive areas

- DSAC eligibility authority did not consistently grant direct athlete-profile visibility across the current meet.
- Technical Officials could be unable to view scoped Coach records despite needing operational context.

## 6. Backend query changes

`CompetitionAccessService` is the central tournament scope resolver. It resolves active `MeetSportAssignment` rows into event IDs. A null category grants the whole sport; a category assignment selects only matching `events.sport_category_id` records. Athlete, eligibility, entry, schedule, match, result, coach, report, and option queries use this event set.

## 7. Policy changes

Athlete and eligibility policies use event-level tournament scope for direct records. DSAC eligibility permission grants current-meet athlete viewing, while update/delete rules remain unchanged. Result/schedule/match actions independently verify both role and event scope.

## 8. UI changes

Tournament accounts display their assigned sport/category labels. Redundant cross-sport filters are hidden for assignment-scoped accounts; backend queries remain authoritative.

## 9. Test changes

Authorization tests cover cross-delegation tournament visibility, coach delegation isolation, direct athlete access, narrow category isolation, result/schedule/match scope, and DSAC visibility/action separation. Existing feature suites remain the regression baseline.

## 10. Security concerns

- Legacy `sport_user` and `sports.tournament_manager_id` mappings are treated as whole-sport compatibility assignments because they contain no meet/category dimension.
- Category-level scope depends on `events.sport_category_id`; uncategorized events are not included in a category assignment.
- Generic raw file downloads must never be used for athlete/result documents without parent-resource authorization.
- Medical status may be exposed operationally, but confidential medical notes must remain in Medical Team-authorized endpoints.

