# Training Outline (WP-06-08)

An agenda and topic list for whoever trains Division staff on PMMS — not
a slide deck, not a script. The actual material to present *from* is
`docs/manuals/` (WP-06-05): one manual per role, already written from the
real app. This document is the shape of the session, not its content.

## Format

Recommended: **one session per role**, hands-on at a real computer with
the app open, not a lecture. 60–90 minutes per role is realistic for the
Organizer session (the largest); 30–45 minutes for the others. Run them
on a **freshly seeded, non-production copy** — `php artisan migrate:fresh
--seed` gives every trainee the same starting data (`docs/uat/README.md`
"Environment setup" has the exact steps, including how to get one test
account per role ready, since that's real setup work either way).

A single combined session covering every role also works if the audience
overlaps (e.g. one person will be both an Organizer and occasionally fill
in for Admin tasks) — just run the sections back to back.

## Prerequisites for the trainer

- A working, seeded, non-production PMMS environment (see above).
- One test account per role already set up and role-promoted (same
  environment-setup steps `docs/uat/README.md` documents — training and
  UAT share this exact need, no separate procedure to invent).
- `docs/manuals/` open and ready to reference on screen.
- Optional but effective: `docs/uat/*-script.md` files make ready-made
  hands-on exercises — a trainee following the Organizer script's
  numbered steps *is* a training exercise, not just a test.

## Agenda

### 1. Orientation (all trainees, 15 minutes)

- What PMMS is for: one Division's provincial meet, one meet at a time —
  not a general-purpose sports platform.
- The Province vs. City distinction and what it means for this
  deployment (Davao de Oro, 11 municipalities, municipal delegations
  pooling several schools) — `docs/division.md`.
- Signing in, the account menu, Settings (profile, password, two-factor,
  passkeys) — the same for every role, covered once here rather than
  repeated per session.
- Where to get help afterward — point at `docs/turnover.md`'s escalation
  table (once it's filled in with real contacts).

### 2. Administrator session (60 minutes)

Present from `docs/manuals/admin-manual.md`. Cover in order:

- Division settings and why the type locks.
- Districts/municipalities and schools registries.
- Sports & events catalog.
- **The account/role limitation, explicitly** — trainees who'll act as
  Administrator need to understand *now*, not discover later, that
  promoting someone to Organizer or Delegation Officer is a direct-
  database action, not a button in the app. Walk through who actually
  has the access to do this at your Division before the session ends.
- Audit log — what it's for, how to search it.
- Backup awareness — they don't need to run `backup-database.ps1`
  themselves, but should know backups exist and where to ask if one is
  needed.

**Exercise:** `docs/uat/admin-script.md`, run live.

### 3. Organizer session (90 minutes — the largest)

Present from `docs/manuals/organizer-manual.md`, in the order a real meet
actually happens:

- Meet lifecycle: create → open registration → publish → close
  registration → start → complete.
- Venues and scheduling.
- Reviewing and approving delegations; assigning officers.
- Accreditation and ID cards.
- Entries and eligibility review.
- Matches.
- **Live scoring — hands-on, not just watched.** Have every Organizer
  trainee actually run a live session end to end (start, score, correct,
  pause/resume, end) — this is the module most likely to feel
  unfamiliar coming in.
- Results: encode → validate → (if needed) correct.
- Protests and incidents.
- Medal tally and reports.
- Announcements and what publishing a meet actually exposes publicly.

**Exercise:** `docs/uat/organizer-script.md` Part A and B, coordinated
live with a Delegation Officer trainee at the checkpoint (this doubles as
a natural point to demonstrate the handoff between the two roles, which
is exactly how a real meet works).

### 4. Delegation Officer session (45 minutes)

Present from `docs/manuals/delegation-officer-manual.md`:

- Getting assigned (and the "you don't register your own delegation"
  point — this surprises people, say it plainly).
- Registering athletes and personnel, home-school attribution under a
  pooled municipal delegation.
- Eligibility documents.
- Submitting entries.
- What they can see read-only once a meet is underway: matches, live
  scores, results, medal tally.
- Filing a protest.

**Exercise:** `docs/uat/delegation-officer-script.md`, ideally run
alongside session 3's Organizer exercise for the real checkpoint handoff.

### 5. Viewer session (30 minutes)

Present from `docs/manuals/viewer-manual.md` — shortest session, mostly
about *managing expectations*: a Viewer sees the full sidebar but several
links 403 on click, which trainees should understand upfront isn't a bug
before they run into it themselves.

**Exercise:** `docs/uat/viewer-script.md`.

### 6. Public portal (15 minutes, optional — for anyone who'll field
public questions, e.g. front-office staff)

Present from `docs/manuals/public-portal-guide.md` — no account needed,
show the schedule/results/tally/live-scoreboard pages and the
"provisional, not official" distinction on the live scoreboard
specifically, since that's the one thing on the public site genuinely
different from everything else there.

## After the session

- Leave trainees with the relevant manual (a PDF export or just the raw
  markdown file both work — these are plain, readable text files).
- Point them at `docs/turnover.md`'s escalation table for what to do when
  something doesn't work as trained.
- If a real UAT session (`docs/uat/`) hasn't happened yet, this training
  session is a natural moment to schedule one — the same people, the same
  scripts, now as a genuine acceptance check rather than a training
  exercise.

## See also

`docs/manuals/` (the actual presentation material), `docs/uat/` (ready-
made hands-on exercises), `docs/turnover.md` (escalation and ongoing
maintenance once training is done).
