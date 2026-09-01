# Current PMMS Competition Model Audit

Date: 2026-08-31  
Scope: Provincial Meet 2026 competition hierarchy, Coach access, Athlete roster/eligibility/entry, and Monitoring > Readiness.

## Executive finding

PMMS already has the correct durable core: `Meet -> MeetSport -> Sport`, `Sport -> Event`, a many-row Coach assignment model, canonical `sport_roster_members`, per-Event individual entries, and separate team-entry membership. No destructive migration or table rebuild is required. The safe alignment is behavioral: stop athlete registration from creating an Event entry, require the canonical Sport roster and eligibility before any entry submission, and make readiness Event-centered.

## Current model

1. **Sport model** — `sports` is the global catalog; `meet_sports` is the meet-specific inclusion and is unique by meet and sport.
2. **Event model** — `events.sport_id` makes Event the competition definition under one Sport. Gender, age division, individual/team type, entry cap, team metadata, venues, schedules, and medal configuration are Event attributes or Event relations.
3. **SportCategory usage** — retained as optional legacy classification/configuration. `events.sport_category_id` is nullable. It is not required for Event identity.
4. **Coach -> Sport** — `coach_onboarding_requests.meet_sport_id` records the selected meet Sport.
5. **Coach -> Event** — approved `coach_assignment_requests.event_id` rows are authoritative. Multiple rows are supported.
6. **Multiple Coach Events** — works. Effective access is the union of all approved, non-ended rows. A null `event_id` retains legacy Sport-wide scope; a legacy `sport_category_id` narrows that compatibility scope without replacing Event assignments.
7. **Athlete -> Sport** — `sport_roster_members -> meet_sports -> sports` is canonical and unique by meet Sport plus Athlete.
8. **Eligibility/accreditation** — DSAC eligibility comes from the meet-scoped `eligibility_reviews` state; accreditation and medical clearance remain separate requirements.
9. **Individual entry** — `entries` links one Athlete to one Event and Delegation. `UNIQUE(athlete_id, event_id)` prevents only a duplicate in the same Event.
10. **Team entry** — `team_entries` identifies Delegation plus Event; `team_entry_members` references existing Athlete and Entry rows. It does not duplicate Athlete identity.
11. **Multiple Events** — supported because uniqueness includes `event_id`; a configured meet-level `max_events_per_athlete` is enforced only when non-null.
12. **Individual plus team** — supported when they are different Events. Team membership reuses the canonical Athlete and creates/reuses the Event-specific Entry.
13. **Relevant unique constraints** — safe: roster `(meet_sport_id, athlete_id)`, individual entry `(athlete_id, event_id)`, team `(delegation_id, event_id)`, team member `(team_entry_id, athlete_id)`. No global `(athlete_id, meet_sport_id)` competition-entry constraint exists.
14. **Previous readiness assumptions** — hierarchy was already Sport -> Event, but Athlete totals were derived only from entry rows, Coach coverage ignored Sport-wide compatibility assignments, and Event rows did not expose roster/eligible/entered counts separately.

## Category dependency classification

### A. Required legacy data

- `sport_categories`, nullable `events.sport_category_id`, nullable schedule and assignment category FKs.
- `sport_category_competition_areas` and existing schedule availability data.
- Category-scoped tournament personnel roles and historical assignments.

### B. Presentation label only

- Public portal age/sex groupings, scoreboard classification strings, medal filters, FAQs, equipment categories, and DRRM checklist categories. These are not an operational Sport hierarchy.

### C. Compatibility code

- `CoachAccessService` expands approved null-Event Sport-wide rows and optionally narrows legacy category-scoped rows to their Events.
- `CompetitionAccessService`, result access, user management, and public personnel display resolve historical category-scoped tournament assignments.
- Team minimum/maximum currently falls back to legacy category configuration when Event `team_size` is insufficient.
- Schedule competition-area resolution retains category availability records where production configuration exists.

### D. Incorrect business-logic dependencies found

- Coach Athlete registration created an Event entry immediately. Corrected: it now creates Sport roster membership only.
- Sport roster validation was generic in the data model but enforced only for Swimming entry paths. Corrected for every individual and team Event.
- Individual Event submission did not require DSAC eligibility (eligibility was checked only at confirmation). Corrected at submission; medical clearance is also enforced when the Meet requires it.
- Readiness counted entry athletes as though they were the Sport roster and did not report roster, eligibility, and entered Athlete metrics independently. Corrected.
- Readiness Coach coverage considered only explicit Event assignment rows and could lose intended Sport-wide access. Corrected with union semantics.

### E. Safe to deprecate later

- Category-specific schedule UI and category tournament-role terminology can be retired only after production availability/personnel data is mapped to explicit Events or documented Sport-wide scope.
- Public components named `sport-categories` may be renamed after confirming they are intended to show Sports Events rather than general public groupings.

## Adjustments by priority

### Critical

- Separate Athlete registration/Sport roster membership from Event entry.
- Require canonical Sport roster membership and eligibility for individual and team entry submission.
- Preserve Coach multi-Event union and Delegation enforcement.

### Required

- Readiness hierarchy and counts are Sport -> Sports Event.
- Report Sport roster Athletes, eligible Athletes, pending eligibility, and Athletes with entries separately.
- Evaluate approved Coach coverage per Event, including compatible Sport-wide historical scope.
- Validate actual entered Athletes rather than treating an entry row alone as ready.

### Cleanup only

- Continue changing operational user-facing “Category” wording when the value is truly a Sports Event.
- Migrate legacy category-based venue/personnel configuration only under a separately approved production-data plan.

## Database migrations and production risk

No migration is required for this alignment. No table, column, assignment, roster membership, Athlete, or entry is deleted or rewritten. Existing legacy Category rows and nullable FKs remain intact. Risk is limited to intentionally stricter future entry submission: an Athlete must now be on the applicable Sport roster and eligible before a submitted entry can be created. Historical entries are preserved.

## Validation matrix

- Coach Event access: union of active approved assignments; unassigned Events denied.
- Athlete identity: one Athlete row reused across individual and team relationships.
- Sport membership: canonical roster membership is independent of Event entry.
- Eligibility: registration alone is insufficient; DSAC approval is required before submission.
- Event multiplicity: same Athlete may enter different Events; same Athlete plus same Event remains rejected.
- Team validity: every member must be rostered, eligible, in the same Delegation, and satisfy configured Event requirements.
- Readiness: Sport and Sports Event totals, Event Coach coverage, roster/eligible/entered Athlete counts, venue, schedule, personnel, and invalid-entry issues are independently visible.
