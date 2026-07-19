# WP-04-01 — Public Portal Foundation & Publication Controls

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
- `is_published` flag on meets (migration + model) with manager-only
  publish/unpublish endpoints, audited (`meet.published` / `meet.unpublished`);
  draft meets cannot be published. Publish/Unpublish controls on the internal
  meets page.
- Public layout (no auth, no app sidebar): lightweight header with PMMS identity
  and portal navigation, footer, mobile-first. Internal app layout untouched.
- Portal home at `/` (replacing the starter welcome page) listing published meets
  with name, school year, dates, status, and links to their (upcoming) public
  pages; a clear empty state when nothing is published; a "Sign in" link to the
  internal app.
- Guest routing baseline for all portal routes: no auth middleware, named
  `public.*`, throttled sensibly; unpublished meets 404 on any public route
  (shared enforcement helper the later WPs reuse).
- Privacy baseline documented in `docs/public-portal.md` (what may ever appear
  publicly, per product scope §9 and DESIGN-NOTES).
- Tests: guest access to the portal home, published/unpublished visibility,
  draft-meet publish refusal, publish/unpublish authorization (managers only,
  matrix rows) and audit records.

## Out of Scope
Schedule/results/tally/announcement pages (WP-04-02..05), SEO/meta polish
(WP-04-06), any change to internal authorization.

## Deliverables
- Updated source code
- Updated documentation (docs/public-portal.md, docs/meets.md note)
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
WP-04-02 — Public Schedule & Venue Guide
