# WP-02-08 — Event Entry Submission

## Purpose
Complete this work package for the PMMS Division Edition. Keep implementation practical
for a Schools Division Office. Build only what is required for a maintainable
production-quality system.

## Tasks
- Inspect the repository before making changes.
- Implement only the scope of this work package.
- Preserve architecture and coding standards.
- Update documentation where necessary.
- Run backend and frontend quality checks.
- Do not commit or push changes.

## Scope
- `entries` table: athlete + meet_event (unique pair), delegation, status
  (`submitted → confirmed | withdrawn`), timestamps.
- Validation on submission: athlete's sex/age division matches the event, entry cap per
  delegation per event respected, meet registration window open, athlete belongs to the
  submitting delegation.
- Delegation officers submit/withdraw for their own delegation; organizers confirm;
  all transitions audited.
- Entry views: per-delegation entry list and per-event entry list on shared components.
- Tests: each validation rule, scoping, cap enforcement, window enforcement.

## Out of Scope
Seeding/draws/heats/lanes (Phase 3), team-event roster composition beyond simple athlete
lists, scoring, schedules.

## Deliverables
- Updated source code and migrations
- Updated documentation
- Completion report
- Git status summary

## Acceptance Criteria
- Repository inspected first.
- No unrelated features added.
- Tests and quality checks completed.
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
WP-02-09 — Eligibility Documents & Manual Review
