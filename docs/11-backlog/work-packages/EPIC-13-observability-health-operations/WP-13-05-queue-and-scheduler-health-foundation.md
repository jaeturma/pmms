# WP-13-05 — Queue and Scheduler Health Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-13-05 | Title | Queue and Scheduler Health Foundation |
| Epic | EPIC-13 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Small |
| Priority | P1 | Implementation sequence | 128 |
| Target release group | Foundation Release E | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Engineering lead |

## 2. Purpose

Registers queue and scheduler health checks into the WP-13-04 framework, using WP-09-06's scheduler heartbeat: answers "are workers processing?" and "did the scheduler run recently?" — the two silent-failure modes ADR-0008 and ADR-0011 flag as most operationally dangerous for meet-day operations.

## 3. Architecture Sources

[../../../05-devops/](../../../05-devops/), [../../../08-workflows/queue-routing-priority-retry-and-failure-architecture.md](../../../08-workflows/queue-routing-priority-retry-and-failure-architecture.md), ADR-0008, ADR-0011.

## 4. Scope

Queue check: Horizon/worker activity status and failed-job count surfaced as a health dimension (proposed: degraded above a documented failed-job threshold); scheduler check: verify the WP-09-06 heartbeat ran within its expected interval; both registered via the WP-13-04 contract.

## 5. Explicit Exclusions

Does not implement failed-job replay or governance (WP-09-05 owns that); does not modify Horizon configuration (WP-09-02); does not implement alerting; does not set final numeric thresholds as policy — values are documented, provisional, and adjustable.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-13-04 | Hard |
| WP-09-02 | Hard |
| WP-09-06 | Hard (scheduler heartbeat consumed) |

## 7. Current-State Inspection

By this point WP-09-02 provides Horizon and WP-09-06 the scheduler heartbeat; no health checks reference them yet.

## 8. Proposed Implementation Direction

Two `HealthCheck` implementations (proposed): `QueueHealthCheck` reading Horizon status/failed-job count, `SchedulerHealthCheck` reading the WP-09-06 heartbeat timestamp from cache.

## 9. Database Changes

Database Changes: None (reads failed-job count only).

## 10. Backend Requirements

Two check implementations, registered in the WP-13-04 registry, with documented provisional thresholds.

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Inherited from WP-13-04's restricted readiness endpoint.

## 14. Security Requirements

Check output reports counts and statuses only — no job payloads, queue names beyond convention names, or connection detail.

## 15. Privacy and Data-Governance Requirements

Not Applicable — no payload content in check output.

## 16. Audit and Activity Events

Not Applicable.

## 17. Event, Queue, Notification, and Real-Time Requirements

This work package observes the queue infrastructure; it must not dispatch, mutate, or replay jobs.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Closes the two silent-failure blind spots: stalled workers and a stopped scheduler.

## 20. Testing Requirements

Test: scheduler check degraded when heartbeat is stale (simulated); queue check degraded above the provisional failed-job threshold (simulated); healthy path for both.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record both checks and their provisional thresholds in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement and register queue health check | WP-13-04, WP-09-02 complete | Simulated-failure test passes |
| TASK-02 | Implement and register scheduler heartbeat check | WP-13-04, WP-09-06 complete | Stale-heartbeat test passes |

## 24. Acceptance Criteria

- **AC-01:** Given a stale scheduler heartbeat, when readiness is requested, then the scheduler check reports degraded/down.
- **AC-02:** Given a failed-job count above the provisional threshold, when readiness is requested, then the queue check reports degraded.
- **AC-03:** Given healthy queue and scheduler, when readiness is requested, then both checks report healthy with no payload or connection detail.

## 25. Definition of Ready

Per [../../phase-1-definition-of-ready.md](../../phase-1-definition-of-ready.md). WP-13-04, WP-09-02, WP-09-06 complete.

## 26. Definition of Done

Per [../../phase-1-definition-of-done.md](../../phase-1-definition-of-done.md). All three criteria verified.

## 27. Completion Evidence

Per [../../phase-1-completion-evidence-standard.md](../../phase-1-completion-evidence-standard.md): readiness output including both checks, simulated-failure test output.

## 28. Rollback and Recovery Considerations

Additive checks; deregistration restores prior state.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-13-05-01 | Provisional thresholds treated as validated operational policy | Medium | Thresholds documented as provisional; revisited in WP-15-06 review | Engineering lead |

## 30. Open Decisions

| Decision ID | Question | Blocking Status |
|---|---|---|
| DEC-WP-13-05-01 | Final failed-job and heartbeat-staleness thresholds | Non-blocking — provisional values documented |

## 31. Future Claude Code Execution Prompt

```text
Implement WP-13-05 — Queue and Scheduler Health Foundation.

Read the complete work-package document first.

Inspect the current repository before making changes.

Implement only the approved scope.

Do not implement excluded or deferred features.

Follow all linked architecture, security, privacy, testing, design, workflow, and operational rules.

Run the required tests and quality checks.

Update the required documentation and AI workspace files.

Do not commit unless explicitly instructed.

At completion, provide:
1. Repository findings
2. Files created
3. Files modified
4. Implementation summary
5. Database changes
6. Backend changes
7. Frontend changes
8. Flutter changes
9. Authorization and audit changes
10. Tests and quality checks
11. Risks and limitations
12. Git status

Additional restrictions specific to this work package:
- Observe only — never dispatch, mutate, or replay jobs from a health check.
- No job payload content in check output.
- Thresholds are provisional and documented as such.
```
