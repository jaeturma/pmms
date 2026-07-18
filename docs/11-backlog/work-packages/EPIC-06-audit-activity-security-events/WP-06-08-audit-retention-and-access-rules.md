# WP-06-08 — Audit Retention and Access Rules

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-06-08 | Title | Audit Retention and Access Rules |
| Epic | EPIC-06 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Small |
| Priority | P1 | Implementation sequence | 53 |
| Target release group | Foundation Release C | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | BC-32 Audit and Compliance |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer, Data owner |

## 2. Purpose

Documents the retention-readiness posture for audit data (GAP-07: final numeric retention values remain blocked on PSG-03, but the schema and access rules must not assume any particular retention period) and the access-control rule for who may read audit data (the `audit.review` permission from WP-06-07).

## 3. Architecture Sources

[../../../../02-data/data-open-decisions.md](../../../../02-data/data-open-decisions.md) (PD-04), [../../../10-review/policy-rulebook-and-source-validation-gap-register.md](../../../10-review/policy-rulebook-and-source-validation-gap-register.md) (PSG-03).

## 4. Scope

Document that no retention-purge job is implemented in Phase 1 (retention periods unknown pending PSG-03); document the `audit.review` permission's scope and that it is the only access path to raw audit data outside direct database access.

## 5. Explicit Exclusions

Does not implement a purge/archival job; does not resolve PD-04's numeric retention values.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-06-06, WP-06-07 | Hard |
| PSG-03 (retention policy source) | Soft — non-blocking, Phase 1 does not purge |

## 7. Current-State Inspection

WP-06-06/06-07 exist by this point; no retention or purge mechanism exists.

## 8. Proposed Implementation Direction

Documentation only — confirms the append-only schema (WP-06-01/02/03) is retention-ready (no destructive purge assumption baked into the schema) without implementing retention enforcement.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Not Applicable — documentation only.

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Restates `audit.review` as the sole access path (from WP-06-07).

## 14. Security Requirements

Documents that audit data must never be purged by any means other than an eventual formal, policy-driven retention job — no ad hoc deletion.

## 15. Privacy and Data-Governance Requirements

This work package's central concern: retention posture for potentially sensitive audit content.

## 16. Audit and Activity Events

Not Applicable directly.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Not Applicable — documentation only.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record retention posture and access rule in `.ai/architecture.md` addendum, explicitly cross-referencing GAP-07/PSG-03.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Document retention-readiness posture (no purge implemented, schema retention-ready) | WP-06-06, WP-06-07 complete | Documented |
| TASK-02 | Document `audit.review` as the sole access path | TASK-01 | Documented |

## 24. Acceptance Criteria

- **AC-01:** Given the retention posture, when documented, then it explicitly states no purge job exists in Phase 1 and why (PD-04/PSG-03 unresolved).
- **AC-02:** Given the access rule, when documented, then `audit.review` is confirmed as the sole application-level access path.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-06-06, WP-06-07 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): the documented posture and rule.

## 28. Rollback and Recovery Considerations

Not Applicable — documentation only.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-06-08-01 | Unbounded audit-table growth without a retention job, eventually affecting performance | Medium | Explicitly accepted as Phase 1 debt (TD-XX), resolution trigger is PSG-03 resolution | Data owner |

## 30. Open Decisions

| Decision ID | Question | Blocking Status |
|---|---|---|
| DEC-WP-06-08-01 | Retention period for audit data (PD-04) | Non-blocking for Phase 1; blocks production readiness |

## 31. Future Claude Code Execution Prompt

```text
Implement WP-06-08 — Audit Retention and Access Rules.

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
- Do not implement a purge/archival job — documentation only.
```
