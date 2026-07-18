# WP-02-07 — Coach & Official Registry

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
- `personnel` table (or similarly simple design): delegation, name, role type
  (coach, assistant coach, chaperone), contact basics, optional photo.
- Same scoping rules as athletes: delegation officers manage their own, organizers/admins
  manage all; meet-window enforcement; audited changes.
- Sport assignment for coaches (which sport(s) they handle) using catalog references.
- Registry pages with search and pagination on shared components.
- Tests: scoping, validation, sport assignment.

## Out of Scope
Technical officials/officiating assignment (Phase 3), DepEd HR integration,
accreditation, user accounts for coaches.

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
WP-02-08 — Event Entry Submission
