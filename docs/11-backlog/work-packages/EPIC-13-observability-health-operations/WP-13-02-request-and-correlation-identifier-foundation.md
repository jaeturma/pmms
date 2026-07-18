# WP-13-02 — Request and Correlation Identifier Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-13-02 | Title | Request and Correlation Identifier Foundation |
| Epic | EPIC-13 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Small |
| Priority | P1 | Implementation sequence | 125 |
| Target release group | Foundation Release E | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Engineering lead |

## 2. Purpose

Makes WP-02-08's request-context correlation identifier visible end-to-end: attached to every structured log record (WP-13-01), propagated into queued jobs, returned in error responses, and available to the audit metadata layer (WP-06-04) — so a single identifier connects a user-reported error to its logs, jobs, and audit trail.

## 3. Architecture Sources

[../../../01-architecture/observability-and-error-handling.md](../../../01-architecture/observability-and-error-handling.md), ADR-0008; builds directly on WP-02-08 (correlation and request context foundation).

## 4. Scope

Attach the WP-02-08 correlation ID to every structured log record via the WP-13-01 processor; propagate the correlation ID through queued job payload metadata so worker-side logs carry the originating request's ID; include the correlation ID in error responses (via the WP-10-01 error contract's error-reference field); document the identifier's format and lifetime.

## 5. Explicit Exclusions

Does not implement distributed tracing (spans, trace propagation to external services) — a future operational decision; does not re-implement WP-02-08's context container — it consumes it; does not modify the audit metadata model (WP-06-04 already consumes the same context).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-02-08 | Hard |
| WP-13-01 | Hard |

## 7. Current-State Inspection

No correlation identifier exists in the starter kit; WP-02-08 will have introduced the context container and WP-13-01 the structured channel by the time this work package starts.

## 8. Proposed Implementation Direction

Extend the WP-13-01 Monolog processor to read the correlation ID from WP-02-08's context; job middleware (proposed) restoring correlation context on the worker side; error-response integration through the WP-10-01 contract's existing error-reference field.

## 9. Database Changes

Database Changes: None.

## 10. Backend Requirements

Processor extension, queue-payload metadata propagation, job middleware restoring context, error-response reference wiring.

## 11. Web Frontend Requirements

Not Applicable beyond the error contract already defined in WP-10-01 — the frontend displays the error reference it already receives.

## 12. Flutter Requirements

Not Applicable in this work package — mobile clients receive the same error-reference field through the API contract.

## 13. Authorization and Access Control

Not Applicable.

## 14. Security Requirements

Correlation IDs must be random/opaque (no user ID, session ID, or timestamp-derivable content) so exposing them in error responses leaks nothing.

## 15. Privacy and Data-Governance Requirements

Not Applicable beyond the opaqueness requirement above.

## 16. Audit and Activity Events

The audit metadata layer (WP-06-04) reads the same correlation context — this work package must not introduce a second, divergent identifier.

## 17. Event, Queue, Notification, and Real-Time Requirements

Queued jobs carry the originating correlation ID in payload metadata and restore it into worker context before the job handler runs.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

After this work package, one identifier connects request logs, job logs, error responses, and audit records.

## 20. Testing Requirements

Test asserting an HTTP request's log records and a job it dispatched share the same correlation ID; test asserting the error response's reference matches the logged correlation ID; test asserting ID opaqueness (format check).

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record the correlation identifier format, propagation rules, and lookup procedure in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Attach correlation ID to all structured log records | WP-02-08, WP-13-01 complete | ID present on every record |
| TASK-02 | Propagate correlation ID into queued jobs | TASK-01 | Job logs share originating ID |
| TASK-03 | Wire correlation ID into error-response reference | TASK-01 | Response reference matches logs |

## 24. Acceptance Criteria

- **AC-01:** Given a request that logs and dispatches a job, when both are processed, then all resulting log records share one correlation ID.
- **AC-02:** Given a request that fails with a handled error, when the error response is returned, then its error reference equals the correlation ID in the corresponding log records.
- **AC-03:** Given any correlation ID, when inspected, then it is opaque — not derivable from user, session, or time data.

## 25. Definition of Ready

Per [../../phase-1-definition-of-ready.md](../../phase-1-definition-of-ready.md). WP-02-08 and WP-13-01 complete.

## 26. Definition of Done

Per [../../phase-1-definition-of-done.md](../../phase-1-definition-of-done.md). All three criteria verified.

## 27. Completion Evidence

Per [../../phase-1-completion-evidence-standard.md](../../phase-1-completion-evidence-standard.md): correlated log excerpt (request + job + error response), test output.

## 28. Rollback and Recovery Considerations

Additive middleware/processor change; removal restores prior behavior. No data migration.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-13-02-01 | A second, divergent correlation mechanism appears in a later epic (e.g., a job-only ID), splitting traceability | Medium | Conventions name WP-02-08's context as the single source; WP-15-01 consistency review re-checks | Engineering lead |

## 30. Open Decisions

| Decision ID | Question | Blocking Status |
|---|---|---|
| DEC-WP-13-02-01 | Distributed tracing (spans/trace propagation) adoption | Non-blocking — out of Phase 1 scope |

## 31. Future Claude Code Execution Prompt

```text
Implement WP-13-02 — Request and Correlation Identifier Foundation.

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
- Correlation IDs must be opaque and random — never derived from user, session, or time data.
- Consume WP-02-08's context container — do not introduce a second correlation mechanism.
- Do not implement distributed tracing.
```
