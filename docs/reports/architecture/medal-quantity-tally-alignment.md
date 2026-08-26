# Medal Quantity and Tally Alignment

## Current architecture

- `events.is_medal_event` is the only event-level medal setting. There is no independent physical-piece or official-tally quantity configuration.
- `ResultWorkflowController::makeOfficial()` changes an `EventResult` to `official`; it creates no immutable medal consequence.
- `MedalTallyService` reads rank 1–3 `ResultPlacement` rows from official results at request time. Its `medalUnits()` method hard-codes every team result to one logical medal and every individual placement to one medal.
- Gold/silver/bronze points are calculated after those placement counts, so points currently inherit the hard-coded unit count.
- Athlete achievements correctly read individual/team placement membership and must remain separate from delegation tally quantities.

## Existing event configuration

`Event` currently carries `event_type`, `team_size`, `relay_legs`, `is_team_event`, and `is_medal_event`. None is a valid substitute for an explicit tally rule. The event catalog create/edit form currently exposes only the basic event and venue fields.

## Physical medal logic

There is no authoritative physical-medal calculation or report. Team roster size exists, but using it automatically would violate the meet-rule requirement. A dedicated event configuration and an official-result snapshot are required.

## Official tally logic

All internal/public consumers use `MedalTallyService`, directly or through `TallyController`, `PortalController`, dashboard data, and `ReportController`. Aligning that service to immutable award snapshots will align:

- internal and public official tally;
- medals by sport and recent medal cards;
- municipality/delegation summaries;
- ranking points; and
- printable/downloaded tally reports.

Top-medalist and medal-winner lists remain placement-based athlete achievements and must not be multiplied by tally quantity.

## Persistence migration

Add:

1. `event_medal_configs`, one authoritative configuration per event, with explicit physical and tally quantities for each medal type.
2. `medal_awards`, immutable/synchronized consequences of an official result containing recipient context plus snapshotted `physical_quantity` and `tally_quantity`.

Normal individual medal events without a stored configuration safely resolve to 1/1/1 physical and 1/1/1 tally. Team/pair/relay/group events without explicit values remain `MEDAL_CONFIGURATION_REQUIRED` and cannot newly become official.

## Result finalization and corrections

Making a result official must atomically snapshot its configured quantities. Editing an event configuration never mutates existing snapshots. Recalculation of an official result must be a separate authorized action requiring a reason and producing an audit record with actor and timestamp.

## Reports affected

Official-tally consumers must sum `medal_awards.tally_quantity`. Physical logistics reports must sum `medal_awards.physical_quantity` (actual official awards) or event configuration physical quantities (planned requirements), and must be labeled **Physical Medal Pieces**. Event/configuration review reports must show both quantity sets and flag incomplete medal-producing team-style events.

## Public portal and municipal pages

Public tally, medals by sport, sport mini-portal summaries, and municipal totals are downstream of `MedalTallyService` and must use tally snapshots. Athlete achievement cards remain one achievement per athlete and are not a tally source.

## Unresolved team events

Every existing medal-producing event with `is_team_event = true` (including pair, relay, and group semantics) requires rule-owner review unless an explicit configuration is already available. No quantity will be inferred from team size, relay legs, roster membership, physical quantity, or athlete achievement count.

## Configuration status

- Non-medal event: `NOT_APPLICABLE`.
- Individual medal event without a row: `DEFAULT_INDIVIDUAL_1` (competition-ready safe default).
- Medal event with all six persisted quantities: `CONFIGURED`.
- Team/pair/relay/group medal event without all quantities: `MEDAL_CONFIGURATION_REQUIRED`.

