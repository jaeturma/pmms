# Turnover Reference (WP-06-08)

For whoever maintains PMMS Division Edition after this development effort
ends — Division IT staff, or a future developer picking this up cold.
This is the one place that ties together what's scattered across
`docs/deployment.md` (WP-06-07, how to actually run/deploy/roll back the
app) and `docs/manuals/` (WP-06-05, how to use it day to day) — read
those two first if you haven't; this document doesn't repeat either.

## System overview

PMMS Division Edition is a **Laravel 13 + Inertia v3 + React 19 +
TypeScript + Tailwind 4** web application, single codebase, single MySQL
database, deployed on one local Windows/Laragon machine
(`docs/deployment.md`). It's a **modular monolith**: standard Laravel
controllers, Eloquent models, and policy classes per feature area
(delegations, athletes, entries, results, live scoring, etc.) — no
separate services, no microservices, no message bus. Authentication runs
on Laravel Fortify (four roles: Administrator, Meet Organizer, Delegation
Officer, Viewer — `docs/authorization.md`). Session, cache, and the one
queued job in the app all run on the same MySQL database — no Redis, no
separate queue broker. The public portal (no login) and the authenticated
app share the same codebase and deployment.

**Detailed technical reference, by topic:** the per-feature `docs/*.md`
files (`division.md`, `delegations.md`, `athletes.md`, `entries.md`,
`results.md`, `live-scoring.md`, `medal-tally.md`, `authorization.md`,
`audit-trail.md`, `reports.md`, `public-portal.md`, and the rest) — each
one documents its own module as actually built, kept current through
every phase. `.ai/architecture.md` and `.ai/project-context.md` are the
short, current source of truth for the overall system shape and build
status.

### A real hazard worth flagging explicitly

**`docs/00-product/` through `docs/11-backlog/` (including
`docs/01-architecture/`) do NOT describe the system that was actually
built.** They're an elaborate pre-implementation planning exercise
(34 bounded contexts, 53 roles, a Flutter mobile app, MinIO object
storage, Redis, Laravel Horizon, AI-assisted duplicate-athlete detection,
multi-tenant concerns) that predates the real, much leaner build and was
never followed — `.ai/project-context.md` already says so explicitly:
*"Enterprise ADRs (`.ai/decisions/`) and `docs/11-backlog/` are
future-readiness reference only — simplified `.ai` files govern."* Every
document in that tree is also marked **"Status: Draft Complete — Pending
Architecture, Security, and Engineering Validation"** — it was never
validated against anything, let alone this app. If you're orienting
yourself in this codebase for the first time, **start from `.ai/
architecture.md`, `.ai/current-phase.md`, and the per-feature `docs/*.md`
files above, not that directory** — this is worth stating plainly here
because it's exactly the kind of thing a new maintainer would otherwise
waste real time on, and because this project has hit the same "stale
generic-template" problem several times before at a smaller scale (see
`.ai/current-phase.md`'s Phase 4/5/6/7 planning notes) — this is that
same pattern, just at the very largest scale, from before Phase 1 ever
started.

## Where things live

| What | Where |
|---|---|
| Codebase | `D:\lara\www\pmms` on the Laragon machine serving `http://pmms.app` |
| Database | MySQL, database name `pmmsdb`, `127.0.0.1:3306` (local to the same machine) |
| Backups | `storage/app/private/backups/database/` (never web-accessible) — full procedure, retention, and the restore drill in `docs/backup-restore.md` |
| Application logs | `storage/logs/laravel.log` (single file, `LOG_CHANNEL=stack`→`single`) — tail it directly, or `Get-Content storage\logs\laravel.log -Wait -Tail 50` in PowerShell |
| Audit trail (who did what, in-app) | `/audit-logs` (Administrator only) — `docs/audit-trail.md` |
| Build-history log (what shipped, when, why) | `.ai/current-phase.md` — the authoritative work-package-by-work-package record of this entire project |
| Task Scheduler entries | "PMMS Database Backup" and "PMMS Queue Worker" (once installed per `docs/deployment.md`) — `taskschd.msc` |
| Environment config | `.env` (never committed — `.env.production.example` is the template, `docs/deployment.md` explains every value) |

## Known limitations and open items

Collected here from across the project rather than left scattered — none
of these are secrets, all are already documented at their source:

- **No in-app account creation or role management.** Every account
  self-registers as Viewer; promoting someone to a higher role is a
  direct-database action (`docs/manuals/admin-manual.md` §2,
  `docs/authorization.md`). This is a real, current gap in the product,
  not a bug — worth knowing before assuming there's a "Users" screen
  somewhere.
- **City division type's "district competes" registration option isn't
  built.** The enum value and schema exist, but only school-rooted
  delegation registration works today; a City deployment can't yet let
  districts themselves compete. Documented as a deliberate, tracked
  deferral, not a silent gap — `docs/division.md` "Open item."
