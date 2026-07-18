# WP-02-05 — Delegation Registration

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
- `delegations` table: school + meet (unique pair), head-of-delegation contact fields,
  status (`draft → submitted → approved`), timestamps.
- Link delegation officers (users) to their delegation; officers manage only their own
  delegation (policy), organizers/admins manage all.
- Registration respects the meet lifecycle (only while `registration_open`).
- Submission and approval flows with confirmation dialogs; all transitions audited.
- Delegation list/detail pages on shared components; empty states for no delegations.
- Tests: one-delegation-per-school-per-meet, officer scoping, window enforcement,
  approval authorization.

## Out of Scope
Athlete/coach records (next WPs), billeting/logistics fields, accreditation, messaging.

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
WP-02-06 — Athlete Registry
