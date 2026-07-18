# WP-09-03 — Queue Naming and Routing Conventions

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-09-03 | Title | Queue Naming and Routing Conventions |
| Epic | EPIC-09 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Small |
| Priority | P1 | Implementation sequence | 79 |
| Target release group | Foundation Release D | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | Cross-cutting |
| Secondary affected contexts | All | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Lead architect |

## 2. Purpose

Defines named-queue conventions (e.g., `default`, `notifications`, `audit`) so high-priority work (future scoring/results, out of Phase 1) can eventually be isolated from lower-priority background work, per ADR-0012's "public traffic must not starve official scoring" principle, established structurally now even though no high-priority workload exists yet in Phase 1.

## 3. Architecture Sources

ADR-0012.

## 4. Scope

Document named-queue conventions and Horizon supervisor-to-queue mapping; configure `config/horizon.php` with the named queues even though only `default`/`notifications` are actually used in Phase 1.

## 5. Explicit Exclusions

Does not implement any queue prioritization logic beyond naming (actual priority tuning is evidence-gated, per ADR-0012).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-09-02 | Hard |

## 7. Current-State Inspection

WP-09-02's single `default` supervisor exists without named-queue segregation.

## 8. Proposed Implementation Direction

Update `config/horizon.php` to define `default` and `notifications` queues (the two Phase 1 needs); document the naming convention for future queues.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Configuration only.

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable.

## 14. Security Requirements

Not Applicable.

## 15. Privacy and Data-Governance Requirements

Not Applicable.

## 16. Audit and Activity Events

Not Applicable.

## 17. Event, Queue, Notification, and Real-Time Requirements

This work package defines which named queue every future job type should use.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable directly.

## 20. Testing Requirements

Integration test confirming a job dispatched to the `notifications` queue processes independently of `default`.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record the naming convention in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Configure `default`/`notifications` named queues in Horizon | WP-09-02 complete | Test confirms independent processing |

## 24. Acceptance Criteria

- **AC-01:** Given a job dispatched to `notifications`, when processed, then it does not require `default`'s workers to be available.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-09-02 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Criterion verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): test output.

## 28. Rollback and Recovery Considerations

Configuration change, reversible.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-09-03-01 | A future high-priority job type is dispatched to `default` instead of a dedicated queue, out of habit | Low | Naming convention documented explicitly for future reference | Lead architect |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-09-03 — Queue Naming and Routing Conventions.

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
- Do not implement priority-tuning logic beyond simple named-queue segregation.
```
