# WP-09-04 — Job Idempotency and Safe Payload Conventions

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-09-04 | Title | Job Idempotency and Safe Payload Conventions |
| Epic | EPIC-09 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 80 |
| Target release group | Foundation Release D | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | Cross-cutting |
| Secondary affected contexts | WP-04-07 | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Lead architect |

## 2. Purpose

Establishes the convention that every job must be safely re-runnable (idempotent) — since queue processing can redeliver a job after a worker crash — and that job payloads carry IDs, not full serialized objects, per Laravel best practice and WP-02-06's discipline against passing stale state.

## 3. Architecture Sources

ADR-0004, ADR-0011.

## 4. Scope

Document the idempotency convention (a job must check current state before acting, not assume its payload reflects current reality); document the ID-only payload convention (never serialize a full Eloquent model into a job payload).

## 5. Explicit Exclusions

Does not implement any concrete job (that's each future job's own responsibility); does not implement a deduplication mechanism beyond the idempotency convention itself.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-09-02, WP-04-07 | Hard |

## 7. Current-State Inspection

No job classes exist yet in the repository beyond framework defaults.

## 8. Proposed Implementation Direction

Documentation and a proposed `IdempotentJob` base class/trait providing a helper for idempotency-key checking (proposed, using a Redis-backed short-lived lock).

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Convention and one reusable trait; no concrete job yet.

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable directly — WP-04-07 already established that jobs re-validate authority at execution time, complementing this idempotency convention.

## 14. Security Requirements

Idempotency keys must not be guessable/collidable across tenants (incorporate organization/meet context per WP-04-08).

## 15. Privacy and Data-Governance Requirements

Payloads carrying only IDs (not full objects) inherently minimize data exposure risk in queue/Redis storage, itself a privacy benefit.

## 16. Audit and Activity Events

Not Applicable directly.

## 17. Event, Queue, Notification, and Real-Time Requirements

This work package is a core convention for all future queue work.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable directly.

## 20. Testing Requirements

Unit test confirming the `IdempotentJob` trait correctly prevents double-processing when the same idempotency key is presented twice.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record both conventions in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Document ID-only payload convention | WP-09-02, WP-04-07 complete | Documented |
| TASK-02 | Implement `IdempotentJob` trait with Redis-backed lock | TASK-01 | Unit test passes |

## 24. Acceptance Criteria

- **AC-01:** Given the same idempotency key presented twice in quick succession, when processed via `IdempotentJob`, then the second attempt is a no-op.
- **AC-02:** Given the payload convention, when documented, then it explicitly prohibits serializing full Eloquent models into job payloads.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-09-02, WP-04-07 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): test output.

## 28. Rollback and Recovery Considerations

Not Applicable — new convention, no prior behavior.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-09-04-01 | A future job forgets to use the idempotency convention, causing a double-processing bug under queue redelivery | Medium | WP-15-06 queue/real-time review checks every job class against this convention | Lead architect |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-09-04 — Job Idempotency and Safe Payload Conventions.

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
- Job payloads must carry IDs only, never serialized full model objects.
```
