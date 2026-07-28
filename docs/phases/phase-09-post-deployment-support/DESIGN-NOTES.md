# Phase 9 Design Notes

- **GitHub Issues is the tracker, not a new in-app or markdown system.**
  The owner chose this specifically over a `docs/support/issues.md`-style
  log — use `gh issue create`/the GitHub web UI, not a file this repo
  tracks. Issue *templates* live in `.github/ISSUE_TEMPLATE/` (tracked in
  git, since they're config, not the issues themselves).
- **`.github/` is reintroduced narrowly.** Phase 1 removed the starter
  kit's own `.github/` early on (CI workflows and PR templates that were
  never written for this project). WP-09-01 adds back only
  `.github/ISSUE_TEMPLATE/*.yml` (or `.md`) files — no
  `.github/workflows/` (GitHub Actions/CI), which stays out of scope the
  same way it has since Phase 1. If CI is ever wanted, that's its own
  future, explicitly-scoped decision, not something to bundle in here.
- **Two issue templates, matching the two things that actually go wrong**
  in a system like this: a bug report (something that used to work, or
  should work per the manuals/docs, doesn't) and a support request
  (someone needs something done that the app doesn't self-serve today —
  e.g. a role promotion, per `docs/manuals/admin-manual.md` §2's documented
  limitation). Not a generic "issue" template — the two real categories
  this project already knows it has.
- **The triage → label → fix → close workflow is a short doc, not a
  process diagram tool.** Plain markdown, referencing GitHub's own label
  feature (a small fixed label set: `bug`, `support`, `needs-triage`, and
  severity if useful) — no new tooling, no automation rules, no bot.
- **Monitoring is a checklist a human runs, not a service.** The owner
  explicitly declined automated alerting. The checklist covers exactly
  three things, all already true today with no new code:
  1. `/up` responds (Laravel's built-in health-check route,
     `bootstrap/app.php`'s `health: '/up'`) — `curl` or a browser hit is
     enough, no new script strictly required, though a tiny convenience
     script that runs all three checks in one pass and prints a summary is
     in scope if it stays a one-shot manual tool, not a background process.
  2. `storage/logs/laravel.log` doesn't show anything alarming since the
     last check (a level to skim for: `ERROR`/`CRITICAL`/`EMERGENCY`).
  3. Both Scheduled Tasks from Phase 6 (`PMMS Database Backup`,
     `PMMS Queue Worker`) are still registered and their last run
     succeeded — `Get-ScheduledTask | Get-ScheduledTaskInfo`, already
     documented in `docs/backup-restore.md`/`docs/deployment.md`, just not
     yet pulled into one combined routine a person actually follows on a
     cadence.
- **No new dependency, no new migration.** If WP-09-01/02 happen to
  surface a real application bug while exercising these flows (unlikely,
  but possible), it gets filed as a GitHub Issue via the very workflow
  being built, not silently fixed inline — this phase's own diff should
  stay scoped to docs/`.github/` templates only, unlike WP-06-03/04 where
  fixing a found defect was explicitly that WP's own job.
- **Reuse, don't duplicate, Phase 6's operational docs.** `docs/turnover.md`
  already has a "Routine maintenance checklist" and an escalation-contacts
  template — this phase's monitoring routine cross-references rather than
  re-explains backup/restore or deployment procedure, and its own
  deliverable should slot into `docs/turnover.md`'s existing structure
  (or be linked from it) rather than becoming a third, disconnected
  operations document.