- **No `.xlsx` export.** CSV (already shipped on every report) was judged
  sufficient — a deliberate scope decision (`docs/reports.md`,
  `docs/phases/phase-06-reports-uat-deployment-turnover/README.md`
  Grounding section), not something dropped by accident.
- **Reverb (real-time live scoring) is off in production; live scoring
  runs on 5-second polling only.** An owner decision made during
  WP-06-07, reversible at any time by following `docs/deployment.md`
  "Changing the broadcast decision" — nothing about the feature itself
  is missing, this is purely an operational choice.
- **Outgoing production email isn't configured yet.** No SMTP provider
  was available as of WP-06-07; until one is set up
  (`docs/deployment.md` "Outgoing email"), self-registration's email
  verification and password reset won't actually deliver mail for real
  users in production. This should be resolved before real go-live.
- **One low-priority, theoretical (not actual) performance observation**
  from WP-06-04: `MedalTallyService::standings()`'s rank filter doesn't
  lean on the existing composite database index's leading column — a
  non-issue at this deployment's real scale (sub-millisecond regardless),
  only worth revisiting if a future phase adds bulk multi-season
  aggregation across many meets at once. `docs/performance.md`.
- **WP-06-03's security review findings are all closed**, not open —
  listed here only so nobody re-discovers them as if new: the one real
  finding (unthrottled self-registration) was fixed in that same WP; the
  two items it explicitly carried forward for WP-06-07
  (`SESSION_SECURE_COOKIE`, the queue-worker gap) were both closed there
  too. See `docs/phases/phase-06-reports-uat-deployment-turnover/
  phase-6-security-review.md` for the full record if you need it.

## Escalation and support

**"Who to contact" — fill in with real names/contacts before turnover is
considered complete.** This project did not fabricate contacts here; a
placeholder is more honest than a guess. **"How" is filled in for the two
categories WP-09-01 built a real workflow for** — see
[`docs/support-workflow.md`](support-workflow.md).

| Issue type | Who to contact | How |
|---|---|---|
| App is down / won't load | _[fill in]_ | _[fill in]_ |
| Database problem / need a restore | _[fill in — should have `scripts\restore-database.ps1` access]_ | _[fill in]_ |
| Need a new account promoted to a higher role | _[fill in — needs console/tinker access, see "Known limitations" above]_ | File a **Support request** GitHub issue — see [`docs/support-workflow.md`](support-workflow.md) |
| A bug in the app itself | _[fill in — the developer/team maintaining the codebase]_ | File a **Bug report** GitHub issue — see [`docs/support-workflow.md`](support-workflow.md) |
| A request for a new feature or a scope change | _[fill in — whoever owns the product decision]_ | _[fill in — a product/business decision, not a GitHub issue category this workflow decides]_ |

## Routine maintenance checklist

Nothing here needs daily attention — PMMS runs one meet at a time for one
Division. A reasonable cadence:

- **Weekly (while a meet is being prepared or is active):** run
  `docs/monitoring.md`'s three-point health check (`/up`, the app log,
  both Scheduled Tasks) — `powershell -File scripts\health-check.ps1`
  runs all three in one pass. Also confirm a new file appeared under
  `storage/app/private/backups/database/` (the health check confirms
  the backup *task* ran; it doesn't check the backup file itself).
- **Monthly, or before/after anything network-facing changes:**
  `composer audit` and `npm audit --omit=dev` from the codebase root —
  both were clean as of WP-06-03; re-run periodically since new
  advisories get published against existing dependencies over time, not
  just when this codebase changes.
- **Monthly, or whenever something seems off:** skim `/audit-logs` for
  anything unexpected (repeated failed logins, unfamiliar accounts,
  unusual export volume).
- **Before a real production go-live, and not yet done as of this
  WP:** arrange the SMTP provider from "Known limitations" above.
- **After any code deploy:** follow `docs/deployment.md`'s build/deploy
  procedure in full, including the cache-clearing and queue-restart
  steps — skipping them is the most common cause of "I deployed but
  nothing changed."

## See also

- `docs/deployment.md` — build, deploy, rollback, and every production
  `.env` decision.
- `docs/manuals/` — day-to-day usage, per role.
- `docs/backup-restore.md` — the backup/restore procedure this doc's
  maintenance checklist and escalation table both reference.
- `docs/support-workflow.md` — how a bug report or support request
  actually moves from filed to closed (WP-09-01), referenced by this
  doc's escalation table above.
- `docs/monitoring.md` — the three-point health-check routine (WP-09-02)
  this doc's maintenance checklist runs weekly.
- `docs/training-outline.md` — an agenda for walking Division staff
  through the system using the manuals as material.
- `.ai/current-phase.md` — the complete work-package history behind
  every decision referenced above.
