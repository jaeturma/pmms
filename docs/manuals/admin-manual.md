# PMMS Administrator Manual

For the Administrator role (`admin`) — full access, including the settings
no other role can reach. Written from the actual shipped app; if a step
below doesn't match what you see, the app has moved on since this was
written and the technical team should be told.

An Administrator can do everything an Organizer can (see
[`organizer-manual.md`](organizer-manual.md) for meet operations — meets,
venues, scheduling, entries, eligibility, matches, live scoring, results,
protests, incidents, medal tally, reports, announcements, publishing).
This manual covers what's **Administrator-only**: division settings, the
audit log, and account/role administration — plus the registries every
role reads from.

## 1. Signing in and your account

Sign in at `/login` with your email and password. Once signed in, your
account menu (top right / sidebar footer) has **Settings**:

- **Profile** (`/settings/profile`) — your name and email.
- **Security** (`/settings/security`) — change your password, set up
  two-factor authentication (an authenticator app QR code plus recovery
  codes), and register a passkey if you'd rather not type a password at
  all.
- **Appearance** (`/settings/appearance`) — light/dark/system theme.

Every role has this same account menu — the rest of this manual won't
repeat it.

## 2. Creating accounts and assigning roles — read this first

**PMMS does not have an in-app screen for creating accounts or changing
someone's role.** This is a real, current limitation, not an oversight to
route around — worth knowing before anyone asks you to "just add a user."

- New accounts are created by the person themselves at the public sign-up
  page (`/register`), the same page anyone can reach from the login
  screen's "Sign up" link. They pick their own name, email, and password.
  Whether they must click a verification link sent to that email before
  they can do anything depends on **System settings** (§3) — off by
  default, and accounts that already existed before you turn it on are
  never retroactively locked out.
- **Every self-registered account starts as a Viewer** — read-only,
  non-sensitive access (see [`viewer-manual.md`](viewer-manual.md) for
  exactly what that means). There is no signup option for a higher role.
- To make someone an Organizer, Delegation Officer, Technical Official, or
  another Administrator, whoever administers the server/database has to
  change their `role` value directly (`docs/authorization.md` documents
  the supported way: `$user->forceFill(['role' => …])->save()` via
  `php artisan tinker`, or the equivalent direct database update). This
  is a one-time, technical, off-screen step — plan for it when onboarding
  a new Organizer, Delegation Officer, or Technical Official rather than
  expecting to do it from the UI.
- Once someone has the Delegation Officer role, you (or an Organizer)
  attach them to a specific delegation from the **Delegations** page — see
  [`organizer-manual.md`](organizer-manual.md) §"Assigning delegation
  officers." That part *is* in the UI.
- Once someone has the **Technical Official** role, you (or an Organizer)
  attach them to the sport(s) they'll run live scoring for from the
  **Sports** page — click a sport's "Assign"/"N assigned" button and check
  off their account. A Technical Official only sees the Dashboard and
  Matches in their sidebar (not the full registry), and Matches only shows
  matches in their assigned sport(s); opening a match's live scoreboard
  lets them start/score/end that session exactly like an Organizer would,
  but nothing else in the app.
- There is no account-deletion screen either. A Delegation Officer or
  Viewer who no longer needs access should have their role changed (same
  off-screen step) or simply stop being given login credentials —
  deactivation isn't a built-in workflow.

## 3. System settings

**Sidebar → System settings** (`/system-settings`, Administrator-only).

Three independent, all-optional pieces of configuration, each inert until
fully filled in — a half-finished setup never partially enforces:

