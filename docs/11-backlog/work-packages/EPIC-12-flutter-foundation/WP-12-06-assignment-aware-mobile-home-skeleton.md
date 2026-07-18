# WP-12-06 — Assignment-Aware Mobile Home Skeleton

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-12-06 | Title | Assignment-Aware Mobile Home Skeleton |
| Epic | EPIC-12 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P2 | Implementation sequence | 117 |
| Target release group | Foundation Release E | Pilot relevance | Required (pilot only) |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | BC-02 Identity/Access |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Domain reviewer |

## 2. Purpose

Builds a bare home screen showing the authenticated user's role/assignment summary (consuming the same `AuthorizationDecisionService`-derived data as WP-10-05's web capability contract, via a mobile-specific endpoint or the same API), giving the mobile app its first authenticated, personalized screen.

## 3. Architecture Sources

ADR-0003.

## 4. Scope

Implement a home screen displaying the user's name and a list of their current role assignments (read-only); no operational module content yet.

## 5. Explicit Exclusions

Does not implement any operational module (scanning, accreditation) — display-only skeleton.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-12-05, WP-05-07 | Hard |

## 7. Current-State Inspection

No home screen exists beyond the default `flutter create` template.

## 8. Proposed Implementation Direction

`lib/features/home/home_screen.dart` calling a simple "my assignments" read endpoint (reusing existing backend data, no new endpoint needed if WP-05-11's underlying query is reusable).

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Not Applicable — reuses existing authorization/assignment query capability.

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Home screen displaying role/assignment summary.

## 13. Authorization and Access Control

The home screen's own access requires a valid authenticated session (WP-12-05); the assignment data displayed is the user's own only, never another user's.

## 14. Security Requirements

Not Applicable beyond standard authenticated-request handling.

## 15. Privacy and Data-Governance Requirements

Displays only the user's own data.

## 16. Audit and Activity Events

Not Applicable directly.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable in Phase 1 — this screen requires connectivity.

## 19. Observability Requirements

Not Applicable directly.

## 20. Testing Requirements

Widget test confirming the home screen renders the user's assignment data correctly.

## 21. Test Data Requirements

Reuses existing factories. Do not create new test data.

## 22. Documentation Updates

Record the home screen in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement home screen displaying role/assignment summary | WP-12-05, WP-05-07 complete | Widget test passes |

## 24. Acceptance Criteria

- **AC-01:** Given an authenticated user, when the home screen loads, then it displays only that user's own assignments.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-12-05, WP-05-07 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Criterion verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): test output, screenshots.

## 28. Rollback and Recovery Considerations

Not Applicable — new, additive screen.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-12-06-01 | The endpoint used accidentally returns another user's data due to a missing filter | Critical | AC-01 explicitly tests own-data-only | Domain reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-12-06 — Assignment-Aware Mobile Home Skeleton.

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
- Display only the authenticated user's own data.
- Do not implement any operational module content — display-only skeleton.
```
