# Phase 6 — Reports, UAT, Deployment, and Turnover

**Status:** Planned 2026-07-26 — pending owner approval. Execution has not started.
Replaces the unreviewed generic-template draft that occupied this directory before
(git history — same recurring scaffolding mixup Phase 4/5/7 each found and
corrected before planning off it): it treated the delegation as the reporting
attribution unit ("keep municipality as the official delegation"), which collided
with the real model (`docs/division.md`, `docs/medal-tally.md`) — an individual's
own school, not the delegation, is the attribution unit almost everywhere, settled
by the Division initiative (WP1–WP7, complete 2026-07-25). It also assumed reports,
CSV export, and a compliance-review cadence didn't exist yet; all three do. This
plan is written fresh for this codebase, scoped down per owner instruction
2026-07-26 (see Grounding and Scoping decisions below).

## Goal

Close out PMMS Division Edition for real use by the Division office: verify the
reporting surface is complete and correct after every phase that touched it,
establish a database backup/restore baseline (none exists today), run a security/
privacy and performance pass, produce the manuals and turnover materials a
non-developer operator needs, prepare (not execute) UAT materials, and harden the
existing local deployment for production use. This phase does not add new product
features — it verifies, documents, and operationally hardens what Phases 1–5, the
Division initiative, and Phase 7 already built.

## Grounding

- **Reports and CSV export already exist and are current.** `docs/reports.md` /
  `app/Http/Controllers/ReportController.php`: six report pages (delegation roster,
  per-event entry list, school participation summary, official result sheet, medal
  tally, daily schedule) each with `window.print()` + an `@media print` stylesheet
  and a CSV download route, audited (`report.*_exported`), role-scoped per report.
  The Division initiative's WP5 already re-labeled every report for
  `division.areaLabel` and re-keyed school attribution. There is no dedicated
  "build reports" work package in this phase — the old draft's WP-06-01 assumed
  reports didn't exist yet; instead this phase verifies them (WP-06-01).
- **No Excel (.xlsx) export exists or is being added.** Per owner decision
  2026-07-26: CSV (already shipped, opens fine in Excel) is sufficient. The old
  draft's WP-06-02 is dropped, not deferred — it's not a gap.
- **No backup/restore mechanism exists for the MySQL database.** `pmmsdb` (local
  MySQL via Laragon) has no documented or scripted backup today. Per owner
  decision 2026-07-26, this needs to be built from scratch (WP-06-02).
