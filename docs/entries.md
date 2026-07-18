# Event Entry Submission

WP-02-08. Athletes enter catalog events within their meet, under the full rule set.

## Data model

`entries` — `delegation_id` (restrict), `athlete_id` (cascade), `event_id` (restrict),
`status` (`App\Enums\EntryStatus`: `submitted → confirmed | withdrawn`, not mass
assignable). Unique per athlete+event; the delegation is always derived from the athlete
server-side, never taken from the request.

## Submission rules (all enforced in `EntryController::store`, each tested)

1. The event must be attached to the athlete's meet (`meet_events`).
2. The athlete's sex must match the event's gender category
   (`GenderCategory::accepts()`; mixed accepts both).
3. The athlete's grade-derived age division must match the event's
   (`Athlete::ageDivision()`: grades 1–6 elementary, 7–12 secondary — grade-based
   because age-based cutoffs are a policy decision PMMS defers).
4. No duplicate entry for the same athlete and event.
5. The per-delegation entry cap (`events.max_entries_per_delegation`) counts only
   non-withdrawn entries.
6. Officers may submit only for their own delegation while the meet's registration
   window is open (managers bypass the window). Entries do **not** require the
   delegation to still be a draft — rosters freeze at submission, entries don't.

## Lifecycle & authorization (EntryPolicy)

- Confirm: managers only, submitted entries only.
- Withdraw: officers for their own still-submitted entries while registration is open;
  managers anytime (including confirmed entries).
- Delete: withdrawn entries only (frees the athlete+event slot for re-entry).
- Viewers have no access (entries expose athlete names — minor data).

Audit: `entry.submitted|confirmed|withdrawn|deleted` with athlete/event context.

## UI

`entries/index.tsx` — filterable by event and by delegation (satisfying the WP's
per-event and per-delegation list requirement in one page), paginated, with a
submit dialog whose event choices narrow to the selected athlete's meet. Actions render
from server-computed `can_*` flags. Sidebar entry: Entries.

## Out of scope (per WP)

Seeding, draws, heats, lane assignment (Phase 3), team-event roster composition beyond
athlete lists, scoring, schedules.
