# PMMS UAT Preparation Materials

Materials for whenever the Division schedules a real User Acceptance
Testing session — **not a record that one has happened**. Per owner
decision 2026-07-26 (see `docs/phases/phase-06-reports-uat-deployment-
turnover/README.md`'s Grounding section), this directory was prepared
without real testers or a timeline; running an actual session with real
people is separate, future, real-world work.

Every script here is a numbered checklist of **real app flows**, verified
against the actual running app (not invented scenarios) — cross-referenced
throughout against `docs/manuals/` (WP-06-05), which explain each screen
in more depth than a test script needs to repeat.

## What's here

| File | Role |
|---|---|
| `admin-script.md` | Administrator |
| `organizer-script.md` | Meet Organizer |
| `delegation-officer-script.md` | Delegation Officer |
| `viewer-script.md` | Viewer |
| `public-guest-script.md` | Anyone, no account (public portal) |
| `feedback-template.md` | One copy per tester per script — fill in during the session |

## Environment setup (do this before any tester starts)

**Never point UAT at the production database.** Use a dedicated copy:

1. On a UAT/staging copy of the app (same code, its own MySQL database —
   see `docs/backup-restore.md` if you're restoring from a production
   backup to sanitize first, or just a fresh local clone), run:
   ```
   php artisan migrate:fresh --seed
   ```
   This gives every tester the same real reference data to start from:
   the 11 real Davao de Oro municipalities, the sports/events catalog,
   and `SampleProvinceDemoSeeder`'s small demo — two sample municipal
   delegations ("Sample Municipality — Alpha," "Sample Municipality —
   Bravo"), three sample athletes, one validated 100m dash result — a
   published, Active "Sample Provincial Meet" testers can look at
   immediately without creating anything first.
2. **Create one test account per role.** Nobody signs up already holding
   a role above Viewer (see `docs/manuals/admin-manual.md` §2 — this is a
   real, current limitation, not a UAT-only step):
   - Sign in as the seeded Administrator (`PMMS_ADMIN_EMAIL`/
     `PMMS_ADMIN_PASSWORD` from `.env`) for the Admin script, or
     self-register a separate `uat-admin@…` account and promote it the
     same way as the others below.
   - Have your Organizer, Delegation Officer, and Viewer testers each
     self-register their own account at `/register` (they'll all start as
     Viewer).
   - Using console access to the UAT database, promote the Organizer and
     Delegation Officer testers' accounts:
     ```
     php artisan tinker
     >>> User::where('email', 'uat-organizer@example.test')->first()->forceFill(['role' => 'organizer'])->save();
     >>> User::where('email', 'uat-officer@example.test')->first()->forceFill(['role' => 'delegation_officer'])->save();
     ```
     The Viewer tester's account needs no change — Viewer is already the
     default.
3. **Assign the Delegation Officer tester to a delegation** — signed in as
   the Administrator or Organizer tester, go to Delegations → open
   "Sample Municipality — Alpha"'s **Officers** dialog and add the
   officer tester's account. (The organizer script also registers a
   brand-new delegation for a new meet and assigns the officer tester
   to *that* one — see the run order below — but having them already
   attached to Alpha
   means step 1 of their own script, "see your own delegations," has
   something real to show immediately.)

## Running order

The scripts aren't fully independent — a Delegation Officer can't
register into a meet that doesn't exist and isn't open for registration
yet, and an Organizer can't validate a result for an entry nobody
submitted. Run them in this order, with two people (or one person playing
two roles) coordinating the middle handoff:

1. **Admin script** — independent, run any time.
2. **Organizer script, Part A** (steps 1–7: create and publish a new
   meet, add a venue, register a delegation, and assign the officer
   tester to it — registering the delegation is a manager action, not
   the officer's) — stop at the checkpoint marked in the script.
3. **Delegation Officer script, Part A** — submits the delegation the
   Organizer just registered and assigned them to, registers two
   athletes and entries into the meet's event — stop at its own
   checkpoint.
4. **Organizer script, Part B** (resume from the checkpoint: approve the
   delegation, confirm the entries, schedule the event, run a match and
   a live scoring session, encode and validate a result, check the
   tally, generate reports) — needs the Officer's Part A work to have
   something to approve/confirm/encode.
5. **Delegation Officer script, Part B** — read-only verification of
   what the Organizer's Part B produced (the match, live session,
   validated result, tally), from the officer's own restricted view.
6. **Viewer script** — best run last, since it's read-only verification
   that everything the earlier scripts did is now visible correctly.
7. **Public guest script** — also best last (and its live-scoreboard step
   is timing-sensitive against the Organizer's Part B, step 14 — see the
   script itself), and needs the Organizer to have published the meet in
   Part A step 4.

A single tester can run several scripts back-to-back by switching
accounts (sign out, sign back in as the next role) if you don't have four
separate people available — the important thing is the *order* above,
not who's sitting at the keyboard.

## Capturing feedback

Copy `feedback-template.md` once per tester per script (e.g.
`feedback-organizer-2026-08-15.md`) and fill in **Actual result**,
**Pass/Fail**, and **Notes** for each numbered step while running the
script — this is a plain offline document, not a new in-app feature.
There is no in-app feedback tool and none is planned for this phase.

## Triaging completed feedback

After a session:

1. Collect every tester's filled-in feedback file.
2. Any step marked **Fail** is a candidate bug — file it the same way any
   other bug would be tracked in this project (a normal work item, not a
   special "UAT bug" category), quoting the script name and step number
   so it's reproducible.
3. Any step marked **Pass** with a **Notes** entry that reads like a
   product suggestion rather than a defect (e.g. "this would be easier
   if…") is feedback for a future phase's scoping conversation, not
   something to act on unilaterally mid-session.
4. Re-run only the failed steps after a fix, not the whole script, unless
   the fix could plausibly have touched earlier steps too.

## See also

- `docs/manuals/` — the task-oriented manuals these scripts assume the
  tester has read, or can refer to mid-step if a screen is unfamiliar.
- `docs/phases/phase-06-reports-uat-deployment-turnover/README.md` — why
  this phase prepares materials only, and what the rest of Phase 6 covers.