- **Production deployment target is the existing local Laragon setup**, per owner
  decision 2026-07-26 — not a new cloud/VPS environment. WP-06-07 hardens what's
  already running (http://pmms.app via Laragon on this machine) rather than
  standing up new infrastructure. `.env.example` currently ships dev defaults
  (`APP_ENV=local`, `APP_DEBUG=true`, `DB_CONNECTION=sqlite`,
  `QUEUE_CONNECTION=database`, `BROADCAST_CONNECTION=log`, `MAIL_MAILER=log`) —
  these are correctly dev-only; WP-06-07 is where the real `.env` gets a
  production pass. Note `QUEUE_CONNECTION=database` matters now: Phase 7's
  `ScoreUpdated` broadcast is a queued job (`ShouldBroadcast`), so a queue worker
  needs to actually be running continuously in production, which it currently
  isn't (no scheduled task/service exists yet) — a real gap WP-06-07 closes.
- **UAT execution needs real testers and a real schedule, which don't exist yet.**
  Per owner decision 2026-07-26, this phase prepares UAT materials (role-based
  scripts, checklists, a feedback-capture mechanism against the existing
  `SampleProvinceDemoSeeder` demo data) but does not run a UAT session — that's a
  real-world activity for whenever the Division schedules it, not something
  executed inside a coding session. The old draft's separate "UAT Execution" WP is
  dropped for the same reason; WP-06-06 covers preparation only.
- **Pilot deployment during a real meet is out of scope for this phase**, for the
  same reason as UAT execution — it depends on an actual scheduled meet and real
  operators, which don't exist yet. The old draft's WP-06-11 is dropped, not
  deferred silently: if/when a real pilot meet is scheduled, that's its own future
  work informed by this phase's manuals and runbooks, not a WP here.
- Security posture already has real foundations to build on, not from scratch:
  `AuthorizationMatrixTest` (58 forbidden actions × role), full audit trail
  (`AuditLogger`), `composer audit` / `npm audit` both clean as of Phase 7's
  review. WP-06-03 is a review and closure pass, not a rebuild.
- Every phase from Phase 3 onward has closed with its own compliance review
  (`phase-3-compliance-review.md`, `phase-4-...`, `phase-5-...`,
  `phase-7-compliance-review.md`) — WP-06-09 continues that convention for Phase 6
  itself.

## Scoping decisions (owner, 2026-07-26)

| Question | Decision |
|---|---|
| Production deployment target | Same local Laragon setup — harden what's running, don't provision new infra |
| Native `.xlsx` export | Not needed — existing CSV export is sufficient |
| Database backup mechanism | Build from scratch (none exists) |
| UAT | Prepare generic materials only — no real testers/timeline yet, execution is future work |

## Principles

- Verify before building: several of the old draft's "build" work packages
  describe things that already exist (reports, CSV export, an authorization/audit
  foundation) — check the real codebase first, same discipline as every prior
  phase's planning.
- No new product features this phase. Every WP either verifies existing behavior,
  adds operational tooling (backup script, deployment hardening), or produces
  documentation/training material.
- Reuse existing conventions: `AuditLogger` for anything new that needs auditing,
  the existing report/print patterns, the existing compliance-review format for
  WP-06-09.
- One work package at a time; nothing committed or pushed without owner
  instruction.

## Work Packages

| WP | Title |
|---|---|
| WP-06-01 | Reports & Print Verification |
| WP-06-02 | Backup & Restore Baseline |
| WP-06-03 | Security & Privacy Review |
| WP-06-04 | Performance & Load Verification |
| WP-06-05 | Administrator & User Manuals |
| WP-06-06 | UAT Preparation Materials |
| WP-06-07 | Production Readiness & Deployment Hardening |
| WP-06-08 | Training & Turnover Package |
| WP-06-09 | Phase 6 Compliance Review & Acceptance |

Sequence is mostly independent (WP-06-01 through WP-06-04 can run in any order),
but WP-06-05 (manuals) and WP-06-08 (turnover) are easiest written after WP-06-07
(deployment hardening) settles the real operating procedure, and WP-06-09 must run
last. Nine WPs — leaner than the old 12-WP draft, since three (Excel export, UAT
execution, pilot deployment) are dropped as either redundant or out of scope for a
coding session, per the scoping decisions above.

## Visual Checkpoints

1. **After WP-06-01/02:** every existing report still prints and exports correctly
   post-Division-initiative; a real backup file can be produced and restored into
   a clean database, proven end-to-end.
2. **After WP-06-03/04:** a documented, COMPLIANT-or-fixed security/privacy review
   and a performance pass at realistic single-province scale (11 municipalities'
   worth of seeded demo data).
3. **After WP-06-07:** the app runs under production-appropriate `.env` settings
   (debug off, queue worker actually running) on the same Laragon host, with a
   documented restart/rollback procedure.
4. **After WP-06-09:** full quality gate green, compliance review filed, Phase 6
   closed — PMMS Division Edition ready to hand off to the Division office.

## Exclusions (deferred or explicitly out of scope)

Native `.xlsx` export; cloud/VPS provisioning; real UAT execution with live
testers; real pilot deployment during an actual meet and its issue resolution;
any new product feature; Flutter; AI; City division type's "district competes"
option (already a documented open item, `docs/division.md`).

## Completion

Phase 6 completes via WP-06-09 (full quality gate + compliance review), mirroring
WP-03-11/WP-04-11/WP-05-08/WP-07-03. The review report goes to this directory; the
WP log lives in `.ai/current-phase.md`.
