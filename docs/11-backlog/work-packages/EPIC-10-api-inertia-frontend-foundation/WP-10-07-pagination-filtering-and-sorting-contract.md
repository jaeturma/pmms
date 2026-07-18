# WP-10-07 — Pagination, Filtering, and Sorting Contract

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-10-07 | Title | Pagination, Filtering, and Sorting Contract |
| Epic | EPIC-10 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 95 |
| Target release group | Foundation Release D | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | Cross-cutting |
| Secondary affected contexts | EPIC-05, EPIC-07 | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Lead architect |

## 2. Purpose

Defines a consistent pagination/filter/sort query-parameter and response convention, so WP-05-11's assignment listing and WP-07-10's reference-data listing (and every future listing page) look and behave the same.

## 3. Architecture Sources

ADR-0004.

## 4. Scope

Document query-parameter conventions (`page`, `per_page`, `sort`, `filter[field]`); implement a shared `ListQueryBuilder` trait/helper (proposed) every repository can use.

## 5. Explicit Exclusions

Does not implement full-text search (a future enhancement, dedicated search engine explicitly deferred per Phase 0.12); does not implement any listing for a real capability beyond what other work packages already need.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-10-01 | Hard |

## 7. Current-State Inspection

No pagination convention exists.

## 8. Proposed Implementation Direction

Proposed `ListQueryBuilder` trait applying `page`/`per_page`/`sort`/`filter[]` parameters against Laravel's native pagination, capped `per_page` at a reasonable maximum (proposed: 100) to prevent abuse.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Shared trait; every filter field must be explicitly allow-listed per usage (never a raw dynamic `WHERE` from arbitrary client input).

## 11. Web Frontend Requirements

Not Applicable in this work package (WP-11-09's table component consumes this contract).

## 12. Flutter Requirements

This is the exact pagination contract Flutter's future listing views would consume.

## 13. Authorization and Access Control

Filtering must never allow a client to bypass tenant-scoping (WP-04-08's convention applies to every filtered query).

## 14. Security Requirements

Filter fields must be allow-listed, never dynamically derived from client input (preventing SQL injection via column-name manipulation).

## 15. Privacy and Data-Governance Requirements

Not Applicable directly.

## 16. Audit and Activity Events

Not Applicable directly.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable directly.

## 20. Testing Requirements

Unit tests: `per_page` is capped at the maximum even if a larger value is requested; a non-allow-listed filter field is ignored or rejected, not silently applied.

## 21. Test Data Requirements

Reuses existing factories. Do not create new test data.

## 22. Documentation Updates

Record the pagination/filter/sort contract in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement `ListQueryBuilder` with allow-listed filters and capped pagination | WP-10-01 complete | Unit tests pass |

## 24. Acceptance Criteria

- **AC-01:** Given a `per_page` request exceeding the maximum, when processed, then it is capped, not honored as requested.
- **AC-02:** Given a filter field not in the allow-list, when requested, then it is rejected or ignored, never applied as a raw dynamic query.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-10-01 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): test output.

## 28. Rollback and Recovery Considerations

Not Applicable — new, additive helper.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-10-07-01 | A future listing implementation bypasses the allow-list, permitting arbitrary column filtering | High | AC-02 is a standing regression guard | Lead architect |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-10-07 — Pagination, Filtering, and Sorting Contract.

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
- Filter fields must be explicitly allow-listed, never dynamically derived from client input.
- Do not implement full-text search.
```
