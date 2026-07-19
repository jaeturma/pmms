# WP-04-04 — Public Medal Tally

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
- Public medal tally page for a published meet, reusing `MedalTallyService`
  unchanged — derived at read time from validated results only, so it can never
  disagree with the internal tally and reacts to corrections automatically.
- School standings and district standings in conventional medal order, with the
  same shared-medal tie behavior; sport filter; "No medals yet" empty state.
- Public meet page and portal home link to the tally; the page states it counts
  validated results only.
- Tests: parity with the service (validated-only, ordering, ties), published-meet
  gating, guest access, sport filter.

## Out of Scope
Championship point formulas, cross-meet or historical standings, public CSV
downloads, any change to `MedalTallyService`.

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
WP-04-05 — Announcements
