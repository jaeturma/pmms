# WP-02-04 — Meet Setup & Lifecycle

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
- `meets` table: name, school year, dates, host venue text fields, status lifecycle
  (`draft → registration_open → registration_closed → active → completed`), guarded
  transitions.
- `meet_events` pivot selecting which catalog events run in a meet.
- Admin/organizer meet CRUD + status transition UI with confirmation dialogs; lifecycle
  changes audited.
- Registration-window enforcement hooks (delegation/entry modules check meet status).
- Dashboard card: current meet and its status (real data, not placeholder).
- Tests: lifecycle transitions, invalid-transition rejection, event attachment,
  authorization.

## Out of Scope
Multiple concurrent meets beyond basic support, venue/resource management module,
calendar/scheduling, committees.

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
WP-02-05 — Delegation Registration
