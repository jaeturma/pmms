# WP-04-05 — Trusted Context Resolution

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-04-05 | Title | Trusted Context Resolution |
| Epic | EPIC-04 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 29 |
| Target release group | Foundation Release B | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | Cross-cutting |
| Secondary affected contexts | All tenant-scoped contexts | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Implements the server-side service that resolves "which organization/meet is the current request operating in" from trusted sources only (session, route parameter validated against WP-04-03/04-04, never raw unvalidated client input) — the single highest-priority security control in this epic, directly implementing ADR-0012's "tenant identifiers never trusted solely from client input" rule.

## 3. Architecture Sources

[../../../../09-enterprise/tenant-data-ownership-and-isolation-architecture.md](../../../../09-enterprise/tenant-data-ownership-and-isolation-architecture.md), ADR-0012.

## 4. Scope

Implement a `CurrentContext` service (proposed) resolving organization/meet ID from the authenticated user's active membership/access records; fail-safe behavior: if context cannot be resolved, deny rather than default to an unfiltered query.

## 5. Explicit Exclusions

Does not implement HTTP/Inertia propagation (WP-04-06) or job/event propagation (WP-04-07) — only the resolution service itself.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-04-03, WP-04-04 | Hard |

## 7. Current-State Inspection

No context-resolution code exists.

## 8. Proposed Implementation Direction

Proposed `App\SharedKernel\Context\CurrentContext` service, resolved once per request, bound in Laravel's service container as request-scoped (`scoped()` binding).

## 9. Database Changes

Database Changes: None (reads WP-04-03/04-04's tables).

## 10. Backend Requirements

Resolution logic queries `organization_memberships` and `user_meet_access`; throws a specific `ContextResolutionException` (per WP-02-07) if no valid context exists, never silently returns an empty/wildcard scope.

## 11. Web Frontend Requirements

Not Applicable in this work package.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

This service is a prerequisite input to EPIC-05's Authorization Decision Service, not itself an authorization decision.

## 14. Security Requirements

Fail-closed: any resolution failure must deny, never default to an unfiltered or first-available context — this is the direct implementation of RISK-GENERAL-10's mitigation.

## 15. Privacy and Data-Governance Requirements

Not Applicable directly.

## 16. Audit and Activity Events

Context-resolution failures are Security-Event-worthy once WP-06-03 exists (repeated failures could indicate a probing attempt).

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable in this work package.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Every resolution failure is logged with the correlation ID (WP-02-08).

## 20. Testing Requirements

Unit tests: valid membership resolves correctly; no membership fails closed; revoked access fails closed; ambiguous multi-organization user requires explicit context (not silently picks one).

## 21. Test Data Requirements

Reuses `OrganizationMembershipFactory`, `UserMeetAccessFactory`. Do not create new test data.

## 22. Documentation Updates

Record `CurrentContext` service design in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement `CurrentContext` resolution service with fail-closed behavior | WP-04-03, WP-04-04 complete | Unit tests pass |
| TASK-02 | Implement `ContextResolutionException` and failure logging | TASK-01 | Failure path tested |

## 24. Acceptance Criteria

- **AC-01:** Given a user with no organization membership, when context resolution is attempted, then it fails closed (denies), never defaults to an unfiltered scope.
- **AC-02:** Given a user with revoked meet access, when context resolution is attempted for that meet, then it fails closed.
- **AC-03:** Given a user with membership in multiple organizations and no explicit selection, when context resolution is attempted, then it requires an explicit choice rather than silently picking one.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-04-03, WP-04-04 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). All three acceptance criteria verified by test.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): test output covering all three fail-closed scenarios.

## 28. Rollback and Recovery Considerations

Not Applicable — new service, no existing behavior to preserve.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-04-05-01 | A future edge case (e.g., concurrent membership changes mid-request) causes fail-open behavior | Critical | WP-04-09 cross-context isolation tests specifically probe this | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-04-05 — Trusted Context Resolution.

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
- Context resolution must fail closed in every failure scenario — never default to an unfiltered or guessed context.
- Never trust a client-supplied organization/meet ID without validating it against the user's actual membership/access records.
```
