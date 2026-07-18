# WP-07-03 — Meet Reference Types

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-07-03 | Title | Meet Reference Types |
| Epic | EPIC-07 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Small |
| Priority | P1 | Implementation sequence | 57 |
| Target release group | Foundation Release C | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | BC-34 Configuration and Reference Data |
| Secondary affected contexts | BC-04 | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Domain reviewer |

## 2. Purpose

Implements the `meet_levels` reference table (Provincial/Division/Regional/National, per the product's own name) that WP-04-02's `Meet.level` field references, following WP-07-01's generic pattern — this catalog is already well-defined by the product name itself, unlike WP-07-02's provisional organization types.

## 3. Architecture Sources

[../../../../00-product/product-scope.md](../../../../00-product/product-scope.md).

## 4. Scope

Implement `meet_levels` table per WP-07-01's pattern; seed with Provincial/Division/Regional/National.

## 5. Explicit Exclusions

Does not implement meet-status reference data (status is an inline enum on `meets` per WP-04-02, not a separate reference table, since it is a small, stable, workflow-tied set).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-07-01, WP-04-02 | Hard |

## 7. Current-State Inspection

`meets.level` column exists per WP-04-02, currently an inline enum — this work package evaluates whether to normalize it to a reference table or keep the enum (recommendation: keep as enum, since the four levels are named directly in the product's own scope and unlikely to change without a product redefinition).

## 8. Proposed Implementation Direction

Given the four levels are stable and product-defined, this work package documents the decision to **keep** `meets.level` as an inline enum rather than a reference table, deviating from the generic WP-07-01 pattern with explicit justification — avoiding unnecessary normalization for a genuinely fixed, small set (per the Phase 0.13 "avoid unnecessary complexity" discipline).

## 9. Database Changes

Database Changes: None (explicit decision not to create a `meet_levels` table).

## 10. Backend Requirements

Not Applicable — no new table.

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

Not Applicable — no code change, decision documentation only.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record the enum-vs-reference-table decision and its rationale in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Evaluate enum vs. reference table for meet level | WP-07-01, WP-04-02 complete | Decision documented with rationale |

## 24. Acceptance Criteria

- **AC-01:** Given the evaluation, when documented, then it explicitly justifies keeping `meets.level` as an enum rather than mechanically applying WP-07-01's reference-table pattern.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-07-01, WP-04-02 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Criterion verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): the documented decision and rationale.

## 28. Rollback and Recovery Considerations

Not Applicable — no schema change.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-07-03-01 | A future need for a fifth meet level (e.g., international) requires a schema migration since it's an enum, not a reference table | Low | Explicitly accepted trade-off, documented; migration to a reference table remains possible later if needed | Domain reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-07-03 — Meet Reference Types.

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
- This is a documentation-only decision work package — no schema change.
```
