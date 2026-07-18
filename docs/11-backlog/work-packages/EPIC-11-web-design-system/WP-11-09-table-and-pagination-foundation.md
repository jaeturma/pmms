# WP-11-09 — Table and Pagination Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-11-09 | Title | Table and Pagination Foundation |
| Epic | EPIC-11 | Phase | Phase 1 | Status | Planned — Not Started |
| Complexity | Medium | Priority | P1 | Implementation sequence | 107 |
| Target release group | Foundation Release D | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | UX reviewer |

## 2. Purpose

Implements a `DataTable` component consuming WP-10-07's pagination/filter/sort contract — the component WP-05-11's assignment listing and WP-07-10's reference-data listing both need, built once and reused rather than each admin page implementing its own table.

## 3. Architecture Sources

[../../../../06-design/](../../../../06-design/), ADR-0004.

## 4. Scope

Implement `DataTable` (column definitions, sortable headers, pagination controls) consuming WP-10-07's query-parameter contract.

## 5. Explicit Exclusions

Does not implement row-selection/bulk-action UI (a future enhancement); does not implement any table for a real capability beyond the generic component.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-11-01, WP-10-07 | Hard |

## 7. Current-State Inspection

No table component exists (`resources/js/components/ui/sidebar.tsx` uses some table-adjacent patterns, but no dedicated `DataTable`).

## 8. Proposed Implementation Direction

`DataTable<T>` generic component, column-configuration-driven, consuming pagination metadata from WP-10-07's response shape.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Not Applicable.

## 11. Web Frontend Requirements

`DataTable` component.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable directly.

## 14. Security Requirements

Not Applicable.

## 15. Privacy and Data-Governance Requirements

Not Applicable directly — columns displayed depend on the data passed in, already minimized per WP-10-04.

## 16. Audit and Activity Events

Not Applicable.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Component test confirming sort/pagination controls correctly trigger the expected query-parameter changes.

## 21. Test Data Requirements

Synthetic component-test fixtures only. Do not create production-like test data.

## 22. Documentation Updates

Record the `DataTable` component in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement `DataTable` generic component | WP-11-01, WP-10-07 complete | Component test passes |

## 24. Acceptance Criteria

- **AC-01:** Given `DataTable` with sortable columns, when a header is clicked, then the correct `sort` query parameter is triggered.
- **AC-02:** Given pagination controls, when a page is changed, then the correct `page` query parameter is triggered.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-11-01, WP-10-07 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): component test output, screenshots.

## 28. Rollback and Recovery Considerations

Not Applicable — new, additive component.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-11-09-01 | Future pages reimplement table logic ad hoc instead of reusing `DataTable` | Low | Documented as the standard pattern | UX reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-11-09 — Table and Pagination Foundation.

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
- Do not implement row-selection/bulk-action UI.
```
