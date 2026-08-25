# Swimming competition alignment

## 1. Existing entry architecture

PMMS already registers an athlete once in `athletes` and reuses that record through `entries`. The unique key on `(athlete_id, event_id)` prevents a duplicate in the same event while allowing the athlete to enter multiple different events. `EntryController` already accepts several event IDs in one request and applies meet, delegation, gender, level, per-event cap, and configurable meet event-limit checks.

## 2. Existing team and relay architecture

`team_entries` owns a delegation's entry in a team, pair, doubles, group, or relay event. `team_entry_members` links any number of existing athletes and their event entries to it. This is the correct relay model; no swimming-specific result or four fixed athlete columns are needed. The only missing relay field is member order, added generically as `member_order`.

## 3. Existing roster-limit support

Event-level entry caps and team sizes exist, but there is no delegation/category roster independent of event entries. Add generic `sport_roster_limits` and `sport_roster_members` tables. A roster is keyed by meet sport, delegation, level, and gender. Swimming is configured as 11 Elementary Boys, 11 Elementary Girls, 12 Secondary Boys, and 12 Secondary Girls.

## 4. Existing event metadata

`events` already has stable `code`, name, `event_type`, discipline, free-form distance, team size, gender, age division, display order, and medal flag. Gender and age division are equivalent to the requested gender and level fields; `event_type` is the broader generic entry type. Missing normalized fields are event number, numeric distance, stroke code, relay legs, and relay leg distance.

## 5. Missing fields and tables

- `events.event_no`, `distance_meters`, `stroke`, `relay_legs`, and `relay_leg_distance_meters`
- `team_entry_members.member_order`
- generic `sport_roster_limits`
- generic `sport_roster_members`

All changes are additive and preserve athletes, entries, schedules, and results.

## 6. Proposed migration

Add the fields above, enforce one roster-limit row per `(meet_sport_id, level, gender)`, one roster membership per athlete/category, and one member order per team entry. Foreign keys use restrictive deletes for competition records. No existing tables are dropped or rebuilt.

## 7. Official 72-event mapping

`SwimmingCompetitionSeeder` is the executable canonical mapping. It preserves event numbers 1–72 exactly, creates four category records, assigns stable descriptive codes, and stores individual/relay metadata. Events 15–18, 33–36, 51–54, and 67–70 are four-leg relays; all other events are individual. Seeder updates are keyed by the existing event natural key and never delete entries, schedules, or results.

## 8. Coach workflow

A coach works inside an authorized delegation and Swimming scope: select Elementary/Secondary and Boys/Girls, add an existing athlete to the category roster, then assign that roster member to one or more individual events. The roster counter is validated transactionally. Final competition readiness requires DSAC approval and medical clearance; draft roster membership itself does not duplicate the Athlete.

## 9. Relay workflow

A relay remains a generic `TeamEntry` with four ordered `TeamEntryMember` rows. Validation requires four distinct roster athletes from the same delegation and matching level/gender. Finalization retains the existing eligibility and medical checks and locks the lineup after confirmation or results.

## 10. Scheduling and result implications

The seeded row is the master Event, not a heat. Existing event schedules/matches represent heats and finals without duplicating the event. Results remain `EventResult`/`ResultPlacement` records tied to scheduled competition context. A relay placement points to one `team_entry`, so its delegation receives one medal; the existing athlete achievement projection exposes that medal to each snapshotted member.

## 11. Tests

Coverage verifies all four roster limits and overflow rejection, multiple individual entries, individual plus relay participation, same-event uniqueness, four ordered relay members, roster/delegation/category enforcement, all 72 event numbers and metadata, and the existing one-medal/multiple-member-achievement behavior.
