# PMMS Meet Organizer Manual

For the Meet Organizer role (`organizer`) — everything needed to run a
meet from setup through closing, minus the Administrator-only settings
(user/role administration, division settings, audit log — see
[`admin-manual.md`](admin-manual.md); an Administrator can do everything
below too). Written from the actual shipped app, in the order you'd
typically touch things across a meet's life.

## 1. Your account

See [`admin-manual.md`](admin-manual.md) §1 — Settings (Profile, Security
incl. two-factor and passkeys, Appearance) work the same for every role.

## 2. Setting up a meet

**Sidebar → Meets** (`/meets`).

1. **Create meet**: name, school year (e.g. "2025-2026"), start/end dates,
   venue text. It starts as **Draft**.
2. Pick its **events** from the catalog (a checklist dialog on the meet
   row) — only events attached to the meet can be scheduled or entered
   into later.
3. Move it through its lifecycle with the status buttons on the meet row:
   `Draft → Registration Open → Registration Closed → Active → Completed`.
   Registration Closed can reopen back to Registration Open if you need to
   extend a deadline — every other step only goes forward. Every
   transition is logged. Only a Draft meet can be deleted.
   - **Registration Open** is required before delegations can register or
     submit entries.
   - **Active** is required before you can encode results.
4. **Publish** the meet (a separate action from its status, on the meet
   row) once you want the public portal to show it — see §14. You can
   unpublish at any time; draft meets can't be published.

See `docs/meets.md`.

## 3. Venues

**Sidebar → Venues** (`/venues`). Name, address, and free-text notes
(capacity, facilities, contact person). Archive once no longer in use
rather than deleting if it's already scheduled somewhere.

## 4. Scheduling events into venues

**Sidebar → Schedule** (`/schedule`) — only while a meet is Registration
Closed or Active (the event list is still allowed to change before that,
and the schedule becomes historical record after the meet completes).

