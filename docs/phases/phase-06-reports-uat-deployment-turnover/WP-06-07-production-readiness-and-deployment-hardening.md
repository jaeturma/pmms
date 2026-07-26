# WP-06-07 — Production Readiness & Deployment Hardening

## Purpose
Harden the existing local Laragon deployment (http://pmms.app) for real
production use by the Division office. Per owner decision 2026-07-26, the target
stays this same local setup — no new cloud/VPS infrastructure is provisioned this
phase.

## Tasks
- Inspect the repository before making changes.
- Implement only the scope of this work package.
- Preserve architecture and coding standards.
- Update documentation where necessary.
- Run backend and frontend quality checks.
- Do not commit or push changes.

## Scope
- Define the real production `.env` values (do not commit actual secrets —
  document the required keys and produce a `.env.production.example` template
  alongside the existing dev-oriented `.env.example`): `APP_ENV=production`,
  `APP_DEBUG=false`, a generated `APP_KEY`, `DB_CONNECTION=mysql` pointed at
  `pmmsdb`, session/cache stores appropriate for a single-server deployment,
  and a real `MAIL_MAILER` if password reset/email verification (Fortify) is
  actually in use — check current Fortify config before assuming this is
  needed.
- **Queue worker**: `QUEUE_CONNECTION=database` means Phase 7's `ScoreUpdated`
  broadcast (`ShouldBroadcast`, queued) currently never fires in production
  because nothing runs `php artisan queue:work` continuously. Set up a Windows
  Task Scheduler entry (or documented service wrapper) that keeps a queue
  worker running and restarts it if it dies. Verify with a real test: start a
  live scoring session, score a point, confirm the queued broadcast job
  actually processes.
- **Broadcast decision**: confirm with the owner whether Reverb should run in
  production for real-time live scoring, or whether polling-only is acceptable
  (both are valid, already-supported configurations per Phase 7's design) —
  document whichever is chosen and why, don't assume.
- A documented build/deploy procedure for this local topology: `composer
  install --no-dev`, `npm run build`, `php artisan migrate --force`, cache/config
  optimization commands (`config:cache`, `route:cache`, etc.), and the exact
  restart steps for the local web server.
- A documented rollback procedure appropriate to a single-server local
  deployment (e.g., keep the previous release directory/git commit, know how to
  re-point and re-migrate down if a deploy goes wrong) — proportionate to this
  topology, not a blue-green/multi-region strategy.
- Confirm HTTPS/TLS posture for `pmms.app` is appropriate for how it's actually
  accessed (LAN-only vs. exposed) — document the finding; only add TLS
  configuration if the current setup genuinely lacks it and the owner confirms
  it's needed.
- New `docs/deployment.md` covering all of the above as the single reference for
  "how do I actually deploy/restart/roll back this app."

## Out of Scope
Docker/containerization; migrating to a different hosting provider; CI/CD
pipeline automation beyond documenting the manual steps; horizontal scaling.

## Deliverables
- `.env.production.example` template
- Windows Task Scheduler setup (or documented steps) for the queue worker
- New `docs/deployment.md`
- Completion report
- Git status summary

## Acceptance Criteria
- Repository inspected first.
- No unrelated features added.
- The queue-worker fix is proven end-to-end (a real broadcast job actually
  processes), not just configured and assumed to work.
- Tests and quality checks completed.
- Documentation updated.
- No secrets exposed — the `.env.production.example` template has placeholder
  values only, never a real `APP_KEY`/DB password.
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
WP-06-08 — Training & Turnover Package
