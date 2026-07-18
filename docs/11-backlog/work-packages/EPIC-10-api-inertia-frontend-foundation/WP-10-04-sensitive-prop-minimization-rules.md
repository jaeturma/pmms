# WP-10-04 — Sensitive Prop Minimization Rules

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-10-04 | Title | Sensitive Prop Minimization Rules |
| Epic | EPIC-10 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Small |
| Priority | P1 | Implementation sequence | 92 |
| Target release group | Foundation Release D | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | Cross-cutting |
| Secondary affected contexts | EPIC-14 | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Privacy reviewer |

## 2. Purpose

Formalizes the minimum-necessary principle for Inertia props specifically: no page ever receives a full Eloquent model serialized as-is; every prop is an explicit, reviewed DTO/resource, using WP-14-01's classification once it exists.

## 3. Architecture Sources

ADR-0006.

## 4. Scope

Document the DTO/resource-per-prop convention (proposed: Laravel API Resources or plain arrays, never `$model->toArray()` passed directly); apply it retroactively to WP-10-03's shared-props implementation as a review checkpoint.

## 5. Explicit Exclusions

Does not implement a resource class for a real capability (none exists yet); rule/convention only.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-10-03, WP-14-01 | Hard |

## 7. Current-State Inspection

WP-10-03's shared props, reviewed against this rule.

## 8. Proposed Implementation Direction

Documentation of the convention; a lightweight architecture-fitness check (manual review in Phase 1, automatable later via WP-02-06's tooling) confirming no controller passes `$model->toArray()` or `$model` directly into an Inertia response.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Convention only in this work package.

## 11. Web Frontend Requirements

Not Applicable directly.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable directly.

## 14. Security Requirements

This convention is itself a security/privacy control — minimizing the attack surface of accidental data exposure.

## 15. Privacy and Data-Governance Requirements

This work package's entire purpose is a privacy control.

## 16. Audit and Activity Events

Not Applicable directly.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Not Applicable directly in this work package — enforced by review and later work packages' own tests.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record the convention in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Document the DTO/resource-per-prop convention | WP-10-03, WP-14-01 complete | Convention documented |
| TASK-02 | Review WP-10-03's existing shared props against this rule | TASK-01 | Review confirms compliance |

## 24. Acceptance Criteria

- **AC-01:** Given WP-10-03's shared props, when reviewed against this rule, then no full Eloquent model is passed directly.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-10-03, WP-14-01 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Criterion verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): the review findings.

## 28. Rollback and Recovery Considerations

Not Applicable — documentation and review, no code change beyond any finding's fix.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-10-04-01 | A future work package passes a full model directly, out of convenience | Medium | WP-15-05 frontend/accessibility review re-checks props | Privacy reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-10-04 — Sensitive Prop Minimization Rules.

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
- No Inertia prop may pass a full Eloquent model directly.
```
