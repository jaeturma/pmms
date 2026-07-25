# WP-05-03 — Operations Progress & Risk

## Purpose
Give Admin/Organizer a cross-meet view of encoding/validation throughput and
simple risk flags — the "is anything stuck" question Phase 3's single-meet
queues can't answer across the whole program.

## Tasks
- Inspect the repository before making changes.
- Implement only the scope of this work package.
- Preserve architecture and coding standards.
- Update documentation where necessary.
- Run backend and frontend quality checks.
- Do not commit or push changes.

## Scope
- Per meet: results encoded vs validated counts (`ResultStatus`), eligibility
  review counts by status, protest counts by status (`ProtestStatus`),
  incident counts by status (`IncidentStatus`) — reusing existing enums, not
  recomputing new state.
- One simple, explicit risk flag: a meet is Active with encoded (not yet
  validated) results older than a fixed threshold (e.g. configurable, default
  reasonable) — no predictive/AI scoring, just a plain age check against
  existing timestamps.
- Table on the management page, linking each row into its owning module
  (results/eligibility/protests/incidents), consistent with how Phase 3's
  operations queues already link out.
- Tests: counts correct per meet, risk flag triggers/doesn't trigger
  correctly at the threshold boundary, authorization unchanged.

## Out of Scope
Participation trends (WP-05-02, already done), medal/performance history
(WP-05-04), venue data (WP-05-05), export (WP-05-06). No new incident/protest
severity model — reuse what exists.

## Deliverables
- Updated source code
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
WP-05-04 — Delegation & School Performance History
