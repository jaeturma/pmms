# WP-03-01 — Venue Registry

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
- `venues` table (name unique, address/notes, active flag) with model, factory, FKs
  and indexes per database rules.
- Manager-gated CRUD + archive/restore on the shared registry pattern (search,
  pagination, dialogs, audit entries `venue.*`), viewers see the list.
- Delete guard once schedules reference venues (WP-03-02 will add the reference; ship
  the guard hook the same way school/district guards were built).
- Sidebar entry alongside the other registries.
- Tests: CRUD authorization, archive/restore, search/pagination, audit records.

## Out of Scope
Scheduling (WP-03-02), venue capacity planning, maps, resource booking.

## Deliverables
- Updated source code
- Updated documentation (docs/venues.md, component/authorization notes as needed)
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
WP-03-02 — Event Scheduling & Venue Assignment
