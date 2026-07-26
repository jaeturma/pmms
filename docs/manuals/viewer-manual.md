# PMMS Viewer Manual

For the Viewer role (`viewer`) — the default role every new self-registered
account gets (see [`admin-manual.md`](admin-manual.md) §2). Read-only,
non-sensitive access: no minor (athlete/personnel) data, no editing
anywhere.

## A note before you start

**The sidebar shows every module to every signed-in role**, including
several you don't have access to as a Viewer (Athletes, Personnel,
Entries, Eligibility, Matches, Protests, and more). Clicking one of those
takes you to a permission-denied page instead of an error — that's
expected, not a bug. The sections below tell you exactly which links
actually work for you.

## What you can see

- **Dashboard** (`/dashboard`) — today's schedule for the meet currently
  running and the medal tally top five, if a meet is Active. No
  operational queues (those are for managers) and no "your delegation's
  protests" widget (that's for Delegation Officers).
- **Districts/Municipalities, Schools, Sports, Events, Meets, Venues**
  (sidebar links of the same names) — the reference registries, list view
  only. No add/edit/archive controls appear for you.
- **Schedule** (`/schedule`) — the full event schedule, filterable by
  meet/venue/day.
- **Delegations** (`/delegations`) — the list of every delegation across
  every meet (municipality name, status, head contact) — read-only, no
  roster/ID-card links (those need Delegation Officer or manager access).
- **Results** (`/results`) — **validated** results only. Encoded
  (not-yet-official) results are manager-only working data and won't
  appear for you at all.
- **Medal tally** (`/tally`) — municipality/district standings (the
  official verdict) and each school's own medals below, exactly as every
  other role sees it.
- **Reports** — four of the six are open to you: **School participation
  summary** (`/reports/participation`), **Official result sheet**
  (`/reports/results/{id}`, validated results only), **Medal tally
  report** (`/reports/tally`), and **Daily schedule sheet**
  (`/reports/schedule`) — each with print and CSV-download buttons. The
  **Delegation roster** and **Event entry list** reports contain minor
  data and are not available to you.
- **Public portal** — the same guest-facing pages anyone can see without
  signing in at all; see [`public-portal-guide.md`](public-portal-guide.md).
  Being signed in as a Viewer doesn't add anything there.
- **Your own account settings** (Profile, Security, Appearance) — same as
  every role.

## What you cannot see or do

Everything involving an individual athlete or personnel member's identity,
any live/in-progress data, and anything an SDO decision-maker acts on:

- Athletes, Personnel, Entries, Eligibility — no access at all, list or
  detail. This is minor (or minor-adjacent) data.
- Matches and live scoring — no access; both carry athlete names.
- Accreditation / ID cards — no access.
- Protests, Incidents, Announcements management — no access.
- Encoded (unvalidated) results — never shown to you.
- Delegation roster report and per-event entry list report — contain
  minor data.
- The cross-meet Management dashboard, the Audit log, and Division
  settings — manager/Administrator only.
- Nothing on any page you *can* reach has an edit, delete, or approval
  control for you — every action button you see elsewhere in this manual
  set belongs to a manager or Delegation Officer role.

## See also

- `docs/authorization.md` — the complete role/action matrix this manual
  is drawn from.
- [`organizer-manual.md`](organizer-manual.md) /
  [`delegation-officer-manual.md`](delegation-officer-manual.md) — what
  the roles above you can additionally do, if your access needs to
  change (see [`admin-manual.md`](admin-manual.md) §2 for how a role
  change actually happens).
