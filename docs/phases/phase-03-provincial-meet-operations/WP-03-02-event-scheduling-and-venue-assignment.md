# WP-03-02 — Event Scheduling & Venue Assignment

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
- Schedule slots for a meet's events: date, start/end time, venue (FK), optional note;
  one event may have multiple slots (sessions/days). Manual scheduling only, per MVP.
- Slots only for events attached to the meet; scheduling allowed while the meet is
  registration-closed or active; changes audited (`schedule.*`).
- Same-venue overlap conflict is blocked server-side with a clear validation message.
- Schedule views: per-day and per-venue listing on shared components, readable by all
  roles (schedule is non-sensitive); manager-only editing.
- Venue delete guard from WP-03-01 activated by the new FK.
- Tests: slot validation (event-in-meet, overlap, meet status), authorization, views.

## Out of Scope
Automated scheduling/optimization, seeding, brackets/heats (WP-03-04), printable
schedule sheets (WP-03-08).

## Deliverables
- Updated source code
- Updated documentation (docs/scheduling.md)
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
WP-03-03 — Accreditation & ID Printing
