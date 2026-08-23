# Multi-event athlete and team-entry alignment

## Scope and invariant

An athlete is one person record. Participation is modeled separately and may include any permitted combination of individual, team, pair, doubles, or relay events. No athlete subtype or duplicate athlete record is required.

## 1. Current Athlete architecture

`athletes` stores identity, delegation, school, grade, sex, photos, and registrar ownership. `Athlete` has many `Entry` records, one eligibility review, eligibility documents, and one medical clearance. It is not permanently classified as individual or team.

The athlete-to-delegation relationship supplies meet registration context. Coach ownership is recorded by `registered_by`; delegation and approved coach event scope are enforced separately.

## 2. Current Sport Entry architecture

`events` belongs to `sports` and optionally to `sport_categories`. Event columns independently describe gender, age division, event name, `event_type`, `is_team_event`, team size, medal-event status, and delegation entry cap.

`entries` is the athlete-to-event join model. Its unique key on `(athlete_id, event_id)` prevents duplicate same-event participation while allowing one athlete to have many different entries. It also records delegation and status (`submitted`, `confirmed`, `withdrawn`). Meet membership, gender, age division, eligibility, delegation, registration window, delegation cap, and meet-level maximum events per athlete are validated by `EntryController`.

There is no separate `athlete_sports` table. Sport registration is presently derived from an athlete's event entries. This is sufficient for the requested many-to-many participation because each event belongs to one sport.

## 3. Current Team architecture

There is no competition `team_entries` or `team_members` structure. The existing `management_teams` tables concern meet operations personnel and are unrelated.

For a team event, every member currently receives an ordinary `entries` row. Results then place multiple athlete entries at the same rank and require them to be marked as ties. Team identity is inferred from event, delegation, rank, and athlete rows rather than stored explicitly.

## 4. Existing support for Individual + Team

The same athlete can already hold entries for multiple distinct events, including both `is_team_event = false` and `is_team_event = true`. The unique key rejects only the same athlete/event pair. Entry creation accepts `event_ids`, so multiple individual events can be submitted together.

This support is generic and not Gymnastics-specific.

## 5. Gaps

1. A team has no durable identity or workflow row.
2. Team minimum/maximum membership cannot be validated as a unit. `events.team_size` and category min/max data exist, but no roster aggregate consumes them.
3. A completed result does not retain an explicit immutable roster snapshot.
4. A team result is represented by one placement per athlete. Aggregate tally code counts placement rows, so a four-member gold can contribute four golds.
5. Athlete achievements and team-result display depend on reconstructing a team from tied placements.
6. Result entry UI selects athletes rather than one team competitor for team events.
7. Medical clearance is not currently enforced when creating entries, even when `meets.medical_clearance_required` is enabled.
8. Entry statuses cover the active workflow without draft/locked detail. Adding a second parallel status system would be harmful; team workflow should reuse `EntryStatus`.

## 6. Proposed minimal changes

Add `team_entries` as the one delegation competitor in a team/pair/relay event, and `team_entry_members` as its roster. Keep existing `entries` as athlete event participation and link each team member row to its corresponding athlete `entry`.

Add nullable `team_entry_id` to `result_placements`. A placement must reference exactly one competitor: an individual `entry` or a `team_entry`. Existing individual results remain unchanged. New team results store one placement per team, so medal aggregation naturally counts one medal event result. Team member achievements are derived through the snapshotted membership.

Roster membership becomes immutable when its team entry is confirmed/locked, participates in a match, or is referenced by a result placement. The member rows themselves are the snapshot; athlete soft deletion does not delete them, and foreign keys restrict destructive deletion.

Resolve team bounds generically from the event's configured `team_size`, then its sport category `min_players`/`max_players`. Do not infer team membership from sport category names.

Reuse `EntryStatus`: submitted team rosters are coach-editable during registration, confirmed team rosters are finalized, withdrawn entries are inactive. Official result locking provides the completed historical boundary.

## 7. Affected database tables

- Preserve: `athletes`, `events`, `sport_categories`, `entries`, `event_results`.
- Add: `team_entries` (`delegation_id`, `event_id`, `status`, timestamps, unique delegation/event).
- Add: `team_entry_members` (`team_entry_id`, `athlete_id`, `entry_id`, timestamps, unique team/athlete and team/entry).
- Alter: `result_placements.entry_id` becomes nullable; add nullable `team_entry_id` and uniqueness/index support.

No data reset or broad athlete-module rebuild is required.

## 8. Affected backend

- Models and relationships for Athlete, Entry, Event, TeamEntry, TeamEntryMember, ResultPlacement.
- A reusable team-entry validation/service boundary for ownership, meet/event/category/delegation, eligibility, medical policy, duplicate membership, team bounds, caps, and locking.
- Entry controller endpoints for roster creation/update/withdrawal/confirmation and scoped reads.
- Entry policy extended to team entries with the same coach delegation/event scope.
- Result controller accepts one team competitor per team-event placement and rejects mixed/wrong-event competitor types.
- Medal tally resolves delegation/school context from either competitor type and counts placement rows (one team placement, one medal).
- Athlete profile exposes individual and team achievements without affecting tally aggregation.

## 9. Affected frontend

- Coach Entries view separates Individual Events and Team Events, shows eligibility and safe medical status, supports multi-event athlete entry, and manages the authorized team roster.
- Tournament operations view groups events by individual/team and shows each delegation's member count against configured minimum/maximum.
- Result encoding selects team entries for team events and athlete entries for individual events.
- Athlete profile adds medals/achievements, including medals earned through snapshotted team membership.

## 10. Result implications

Individual results remain one `ResultPlacement` to one athlete `Entry`. Team results become one `ResultPlacement` to one `TeamEntry`. Team roster names are loaded from `team_entry_members`; later withdrawal or athlete soft deletion cannot rewrite an official result's historical composition.

Existing historical team results can continue to be read through the legacy tied-placement path until explicitly migrated; new writes must use team placements.

## 11. Medal implications

The official delegation tally counts result placements at ranks 1–3. With one placement per team competitor, a team gold contributes exactly `Gold +1`, regardless of roster size. Athlete achievement queries expand a team placement across its member snapshot only for personal display; this expansion is never fed back into aggregate standings.

## 12. Tests required

- One athlete can enter an individual event, a team event, and a second distinct individual event.
- Duplicate individual athlete/event is rejected.
- Duplicate athlete membership in the same team is rejected.
- Below-minimum roster cannot be finalized and is visibly incomplete while submitted.
- Above-maximum roster is rejected.
- Wrong-delegation athlete is rejected.
- DSAC-ineligible athlete is rejected.
- Medically uncleared athlete is rejected when meet policy requires clearance.
- Coach cannot modify another delegation's team.
- Registration deadline prevents coach edits.
- Finalized/competition-used/result-linked roster is immutable.
- Four-member team gold increments delegation gold by one.
- Each of the four snapshotted members sees the team gold achievement.
- Existing individual medal behavior remains unchanged.

