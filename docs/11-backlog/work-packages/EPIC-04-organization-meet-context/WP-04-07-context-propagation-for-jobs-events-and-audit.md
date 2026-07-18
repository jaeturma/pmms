# WP-04-07 — Context Propagation for Jobs, Events, and Audit

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-04-07 | Title | Context Propagation for Jobs, Events, and Audit |
| Epic | EPIC-04 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 31 |
| Target release group | Foundation Release B | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | Cross-cutting |
| Secondary affected contexts | EPIC-06, EPIC-09 | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Extends WP-04-05's context resolution into asynchronous execution (queued jobs, dispatched events), since a job running outside the original HTTP request lifecycle would otherwise lose organization/meet context entirely — a common source of tenant-isolation bugs in Laravel applications.

## 3. Architecture Sources

ADR-0012 (context propagation across queues/events).

## 4. Scope

Serialize the resolved context (organization/meet ID) into every job payload dispatched from a context-bearing request; restore it when the job executes on the worker.

## 5. Explicit Exclusions

Does not implement the queue infrastructure itself (WP-09-02); this work package only defines the context-carrying convention jobs must follow once the queue exists.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-04-05 | Hard |
| WP-09-02 (queue baseline) | Soft — convention defined here, exercised once queue exists |

## 7. Current-State Inspection

No job/event context-propagation code exists.

## 8. Proposed Implementation Direction

Proposed: a `HasContext` trait (or a base `ContextAwareJob` class) that jobs extend/use, serializing organization/meet ID into the job payload and restoring `CurrentContext` in the job's `handle()` via a shared trait.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Convention/trait implementation; every future job touching organization/meet-scoped data must use it (enforced by code review, not automated in Phase 1).

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Restored context must be re-validated at job-execution time (e.g., access may have been revoked between dispatch and execution) — fail closed if no longer valid.

## 14. Security Requirements

Job payload must carry only the context ID, never a full trust assertion — the worker re-derives authority, it does not blindly trust the payload's claim.

## 15. Privacy and Data-Governance Requirements

Not Applicable directly.

## 16. Audit and Activity Events

This is the mechanism WP-06-04's "actor, effective user, device, context, and correlation metadata" contract depends on for jobs specifically (as opposed to HTTP requests).

## 17. Event, Queue, Notification, and Real-Time Requirements

This work package is itself part of EPIC-09's queue foundation, defining the context-carrying convention before real jobs are dispatched.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Restored context is included in job-execution logs (WP-13-01 integration point).

## 20. Testing Requirements

Unit test: a dispatched job carries context in its payload; a job whose context access was revoked between dispatch and execution fails closed rather than executing with stale authority.

## 21. Test Data Requirements

Reuses existing factories. Do not create new test data.

## 22. Documentation Updates

Record the job-context convention in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement `HasContext`/`ContextAwareJob` convention | WP-04-05 complete | Unit tests pass |
| TASK-02 | Implement re-validation-at-execution-time guard | TASK-01 | Revoked-access-during-flight scenario tested |

## 24. Acceptance Criteria

- **AC-01:** Given a job dispatched with organization/meet context, when it executes, then `CurrentContext` is correctly restored inside `handle()`.
- **AC-02:** Given a job whose originating user's meet access was revoked after dispatch but before execution, when the job runs, then it fails closed rather than proceeding with stale authority.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-04-05 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): test output for both scenarios.

## 28. Rollback and Recovery Considerations

Not Applicable — new convention, no prior behavior to preserve.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-04-07-01 | A future job implementation forgets to use the context convention, silently losing tenant scoping | High | WP-15-01/WP-15-03 review checks every job class against this convention | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-04-07 — Context Propagation for Jobs, Events, and Audit.

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
- Jobs must re-validate context authority at execution time, never blindly trust the dispatch-time payload.
```
