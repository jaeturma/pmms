# WP-06-02 — Backup & Restore Baseline

## Purpose
Establish a real, provable database backup and restore procedure for the actual
production database (`pmmsdb`, local MySQL via Laragon). None exists today — this
is new operational tooling, not a review.

## Tasks
- Inspect the repository before making changes.
- Implement only the scope of this work package.
- Preserve architecture and coding standards.
- Update documentation where necessary.
- Run backend and frontend quality checks.
- Do not commit or push changes.

## Scope
- A `mysqldump`-based backup script (Windows-appropriate — PowerShell or a
  `.bat`/`.cmd` wrapper, consistent with this project's Windows/Laragon
  environment) that dumps `pmmsdb` to a timestamped file in a documented
  location, excluding anything that shouldn't be in a backup artifact (no
  `.env` secrets bundled in).
- A restore procedure: documented steps (and a script, if it meaningfully
  reduces error) to restore a dump into a fresh/throwaway database.
- **Prove it end-to-end**: take a real backup of the current `pmmsdb`, restore
  it into a separate throwaway database, and confirm the app can read from the
  restored copy correctly (e.g., point a local `.env` copy at it and verify a
  known record — not just that `mysql` exits 0).
- A retention policy recommendation appropriate to this deployment's actual
  scale (a single Division's meets, one local server) — e.g., how many
  generations to keep and where, not an enterprise multi-region policy.
- A scheduling recommendation for how the backup script actually runs
  unattended (Windows Task Scheduler entry) — document the setup steps; only
  create the actual scheduled task if it can be done without requiring
  elevated/administrative changes outside what this session can verify,
  otherwise document the exact steps for the owner to run.
- New `docs/backup-restore.md` covering: what's backed up, how, where backups
  land, how to restore, and the retention/schedule recommendation.
- File access: the backup file(s) contain full PII (minor athlete data,
  personnel data) — document where they're stored and that directory access
  needs to be restricted to whoever administers the server, cross-referenced
  from WP-06-03's security review.

## Out of Scope
Cloud backup storage/offsite replication (not applicable to the local-only
deployment target); point-in-time recovery beyond periodic full dumps;
automated backup monitoring/alerting.

## Deliverables
- New backup script
- New restore script/documented procedure
- New `docs/backup-restore.md`
- Completion report
- Git status summary

## Acceptance Criteria
- Repository inspected first.
- No unrelated features added.
- Backup-then-restore proven end-to-end against the real `pmmsdb`, not just
  scripted and assumed to work.
- Tests and quality checks completed (if any code changes touch the
  application itself — this WP is mostly outside the Laravel app's own test
  suite, but existing gate must still pass green).
- Documentation updated.
- No secrets exposed (verify the backup script itself doesn't print or log
  credentials, and isn't committed with a hardcoded password).
- No commit or push performed.

## Completion Report
Include:
1. Repository findings
2. Files created
3. Files modified
4. Test results
5. Quality results
6. Remaining issues
7. Recommended next work package

Next:
WP-06-03 — Security & Privacy Review
