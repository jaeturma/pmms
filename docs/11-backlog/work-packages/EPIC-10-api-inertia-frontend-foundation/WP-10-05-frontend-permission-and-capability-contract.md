# WP-10-05 — Frontend Permission and Capability Contract

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-10-05 | Title | Frontend Permission and Capability Contract |
| Epic | EPIC-10 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 93 |
| Target release group | Foundation Release D | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | Cross-cutting |
| Secondary affected contexts | EPIC-05, EPIC-11 | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Shares a "capabilities" prop (a set of permission flags for the current context, computed server-side via WP-05-07) so the frontend can conditionally render UI elements — explicitly for usability only, since the backend independently re-checks every action per WP-05-07's own discipline, never trusting this prop as authoritative.

## 3. Architecture Sources

ADR-0003.

## 4. Scope

Implement a `capabilities` shared prop (proposed: a flat object of permission-name-to-boolean, computed for the current context) added to WP-10-03's shared-props contract; document explicitly that it is advisory only.

## 5. Explicit Exclusions

Does not compute capabilities for every permission in the system (only the ones relevant to currently-rendered pages, to avoid over-fetching); does not use this prop as a substitute for backend authorization anywhere.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-10-03, WP-05-07 | Hard |

## 7. Current-State Inspection

WP-10-03's shared-props contract, extended.

## 8. Proposed Implementation Direction

A `CapabilityProvider` service (proposed) computing a small, page-relevant set of capability flags, injected into the Inertia response by whichever controller needs it (not globally shared for every request, to avoid unnecessary computation).

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

`CapabilityProvider` service delegating entirely to `AuthorizationDecisionService::can()` per permission checked — never independent logic.

## 11. Web Frontend Requirements

A `useCapabilities()` hook (proposed) reading the `capabilities` prop for conditional rendering (e.g., hiding a "Grant Role" button from a user without the permission).

## 12. Flutter Requirements

Not Applicable — Flutter re-derives its own capability checks via the API contract, following the same principle independently.

## 13. Authorization and Access Control

This work package's central discipline: the capability prop is advisory only — every backend action independently re-checks via `AuthorizationDecisionService`, restated as the title finding of RISK-EPIC10-01.

## 14. Security Requirements

The capability prop must never be treated as sufficient for a backend decision — code review must catch any controller that skips its own authorization check because "the frontend already checked."

## 15. Privacy and Data-Governance Requirements

Not Applicable directly.

## 16. Audit and Activity Events

Not Applicable directly.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable in Phase 1 — a cached/stale capability set on a reconnecting offline client is a future EPIC-12 concern (WP-09-11's reconnection pattern applies analogously).

## 19. Observability Requirements

Not Applicable directly.

## 20. Testing Requirements

Feature test confirming the capability prop correctly reflects `AuthorizationDecisionService`'s decision; a negative test confirming a backend action still denies even if a (hypothetically tampered) capability prop claimed true.

## 21. Test Data Requirements

Reuses existing factories. Do not create new test data.

## 22. Documentation Updates

Record the capability-contract pattern, with its advisory-only discipline explicitly restated, in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement `CapabilityProvider` service | WP-05-07 complete | Unit tests pass |
| TASK-02 | Implement `useCapabilities()` hook | WP-10-03 complete | Frontend test passes |
| TASK-03 | Implement negative test confirming backend independence from frontend claims | TASK-01..02 | Test passes |

## 24. Acceptance Criteria

- **AC-01:** Given a user's actual authorization, when the capability prop is computed, then it correctly reflects `AuthorizationDecisionService`'s decision.
- **AC-02:** Given a simulated tampered/incorrect capability claim on the client, when the corresponding backend action is attempted, then it is independently denied by the backend regardless.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-10-03, WP-05-07 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): test output for both criteria.

## 28. Rollback and Recovery Considerations

Not Applicable — new, additive service and hook.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-10-05-01 | A future controller skips its own authorization check, trusting the frontend capability prop instead | Critical | AC-02 is a standing regression guard; WP-15-03 explicitly checks for this pattern | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-10-05 — Frontend Permission and Capability Contract.

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
- The capabilities prop is advisory only — every backend action must independently re-check authorization, never trusting this prop.
```
