# WP-02-02 — Shared Kernel and Common Contracts Foundation

## 1. Work Package Identity

| Field | Value |
|---|---|
| Work Package ID | WP-02-02 |
| Title | Shared Kernel and Common Contracts Foundation |
| Epic | EPIC-02 — Modular Monolith and Application Architecture Foundation |
| Phase | Phase 1 |
| Status | Planned — Not Started |
| Complexity | Medium |
| Priority | P1 |
| Implementation sequence | 10 |
| Target release group | Foundation Release A |
| Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation |
| Primary bounded context | Cross-cutting (shared kernel) |
| Secondary affected contexts | All |
| Product owner | To be identified |
| Technical owner | To be identified |
| Required reviewers | Lead architect |

## 2. Purpose

Defines the small set of framework-agnostic value objects and interfaces every bounded context is allowed to depend on (e.g., an ID type, a Money/DateRange value object if needed, a `Result`/outcome type) — the "shared kernel" per DDD terminology already used in Phase 0.2 — so that contexts share vocabulary without coupling to each other's internals.

## 3. Architecture Sources

[../../../../01-architecture/phase-0.2-domain-architecture.md](../../../../01-architecture/phase-0.2-domain-architecture.md).

## 4. Scope

Identify and document the minimal shared-kernel contract set (proposed: an ID value-object base, a domain-event marker interface, a Result/outcome type); place under the WP-02-01 namespace convention's shared location (proposed: `App\SharedKernel\...`).

## 5. Explicit Exclusions

Does not define any context-specific value object (e.g., no `MeetId`, no `OrganizationId` concrete class — only the base/interface); does not implement command/query base classes (WP-02-03) or event contracts (WP-02-04) beyond the shared marker interface.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-02-01 | Hard |

## 7. Current-State Inspection

No shared-kernel code exists in the repository; `app/Models/User.php` is the only domain-adjacent class present.

## 8. Proposed Implementation Direction

Proposed namespace `App\SharedKernel\`; core objects: an abstract typed-ID base class, a `DomainEvent` marker interface, a `Result`/outcome type for operations that can fail without throwing. Extension point: contexts extend the ID base per their own entity.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Domain requirement: shared-kernel classes must have zero dependency on Eloquent, Laravel facades, or any specific bounded context — pure PHP only.

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

The `DomainEvent` marker interface here is the shared contract WP-02-04 builds concrete conventions on top of.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Unit tests for the ID base class and Result type (construction, equality, immutability) once implemented.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record shared-kernel contract list in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Expected Files/Areas | Preconditions | Verification | Notes |
|---|---|---|---|---|---|
| TASK-01 | Define shared-kernel contract list (ID base, DomainEvent interface, Result type) | Documentation | WP-02-01 complete | List documented | Proposed names |
| TASK-02 | Confirm zero-Eloquent/zero-facade constraint is stated explicitly | Documentation | TASK-01 | Constraint documented | — |
| TASK-03 | Record in `.ai/architecture.md` | `.ai/architecture.md` | TASK-01..02 | Section added | — |

## 24. Acceptance Criteria

- **AC-01:** Given the shared-kernel contract list, when reviewed, then it contains no context-specific type.
- **AC-02:** Given the documented constraint, when checked, then it explicitly states shared-kernel code has zero Eloquent/facade dependency.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-02-01 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Contract list documented and consistent with WP-02-01's namespace convention.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): the documented contract list.

## 28. Rollback and Recovery Considerations

Not Applicable — documentation-only at this stage.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Detection | Escalation | Residual Concern |
|---|---|---|---|---|---|---|
| RISK-WP-02-02-01 | Shared kernel grows to include context-specific logic over time ("kernel bloat") | Medium | Explicit exclusion in Section 5; WP-15-01 reviews for drift | Architecture consistency review | Lead architect | Ongoing discipline required |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-02-02 — Shared Kernel and Common Contracts Foundation.

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
- Shared-kernel code must have zero Eloquent or Laravel-facade dependency.
- Do not add any context-specific type to the shared kernel.
```
