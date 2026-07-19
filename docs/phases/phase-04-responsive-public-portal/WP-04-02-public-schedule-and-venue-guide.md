# WP-04-02 — Public Schedule & Venue Guide

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
- Public meet page (`/meets/{meet}` public route, published meets only — 404
  otherwise) showing the meet header and its schedule grouped per day, then per
  venue, with time range, event label, and note — reusing the day/venue grouping
  proven by the daily schedule sheet, built as public-safe props.
- Day selector (defaults to today during the meet, first day otherwise);
  empty/unavailable states when no slots exist or the schedule is not yet set.
- Venue guide section: venue names and addresses of the meet's scheduled venues
  only (no notes — internal field), so visitors can find the grounds.
- Portal home links each published meet to this page.
- Tests: published-only access, day grouping and ordering, venue list correctness
  (no internal venue notes in props), guest access, empty states.

## Out of Scope
Results and tally (WP-04-03/04), maps or geolocation, per-venue detail pages,
internal schedule module changes.

## Deliverables
- Updated source code
- Updated documentation (docs/public-portal.md additions)
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
WP-04-03 — Public Results
