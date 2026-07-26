# Database Backup & Restore

WP-06-02. A `mysqldump`-based backup and restore baseline for the production
database (`pmmsdb`, local MySQL via Laragon at `D:\lara`). Nothing like this
existed before this WP — it's new operational tooling, not an app feature, so
it lives outside the Laravel app itself in `scripts/` and isn't covered by the
Pest suite.

## What's backed up

A full logical dump of the configured database (`DB_DATABASE` in `.env`) via
`mysqldump --single-transaction --routines --triggers` — schema and data for
every table, taken as a consistent InnoDB snapshot without locking the tables
(safe to run while the app is live). This is a full backup every time, not
incremental — appropriate at this deployment's scale (a single Division's
meets on one local server), not a multi-terabyte system that would need
incremental/point-in-time strategies.

`.env` itself and any other application secrets are **never** included in a
backup — only the database contents.

## How

`scripts/backup-database.ps1`:

```powershell
powershell -File scripts\backup-database.ps1
```

- Reads `DB_HOST`/`DB_PORT`/`DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD` from the
  project's `.env` at runtime. Credentials are never hardcoded, logged, or
  passed on the command line — they're written to a short-lived, ACL-restricted
  MySQL "defaults extra file" in `%TEMP%` that's deleted immediately after the
  dump finishes (success or failure).
- Refuses to run if `DB_CONNECTION` isn't `mysql` (a dev sqlite setup has
  nothing for this script to do).
- Requires `mysqldump`/`mysql` on `PATH` — Laragon ships these under
  `bin\mysql\<version>\bin`.
- Writes a timestamped, gzip-compressed dump:
  `storage/app/private/backups/database/pmmsdb-YYYYMMDD-HHmmss.sql.gz`
  (`storage/app/private` is Laravel's non-web-accessible local disk root —
  see `config/filesystems.php` — so a backup file is never reachable by URL).
  Pass `-NoCompress` to skip compression and get a plain `.sql` file.
- Applies retention: keeps the newest `-RetentionCount` backups (default 14)
  for this database and deletes older ones in the same run.

`storage/app/private/backups/` is gitignored — backup files are real
artifacts on the server, never committed.

## Restore

`scripts/restore-database.ps1`:

```powershell
powershell -File scripts\restore-database.ps1 -BackupFile <path to .sql or .sql.gz> -TargetDatabase <name>
```

- Creates `TargetDatabase` if it doesn't already exist, then loads the dump
  into it (transparently decompressing first if the file is `.sql.gz`).
- **Refuses to target the production database name from `.env` unless you
  pass `-Force`** — a routine restore-and-verify drill can't accidentally
  overwrite live data. Real disaster recovery (restoring over a genuinely
  lost/corrupted production database) is the one case where `-Force` is
  correct.
- Same credential handling as the backup script — `.env`-sourced, short-lived
  defaults file, never on the command line.

### Proof (WP-06-02, 2026-07-26)

Backup → restore was proven end-to-end against the real `pmmsdb`, not just
run and assumed to work:

1. `backup-database.ps1` produced a real dump of `pmmsdb`
   (`pmmsdb-20260726-194559.sql.gz`, 39 tables).
2. Recorded a baseline directly from production: `districts` row count and
   the current `divisions.name` value.
3. `restore-database.ps1` restored that dump into a throwaway database
   (`pmmsdb_restore_test` — refused without `-Force` since it isn't the
   production name).
4. Queried the same two facts from the restored copy: **identical** row count
   and division name, and the same 39-table schema — a genuine data-integrity
   check, not just a zero exit code.
5. The throwaway database was dropped immediately after verification; nothing
   was left behind beyond the one real backup file from step 1.

## Retention

Recommended: keep 14 daily backups on local disk (the default). At this
deployment's scale — a single Division's meets, not a multi-tenant system —
that's roughly two weeks of daily recovery points, comfortably small on local
disk (each dump was under 1 MB against the current dataset; expect low tens of
MB even at full provincial-meet scale). No offsite/cloud replication — the
deployment target is this local server only (owner decision, 2026-07-26); if
that ever changes, copying the `backups/database/` directory to another
machine after each run (or after the scheduled task) is a reasonable manual
addition, not something this WP builds.

## Scheduling

`scripts/install-backup-schedule.ps1` registers a daily Windows Task Scheduler
entry that runs `backup-database.ps1` unattended:

```powershell
powershell -File scripts\install-backup-schedule.ps1
powershell -File scripts\install-backup-schedule.ps1 -Time '02:00' -RetentionCount 30
```

**Not run automatically by this WP or by anything in the repo** — registering
a scheduled task changes real state on the host machine outside of source
control, so it's provided as a script for whoever administers the production
server to run deliberately, once, during deployment setup (or to re-run if the
schedule/retention needs to change — `Register-ScheduledTask -Force` inside it
overwrites any existing task of the same name). Verify afterward with:

```powershell
Get-ScheduledTask -TaskName 'PMMS Database Backup' | Get-ScheduledTaskInfo
```

## Access control

A backup file is a complete copy of the database — full minor-athlete and
personnel PII, not a redacted export. `storage/app/private/backups/` is not
web-accessible (see above), but filesystem-level access on the host still
matters: only the account(s) that administer the server should be able to read
that directory. This is flagged for WP-06-03's security review to confirm the
directory's OS-level permissions are actually restricted on the real
deployment host, not just "not served over HTTP."

## Out of scope

Cloud/offsite backup storage, point-in-time recovery beyond periodic full
dumps, and automated backup monitoring/alerting are all explicitly out of
scope for this deployment target — see
`docs/phases/phase-06-reports-uat-deployment-turnover/WP-06-02-backup-and-restore-baseline.md`.
