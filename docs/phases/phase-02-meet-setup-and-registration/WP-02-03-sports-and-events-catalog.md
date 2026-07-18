# WP-02-03 — Sports & Events Catalog

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
- `sports` and `events` tables: sport has many events; event carries gender category and
  age division (e.g., elementary/secondary), team-vs-individual flag, and max entries per
  delegation (configuration over one-off code).
- Admin/organizer CRUD pages on the shared component library; read-only for others.
- Soft archive once referenced; audit changes.
- Seeder with common provincial-meet sports as clearly-labeled reference data.
- Tests: CRUD authorization, validation, sport–event relationships.

## Out of Scope
Sport-specific scoring rules, brackets/formats logic, scheduling, officiating —
configuration fields may exist only where registration needs them.

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
WP-02-04 — Meet Setup & Lifecycle