- **reCAPTCHA** — shows a "I'm not a robot" checkbox on the login and
  registration pages and rejects the submission server-side without it.
  Needs the enable toggle plus a site key and secret key from
  [Google's reCAPTCHA admin console](https://www.google.com/recaptcha/admin)
  (v2 Checkbox). Leave the secret key field blank on an update to keep the
  one already saved — it's never shown back to you once set, only an
  "already set" label.
- **Outgoing mail (SMTP)** — host, port, username, password, encryption,
  and from-address/name for the server to send mail through (password
  resets, and email verification below). Falls back to the server's own
  `.env` mail configuration until every field here is filled in.
- **Email verification** — once turned on (and only once SMTP above is
  fully working), new registrations must click a verification link before
  they can use the app. Turning this on **grandfathers every existing
  account that hadn't verified** — none of them get locked out
  retroactively, only accounts registered from that point on are affected.

## 4. Division settings

**Sidebar → Division settings** (`/division`, Administrator-only).

The division's **type** (City or Province) decides who registers as a
delegation for a meet — schools directly under City, or municipalities
under Province (this deployment defaults to **Province — Davao de Oro**,
its 11 municipalities already loaded). You can rename the division here at
any time.

**The type locks the first time any delegation is registered for any
meet** — the form will show why and stop offering the type field once
that happens. If your deployment is brand new and you genuinely need to
switch from Province to City (or the reverse) before real registrations
start, do it now; after that, it needs a fresh deployment or direct
database intervention, not a settings change. See `docs/division.md`.

## 5. Districts / Municipalities registry

**Sidebar → Districts** (labeled **Municipalities** under this Province
deployment) — `/districts`.

- **Add / edit** a municipality: name and an optional nickname (used on
  banners and ID cards — e.g. Maco → "Tigers").
- **Archive** instead of delete once a municipality has schools under it;
  archived rows stay visible with an "Archived" badge and drop out of new
  registration pickers. Empty municipalities can be hard-deleted.

## 6. Schools registry

**Sidebar → Schools** — `/schools`. Search by name or code.

- **Add / edit**: municipality, name, school ID code, level
  (Elementary/Secondary/Integrated), address.
- **Archive** once a school has a delegation registered under it, same
  pattern as municipalities.

## 7. Sports & Events catalog

**Sidebar → Sports** (`/sports`) and **Events** (`/events`).

- Sports: name only, archive once it has events.
- Events: pick a sport, name, gender (Boys/Girls/Mixed), age division
  (Elementary/Secondary), whether it's a team event, and the max entries
  one delegation may submit per event (1–50). The seeded catalog already
  has 14 common sports and 16 standard athletics track events — add more
  events for other sports as your meet needs them.

Both are shared, reference-level catalogs — an Organizer can manage these
too (`role:admin,organizer`), it's not Administrator-exclusive; it's
grouped here because it's registry data, not meet-day operations.

## 8. Audit log

**Sidebar → Audit log** (`/audit-logs`, Administrator-only).

Every meaningful action in the app — registrations, approvals, result
encoding/validation/corrections, eligibility document views, accreditation
decisions, exports, live-scoring events, and more — is recorded here with
who did it, when, from what IP, and what it affected. Search by action
name or the actor's name; filter by action type. Use this when you need to
answer "who did X" or "when did Y happen" — see `docs/audit-trail.md` for
the full list of recorded actions.

## 9. Backups

PMMS does not back itself up automatically from within the app — there is
no "Backup" button in the UI. Backing up and restoring `pmmsdb` is a
server-side task using the scripts under `scripts/` (`backup-database.ps1`
/ `restore-database.ps1`), covered in full in `docs/backup-restore.md`.
As Administrator, your job is to make sure whoever runs the production
server actually has the scheduled backup task installed
(`scripts/install-backup-schedule.ps1`) — it is **not** installed
automatically and won't run itself.

## See also

- `docs/authorization.md` — the full role/action matrix, including the
  System settings gating rules from §3.
- `docs/division.md`, `docs/registry.md`, `docs/sports-catalog.md` — the
  technical reference for everything in §4–7.
- `docs/audit-trail.md` — every recorded action.
- `docs/backup-restore.md` — backup/restore procedure.
- [`organizer-manual.md`](organizer-manual.md) — everything else an
  Administrator can also do (meet operations).
