# WP-09-10 — Private Channel Authorization Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-09-10 | Title | Private Channel Authorization Foundation |
| Epic | EPIC-09 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 86 |
| Target release group | Foundation Release D | Pilot relevance | Required (pilot only) |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Implements Laravel's private-channel authorization callback (`routes/channels.php`), wired to WP-05-07's `AuthorizationDecisionService`, so a private channel (e.g., a future per-meet notification channel) requires the same authorization discipline as any other protected resource — never a separate, parallel channel-specific authorization mechanism.

## 3. Architecture Sources

ADR-0012.

## 4. Scope

Implement a private-channel authorization pattern (proposed: `private-organization.{id}`) checking `AuthorizationDecisionService::can()`; implement one concrete example channel for testing purposes.

## 5. Explicit Exclusions

Does not implement any channel for a real capability beyond the test example; does not implement presence channels (not needed in Phase 1).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-09-09, WP-05-07 | Hard |

## 7. Current-State Inspection

`routes/channels.php` does not exist yet (not part of the starter kit, since no broadcasting was configured).

## 8. Proposed Implementation Direction

`routes/channels.php` with a channel-authorization closure delegating entirely to `AuthorizationDecisionService::can()`, never implementing independent logic (same discipline as WP-05-08's Policy convention).

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Channel-authorization closures delegating to WP-05-07.

## 11. Web Frontend Requirements

Laravel Echo private-channel subscription pattern documented for future use.

## 12. Flutter Requirements

Not Applicable in Phase 1.

## 13. Authorization and Access Control

This work package's entire purpose is applying the canonical authorization service to a new surface (WebSocket channels).

## 14. Security Requirements

An unauthorized subscription attempt must be rejected at the channel-authorization layer, never merely hidden from the frontend UI.

## 15. Privacy and Data-Governance Requirements

Not Applicable directly.

## 16. Audit and Activity Events

Not Applicable directly in this work package.

## 17. Event, Queue, Notification, and Real-Time Requirements

This work package extends EPIC-09's real-time foundation with authorization.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable directly.

## 20. Testing Requirements

Feature test confirming an authorized user can subscribe to the test private channel; an unauthorized user is rejected.

## 21. Test Data Requirements

Reuses existing factories. Do not create new test data.

## 22. Documentation Updates

Record the private-channel authorization pattern in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement `routes/channels.php` delegating to `AuthorizationDecisionService` | WP-09-09, WP-05-07 complete | Feature tests pass |

## 24. Acceptance Criteria

- **AC-01:** Given an authorized user, when they subscribe to the test private channel, then the subscription succeeds.
- **AC-02:** Given an unauthorized user, when they attempt to subscribe, then the subscription is rejected.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-09-09, WP-05-07 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): test output.

## 28. Rollback and Recovery Considerations

Not Applicable — new, additive infrastructure.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-09-10-01 | A future channel implements its own ad hoc authorization instead of delegating to the canonical service | High | WP-15-06 queue/real-time review checks every channel definition | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-09-10 — Private Channel Authorization Foundation.

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
- Channel authorization must delegate entirely to AuthorizationDecisionService, never implement independent logic.
- Do not implement presence channels.
```
