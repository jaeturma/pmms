# WP-02-06 — Architecture Dependency Rules and Fitness-Test Readiness

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-02-06 | Title | Architecture Dependency Rules and Fitness-Test Readiness |
| Epic | EPIC-02 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 14 |
| Target release group | Foundation Release A | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | Cross-cutting |
| Secondary affected contexts | All | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Lead architect |

## 2. Purpose

Documents the dependency rules bounded contexts must obey (no cross-context write, no direct Eloquent-model reference across contexts, shared kernel only in one direction) and evaluates candidate tooling (deptrac, PHPStan custom rules) to enforce them automatically later — implementing [../../../10-review/architecture-fitness-functions-and-validation-gates.md](../../../10-review/architecture-fitness-functions-and-validation-gates.md)'s "Domain Boundaries" category for the first time against real code.

## 3. Architecture Sources

[../../../10-review/architecture-fitness-functions-and-validation-gates.md](../../../10-review/architecture-fitness-functions-and-validation-gates.md).

## 4. Scope

Document the dependency rules explicitly; evaluate deptrac and PHPStan custom-rule approaches at a documentation level (comparison, not installation); recommend one as the Phase 1 target, to be actually installed only once a real cross-context boundary exists to test (EPIC-04/05).

## 5. Explicit Exclusions

Does not install deptrac or any static-analysis package; does not implement an executable fitness test.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-02-01 | Hard |

## 7. Current-State Inspection

No fitness-test tooling installed; `phpstan.neon` exists with Larastan only (no custom architecture rules).

## 8. Proposed Implementation Direction

Documentation and tooling recommendation only. Recommend deptrac (purpose-built for layer/dependency rules) as the Phase 1 target tool, installed by whichever work package first has two real contexts to check (likely WP-04-09 or WP-15-01).

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Not Applicable (recommendation only).

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

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Not Applicable — no executable fitness test created here.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record the dependency rules and tooling recommendation in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Document cross-context dependency rules | WP-02-01 complete | Rules documented |
| TASK-02 | Compare deptrac vs. PHPStan custom rules at a documentation level | TASK-01 | Comparison documented, recommendation stated |
| TASK-03 | Record in `.ai/architecture.md` | TASK-01..02 | Section added |

## 24. Acceptance Criteria

- **AC-01:** Given the dependency rules, when documented, then "no cross-context write" and "no direct Eloquent-model reference across contexts" are both stated explicitly.
- **AC-02:** Given the tooling comparison, when documented, then a specific recommendation (deptrac) is stated, not left open-ended.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-02-01 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Rules and tooling recommendation documented.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): the documented rules and comparison.

## 28. Rollback and Recovery Considerations

Not Applicable — documentation only.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-02-06-01 | Fitness rules stay undocumented-as-tooling until a real violation already exists | Medium | WP-15-01 explicitly re-checks whether tooling has been installed by Release F | Lead architect |

## 30. Open Decisions

| Decision ID | Question | Blocking Status |
|---|---|---|
| DEC-WP-02-06-01 | deptrac vs. PHPStan custom rules — final selection | Non-blocking; deptrac recommended provisionally |

## 31. Future Claude Code Execution Prompt

```text
Implement WP-02-06 — Architecture Dependency Rules and Fitness-Test Readiness.

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
- Do not install deptrac or any static-analysis package.
- Recommendation only; no executable fitness test.
```
