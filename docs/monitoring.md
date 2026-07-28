# Application Monitoring & Health Check

WP-09-02. A manual, repeatable three-point routine to confirm the app is
actually up and its Phase 6 scheduled tasks (backup, queue worker) are
actually running — proportionate to one local server, no new automated
monitoring/alerting (owner decision, WP-09 planning). Run it on whatever
cadence you decide; `docs/turnover.md`'s "Routine maintenance checklist"
suggests weekly while a meet is active.

## The three checks

1. **`/up` responds.** Laravel's built-in health-check route
   (`bootstrap/app.php`'s `health: '/up'`) — hit it with a browser or
   `curl`:

   ```powershell
   curl.exe -s -o NUL -w "HTTP %{http_code}`n" https://pmms.app/up
   ```

   A `200` with the "Application up" page means the app booted, resolved
   routes, and rendered a view successfully. It does **not** by itself
   prove the database is reachable — Laravel's default health check
   doesn't query it.

2. **`storage/logs/laravel.log` has nothing alarming since the last
   check.** Skim (or search) for `.ERROR:`, `.CRITICAL:`, or
   `.EMERGENCY:` lines. Two things worth knowing before you do:
   - Lines prefixed `testing.` (e.g. `testing.ERROR: ...`) are from Pest
     test runs, not real traffic — this app logs to the same file
     regardless of `APP_ENV`. Skip past these; they're expected noise,
     not a real finding.
   - This file is **not rotated** — an old error from days ago stays in
     it until something clears it. A match doesn't automatically mean
     "something is wrong right now" — check the timestamp on the line
     itself before treating it as current. (A real example encountered
     while writing this doc: a one-off `local.ERROR: SQLSTATE[HY000]
     [2002] No connection could be made...` from a moment when MySQL
     wasn't yet up — `php artisan db:show` confirmed the database was
     reachable again by the time this was checked; the old line was
     correctly flagged by the check below, then correctly judged as
     historical, not current, by reading its timestamp.)

3. **Both Scheduled Tasks from Phase 6 are registered and their last run
   succeeded.**

   ```powershell
   Get-ScheduledTask -TaskName 'PMMS Database Backup' | Get-ScheduledTaskInfo
   Get-ScheduledTask -TaskName 'PMMS Queue Worker' | Get-ScheduledTaskInfo
   ```

   `LastTaskResult` of `0` means success. If either task doesn't exist at
   all, it was never installed on this machine — see
   `docs/backup-restore.md`/`docs/deployment.md` for the one-time
   `scripts\install-backup-schedule.ps1`/
   `scripts\install-queue-worker-schedule.ps1` setup step. **As of this
   writing, neither task is registered on this development machine** —
   expected here (both install scripts are explicitly a one-time,
   deliberate step "the server administrator runs... when setting up,"
   not something development work triggers automatically), but a real
   action item before/at production go-live if this machine is also the
   production server (`docs/deployment.md`).

## One-shot convenience script

`scripts/health-check.ps1` runs all three checks in one pass and prints a
pass/fail summary (reads `APP_URL` from `.env`, same `Read-EnvValue`
pattern `backup-database.ps1` already uses):

```powershell
powershell -File scripts\health-check.ps1
```

Exits `0` if everything passes, or the number of failed checks otherwise
— a person runs this themselves; it is **not** a background process or a
scheduled task.

**Proven against the real running app while writing this WP**, not just
written and assumed to work:

```text
=== PMMS health check ===

[1/3] https://pmms.app/up
  PASS - HTTP 200

[2/3] storage/logs/laravel.log (last 200 lines)
  FAIL - 1 alarming line(s), most recent:
    [2026-07-27 15:59:54] local.ERROR: SQLSTATE[HY000] [2002] ...

[3/3] Scheduled Tasks
  FAIL - 'PMMS Database Backup' is not registered
  FAIL - 'PMMS Queue Worker' is not registered

===========================
3 check(s) failed - see above.
```

This is the genuine current state of this development machine, not a
sanitized example — check 1 passes for real, and checks 2/3 fail for the
real, explainable reasons described above (a historical log line, and
scheduled tasks that are a deliberate one-time production setup step, not
yet run on this dev machine). The script correctly detected all three —
which is itself the proof that it's runnable and reports real status,
not a script that just always prints "PASS."

## What this deliberately doesn't do

No automated alerting (email/SMS/webhook on failure) — a human runs this
and reads the output. No new scheduled task that runs monitoring itself.
No external monitoring/APM service, no uptime history or dashboards.
Proportionate to this deployment's real scale: one local server, one
Division, no dedicated ops team.

## See also

- `docs/turnover.md` — "Routine maintenance checklist" (the suggested
  cadence) and the escalation table (who to contact if a check fails).
- `docs/backup-restore.md` / `docs/deployment.md` — the two scheduled
  tasks this routine checks on, and how to install them.
- `docs/support-workflow.md` — if a check reveals a real bug, file it the
  same way any other bug gets filed.
