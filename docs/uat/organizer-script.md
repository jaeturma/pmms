# UAT Script — Meet Organizer

Sign in as the Organizer test account. This script has a **checkpoint**
partway through where the Delegation Officer script must run before you
continue — see `README.md` "Running order." Cross-reference:
`docs/manuals/organizer-manual.md`.

Use a clearly-named test meet throughout so it's never confused with real
data: **"UAT Test Meet."** It's expected to stay in the UAT database as
test data once created — no cleanup step at the end, unlike the
Administrator script's incidental registry additions.

## Part A — before the Delegation Officer script

1. **Create the meet.** Sidebar → Meets → create: name "UAT Test Meet,"
   school year matching the current one (e.g. "2025-2026"), start/end
   dates a few days apart, venue text "TBD."
   **Expected:** meet appears in the list, status "Draft."
2. **Attach an event.** Open "UAT Test Meet"'s **Events** checklist and
   attach **100 Meter Dash — Boys, Secondary** (the same event
   `SampleProvinceDemoSeeder` already uses, guaranteed to exist).
   **Expected:** the event now shows under this meet's Events count.
3. **Open registration.** Click **Open registration** on the meet row.
   **Expected:** status changes to "Registration Open."
4. **Publish the meet.** Click **Publish** on the meet row (confirm the
   "Publish to public portal?" dialog).
   **Expected:** the row now shows a "Published" indicator; the meet is
   now visible on the public portal at `/` (spot-check this now, or leave
   it for the Public Guest script).
5. **Add a venue.** Sidebar → Venues → add "UAT Test Venue" (any
   address/notes).
   **Expected:** the venue appears in the Venues list, active.
6. **Register a delegation.** Sidebar → Delegations → **Register
   delegation**: pick a real municipality (e.g. "Compostela," not one of
   the "Sample Municipality —" seed rows) for "UAT Test Meet," with any
   head-of-delegation contact.
   **Note this deliberately, it's not obvious from the UI alone:
   registering a delegation is a manager action.** A Delegation Officer
   cannot create this record themselves — only assign one to work on it
   once it exists (next step).
   **Expected:** delegation appears in the list, status "Draft."
7. **Assign the officer tester.** Open the new delegation's **Officers**
   checklist and add the Delegation Officer test account.
   **Expected:** the officer tester now sees this delegation on their own
   Delegations page.

## ⏸ Checkpoint

**Stop here.** Hand off to the Delegation Officer tester now — they need
the delegation from steps 6–7 to exist, assigned to them, and "UAT Test
Meet" to be Registration Open, before their script can submit it and
register athletes/entries. Resume Part B once they report back that
their script's steps are done (they should have submitted the delegation
with two athletes, both entered into the 100 Meter Dash event you
attached in step 2).

## Part B — after the Delegation Officer script

8. **Approve the delegation.** Sidebar → Delegations → find the
   now-submitted delegation from step 6 and click **Approve**.
   **Expected:** status changes to "Approved"; "IDs" link becomes
   available on the row.
9. **Close registration.** Back on Meets, click **Close registration**
   on "UAT Test Meet."
   **Expected:** status changes to "Registration Closed."
10. **Schedule the event.** Sidebar → Schedule → add a slot: "UAT Test
    Meet" → the 100 Meter Dash event → "UAT Test Venue" → any date within
    the meet's range → a start/end time.
    **Expected:** the slot appears in the schedule list for the right day
    and venue; scheduling was refused before step 9 (registration still
    open) if you try it out of order — confirm that's true by trying it
    before step 9 in a second pass, or just note it from the manual.
11. **Confirm both entries.** Sidebar → Entries, filtered to the 100
    Meter Dash event → **Confirm** each of the officer's two submitted
    entries.
    **Expected:** both entries show status "Confirmed."
12. **Start the meet.** Back on Meets, click **Start meet** on "UAT Test
    Meet."
    **Expected:** status changes to "Active."
13. **Create a match.** Sidebar → Matches → **Add match**: "UAT Test
    Meet" → the 100 Meter Dash event → the schedule slot from step 10 →
    round label "Final" → sequence 1. Open **Participants** and check
    both confirmed entries.
    **Expected:** match created, status "Scheduled," both entries listed
    as participants.
14. **Run live scoring on the match.** Click the match's **Live** link
    (or go to `/matches/{id}/scoreboard`). **Start live scoring** — side
    labels should pre-fill from the two entries' schools since there are
    exactly two. Add a few points to each side with the quick-score
    buttons, run one **Correct** (with a reason), update the period/
    status text, **pause**, then **resume**. Finally **End** the session
    (read the confirmation dialog's reminder that the official result
    still needs encoding).
    **Expected:** running score updates live on screen at every step;
    ending the session does not create or change any result (verify this
    on the Results page — nothing appears there from live scoring alone).
15. **Encode the result.** Sidebar → Results → **Encode result** for the
    100 Meter Dash event: rank 1 and rank 2 for the two confirmed
    entries, with a mark each (e.g. "11.42s" / "11.58s").
    **Expected:** result appears with status "Encoded," visible to you
    but not yet official.
16. **Validate it.** Click **Validate** on the encoded result.
    **Expected:** status changes to "Validated"; the result becomes
    read-only (no more Edit/Delete on it) and now visible to every role
    including the public portal.
17. **File and decide a protest (optional but recommended).** Sidebar →
    Protests → **File protest** against the result you just validated,
    any grounds text. Then **Review** it, then **Decide protest #N** —
    choose Dismiss (or Uphold, then use the pre-filled **"Correct
    result"** shortcut it offers to actually change the placements,
    confirming it reuses the exact same correction flow as step 15/16).
    **Expected:** protest moves filed → under review → decided; if
    upheld and corrected, the result reopens to Encoded and needs
    re-validating.
18. **Check the medal tally.** Sidebar → Medal tally, filtered to "UAT
    Test Meet." Confirm the municipality from step 6 shows 1 gold + 1
    silver, and both placed athletes' individual schools show correctly
    underneath (both athletes' home schools, not the municipality name).
    **Expected:** matches the placements from step 15/16 exactly.
19. **Generate reports.** From the validated result card, open the
    **Sheet** report (print preview + CSV download). From the
    delegation's row, open **Roster** (print + CSV). From Medal tally,
    open the **Printable report**.
    **Expected:** all three render correctly and their Download buttons
    produce a CSV file; check the Audit log afterward for matching
    `report.*_exported` entries.
20. **Log and resolve an incident.** Sidebar → Incidents → **Log
    incident**: description, severity "minor," medical referral "no."
    Then **Resolve** it.
    **Expected:** incident created, then shows status "Resolved."
21. **Publish an announcement.** Sidebar → Announcements → **New
    announcement**: tie it to "UAT Test Meet," any title/body, then
    **Publish**.
    **Expected:** it now appears on the public meet page (verify in the
    Public Guest script, or spot-check now).

## See also

`docs/manuals/organizer-manual.md` (every step above is explained in more
depth there), `docs/meets.md`, `docs/venues.md`, `docs/scheduling.md`,
`docs/delegations.md`, `docs/entries.md`, `docs/matches.md`,
`docs/live-scoring.md`, `docs/results.md`, `docs/protests.md`,
`docs/medal-tally.md`, `docs/reports.md`, `docs/announcements.md`.
