# WP-11-05 — Organization and Meet Context Switcher Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-11-05 | Title | Organization and Meet Context Switcher Foundation |
| Epic | EPIC-11 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 103 |
| Target release group | Foundation Release D | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | Cross-cutting |
| Secondary affected contexts | BC-03, BC-04 | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | UX reviewer, Security reviewer |

## 2. Purpose

Builds a UI component letting a user with multiple organization memberships (WP-04-03) or meet access (WP-04-04) explicitly switch their active context, resolving WP-04-05's "ambiguous multi-organization user requires explicit context" requirement in the UI.

## 3. Architecture Sources

ADR-0012.

## 4. Scope

Implement a context-switcher dropdown in the app header, listing the user's actual organization/meet memberships (from WP-04-03/04-04), submitting the selection to switch `CurrentContext` (WP-04-05).

## 5. Explicit Exclusions

Does not implement cross-tenant switching for platform administrators (no such role exists in Phase 1); does not implement a "favorite"/pinned-context feature.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-11-04, WP-04-04 | Hard |

## 7. Current-State Inspection

No context-switcher UI exists; `nav-user.tsx`/`user-menu-content.tsx` are the closest existing analogous pattern to extend from.

## 8. Proposed Implementation Direction

A dropdown component (proposed, reusing WP-11-04's header area) listing the user's memberships, submitting via a simple form post that updates the session-stored active context, consumed by WP-04-05 on the next request.

## 9. Database Changes

Database Changes: None (reads WP-04-03/04-04, writes to session only).

## 10. Backend Requirements

A `SwitchContextController` (proposed) validating the requested context is actually one the user has access to (never trusting a client-supplied context blindly, per WP-04-05's fail-closed discipline).

## 11. Web Frontend Requirements

The context-switcher dropdown component.

## 12. Flutter Requirements

Not Applicable in Phase 1 — an analogous mobile pattern is a future EPIC-12 enhancement.

## 13. Authorization and Access Control

The switch request itself is validated server-side against the user's actual memberships — the dropdown's displayed options are for usability, the backend independently re-validates.

## 14. Security Requirements

A tampered request selecting a context the user doesn't actually have access to must be rejected, not silently granted.

## 15. Privacy and Data-Governance Requirements

Not Applicable directly.

## 16. Audit and Activity Events

Context switches are audit-worthy (a user actively changing their operating context) once wired to WP-06-06.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable directly.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable directly.

## 20. Testing Requirements

Feature tests: a user switches to a context they have access to (succeeds); a tampered request for an inaccessible context is rejected.

## 21. Test Data Requirements

Reuses `OrganizationMembershipFactory`, `UserMeetAccessFactory`. Do not create new test data.

## 22. Documentation Updates

Record the context-switcher pattern in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement `SwitchContextController` with server-side validation | WP-04-04 complete | Feature tests pass |
| TASK-02 | Implement the context-switcher dropdown component | WP-11-04 complete | Frontend test passes |

## 24. Acceptance Criteria

- **AC-01:** Given a user with multiple valid contexts, when they switch, then `CurrentContext` correctly reflects the new selection on the next request.
- **AC-02:** Given a tampered request for an inaccessible context, when attempted, then it is rejected, and the active context remains unchanged.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-11-04, WP-04-04 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): test output, screenshots.

## 28. Rollback and Recovery Considerations

Not Applicable — new, additive feature.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-11-05-01 | The switch endpoint trusts the client-supplied context without server-side re-validation | Critical | AC-02 is a standing regression guard | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-11-05 — Organization and Meet Context Switcher Foundation.

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
- The context-switch request must be independently re-validated server-side, never trusted from the client alone.
```
