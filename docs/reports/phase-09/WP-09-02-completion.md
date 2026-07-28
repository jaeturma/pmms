# WP-09-02 — Completion Report

Monitoring & Health-Check Routine. Status: **done**.

## Repository findings

Before writing anything: confirmed `bootstrap/app.php`'s `health: '/up'`
already exists (no new code needed for the health route itself);
confirmed `storage/logs/laravel.log` exists with real content, not
rotated; confirmed `backup-database.ps1`'s `Read-EnvValue` helper as the
established pattern to reuse for reading `APP_URL` from `.env` rather
than inventing a second env-parsing approach.

**Proved the checklist against the real app, not just written and
assumed to work — per this WP's own explicit acceptance criterion**:

- `curl https://pmms.app/up` → real `200`, the genuine "Application up"
  page (not a placeholder — worth noting since earlier phases' reports
  flagged this Apache instance as sometimes serving Laragon's own
  default page instead of the app; it is serving the real app now).
- `Get-ScheduledTask -TaskName "PMMS*"` → **no matches**. Neither `PMMS
  Database Backup` nor `PMMS Queue Worker` is currently registered on
  this machine, despite WP-06-02/WP-06-07 building the installer
  scripts for both. Checked *why* rather than treating this as a bug:
  both `scripts/install-*-schedule.ps1` files explicitly document
  themselves as "a one-time setup... the server administrator runs it
  once, deliberately" — not something triggered by development work.
  This dev machine simply never had that deliberate step run on it
  (or WP-06-07's own end-to-end proof, which used `queue:work --once`
  directly rather than the installed task, was cleaned up afterward, per
  `.ai/current-phase.md`'s own record of that WP). Expected state for a
  pre-go-live dev machine, not a regression — but a real, concrete
  action item before/at production cutover if this machine is also the
  production server, which `docs/deployment.md` says it is.
- `storage/logs/laravel.log`: a real grep for `.ERROR:`/`.CRITICAL:`/
  `.EMERGENCY:` found 11 matches — most are `testing.ERROR:` lines (Pest
  test-run noise, this app logs to the same file regardless of
  `APP_ENV`) plus one real `local.ERROR: SQLSTATE[HY000] [2002] No
  connection could be made...` from 2026-07-27 15:59:54. Checked whether
  this is current: `php artisan db:show` confirms the database is
  reachable right now — the old line is historical (a moment MySQL
  wasn't yet up), not an active problem. This distinction (a match
  existing vs. a match being *current*) became a real documented nuance
  in `docs/monitoring.md`, discovered by actually running the check
  rather than assumed.

Did **not** unilaterally install the two Scheduled Tasks on this
machine — the install scripts' own documentation frames that as a
deliberate decision for whoever administers the real server, not
something to trigger silently while writing a monitoring doc. Left as an
explicit, flagged action item (see "Remaining issues").

## Files created

- `docs/monitoring.md` — the three-point checklist (`/up`, log skim with
  the "testing. noise" and "check the timestamp" nuances discovered
  above, both Scheduled Tasks), the one-shot `scripts/health-check.ps1`
  convenience script, and a real (not sanitized) transcript of running it
  against this actual machine right now — 1 pass, 2 real, explainable
  fails.
- `scripts/health-check.ps1` — runs all three checks in one pass, exits
  with the failure count (0 = all clear). Read-only: `Invoke-WebRequest`,
  `Get-Content -Tail`, `Get-ScheduledTask`/`Get-ScheduledTaskInfo` — no
  state anywhere is changed by running it. Reuses
  `backup-database.ps1`'s exact `Read-EnvValue` pattern for reading
  `APP_URL` rather than a second parsing approach.
- `docs/reports/phase-09/WP-09-02-completion.md` — this file.

## Files modified

- `docs/turnover.md` — "Routine maintenance checklist"'s weekly bullet
  now points at `docs/monitoring.md`/`scripts/health-check.ps1` instead
  of just the one backup-task check it had before (folding the two
  Scheduled Task checks, the `/up` check, and the log check into one
  cross-referenced routine rather than leaving this a second, partial,
  disconnected checklist). Added a `docs/monitoring.md` entry to "See
  also."

No `app/`, `database/`, `routes/`, `composer.json`/`.lock`, or
`package.json`/`.lock` file was touched — confirmed by `git diff --stat`
against those paths, empty.

## Test results

No test changes — none were needed or expected (no application code
changed). Full suite reran to confirm the gate is still green: **703/703
passing, 3,716 assertions** — identical to the count before this WP,
zero regressions.

## Quality results

- Pint: **PASS**
- PHPStan L7: **PASS** (0 errors)
- Pest: **PASS** — 703/703, 3,716 assertions
- ESLint: **PASS** · Prettier (`resources/`, the project's own
  formatting scope): **PASS** (the same two pre-existing, unrelated
  files flagged since before this WP — `registry/school-districts.tsx`/
  `schools.tsx` — confirmed untouched, out of scope) · `tsc --noEmit`:
  **PASS**
- `npm run build`: **PASS** (identical output chunk hashes to before —
  confirms zero frontend code changed)
- `scripts/health-check.ps1` itself: run for real against the live app
  (see "Repository findings" above and the transcript in
  `docs/monitoring.md`) — correctly reported 1 pass / 2 real fails, exit
  code `3`. This is the proof the checklist is genuinely runnable, not
  just documentation that assumes it would work.

## Remaining issues

None blocking this WP's own scope, but two real, flagged action items
surfaced by actually running the checklist (not fixed here — this WP
documents and proves, it doesn't silently patch operational gaps found
along the way, matching this phase's own README "Out of Scope" note):

1. **Neither `PMMS Database Backup` nor `PMMS Queue Worker` is currently
   registered as a Scheduled Task on this machine.** Both install
   scripts already exist (`scripts/install-backup-schedule.ps1`,
   `scripts/install-queue-worker-schedule.ps1`) — running them is a
   real, deliberate action for whoever administers the production
   cutover, not something this WP took unilaterally on the owner's real
   Windows machine.
2. **One historical `local.ERROR` (MySQL momentarily unreachable) sits
   in the current log tail** — confirmed not a current problem
   (database is reachable now), left as-is since the log isn't rotated
   and this is expected accumulation, not a live issue.

## Recommended next work package

```text
WP-09-03 — Phase 9 Compliance Review & Acceptance
```
