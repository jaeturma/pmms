# WP-02-08 — Correlation and Request Context Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-02-08 | Title | Correlation and Request Context Foundation |
| Epic | EPIC-02 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Small |
| Priority | P1 | Implementation sequence | 16 |
| Target release group | Foundation Release A | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | Cross-cutting |
| Secondary affected contexts | EPIC-06 (audit), EPIC-13 (observability) | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Lead architect |

## 2. Purpose

Defines a single correlation-ID mechanism generated per request/job and threaded through logs, audit records, and queued work — the shared primitive WP-06-04 (audit metadata) and WP-13-01/02 (structured logging) both build on, avoiding two different, incompatible correlation schemes emerging independently in EPIC-06 and EPIC-13.

## 3. Architecture Sources

[../../../../05-devops/](../../../../05-devops/), ADR-0008.

## 4. Scope

Document correlation-ID generation (proposed: UUID v4 per inbound request, propagated to queued jobs via job payload); document propagation into Laravel's log context (`Log::withContext`) and into any dispatched job/event.

## 5. Explicit Exclusions

Does not implement structured logging itself (WP-13-01); does not implement audit metadata recording (WP-06-04) — only the correlation-ID primitive both depend on.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-02-02 | Hard |

## 7. Current-State Inspection

No correlation-ID mechanism exists; Laravel's default logging (`config/logging.php`, `LOG_CHANNEL=stack`) has no request-ID context configured.

## 8. Proposed Implementation Direction

Proposed: a middleware generating/reading an `X-Correlation-Id` header, storing it in a request-scoped container binding, exposed via a small `CorrelationContext` service in the shared kernel.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Middleware-level convention; no domain logic.

## 11. Web Frontend Requirements

Not Applicable directly — WP-10-01 may echo the correlation ID back in error responses for support purposes.

## 12. Flutter Requirements

Not Applicable directly — WP-12-03 propagates the same header from mobile requests.

## 13. Authorization and Access Control

Not Applicable.

## 14. Security Requirements

Correlation ID must not itself be usable as an authentication or authorization token — it identifies a request trace, not a principal.

## 15. Privacy and Data-Governance Requirements

Correlation ID must not encode any personal data.

## 16. Audit and Activity Events

This is the shared primitive WP-06-04's audit metadata contract consumes.

## 17. Event, Queue, Notification, and Real-Time Requirements

Correlation ID propagates into queued job payloads (WP-09-04 integration point) so a job's logs can be traced back to its originating request.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

This is the shared primitive WP-13-01/WP-13-02's structured logging and correlation-identifier foundation consumes directly.

## 20. Testing Requirements

Unit test confirming a correlation ID is generated when absent and preserved when provided by the client.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record convention in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Document correlation-ID generation/propagation convention | WP-02-02 complete | Convention documented |
| TASK-02 | Document propagation into logs and queued jobs | TASK-01 | Documented |
| TASK-03 | Record in `.ai/architecture.md` | TASK-01..02 | Section added |

## 24. Acceptance Criteria

- **AC-01:** Given a request with no `X-Correlation-Id` header, when documented behavior is followed, then a new correlation ID is generated.
- **AC-02:** Given a request with an existing `X-Correlation-Id` header, when documented behavior is followed, then that ID is preserved, not replaced.
- **AC-03:** Given the convention, when reviewed, then it explicitly states the correlation ID carries no personal data and is not a security token.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-02-02 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Convention documented; unit-test plan defined for future implementation.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): the documented convention.

## 28. Rollback and Recovery Considerations

Not Applicable — documentation only.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-02-08-01 | EPIC-06 and EPIC-13 independently invent incompatible correlation schemes if this work package is skipped | Medium | This work package is a hard dependency of both WP-06-04 and WP-13-01/02 | Lead architect |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-02-08 — Correlation and Request Context Foundation.

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
- Correlation ID must not encode personal data or serve as an auth token.
- Do not implement structured logging or audit metadata recording — only the shared correlation primitive.
```
