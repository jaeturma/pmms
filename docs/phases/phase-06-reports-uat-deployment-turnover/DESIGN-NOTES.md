# Phase 6 Design Notes

Correction to the superseded draft: it opened with "Municipality = Official
Delegation" — the exact assumption the Division initiative (WP1–WP7, complete
2026-07-25) replaced. Reports, CSV export, and a compliance-review cadence also
already exist in this codebase — see `README.md`'s Grounding section. The draft's
remaining ideas (backup baseline, security review, performance check, manuals,
UAT prep, deployment hardening, training/turnover, final review) were sound in
category and are kept, rescoped to what's actually missing; "pilot issue
resolution" is dropped — see README's Exclusions.

Important rules:

- **Verify, don't rebuild.** WP-06-01 explicitly does not touch report code unless
  a real gap is found (e.g., a report that still reads a pre-Division field, or a
  print-layout regression from a later phase). If nothing is wrong, the WP's
  output is a verification note in `docs/reports.md`, not a diff.
- **Backups target the real deployment, not a hypothetical one.** WP-06-02 builds
  a `mysqldump`-based backup and restore procedure for the actual `pmmsdb` MySQL
  database on the actual Laragon/Windows host this app runs on — not a
  cloud-provider snapshot feature that doesn't apply here. Restore must be proven
  by actually restoring a dump into a throwaway database and confirming the app
  reads it correctly, not just by the dump file existing.
- **Security review builds on existing foundations, not from zero.**
  `AuthorizationMatrixTest`, `AuditLogger`, and Phase 7's clean `composer
  audit`/`npm audit` already exist. WP-06-03 re-runs those checks, sweeps for
  anything Phase 6 or Phase 7 might have missed (e.g., new live-scoring public
  routes from WP-07-08, the backup file itself as a new artifact that needs
  access control), and reviews minor-athlete/PII exposure paths specifically —
  it does not re-derive authorization from scratch.
- **Performance target is realistic, not generic.** This is a single Division's
  meet (Davao de Oro, 11 municipalities) running on one local server, not a
  multi-tenant SaaS product. WP-06-04 verifies the app performs acceptably at
  that actual scale (using `SampleProvinceDemoSeeder`-shaped volume, scaled up if
  needed) — N+1 query checks on the pages that list/aggregate across a whole meet
  (reports, medal tally, dashboards), not a synthetic load-testing framework this
  project has no infrastructure to run continuously.
- **UAT materials are prepared against real app behavior, not invented
  scenarios.** WP-06-06 writes test scripts per role (Admin, Organizer, Delegation
  Officer, Viewer, public guest) that walk through actual existing flows
  (registration → entries → results → medal tally → live scoring → reports),
  usable against `SampleProvinceDemoSeeder` data. It explicitly does not run these
  scripts against real users — see README's Grounding section for why.
- **Deployment hardening works within the existing local Laragon topology.**
  WP-06-07 does not introduce Docker, a new hosting provider, or a different
  runtime — the owner's decision is to harden the current setup, not replace it.
  Concretely: production `.env` values (`APP_ENV=production`, `APP_DEBUG=false`,
  proper `APP_KEY`, `DB_CONNECTION=mysql` pointed at `pmmsdb`, a real `MAIL_MAILER`
  if password reset/email verification is in active use — check Fortify config
  before assuming), and — the one functionally new piece — a persistently-running
  queue worker (Windows Task Scheduler or a service wrapper around `php artisan
  queue:work`), since `QUEUE_CONNECTION=database` means Phase 7's
  `ScoreUpdated` broadcast silently never fires without one. `BROADCAST_CONNECTION`
  staying `log`/Reverb-off in production is an acceptable, documented choice (live
  scoring still works via polling) unless the owner wants Reverb running for
  real-time updates, which is a deployment decision to confirm during the WP, not
  assume.
- **Manuals and turnover materials describe what exists, not aspirational
  features.** WP-06-05/WP-06-08 are written from the actual UI and actual
  workflows (grep the routes/pages, don't invent screens), same discipline as
  every other phase's documentation.
- Reuse the shared component library and existing doc conventions
  (`docs/*.md` one-file-per-feature, `.ai/current-phase.md` WP log,
  `docs/phases/phase-XX/phase-X-compliance-review.md` closing report) — no new
  documentation system invented for this phase.
