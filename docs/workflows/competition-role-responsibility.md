# Competition role responsibility

## Current competition record

PMMS uses `EventMatch` (`matches`) as the competition/game/bout record and `EventSchedule` as its optional date, time, venue, and court/table slot. A result is `EventResult`, linked by `match_id` and `event_schedule_id`. It inherits its meet, event, participants, schedule, venue, and playing area from that scheduled match. The result-entry UI therefore selects a completed scheduled competition, not a second sport/category/venue context.

The lifecycle is one connected chain:

`Entry -> EventMatch -> EventSchedule -> ScoringSession (optional) -> EventResult -> signed Result Form -> Event Secretariat review -> Official`

The existing status fields express the conceptual stages: planned/scheduled (`EventMatch`), live (`ScoringSession`), completed/result pending (`EventMatch`), encoded/returned/submitted/validated/official (`EventResult`). They are deliberately not duplicated into another competition table.

## Responsibility matrix

`A` accountable, `R` responsible, `S` support, `V` view, `-` no operational access. “Source” means the sporting authority/certification source, not PMMS approval authority.

| Process | TM | Asst TM | Secretary | ICT | TO | Event Secretariat |
|---|---:|---:|---:|---:|---:|---:|
| Competition format | A/R | S | S | V | S | - |
| Draw/bracket | A/R | S | S | V | S | - |
| Create match/event | A | S | R | V | V | - |
| Schedule | A | S | R | V | V | - |
| Start competition | A | S | R | S | S | - |
| Live score | A | S | S | R | Source | - |
| Manual result | A | S | R | S | Source | - |
| Confirm result | A/R | R (delegated) | V | V | V | V |
| Generate form | A | S | R | S | V | V |
| Upload signed form | A | S | R | S | V | V |
| Submit | A | S | R | S | V | V |
| Review | V | V | V | V | V | R |
| Return | V | V | V | V | V | R |
| Make official | V | V | V | V | V | A/R |
| Standings/bracket | V | V | V | V | V | V |
| Medal tally | V | V | V | V | V | V |

Standings, bracket progression, ranking, and medal tally updates are system automation after the relevant result becomes official.

## Enforced hand-offs

- Routine manual encoding starts from a completed `EventMatch`; the server rejects entries outside that match.
- Ending live scoring creates the draft result against the same match and schedule.
- A Tournament Manager (or assigned Assistant/discipline/category TM) confirms the sport-level result.
- Result Form generation and submission are blocked for match-linked results until TM confirmation.
- Editing, returning, or reopening a result clears confirmation and requires confirmation of the new version.
- Tournament Secretary and authorized Tournament ICT personnel may generate/print and upload the signed form; the Secretary normally submits it.
- Event Secretariat access begins with submitted results. It may review, return, validate, and make official, but it does not create competition records or encode the sporting result.
- Technical Officials remain the sporting source/certifier and live-operation support; they do not make a PMMS result official.

## UI vocabulary

Operational queues should use these derived labels without creating new records: Upcoming, Ready, Live, Awaiting Result, Result Draft, Awaiting TM Confirmation, Ready for Result Form, Ready for Submission, Returned, Submitted, and Official. The action always opens or updates the existing match, schedule, scoring session, or result.
