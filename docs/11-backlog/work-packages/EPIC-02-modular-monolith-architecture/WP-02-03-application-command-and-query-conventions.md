# WP-02-03 — Application Command and Query Conventions

## 1. Work Package Identity

| Field | Value |
|---|---|
| Work Package ID | WP-02-03 |
| Title | Application Command and Query Conventions |
| Epic | EPIC-02 — Modular Monolith and Application Architecture Foundation |
| Phase | Phase 1 | Status | Planned — Not Started |
| Complexity | Medium | Priority | P1 |
| Implementation sequence | 11 | Target release group | Foundation Release A |
| Pilot relevance | Required | Architecture readiness classification | Ready for Implementation |
| Primary bounded context | Cross-cutting | Secondary affected contexts | All |
| Product owner | To be identified | Technical owner | To be identified |
| Required reviewers | Lead architect | | |

## 2. Purpose

Defines a consistent command (write) and query (read) handler convention so every bounded context's application layer looks the same, avoiding a mix of "fat controller," "fat model," and ad hoc service patterns across different epics.

## 3. Architecture Sources

[../../../../01-architecture/phase-0.4-application-integration-and-runtime-architecture.md](../../../../01-architecture/phase-0.4-application-integration-and-runtime-architecture.md).

## 4. Scope

Document a Command/CommandHandler and Query/QueryHandler pattern (proposed, framework-light — plain PHP classes, dispatched via a thin Laravel service, not a heavyweight CQRS bus package); document where validation and authorization checks occur relative to the handler.

## 5. Explicit Exclusions

Does not implement a message-bus package; does not implement any concrete command/query for a real capability (those belong to their owning epic).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-02-02 | Hard |

## 7. Current-State Inspection

No application-layer convention exists; starter kit uses direct controller-to-model interaction (Fortify actions pattern under `app/Actions/Fortify/` is the closest existing precedent).

## 8. Proposed Implementation Direction

Proposed: `App\Application\<Context>\Commands\<Name>Command` + `<Name>CommandHandler`, `App\Application\<Context>\Queries\<Name>Query` + `<Name>QueryHandler`, invoked directly from controllers (no bus package) — mirrors the existing `app/Actions/Fortify` pattern already in the codebase, extended per-context.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Convention only; handlers depend on repository interfaces (WP-02-05), not Eloquent directly.

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Convention documents that authorization checks occur before handler invocation (in the controller or a policy), not inside the handler itself.

## 14. Security Requirements

Not Applicable beyond the authorization-placement convention above.

## 15. Privacy and Data-Governance Requirements

Not Applicable.

## 16. Audit and Activity Events

Convention documents that commands are a natural place to trigger audit recording (WP-06-06 integration point), once that service exists.

## 17. Event, Queue, Notification, and Real-Time Requirements

Convention documents that command handlers may dispatch domain events (WP-02-04) after a successful write.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Convention documents that every command/query carries a correlation ID (WP-02-08 integration point).

## 20. Testing Requirements

Not Applicable — no concrete command/query yet; pattern is validated once EPIC-04/05 use it.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record convention in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Document Command/Query/Handler convention, mirroring existing Fortify Actions pattern | WP-02-02 complete | Convention documented |
| TASK-02 | Document authorization-placement and audit/event integration points | TASK-01 | Documented |
| TASK-03 | Record in `.ai/architecture.md` | TASK-01..02 | Section added |

## 24. Acceptance Criteria

- **AC-01:** Given the convention, when compared to `app/Actions/Fortify`, then it is a natural extension, not a conflicting pattern.
- **AC-02:** Given the convention, when reviewed, then it states explicitly where authorization checks occur.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-02-02 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Convention documented and consistent with the shared kernel.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): the documented convention.

## 28. Rollback and Recovery Considerations

Not Applicable — documentation only.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-02-03-01 | Handlers accumulate business logic that belongs in the domain layer ("anemic domain" drift) | Medium | WP-15-01 reviews for drift | Lead architect |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-02-03 — Application Command and Query Conventions.

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
- Do not implement a message-bus package.
- Do not implement a concrete command/query for a real capability.
```
