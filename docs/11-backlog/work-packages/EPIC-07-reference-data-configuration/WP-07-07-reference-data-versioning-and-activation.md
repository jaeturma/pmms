# WP-07-07 — Reference Data Versioning and Activation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-07-07 | Title | Reference Data Versioning and Activation |
| Epic | EPIC-07 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 61 |
| Target release group | Foundation Release C | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | BC-34 Configuration and Reference Data |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Data owner |

## 2. Purpose

Formalizes the `active` flag pattern already used across WP-07-02 through WP-07-06 into a proper activation/deactivation workflow (never a hard delete of a reference entry that may already be referenced elsewhere), preserving referential history per ADR-0005's append-only discipline extended to reference data.

## 3. Architecture Sources

ADR-0005.

## 4. Scope

Document and implement `ActivateReferenceEntryCommand`/`DeactivateReferenceEntryCommand` (proposed generic commands, one implementation reused across all reference types via WP-07-01's shared repository pattern); confirm deactivation never deletes, only flips `active`.

## 5. Explicit Exclusions

Does not implement true multi-version history (e.g., renaming a reference entry while preserving old-name history) — deferred as a future enhancement if genuinely needed; Phase 1 only needs activate/deactivate.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-07-01, WP-07-02 through WP-07-06 | Hard |

## 7. Current-State Inspection

`active` column exists on every WP-07-02 through WP-07-06 table; no command wraps toggling it yet.

## 8. Proposed Implementation Direction

Generic `ReferenceDataActivationService` (proposed) operating against any table following WP-07-01's pattern, via the shared repository interface.

## 9. Database Changes

Database Changes: None (uses existing `active` columns).

## 10. Backend Requirements

One generic service, reused across all reference types.

## 11. Web Frontend Requirements

Not Applicable in this work package (WP-07-10 exposes it via UI).

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable directly at this point — WP-07-10's UI applies authorization.

## 14. Security Requirements

Not Applicable beyond standard validation.

## 15. Privacy and Data-Governance Requirements

Not Applicable.

## 16. Audit and Activity Events

Activation/deactivation is audit-worthy once wired to WP-06-06.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable in this work package.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Unit tests confirming deactivation never deletes; confirming a deactivated entry is excluded from active-only queries but still exists for historical FK integrity.

## 21. Test Data Requirements

Reuses existing factories. Do not create new test data.

## 22. Documentation Updates

Record the activation pattern in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement generic `ReferenceDataActivationService` | WP-07-01..06 complete | Unit tests pass |

## 24. Acceptance Criteria

- **AC-01:** Given a deactivated reference entry, when checked, then the row still exists (not deleted) and is excluded from active-only queries.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-07-01 through WP-07-06 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Criterion verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): test output.

## 28. Rollback and Recovery Considerations

Deactivation is inherently non-destructive and reversible (re-activate).

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-07-07-01 | A future work package implements a hard delete instead of using this activation service | Medium | WP-15-02 database review checks for this | Data owner |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-07-07 — Reference Data Versioning and Activation.

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
- Deactivation must never delete a reference entry.
```
