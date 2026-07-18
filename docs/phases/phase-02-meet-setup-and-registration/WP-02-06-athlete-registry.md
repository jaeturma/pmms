# WP-02-06 — Athlete Registry

## Purpose
Complete this work package for the PMMS Division Edition. Keep implementation practical
for a Schools Division Office. Build only what is required for a maintainable
production-quality system. Athletes are minors — collect minimal data and protect it.

## Tasks
- Inspect the repository before making changes.
- Implement only the scope of this work package.
- Preserve architecture and coding standards.
- Update documentation where necessary.
- Run backend and frontend quality checks.
- Do not commit or push changes.

## Scope
- `athletes` table: delegation, name, sex, birthdate, LRN (learner reference number),
  grade level, optional photo via the existing `FileUploadService`. Minimal fields only —
  no medical, no address, no guardian data in this phase.
- Age computed from birthdate against the meet's age divisions where needed for entries.
- Delegation officers register athletes for their own delegation while the meet is open;
  organizers/admins manage all. Policies enforce scoping.
- Every create/update/delete and every athlete-detail view audited (minor data).
- Registry pages with search and pagination on the shared table component.
- Tests: officer scoping, validation (birthdate sanity, required fields), audit of views,
  photo upload path.

## Out of Scope
Eligibility documents (WP-02-09), medical data, guardian records, cross-meet athlete
history, duplicate-identity resolution.

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
WP-02-07 — Coach & Official Registry
