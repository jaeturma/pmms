# WP-08-02 — Monitoring & Health-Check Routine

## Purpose
A documented, repeatable **manual** routine to confirm the app is up and
its Phase 6 scheduled tasks (backup, queue worker) are actually running —
proportionate to one local server, no new automated monitoring/alerting
(owner decision 2026-07-27).

## Tasks
- Inspect the repository before making changes.
- Implement only the scope of this work package.
- Preserve architecture and coding standards.
- Update documentation where necessary.
- Run backend and frontend quality checks.
- Do not commit or push changes.

## Scope
- New `docs/monitoring.md`: a three-point checklist someone runs on a
  cadence they decide —
  1. `/up` responds (Laravel's built-in health-check route).
  2. `storage/logs/laravel.log` has nothing alarming since the last check
     (what to skim for: `ERROR`/`CRITICAL`/`EMERGENCY`).
  3. Both Scheduled Tasks from Phase 6 (`PMMS Database Backup`,
     `PMMS Queue Worker`) are registered and their last run succeeded
     (`Get-ScheduledTask | Get-ScheduledTaskInfo`).
- Optional convenience script (`scripts/health-check.ps1`) that runs all
  three checks in one pass and prints a pass/fail summary — **a one-shot
  manual tool a person runs themselves, not a background process or
  scheduled task**; build it only if it doesn't turn into more than that.
- Fold into or cross-reference `docs/turnover.md`'s existing "Routine
  maintenance checklist" (WP-06-08) so there's one canonical maintenance
  reference, not a second disconnected operations document.

## Out of Scope
Automated alerting (email/SMS/webhook on failure); a new scheduled task
that runs monitoring itself; any external monitoring/APM service; uptime
history/dashboards.

## Deliverables
- `docs/monitoring.md`
- `scripts/health-check.ps1` (optional, only if it stays a one-shot tool)
- Updated `docs/turnover.md`
- Completion report
- Git status summary

## Acceptance Criteria
- Repository inspected first.
- No unrelated features added.
- The checklist is proven runnable against the real app, not just
  written and assumed to work.
- Tests and quality checks completed (no code expected to change; gate
  must still pass green).
- Documentation updated.
- No secrets exposed.
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
WP-08-03 — Phase 8 Compliance Review & Acceptance
