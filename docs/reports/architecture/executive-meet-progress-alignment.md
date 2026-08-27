# Executive Meet Progress Alignment

## 1. Current medal tally architecture

Official standings are calculated from `EventResult` placements and snapshotted `MedalAward` rows. `MedalAward` stores both physical quantity and official tally quantity; executive progress must use only `tally_quantity`.

## 2. Current Sport Event architecture

`Meet` and `Event` are linked through `meet_events`. Events belong to a catalog `Sport`, carry `active` and `is_medal_event` flags, and may have one `EventMedalConfig`. `MeetSport` separately describes whether a sport is active for a meet and owns tournament personnel assignments.

## 3. Expected medal source

The denominator is the sum of gold, silver, and bronze tally quantities on complete `EventMedalConfig` rows for active events attached to the current meet where `awards_medals = true`. Physical quantities, schedules, entries, and athlete counts are excluded. Medal events with absent or incomplete configuration are reported as configuration issues and do not silently inflate the denominator.

## 4. Official medal source

The numerator is the sum of immutable `MedalAward.tally_quantity` snapshots whose parent `EventResult` belongs to the meet and has `official` status. Encoded, submitted, returned, validated, reopened, and cancelled results are excluded.

## 5. Proposed progress formula

`official awarded tally quantity / expected configured tally quantity * 100`, calculated by medal type, overall, and sport. Display is clamped to 100%, while an awarded-over-expected mismatch remains visible as `data_review_required`. Remaining quantities never display below zero.

## 6. Current executive roles

The existing `view-management-reports` gate delegates to `User::canViewManagementReports()`. It covers Administrator, Meet Organizer, active Top Management and Meet Management members, and production-account oversight. This is a read gate and does not confer approval or mutation authority.

## 7. Sports Coordinator/Supervisor role mapping

There are no duplicate global Sports Coordinator or Supervisor login roles. Sport-scoped operational users are represented by `MeetSportAssignment`; organizational oversight is represented by management-team membership. No new broad role is introduced. Future coordinator/supervisor access should be added through explicit scoped assignments and this report gate, not through automatic system-wide authority.

## 8. Personnel visibility

`ManagementTeam`/members and `MeetSportAssignment` are the authoritative structures for TWG and tournament personnel. The executive report may aggregate roles and readiness but must not duplicate personnel tables.

## 9. Athlete/Coach visibility

Athlete readiness is derived from existing eligibility review, medical clearance, accreditation, document, entry, personnel, and coach-request relationships. Detailed profile navigation remains governed by existing policies.

## 10. Security/privacy restrictions

The report exposes counts, names/roles only where an existing protected detail page permits them, and operational statuses. Password hashes, credentials, medical findings, private documents, birth dates, LRNs, and confidential notes are excluded. Monitoring access remains distinct from manage/approve permissions.

## 11. Dashboard/report changes

The existing Management Dashboard gains a primary Medal Award Progress section, per-medal totals, configuration warning, last official result, and a compact sport-progress table. Existing print and CSV reporting can consume the same centralized summary.

## 12. Performance strategy

`MeetProgressService` uses bounded eager loading for configured meet events and grouped aggregate queries for official awards and result statuses. It avoids per-event queries. Cache can be added after event/result mutation invalidation is centralized; premature caching would risk stale executive figures.

## 13. Tests

Tests cover the 6/30 formula, configurable team tally quantities, separation from physical quantities, exclusion of non-official awards, exclusion of inactive/non-medal events, incomplete configuration warnings, over-award detection, and report access.

## 14. Data quality concerns

The schema has no event lifecycle beyond `active`; therefore `active` is the current competition-ready equivalent. Explicit duplicate-event prevention is not encoded in the current schema. Missing configs and awarded-over-expected quantities are surfaced as `needs_attention`/`data_review_required` rather than hidden.