Add a slot: meet → event (only that meet's own events are offered) →
venue (active venues only) → date → start/end time → optional note. PMMS
blocks two slots at the **same venue, same day** that overlap in time — it
will tell you which event/time conflicts. Filter the list by meet, venue,
or day; search by event name.

## 5. Delegation review

**Sidebar → Delegations** (`/delegations`) — Municipality registrations
under this Province deployment (a School registration under a City
division).

**Registering a delegation is a manager action, not something a
Delegation Officer does themselves** — worth stating plainly, since it's
easy to assume otherwise. You (or another manager) do it:

1. **Register delegation**: pick the municipality (a school picker
   appears instead under a City-type division), fill in the
   head-of-delegation contact, meet must be Registration Open. It starts
   as Draft.
2. **Assign officers**: open the new delegation's officer checklist and
   pick from users already holding the Delegation Officer role (see
   [`admin-manual.md`](admin-manual.md) §2 for how that role gets
   assigned in the first place — it's not done from this screen). One
   delegation can have several officers. Only after this step can an
   officer see and work with it at all.
3. From there, the officer edits contact details and **submits** it (see
   [`delegation-officer-manual.md`](delegation-officer-manual.md)) — your
   job is the decision once submitted: **Approve** / **Return to draft**
   row actions. Returning sends it back for the officer to fix and
   resubmit.
4. Under Province, one municipal delegation can pool athletes/personnel
   from **several schools** — an assigned officer manages the whole
   pooled roster, not just one school within it. This is intentional; see
   `docs/delegations.md` "Officer roster scope."

Only a **Draft** delegation can be deleted, and only by a manager.

## 6. Accreditation & ID cards

From a delegation's row, open **IDs** (`/delegations/{id}/accreditation`)
— requires the delegation to be **Approved**.

- Each athlete additionally needs an **approved eligibility review**
  (§7) before you can accredit them; personnel don't need one.
- **Accredit** issues a printable ID number (`ACR-{meet}-{n}`); **Revoke**
  removes it (a re-accredited person gets a new number — old numbers are
  never reused).
- Print one card at a time, or **batch-print** the whole delegation's
  accredited cards from the same page. Cards show a photo (or initials),
  name, role/grade, the person's own school (not the municipality, even
  under a pooled delegation), and the accreditation number/date.

## 7. Entries & eligibility review

**Sidebar → Entries** (`/entries`) — Delegation Officers submit entries for
their own athletes; your job is confirming them once submitted:

- **Confirm** a submitted entry (manager-only) once you're satisfied it's
  correct.
- **Withdraw** any entry at any time (an officer can only withdraw their
  own, and only while registration is open).
- Delete is only offered for already-withdrawn entries.

**Sidebar → Eligibility** (`/eligibility`) — the review queue for uploaded
documents (birth certificate, proof of enrollment, report card, parental
consent, other). Pending reviews sort first.

1. Open a document to view it (every view is logged — this is minor
   data).
2. **Approve eligibility for {athlete}?** — optional remarks, terminal:
   no further uploads or re-decisions once approved.
3. **Return {athlete}'s documents?** — remarks are **required** here so
   the officer knows what to fix. Uploading again after a return
   automatically reopens the review to pending.

An entry without an approved review shows an "Eligibility pending" badge
on the Entries page — it's a flag, not a block; the entry can still be
confirmed.

## 8. Matches

**Sidebar → Matches** (`/matches`), filterable by meet/event.

1. **Add match**: meet → event → optional schedule slot → round label
   (e.g. "Heat 1", "Semifinal") → sequence.
2. **Participants**: a checkbox list of that event's **confirmed**
   entries only. A team event allows at most one entry per school on the
   roster (checked by each entry's own school, so a pooled municipal
   delegation can field several different schools' entries on one team);
   an individual event allows several entries from the same school.
   Participants can only change while the match is still Scheduled.
3. **Status**: Complete / Declare walkover / Cancel — all terminal once
   set (each has its own confirmation dialog explaining what it means).

## 9. Running live scoring (optional, per match)

From a match's **Live** column (visible to everyone, but the controls
below only appear for you) or its scoreboard page
(`/matches/{id}/scoreboard`) — only for a **Scheduled** match with no
already-active session.

**Live scoring is provisional and spectator-facing only. It never creates
or changes an official result** — you still encode and validate the
result separately (§10) exactly as if no live session had ever run.

1. **Start live scoring**: side A/B labels are pre-filled from the
   match's two entries when there are exactly two; otherwise type them
   in. If the match's sport has its own dedicated scoreboard (Basketball,
   Boxing, Softball/Baseball today) it's used automatically — tick "Use
   the generic scoreboard instead" at start if this particular match
   (an exhibition, a mixed-rules friendly) doesn't fit that sport's usual
   structure. This choice can't be changed once the session has started.
2. **Score**: +1/+2/+3 quick buttons per side, or a per-side "Correct"
   dialog for anything else (always requires a reason). Score never goes
   below zero.
3. **Sport-specific controls** when applicable: Basketball adds per-side
   foul buttons and a "Reset fouls" action (a "Bonus" badge appears at 5
   fouls); Boxing adds a "Record round N" dialog (two 0–10 scores,
   summed into the running total automatically); Softball/Baseball adds
   Out/Ball/Strike/Reset-count buttons (outs and the ball/strike count
   follow real game rules automatically — three outs flips the inning,
   four balls is a walk) and a per-side runs dialog.
