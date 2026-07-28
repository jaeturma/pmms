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
   `scripts\install-queue-worker-schedule.ps1` setup step.

   **Both tasks were installed on this machine 2026-07-28** (owner
   instruction, following up on this WP's own flagged action item).
   Registration was verified for real, not assumed: `Get-ScheduledTask`
   shows both present with the correct action/trigger, and the backup
   task's `NextRunTime` correctly shows the next 2 AM slot. The backup
   script itself was also proven end-to-end — invoked directly with the
   exact command line the task runs, it produced a real
   `pmmsdb-*.sql.gz` file (then removed, as a test artifact, not a
   scheduled one). One honest caveat: manually forcing an immediate run
   via `Start-ScheduledTask` from an automated/remote session gave
   inconsistent results for both tasks (registered correctly, but the
   forced-immediate-run didn't reliably reflect in a new file or a
   persistent process) — most likely because both tasks use
   `LogonType: Interactive`, which expects a genuine interactively
   logged-on desktop session to attach to, and that automated session
   may not present as one. This shouldn't affect the *real* trigger
   paths (the actual 2 AM clock event, or a normal user login for the
   `AtStartup` trigger), which go through Windows' standard trigger-and-
   logon flow — but it also hasn't been independently confirmed by a
   real reboot or a real overnight backup yet. Worth a look at
   `storage/app/private/backups/database/` tomorrow morning to confirm
   a same-day file actually appeared.

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

**Proven against the real running app**, not just written and assumed to
work — first when writing this doc (2026-07-28, before either scheduled
task was installed), and re-run after installing both:

```text
=== PMMS health check ===

[1/3] https://pmms.app/up
  PASS - HTTP 200

[2/3] storage/logs/laravel.log (last 200 lines)
  FAIL - 1 alarming line(s), most recent:
    [2026-07-27 15:59:54] local.ERROR: SQLSTATE[HY000] [2002] ...

[3/3] Scheduled Tasks
  PASS - 'PMMS Database Backup' (Queued, last run: 07/28/2026 22:04:20)
  PASS - 'PMMS Queue Worker' (Queued, last run: 07/28/2026 22:03:23)

===========================
1 check(s) failed - see above.
```

This is the genuine current state of this development machine, not a
sanitized example. Check 1 passes for real. Check 2 still fails for the
same real, explainable, historical reason described above (a log line
that's no longer current, not something to fix). Check 3 now passes —
both tasks are registered as of 2026-07-28 (see above). The script
correctly detected all three states, before and after the install —
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
