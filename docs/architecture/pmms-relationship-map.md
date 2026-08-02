# PMMS Relationship Map

Companion to [pmms-organizational-realignment-gap-assessment.md](../reports/architecture/pmms-organizational-realignment-gap-assessment.md) and [pmms-approved-organizational-model.md](pmms-approved-organizational-model.md). Two diagrams: **current** (verified against the running schema) and **target** (proposed, additive). New entities are marked `NEW`; everything else exists today exactly as drawn.

## Current state (verified)

```mermaid
erDiagram
    District ||--o{ SchoolDistrict : "has many (district_id = municipality)"
    District ||--o{ School : "has many"
    SchoolDistrict ||--o{ School : "has many (optional)"
    Division ||--o{ District : "areaLabel() only, no FK"

    Meet ||--o{ Delegation : "has many"
    District ||--o{ Delegation : "registers under (Province mode)"
    School ||--o{ Delegation : "registers under (City mode, unused today)"
    Meet }o--o{ Event : "meet_events pivot"
    Sport ||--o{ Event : "has many"
    Sport }o--o{ User : "sport_user pivot (technicalOfficials, no meet_id)"

    Delegation ||--o{ Athlete : "has many"
    Delegation ||--o{ Personnel : "has many"
    School ||--o{ Athlete : "own home school"
    School ||--o{ Personnel : "own home school"
    Athlete ||--o{ Entry : "has many"
    Event ||--o{ Entry : "has many"
    Athlete ||--o| EligibilityReview : "has one"
    Athlete ||--o{ EligibilityDocument : "has many"
    Athlete ||--o| Accreditation : "has one"
    Personnel ||--o| Accreditation : "has one"
    Personnel }o--o{ Sport : "personnel_sport pivot"

    Meet ||--o{ EventResult : "has many (via event)"
    Event ||--o{ EventResult : "has one per meet"
    EventResult ||--o{ ResultPlacement : "has many"
    Entry ||--o{ ResultPlacement : "has many"

    Meet ||--o{ EventMatch : "has many (via event/schedule)"
    EventMatch ||--o{ ScoringSession : "has many"
    Delegation }o--o{ User : "delegation_user pivot (officers)"
```

Key facts this diagram makes visible:
- `Sport` has no edge to `Meet` at all — it only reaches a meet indirectly through `Event`.
- `sport_user` (Technical Official assignment) hangs off `Sport` directly, not off any meet-scoped entity — this is the "global, not meet-specific" gap from the gap assessment §7/§9, drawn here as the one relationship in this diagram with no meet in its path.
- `District` has two incoming "delegation registers under" edges (School and District) because the schema supports both division types; only the `District` edge is active for this deployment (Province/Davao de Oro).

## Target state (proposed, additive)

```mermaid
erDiagram
    CongressionalDistrict ||--o{ District : "NEW: has many"
    District ||--o{ SchoolDistrict : "has many (unchanged)"
    District ||--o{ School : "has many (unchanged)"

    Meet ||--o{ Delegation : "has many (unchanged)"
    Meet ||--o{ MeetSport : "NEW: has many"
    Sport ||--o{ MeetSport : "NEW: has many"
    MeetSport ||--o{ SportCategory : "NEW: has many (optional)"
    Sport ||--o{ SportCategory : "NEW: has many"
    SportCategory ||--o{ Event : "NEW: optional FK"
    Sport ||--o{ Event : "has many (unchanged, still direct)"

    MeetSport ||--o{ MeetSportAssignment : "NEW: has many"
    SportCategory ||--o{ MeetSportAssignment : "NEW: optional scope"
    User ||--o{ MeetSportAssignment : "NEW: has many"

    Meet ||--o{ ManagementTeam : "NEW: has many"
    ManagementTeam ||--o{ ManagementTeamMember : "NEW: has many"
    User ||--o{ ManagementTeamMember : "NEW: has many"

    ManagementTeam ||--o{ EquipmentIssue : "NEW: Supply team owns"
    ManagementTeam ||--o{ MealAnnouncement : "NEW: Food team owns"
    ManagementTeam ||--o{ BilletingAssignment : "NEW: Billeting team owns"
    ManagementTeam ||--o{ TransportTrip : "NEW: Transport team owns"
    ManagementTeam ||--o{ MedicalClearance : "NEW: Medical team owns, restricted"
    ManagementTeam ||--o{ DrrmPlan : "NEW: DRRM team owns"

    Delegation ||--o{ Athlete : "unchanged"
    Athlete ||--o| EligibilityReview : "unchanged"
    Athlete ||--o| Accreditation : "unchanged"
    EventResult ||--o{ ResultPlacement : "unchanged"
```

Everything not shown as `NEW` in this second diagram is identical to the current-state diagram — the target model does not remove or rewire any existing edge; it only adds new nodes and edges around them (`MeetSport` sitting between `Meet`/`Sport`, `ManagementTeam` as the new anchor for six operational subtrees).

## Reading guide

- An edge in the target diagram that has no `NEW` label but touches a `NEW` node (e.g. `Sport ||--o{ MeetSport`) means: the existing entity (`Sport`) is untouched, only the new table's foreign key into it is added — the existing table gains no new column from that edge.
- `sport_user` (current-state diagram) is not carried into the target diagram — per the migration plan, it is retired once `MeetSportAssignment` (role = 'Technical Official') fully replaces its one consumer, `ScoringSessionController::canManage()`. It is not shown as removed above because that retirement happens in a later, separate migration, not the same one that introduces `MeetSportAssignment`.
- `Person`/unified identity (mandate §27) is deliberately **absent** from the target diagram — flagged as Open Question OQ-1 in the approved organizational model, not a designed-and-pending entity.
