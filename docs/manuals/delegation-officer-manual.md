# PMMS Delegation Officer Manual

For the Delegation Officer role (`delegation_officer`) — registering and
managing **your own delegation's** roster and entries. Everywhere in PMMS,
"your own" means the delegation(s) a manager has explicitly assigned you
to, never delegations in general.

## 1. Getting started

You don't register yourself into this role, and — this trips people up,
so it's worth stating plainly — **you don't register your delegation's
record either.** Both are things only an Administrator or Organizer does:

1. Your account exists (you registered at `/register`, or an admin
   arranged one for you) and its role has been set to Delegation Officer
   — this is a technical, off-screen step on their end, not something you
   do.
2. A manager first **registers the delegation itself** for a meet (under
   this Province deployment, by picking your municipality — a municipal
   delegation can pool athletes/personnel from several schools in it
   under one registration), then attaches your account to it from the
   Delegations page's officer list. Until both have happened, you have an
   account but nothing to manage — there is no "create my delegation"
   action anywhere for you to find.

Once assigned to an already-registered delegation, sign in at `/login`
and everything below becomes visible. Your account menu → **Settings**
has Profile, Security (password, two-factor, passkeys), and Appearance —
the same for every role.

## 2. Your delegation's registration

**Sidebar → Delegations** (`/delegations`) shows the delegation(s) a
manager has assigned you to, with your own working part of the flow
once the record already exists:

1. It starts as **Draft** (the state it was in when a manager created
   it). While it's still Draft and the meet's registration is open, you
   can edit the head-of-delegation contact (name/phone/email).
2. **Submit** it when your roster is ready for review — this is the one
   status change you can trigger yourself. Submitting doesn't require the
   draft state to still hold, but registration must still be open.
3. A manager **approves** it, or **returns** it to draft for you to fix
   and resubmit.
4. Only a **Draft** delegation can be deleted, and only by a manager —
   you can't delete your own, and you can't create a replacement one
   yourself either; ask a manager.

## 3. Registering athletes

**Sidebar → Athletes** (`/athletes`) — while your delegation is still a
**Draft** and registration is open.

**Register athlete**: pick your delegation (auto-selected if you only
have one), then a **Home school** — narrowed automatically to your own
school (if you registered by school) or any active school within your
municipality (if you registered by municipality); it's auto-selected
when there's only one option. This is the athlete's real home school,
**required and permanent** once set — pick it carefully, it can't be
changed afterward. Then: name, sex, birthdate, LRN (12 digits, unique),
grade level, and an optional photo.

PMMS deliberately does not collect medical, address, or guardian
information for athletes — only what's needed to register and place
them. A photo, once uploaded, is only ever shown to people already
authorized to see that athlete.

Only you and your delegation's other officers (and managers) can see your
athletes at all — Viewers have no access to this data whatsoever.

**If your delegation pools several schools** (a municipal delegation),
you'll see and manage the **whole pooled roster** across every school
under it, not just one school in isolation — that's intentional, since
you're the one trusted with the whole delegation.

## 4. Registering personnel

**Sidebar → Personnel** (`/personnel`) — coaches, assistant coaches, and
chaperones, same registration-window and home-school rules as athletes
(§3). Coaching roles additionally get a **sports** checklist (which
sport(s) they coach); assigning sports to a chaperone is refused, and
demoting a coach to chaperone clears their sport assignments
automatically.

## 5. Eligibility documents

**Sidebar → Eligibility** (`/eligibility`) — while registration is open.

**Upload eligibility document**: pick the athlete, a document type (birth
certificate, proof of enrollment, report card, parental consent, other),
and the file (PDF/JPG/PNG, up to 10 MB). The first upload for an athlete
creates their review as pending automatically.

A manager approves or returns each review:

- **Approved** is final — no more uploads, no re-decision.
- **Returned** always comes with remarks explaining what to fix — upload
  again and the review reopens to pending automatically.

You can delete a document yourself before a review is approved.

## 6. Submitting entries

**Sidebar → Entries** (`/entries`) — for your own delegation's athletes,
while registration is open (managers can submit any time).

**Submit entry**: pick the athlete, then an event from that athlete's own
meet. PMMS checks automatically that the athlete's sex and grade-derived
age division actually match the event, blocks a duplicate entry into the
same event, and enforces the per-delegation entry cap for that event — if
your submission is rejected, one of these is why.

- **Withdraw** your own still-submitted entries yourself, any time
  registration is open. A manager confirms entries and can withdraw any
  entry, including already-confirmed ones.
- An "Eligibility pending" badge next to an entry is a reminder, not a
  block — it still submits fine; get the document approved (§5) when you
  can.

## 7. Watching your delegation's matches and live scores

**Sidebar → Matches** (`/matches`) shows matches your delegation's
entries are part of. Each row's **Live** column links to that match's
scoreboard (`/matches/{id}/scoreboard`) whenever a live scoring session
is running — you can watch the running score update in real time (or via
a short automatic refresh if real-time isn't available), but you cannot
operate the scoreboard yourself; that's manager-only. The scoreboard
always makes clear this is a provisional, in-progress number, not the
official result.

## 8. Viewing results and the medal tally

**Sidebar → Results** (`/results`) shows **validated** results only for
you — the same official standings everyone sees, filterable by meet/event.
**Sidebar → Medal tally** (`/tally`) is likewise read-only for you,
showing the municipality/district standings first (the official verdict)
and each school's own medals below as reference.

## 9. Filing a protest

**Sidebar → Protests** (`/protests`) — for your own delegation only.

**File protest**: pick either a result or a match, and state your grounds
(up to 1000 characters). A manager reviews and decides (uphold/dismiss,
with remarks); an upheld protest against a result doesn't automatically
change it — the manager corrects it through the normal results-correction
process, which is always logged with a reason.

## 10. Roster report and ID cards

From your Delegations row: **Roster** opens a printable/downloadable
roster of your own athletes and personnel; **IDs** (once your delegation
is approved) shows accreditation status for each member and lets you
print already-issued cards — accrediting/revoking itself is a manager
decision, not yours.

## See also

- `docs/delegations.md` — registering unit, officer roster scope, home
  school attribution in full detail.
- `docs/athletes.md`, `docs/personnel.md`, `docs/entries.md`,
  `docs/eligibility.md` — the technical reference for §3–6.
- `docs/authorization.md` — exactly what's yours vs. manager-only, in one
  table.
- [`organizer-manual.md`](organizer-manual.md) — the manager side of
  every review/decision step above.
