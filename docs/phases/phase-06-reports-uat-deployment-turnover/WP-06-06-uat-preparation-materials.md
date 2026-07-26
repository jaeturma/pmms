# WP-06-06 — UAT Preparation Materials

## Purpose
Prepare the materials a real User Acceptance Testing session will need whenever
the Division schedules one — role-based test scripts, checklists, and a
feedback-capture mechanism. Per owner decision 2026-07-26, this WP prepares
materials only; it does not execute UAT with real testers (no testers or
timeline exist yet — see README's Grounding section).

## Tasks
- Inspect the repository before making changes.
- Implement only the scope of this work package.
- Preserve architecture and coding standards.
- Update documentation where necessary.
- Run backend and frontend quality checks.
- Do not commit or push changes.

## Scope
- New `docs/uat/` directory with one test script per role (Admin, Organizer,
  Delegation Officer, Viewer, public guest), each a numbered checklist of real
  scenarios exercising actual app flows end-to-end (e.g., Organizer script:
  create a meet → publish it → register a delegation as an officer → enter
  athletes → encode and validate a result → view medal tally → start and end a
  live scoring session → generate a report).
- Scripts must be runnable against `SampleProvinceDemoSeeder` data (or a
  documented superset of it) so a real tester can follow them without needing
  production data first.
- A feedback-capture template (a simple structured markdown or CSV form: step,
  expected result, actual result, pass/fail, notes) testers fill in during a
  real session — decide the simplest format that doesn't require new app
  features (no new in-app feedback tool; this is an offline artifact).
- A short `docs/uat/README.md` explaining how to run a UAT session once
  scheduled: environment setup (point at a fresh seeded copy, not production
  data), how to assign scripts to testers, how completed feedback forms get
  triaged back into follow-up work.
- Cross-reference `docs/manuals/` (WP-06-05) — UAT scripts test the same flows
  the manuals document, so testers can use the manual to understand a screen
  and the script to know what to verify.

## Out of Scope
Recruiting or scheduling real testers; running an actual UAT session; building
an in-app feedback/bug-report feature; automated UAT (that's what the Pest
suite already is).

## Deliverables
- New `docs/uat/` directory: per-role scripts, feedback template, README
- Completion report
- Git status summary

## Acceptance Criteria
- Repository inspected first — every script step verified against the actual
  running app.
- No unrelated features added.
- Tests and quality checks completed (no code expected to change; gate must
  still pass green).
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
WP-06-07 — Production Readiness & Deployment Hardening
