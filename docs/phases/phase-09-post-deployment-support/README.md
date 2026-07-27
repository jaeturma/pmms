# Phase 8 — Post-Deployment Support

**Status:** Planned 2026-07-27 — pending owner approval. Execution has not
started. This directory did not exist before this plan — the roadmap
(`docs/howtorun/ROADMAP-UPDATE.md`) only ever named the phase
("Post-Deployment Support," renamed from the original Phase 7 slot to make
room for Live Scoring), with no scope written down anywhere. Scoped fresh
from two rounds of owner Q&A 2026-07-27, the same approach every phase since
Phase 6 has used.

## Goal

Give the Division a real, lightweight way to handle what happens *after*
go-live: someone finds a bug or needs a fix, and someone needs to know
whether the app is actually up and its scheduled maintenance tasks
(WP-06-02's backup, WP-06-07's queue worker) are actually running. Both are
process/documentation problems, not missing product features — this phase
adds no new application code.

## Scoped decisions (owner, 2026-07-27)

- **In scope:** a bug-fix/support workflow, and monitoring/health-check
  coverage. Asked as a multi-select from four options; the owner picked
  these two specifically.
- **Not in scope:** running a real UAT/pilot session (WP-06-06 already
  prepared the materials for this — it stays real-world future work, not
  something this phase executes either).
- **Issue tracking:** GitHub Issues — the repo already lives at
  `github.com/jaeturma/pmms`, so this is zero new infrastructure. Not a
  markdown log in the repo (the other option offered).
- **Monitoring depth:** a documented **manual** routine — no new
  always-on process, no automated alerting. The owner explicitly did not
  choose the "scheduled task + alerting" option, matching every prior
  phase's "no new cloud/VPS infra, no CI/CD automation" posture.

## Grounding

- `.github/` does not currently exist in this repo — Phase 1 removed the
  starter kit's own `.github/` (CI workflows/PR templates not written for
  this project) early on. WP-08-01 reintroduces `.github/` deliberately and
  narrowly: **issue templates only**, never GitHub Actions/CI workflows —
  that stays out of scope here the same way it has in every phase since
  Phase 1.
- WP-06-02 (`scripts/backup-database.ps1` + `install-backup-schedule.ps1`)
  and WP-06-07 (`scripts/install-queue-worker-schedule.ps1`) already created
  the two Windows Scheduled Tasks this phase's monitoring routine checks on
  — this phase does not re-create or duplicate that tooling, only verifies
  it's actually running and documents how to tell.
- `/up` (Laravel's built-in health-check route, `bootstrap/app.php`'s
  `health: '/up'`) already exists and needs no new code — this phase's job
  is documenting how/when to check it, not building it.
- No new dependency, no new migration, no new `app/` code is expected from
  this phase — if a real bug surfaces during WP-08-01/02 that genuinely
  needs a code fix, it gets logged as a GitHub Issue and fixed as its own
  scoped follow-up, not folded silently into this phase's diff (mirrors how
  WP-06-03/04 handled real findings they *did* fix inline — those were
  genuinely in-scope for their own review WPs; an unrelated bug found while
  writing this phase's support docs would not be).

## Principles

- Process and documentation, not new product features or infrastructure.
- Reuse what Phases 1–7 already built (health route, scheduled tasks,
  audit log, backup/restore) rather than inventing parallel tooling.
- Proportionate to this deployment's real scale: one local server, one
  Division, no dedicated ops team — a documented checklist someone runs
  periodically, not a monitoring platform.
- One work package at a time; nothing committed or pushed without owner
  instruction.

## Work Packages

| WP | Title |
|---|---|
| WP-08-01 | Bug & Support Workflow |
| WP-08-02 | Monitoring & Health-Check Routine |
| WP-08-03 | Phase 8 Compliance Review & Acceptance |

Three WPs — deliberately small, matching the phase's actual scope. Sequence
is loose between WP-08-01/02 (neither depends on the other's output) but
both must land before WP-08-03 closes the phase.

## Visual Checkpoints

1. **After WP-08-01:** a real GitHub issue can be filed against
   `github.com/jaeturma/pmms` using the new templates, and the documented
   triage → label → fix → close workflow is followable end to end by
   reading it alone.
2. **After WP-08-02:** the monitoring checklist can be run start to finish
   against the real running app and correctly reports the app is healthy
   and both scheduled tasks are active.
3. **After WP-08-03:** full quality gate green, compliance review filed,
   Phase 8 closed.

## Exclusions (deferred or explicitly out of scope)

Real UAT/pilot execution (WP-06-06's materials, still real-world future
work); automated monitoring/alerting; any new cloud/VPS/observability
infrastructure; CI/CD pipeline automation; GitHub Actions or any workflow
automation beyond issue templates; any new product feature; Flutter; AI.

## Completion

Phase 8 completes via WP-08-03 (full quality gate + compliance review),
mirroring WP-03-11/WP-04-11/WP-05-08/WP-07-03/WP-06-09. The review report
goes to this directory; the WP log lives in `.ai/current-phase.md`.
