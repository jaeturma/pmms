# WP-02-02 — Organization & School Registry

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
- `districts` and `schools` tables (school belongs to district; name, school ID code,
  level, address basics — minimal fields only).
- Admin/organizer CRUD pages built on the shared component library (table, PageHeader,
  EmptyState, ConfirmDialog); delegation officers and viewers read-only.
- Soft archive (active flag) instead of hard delete once referenced.
- Audit create/update/archive via `AuditLogger`.
- Seeder with clearly-labeled sample reference data for local development only.
- Tests: CRUD authorization per role, validation, archive behavior.

## Out of Scope
Region/national hierarchy, DepEd system integration, bulk import (CSV import may come as
a later enhancement), delegation or athlete data.

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
WP-02-03 — Sports & Events Catalog