4. Update the **period/status** text at any time (e.g. "Q2", "Round 3",
   or free text for anything else); **pause**/**resume** as needed.
5. **End** the session when play is over — its confirmation dialog
   reminds you the official result still needs to be encoded separately.
6. **Full-screen mode** (a button on the scoreboard itself) is meant for
   a laptop, tablet, or projector display facing spectators.

Live scores update for viewers roughly every 5 seconds by polling even if
the real-time push (Reverb) isn't running — nothing breaks either way.

## 10. Encoding & validating results

**Sidebar → Results** (`/results`) — only while the meet is **Active**,
and only for events not yet encoded.

1. **Encode result**: pick the event, then add placement rows (rank,
   entry, optional mark/time, tie flag) — only that event's confirmed
   entries can be placed, one rank per entry, and duplicate ranks are
   only allowed when every tied placement is flagged as a tie.
2. An encoded result can be edited or deleted freely — it's still working
   data, visible to managers only.
3. **Validate** — a second, deliberate decision. Once validated, the
   result is **official, locked, and visible to every role** (including
   the public portal, if the meet is published). It can no longer be
   edited or deleted directly.
4. **Correct result**: the only way to change a validated result. Always
   requires a written reason; it reopens the result to encoded (clearing
   validation) and preserves the old placements in the audit log. You
   then re-encode and re-validate it like any other correction — there is
   no silent edit path.

## 11. Protests & incidents

**Sidebar → Protests** (`/protests`) — against a specific result or match.
Officers file for their own delegation; you review and decide:

1. **Review** moves a filed protest to under-review.
2. **Decide protest #N**: **Uphold** or **Dismiss**, remarks required
   either way.
3. **Upholding a result protest does not itself change the result** — the
   page offers a **"Correct result"** shortcut that pre-fills the
   correction reason from the protest and reuses the exact same §10
   correction flow, so there is still only one path a result ever
   changes through.

**Sidebar → Incidents** (managers-only nav item, `/incidents`) — a simple
meet-day log, not a case file. **Log incident**: description, severity
(minor/moderate/serious), a medical-referral flag (yes/no only — no
medical details are recorded, by design), optional venue. Resolve /
Reopen / delete as needed.

## 12. Medal tally

**Sidebar → Medal tally** (`/tally`) — read-only for everyone, computed
live from validated results only (a correction removes its medals
automatically; re-validating restores them). Municipality/district
standings are the official verdict, shown first; the school table below
is reference-only, showing which school each medal actually came from.
Filter by meet and sport.

## 13. Reports

Six printable/exportable reports (print button uses the browser's print
dialog; download streams a CSV) — all linked from their owning page:

| Report | Where to find it |
|---|---|
| Delegation roster | "Roster" action on a delegation's row |
| Per-event entry list | "Event list" action on the Entries page (with an event filter active) |
| School participation summary | "Participation" action on the Schools page |
| Official result sheet | "Sheet" action on a validated result card |
| Medal tally report | "Printable report" action on the Medal tally page |
| Daily schedule sheet | "Daily sheet" action on the Schedule page |

Plus the cross-meet **Management dashboard** (**Sidebar → Management**,
`/management`, manager-only) — participation/registration trends,
operations progress & risk (a "Stalled" badge flags an Active meet with a
result sitting encoded-but-unvalidated for 24+ hours), delegation/school
performance history across meets, and venue utilization, with its own
printable report and CSV export. Filter by school year.

Every CSV download and several sensitive page views are logged in the
audit log.

## 14. Announcements & publishing to the public

**Sidebar → Announcements** (managers-only nav item, `/announcements`) —
plain-text advisories, general or tied to one meet. **Publish**/
**Unpublish** controls whether guests can see it; drafts are never public.

**Publishing the meet itself** (§2, step 4) is the one decision that
exposes it on the public portal at all — once published, guests can see
its schedule, validated results, medal tally, published announcements,
and any live scoreboard currently in progress (clearly marked
provisional — see [`public-portal-guide.md`](public-portal-guide.md)).
Unpublishing takes effect immediately.

## See also

- `docs/meets.md`, `docs/venues.md`, `docs/scheduling.md`,
  `docs/delegations.md`, `docs/accreditation.md`, `docs/entries.md`,
  `docs/eligibility.md`, `docs/matches.md`, `docs/live-scoring.md`,
  `docs/results.md`, `docs/protests.md`, `docs/medal-tally.md`,
  `docs/reports.md`, `docs/management-dashboard.md`,
  `docs/announcements.md`, `docs/public-portal.md` — the technical
  reference behind every section above.
- [`admin-manual.md`](admin-manual.md) — Administrator-only settings.
- [`delegation-officer-manual.md`](delegation-officer-manual.md) — the
  registration side of §5–7 from an officer's own point of view.
