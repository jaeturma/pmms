# UAT Script — Delegation Officer

Sign in as the Delegation Officer test account. This script has two
parts split by a checkpoint that hands control back to the Organizer
tester — see `README.md` "Running order." Cross-reference:
`docs/manuals/delegation-officer-manual.md`.

Requires the environment setup's officer-assignment step (README §2) and
the Organizer script's Part A (steps 1–7, which register a delegation for
"UAT Test Meet" and assign you as its officer) to already be done.

## Part A — after the Organizer script's Part A

1. **Sign in and orient yourself.** Sidebar → Delegations. Confirm you
   can see **two** delegations: "Sample Municipality — Alpha" (from the
   seeded demo data, per the environment-setup step) and the new one the
   Organizer just registered for "UAT Test Meet." Confirm you do **not**
   see a "Register delegation" button anywhere — that action is
   manager-only, not something your role can do (see
   `docs/manuals/delegation-officer-manual.md` §1 if this surprises you).
   **Expected:** exactly two delegations visible, both showing your own
   as an assigned officer; no create/register control anywhere on the
   page.
2. **Edit head contact.** Open the "UAT Test Meet" delegation (still
   Draft) and update the head-of-delegation phone/email.
   **Expected:** saves successfully — this is allowed while Draft and
   registration is open.

**Order matters for the next few steps**: athlete/personnel registration
requires the delegation to still be **Draft** — submitting it too early
locks you out of adding more roster members. Get the whole roster ready
first, submit last (steps 3–7 before step 8).

3. **Register two athletes.** Sidebar → Athletes → **Register athlete**,
   twice, into the "UAT Test Meet" delegation: pick a **Home school**
   (narrowed to schools in your municipality — pick two *different*
   schools if more than one option appears, to exercise the pooled-
   municipality case; otherwise the auto-selected single school is
   fine), name, sex (Boys), birthdate that makes them Secondary grade
   level (e.g. grade 10), a 12-digit LRN, no photo needed.
   **Expected:** both athletes appear in your Athletes list with the
   correct home school shown — **not** editable if you try to change it
   afterward.
4. **Register one coach (optional, bonus coverage).** Sidebar →
   Personnel → **Register personnel**: role "Coach," any name/home
   school, then open **Sports for {name}** and check one sport.
   **Expected:** personnel record created with the sport assignment
   saved.
5. **Upload an eligibility document.** Sidebar → Eligibility →
   **Upload eligibility document** for one of your two athletes: type
   "Birth certificate," any small PDF/JPG/PNG under 10 MB.
   **Expected:** a pending review is created automatically for that
   athlete; deciding it (Approve/Return) is a manager action, not
   covered further in this script.
6. **Submit both entries.** Sidebar → Entries → **Submit entry** for
   each athlete into **100 Meter Dash — Boys, Secondary**. (This step
   doesn't need the delegation to be Draft — entries submit
   independently of the delegation's own status — but doing it before
   step 8 keeps this script's order simple to follow.)
   **Expected:** both entries appear with status "Submitted"; if either
   athlete's sex/grade doesn't match the event, PMMS refuses it with a
   clear error — confirm that by trying it once with a mismatched grade
   before fixing it, if you have time.
7. **Confirm the "Eligibility pending" badge.** Back on Entries, look at
   the athlete whose document is still pending (step 5).
   **Expected:** an "Eligibility pending" badge shows next to that
   entry — this is a reminder only; the entry is still fully submitted.
8. **Now submit the delegation.** Back on Delegations, click **Submit**.
   **Expected:** status changes to "Submitted"; head-contact editing and
   adding further athletes/personnel are no longer available to you
   (only a manager can act on the delegation now) — try opening
   **Register athlete** again and confirm this delegation no longer
   appears as an option.

## ⏸ Checkpoint

**Stop here and hand back to the Organizer tester** — they need both
entries submitted (step 6) and the delegation submitted (step 8) before
they can confirm the entries, approve your delegation, run a match, and
validate a result. Resume Part B once they report their script's Part B
is complete.

## Part B — after the Organizer script's Part B

9. **Sign back in.** Sidebar → Matches. Confirm you can see the match
   the Organizer created for the 100 Meter Dash event, with both your
   athletes listed as participants — but no Edit/Participants/status
   buttons (those are manager-only for you). Open its **Live** link;
   confirm the scoreboard shows the final score read-only, with no
   operator controls (no quick-score buttons, no Start/Correct/End).
   **Expected:** match and its live-session history are visible; nothing
   on the page is editable by you.
10. **Check the result.** Sidebar → Results. Confirm the validated 100m
    dash result shows both your athletes' placements and marks.
    **Expected:** visible, read-only, matches what the Organizer encoded.
11. **Check the medal tally.** Sidebar → Medal tally, filtered to "UAT
    Test Meet." Confirm your municipality shows the gold and silver from
    your two athletes' placements.
    **Expected:** matches step 10 exactly.
12. **Roster and IDs.** From your delegation's row: open **Roster** (view
    and print your own athletes/personnel) and, since it's now Approved,
    **IDs** (accreditation status per person — expect "not yet
    accredited" for both, since accrediting is a manager decision this
    script doesn't exercise).
    **Expected:** both pages render correctly for your delegation only.

## See also

`docs/manuals/delegation-officer-manual.md` (every step above is
explained in more depth there), `docs/delegations.md`, `docs/athletes.md`,
`docs/personnel.md`, `docs/eligibility.md`, `docs/entries.md`.
