# Provincial Meet Competition Workflow Alignment

Audit date: 2026-08-21

## 1. Existing Workflow

PMMS currently links a meet event to schedule slots and optionally links a `Match` to one schedule slot. Confirmed `Entry` records are attached to matches through `match_entries`. Live scoring belongs to a match and keeps an append-only score-event history. Results, however, are stored once per `meet_id + event_id`, accept arbitrary confirmed entries from that event, and do not reference a match or schedule. Ending live scoring does not populate a result. Result Form generation selects the first schedule slot for the event rather than the competition that produced the result.

The later workflow is already implemented: versioned Result Forms, signed PDF/image attachments, submission, required return reasons, Event Secretariat validation, explicit make-official action, audited reopening, and official-only public results and medal tally.

## 2. Target Workflow

`Coach -> Athlete -> Eligibility/Medical -> Confirmed Entry -> Scheduled Match/Competition -> Live or Manual Result -> Result Form -> Signed Attachment -> Submission -> Event Secretariat -> Official -> Standings/Medals`.

`Match` is the existing scheduled-competition aggregate: it references the meet/event and schedule slot, owns participants, and owns live-scoring sessions. A result should belong to that Match and inherit its event, category, venue, time, competition area, round, sequence, and participants.

## 3. Already Aligned

- Coach self-registration and approved assignment scope exist.
- Athlete registration, distinct DSAC eligibility, Medical clearance, and DSC monitoring exist.
- Entries reference registered athletes/events and support confirmed status.
- Match participants reference Entries rather than free-text names.
- Live scoring is match-scoped and audited.
- Signed Result Form uploads are versioned and accept PDF/JPG/JPEG/PNG.
- Submission requires current-version signed form and placements.
- Event Secretariat return, validation, make-official, and reopening are role-scoped and audited.
- Public results and `MedalTallyService` read only `Official` results.

## 4. Gaps

- `event_results` has no `match_id`/`event_schedule_id` and is unique per meet/event.
- Generic result creation asks for meet, event, and arbitrary event entries again.
- A result can be encoded without a scheduled competition or completed match.
- Result Form generation guesses the first event schedule.
- Live-scoring finalization does not create/populate a draft result.
- Live scoring is not configurable per match.
- Competition area and medal-producing-stage intent are not represented on Match.
- Bracket advancement is not currently modeled as an official-result transition.

## 5. Duplicate Logic

`event_results.meet_id` and `event_id` duplicate context available through Match, but must remain as historical snapshots and compatibility columns for existing production rows. They will be derived from Match for every new result and cannot be selected independently. Existing rows are preserved and backfilled only where one unambiguous match exists.

## 6. Required Changes

- Add nullable historical `match_id` and `event_schedule_id` links to existing results; require Match for all newly created results.
- Replace the meet/event create picker with completed scheduled competitions.
- Restrict placements to the selected Match's participants.
- Add per-Match live-scoring and medal-stage flags plus optional competition area.
- Populate the same draft Result architecture when live scoring ends.
- Use the exact Match schedule in Result Forms and submission validation.
- Prevent more than one Result per Match.

## 7. Database Changes

Additive, non-destructive columns and indexes only. Existing result rows, placements, attachments, statuses, and snapshot columns remain intact. The old meet/event unique constraint must be removed because an event may contain multiple scheduled matches; a unique nullable `match_id` prevents duplicate competition results.

## 8. Backend Changes

Centralize manual/live creation in a competition-result service, derive all context from Match, validate completed status and participants, and audit `result.manually_entered` or `result.created_from_live_score`. Result submission and forms must resolve the exact schedule from the result's Match.

## 9. Frontend Changes

Result encoding starts from a completed scheduled competition. Sport, category, round, participants, venue, time, and competition area are displayed as read-only context. Match management exposes Live Scoreboard and Awards Medals flags.

## 10. Permission Changes

Retain existing meet-sport assignment checks. Tournament Manager, Assistant TM, Tournament Secretary, and Tournament ICT may manage forms/submission within assigned sport. Manual result entry is scoped to approved competition personnel. Event Secretariat alone validates/makes official, with Admin emergency authority retained.

## 11. Tests Required

Cover result-without-match rejection, inherited context, participant restriction, duplicate prevention, live enabled/disabled paths, live draft creation, exact schedule form context, completed-match and signed-form submission requirements, Event Secretariat return/official actions, official-only output, medal-stage filtering, and sport-scoped authorization.

## 12. Data Migration Concerns

Existing event-level results may not map unambiguously to one Match. They must not be guessed or deleted. Such rows remain legacy historical results with null Match links and keep their existing official/tally behavior. Operators should resolve them through a separately reviewed data-mapping exercise. No production result, attachment, placement, or audit row is rewritten destructively by this alignment.
