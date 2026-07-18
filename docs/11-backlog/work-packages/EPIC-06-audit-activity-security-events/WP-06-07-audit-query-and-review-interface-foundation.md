# WP-06-07 — Audit Query and Review Interface Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-06-07 | Title | Audit Query and Review Interface Foundation |
| Epic | EPIC-06 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P2 | Implementation sequence | 52 |
| Target release group | Foundation Release C | Pilot relevance | Required (pilot only) |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | BC-32 Audit and Compliance |
| Secondary affected contexts | EPIC-10 | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Implements a query service (and, time-permitting, a minimal admin page) allowing an authorized reviewer to search recorded audit events by entity, actor, or date range — the second real administrative UI in the backlog after WP-05-11, and the first to expose potentially sensitive historical data.

## 3. Architecture Sources

ADR-0006.

## 4. Scope

Implement `AuditQueryService` (proposed) with filter-by-entity/actor/date-range methods; a minimal admin listing page (reusing WP-05-11's UI patterns) gated behind a dedicated permission (proposed `audit.review`).

## 5. Explicit Exclusions

Does not implement export functionality (WP-14-04's scope, when it exists); does not implement cross-table search across all three event types simultaneously in Phase 1 (audit events only, activity/security events deferred to a follow-on enhancement if needed).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-06-06, WP-05-07, WP-10-01 through WP-10-09 | Hard |

## 7. Current-State Inspection

`audit_events` table populated by WP-06-06 by this point; no query interface exists.

## 8. Proposed Implementation Direction

Proposed `AuditQueryService::search(filters): Paginator`; `AuditReviewController` (proposed), authorized via `audit.review` permission through WP-05-08's Policy convention.

## 9. Database Changes

Database Changes: None (read-only against existing tables).

## 10. Backend Requirements

Query service with pagination (per WP-10-07's contract); no write capability exposed.

## 11. Web Frontend Requirements

A minimal listing page reusing WP-11-09's table component; permission-denied state per WP-10-09 for unauthorized users.

## 12. Flutter Requirements

Not Applicable — audit review is web-only in Phase 1.

## 13. Authorization and Access Control

Gated behind a dedicated `audit.review` permission — distinct from and typically more restricted than general admin permissions, since audit data itself is sensitive.

## 14. Security Requirements

Query results must respect WP-04-08's tenant-isolation convention (a reviewer sees only their organization's audit trail, unless granted a broader cross-tenant permission, which does not exist in Phase 1).

## 15. Privacy and Data-Governance Requirements

Viewing audit events containing sensitive `before_state`/`after_state` JSON is itself subject to WP-06-05's sensitive-view convention — viewing the audit log is itself audited (a meta-audit entry), avoiding infinite recursion by excluding audit-of-audit-views from re-triggering itself.

## 16. Audit and Activity Events

Viewing this page is itself recorded per WP-06-05's convention.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable in this work package.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Standard request logging via WP-13-01 once it exists.

## 20. Testing Requirements

Feature tests: authorized reviewer can search and see only their tenant's data; unauthorized user is denied; viewing the page itself generates a meta-audit entry without infinite recursion.

## 21. Test Data Requirements

Reuses `AuditEventFactory`. Do not create new test data.

## 22. Documentation Updates

Record the query interface and its permission requirement in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement `AuditQueryService` with tenant-scoped filtering | WP-06-06 complete | Unit tests pass |
| TASK-02 | Implement `AuditReviewController` and minimal listing page | WP-05-07, WP-10-01..09 complete | Feature tests pass |
| TASK-03 | Implement meta-audit-without-recursion for page views | TASK-02 | Recursion-guard test passes |

## 24. Acceptance Criteria

- **AC-01:** Given an authorized reviewer, when they search audit events, then only their own organization's events are returned.
- **AC-02:** Given an unauthorized user, when they attempt to access the review page, then they receive a permission-denied response.
- **AC-03:** Given a reviewer viewing the audit page, when the view is itself recorded, then it does not trigger infinite recursive audit-of-audit-of-audit entries.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-06-06, WP-05-07, WP-10-01 through WP-10-09 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). All three criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): test output, screenshots.

## 28. Rollback and Recovery Considerations

Feature can be disabled by removing the route without affecting underlying audit data.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-06-07-01 | Meta-audit recursion causes an infinite loop or table flood | Medium | AC-03 explicitly tests the recursion guard | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-06-07 — Audit Query and Review Interface Foundation.

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
- Do not implement export functionality.
- Prevent infinite meta-audit recursion when the audit page itself is viewed.
```
