# WP-05-01 — Management Dashboard Foundation

## Purpose
Establish the shell and access control every later Phase 5 WP builds on: a new
cross-meet management view for Admin/Organizer, and the query foundation
(meet/school-year scoping) the trend WPs will reuse.

## Tasks
- Inspect the repository before making changes.
- Implement only the scope of this work package.
- Preserve architecture and coding standards.
- Update documentation where necessary.
- Run backend and frontend quality checks.
- Do not commit or push changes.

## Scope
- New `ManagementDashboardController` (or equivalent) + `/management` route,
  named `management.index`, gated `can:manage-meet-data` (same gate as Phase
  3's operational queues — Admin and Organizer only; Delegation Officer and
  Viewer get a 403, matching the existing authorization matrix pattern).
- Page shell (`resources/js/pages/management/index.tsx`): `PageHeader`, a
  meet/school-year filter control (reused by every later WP's widgets on this
  page), empty state when no meets exist yet. No widgets with real data in
  this WP beyond a placeholder confirming the filter works end-to-end.
- Sidebar nav item "Management" in `managerNavItems` (admin+organizer), icon
  consistent with the existing set.
- Shared query helper(s) for "meets in scope" (all meets, or filtered to a
  school-year) that WP-05-02..05 will each extend with their own aggregate —
  keep this WP's own helper minimal and proven by its test, not speculative.
- Tests: guest/Delegation-Officer/Viewer forbidden (403, matrix rows),
  Admin/Organizer allowed, filter changes the meet scope reflected in props.
- `docs/authorization.md` matrix row(s) for the new route.

## Out of Scope
Any actual trend/history widget (WP-05-02..05), reports/export (WP-05-06),
accessibility polish (WP-05-07).

## Deliverables
- Updated source code
- Updated documentation (docs/dashboard.md or a new docs/management-dashboard.md,
  docs/authorization.md)
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
WP-05-02 — Participation & Registration Trends
