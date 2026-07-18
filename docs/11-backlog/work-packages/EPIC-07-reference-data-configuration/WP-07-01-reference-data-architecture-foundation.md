# WP-07-01 — Reference Data Architecture Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-07-01 | Title | Reference Data Architecture Foundation |
| Epic | EPIC-07 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 55 |
| Target release group | Foundation Release C | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | BC-34 Configuration and Reference Data |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Domain reviewer |

## 2. Purpose

Establishes the generic reference-data table pattern (code, label, active, sort-order) every specific reference type (WP-07-02 through WP-07-06) follows, per ADR-0005's rule that reference data "never absorbs business rules" — a reference table stores a picklist entry, not logic.

## 3. Architecture Sources

[../../../../02-data/](../../../../02-data/), ADR-0005.

## 4. Scope

Document and implement a generic `ReferenceDataEntry` pattern (proposed base migration/entity shape) that WP-07-02 through WP-07-06 each instantiate for their own reference type; document the explicit boundary between "reference data" (a picklist value) and "business rule" (logic that acts on that value).

## 5. Explicit Exclusions

Does not implement any specific reference type's table (those are WP-07-02 through WP-07-06); does not implement any business logic.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-02-05 | Hard |

## 7. Current-State Inspection

No reference-data pattern exists.

## 8. Proposed Implementation Direction

Proposed common columns: `id`, `code` (unique within type), `label`, `active` (boolean), `sort_order`, timestamps — each specific reference type gets its own table (not one polymorphic mega-table, per WP-02-06's explicit-over-magic discipline).

## 9. Database Changes

Database Changes: None directly (this work package documents the pattern; WP-07-02 through WP-07-06 create the actual tables).

## 10. Backend Requirements

Document a shared `ReferenceDataRepository` base/trait (proposed) each specific repository extends.

## 11. Web Frontend Requirements

Not Applicable in this work package.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable directly — read access to reference data is generally broad (any authenticated user), write access is admin-only (WP-07-10).

## 14. Security Requirements

Not Applicable beyond standard validation.

## 15. Privacy and Data-Governance Requirements

Reference data is generally not personal data.

## 16. Audit and Activity Events

Reference-data changes are audit-worthy once WP-07-10 exists (write UI).

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable in this work package.

## 18. Offline and Synchronization Requirements

Not Applicable in Phase 1 — reference-data caching for offline mobile use is a future EPIC-12 enhancement.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Not Applicable directly in this work package — pattern validated once WP-07-02 through WP-07-06 use it.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record the reference-data-vs-business-rule boundary explicitly in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Document the generic reference-data table pattern | WP-02-05 complete | Pattern documented |
| TASK-02 | Document the explicit reference-data-vs-business-rule boundary | TASK-01 | Documented |

## 24. Acceptance Criteria

- **AC-01:** Given the pattern, when documented, then it defines a consistent column set (`code`, `label`, `active`, `sort_order`) every specific reference type must follow.
- **AC-02:** Given the boundary documentation, when reviewed, then it explicitly prohibits encoding sport-specific rules or logic in any reference table.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-02-05 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): the documented pattern and boundary.

## 28. Rollback and Recovery Considerations

Not Applicable — documentation only.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-07-01-01 | A later work package (WP-07-04, sport reference) is tempted to encode real sport rules into the picklist | High | Section 4's explicit boundary and WP-07-04's own Section 5 exclusion reinforce this | Domain reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-07-01 — Reference Data Architecture Foundation.

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
- Documentation only — no specific reference-type table created here.
- Reference data must never absorb business rules or logic.
```
