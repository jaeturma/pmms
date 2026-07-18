# WP-15-06 — Foundation Queue and Real-Time Review

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-15-06 | Title | Foundation Queue and Real-Time Review |
| Epic | EPIC-15 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P2 | Implementation sequence | 149 |
| Target release group | Foundation Release F | Pilot relevance | Required |
| Architecture readiness classification | Verification Only | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Engineering lead, Architecture reviewer |

## 2. Purpose

Verifies the asynchronous and real-time foundation (EPIC-09) as integrated: queue conventions actually followed by every dispatching epic, idempotency and safe-payload rules held, failed-job governance workable in practice, scheduler reliable, notifications delivered through the intent model, and Reverb behavior (authorization, reconnection, state refresh) correct under realistic interruption — with MySQL remaining authoritative throughout (the ADR-0004/ADR-0012 rule RISK-EPIC09-01 guards).

## 3. Architecture Sources

[../../../10-review/workflow-event-notification-and-automation-review.md](../../../10-review/workflow-event-notification-and-automation-review.md) (method), ADR-0004, ADR-0011; reviews WP-09-01..12 output plus every dispatching consumer.

## 4. Scope

Convention sweep across all dispatched jobs/events foundation-wide (naming, routing, payload discipline — no sensitive payloads, IDs-not-objects rules); idempotency spot exercises (double-dispatch representative jobs, verify no duplicate effects); failed-job drill (fail a job, walk the WP-09-05 governance path end-to-end); scheduler verification over a realistic window (heartbeat continuity); notification-intent flow exercised (in-app + email dev baseline) including recipient resolution; Reverb drill: private-channel authorization denial, mid-session disconnection, reconnection with state refresh (WP-09-11) verified against missed-update correctness; Redis-authority check (nothing durable lives only in Redis); WP-09-12 suite re-run; review record for WP-15-12.

## 5. Explicit Exclusions

Does not fix queue/real-time defects (EPIC-09 owns them); does not load-test (WP-15-08 baselines; real load testing is deferred enterprise scope); does not add SMS/push (explicitly not approved for Phase 1).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-09-01 through WP-09-12 at Implementation Complete | Hard |
| All dispatching epics complete | Hard |

## 7. Current-State Inspection

Reviews real dispatch sites and real Reverb behavior under induced interruption — not configuration files alone.

## 8. Proposed Implementation Direction

Dispatch-site inventory sweep + scripted drills; findings register in evidence format.

## 9. Database Changes

Database Changes: None.

## 10. Backend Requirements

Review execution only.

## 11. Web Frontend Requirements

Reconnection/state-refresh behavior verified in the browser client.

## 12. Flutter Requirements

Not Applicable — mobile real-time is out of Phase 1 scope.

## 13. Authorization and Access Control

Private-channel authorization denial paths exercised (WP-09-10).

## 14. Security Requirements

Payload sweep confirms no sensitive data in job payloads or broadcast messages (IDs and minimal state only).

## 15. Privacy and Data-Governance Requirements

Broadcast-content review: nothing personal beyond what the channel's authorization already entitles.

## 16. Audit and Activity Events

Context propagation into jobs (WP-04-07) re-verified on real dispatch paths.

## 17. Event, Queue, Notification, and Real-Time Requirements

This work package **is** the queue/real-time review.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Queue/scheduler/Reverb health checks (WP-13-05/06) cross-verified against the drill states they should have detected.

## 20. Testing Requirements

WP-09-12 suite re-run; drill records; sweep outputs.

## 21. Test Data Requirements

Synthetic jobs/notifications; drills in disposable environments.

## 22. Documentation Updates

Review record to the sign-off evidence set.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Dispatch-site convention and payload sweep | EPIC-09 + consumers complete | Sweep record complete |
| TASK-02 | Idempotency and failed-job drills | TASK-01 | Drill records complete |
| TASK-03 | Scheduler window and notification-intent verification | TASK-01 | Records complete |
| TASK-04 | Reverb authorization/disconnection/reconnection drill | TASK-01 | Correctness verified |
| TASK-05 | Redis-authority check; re-run WP-09-12; produce record | TASK-02..04 | Record in evidence format |

## 24. Acceptance Criteria

- **AC-01:** Given every dispatch site, when swept, then conventions hold or findings are filed — including zero sensitive-payload violations.
- **AC-02:** Given the idempotency and failed-job drills, when executed, then double dispatch causes no duplicate effects and the governance path completes end-to-end.
- **AC-03:** Given the Reverb drill, when a client disconnects and reconnects, then state refresh yields correctness despite missed broadcasts.
- **AC-04:** Given the Redis-authority check, when complete, then no durable authoritative state exists only in Redis.

## 25. Definition of Ready

Per [../../phase-1-definition-of-ready.md](../../phase-1-definition-of-ready.md). EPIC-09 and dispatching epics at Implementation Complete.

## 26. Definition of Done

Per [../../phase-1-definition-of-done.md](../../phase-1-definition-of-done.md). All four criteria verified.

## 27. Completion Evidence

Per [../../phase-1-completion-evidence-standard.md](../../phase-1-completion-evidence-standard.md): sweep outputs, drill records, suite output, findings register.

## 28. Rollback and Recovery Considerations

Not Applicable — review in disposable environments.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-15-06-01 | Drills in quiet test conditions miss timing-dependent reconnection bugs | Medium | Reconnection drill includes broadcasts sent during disconnection; limitation documented in the record | Engineering lead |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-15-06 — Foundation Queue and Real-Time Review.

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
- Verification only — defects route to EPIC-09 work packages.
- Drills run in disposable environments — never against shared state.
- MySQL authority is the standard: durable state existing only in Redis is a finding.
```
