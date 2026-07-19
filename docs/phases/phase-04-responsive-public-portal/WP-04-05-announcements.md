# WP-04-05 — Announcements

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
- `announcements` table — the phase's only new entity: title, body (plain
  text/simple paragraphs, no rich-text editor), optional meet link, published
  flag + `published_at`, author FK; model, factory, indexes per database rules.
- Manager-only CRUD + publish/unpublish on an internal page (shared registry
  pattern: search, pagination, dialogs), audited (`announcement.*`); matrix rows
  for the forbidden role × action sweep.
- Public display: published announcements newest-first on the portal home
  (general + per-meet) and on the public meet page (that meet's only);
  unpublished announcements invisible everywhere public.
- Tests: CRUD authorization, publish flow + audit, public visibility rules,
  meet scoping, guest access.

## Out of Scope
Rich text/attachments, scheduling future publication, notifications of any kind,
comments or reactions.

## Deliverables
- Updated source code
- Updated documentation (docs/public-portal.md additions, docs/announcements.md)
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
WP-04-06 — Accessibility & Mobile Review
