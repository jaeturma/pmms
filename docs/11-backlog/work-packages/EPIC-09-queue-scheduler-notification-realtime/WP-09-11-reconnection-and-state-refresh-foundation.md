# WP-09-11 — Reconnection and State Refresh Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-09-11 | Title | Reconnection and State Refresh Foundation |
| Epic | EPIC-09 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P2 | Implementation sequence | 87 |
| Target release group | Foundation Release D | Pilot relevance | Required (pilot only) |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Lead architect |

## 2. Purpose

Implements the client-side pattern for recovering from a dropped WebSocket connection — since Reverb (like Redis) is transient, a client that reconnects must re-fetch authoritative state from the server (an API call), never assume the missed events during disconnection can be replayed from the broadcast channel itself.

## 3. Architecture Sources

ADR-0004, ADR-0012.

## 4. Scope

Document and implement a `useRealtimeReconnection` React hook (proposed) that, on Echo reconnection, triggers a state-refresh API call rather than assuming continuity.

## 5. Explicit Exclusions

Does not implement any specific state-refresh endpoint for a real capability (none exists yet); provides the pattern/hook only.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-09-09, WP-09-10 | Hard |

## 7. Current-State Inspection

No reconnection-handling code exists.

## 8. Proposed Implementation Direction

Proposed `resources/js/hooks/use-realtime-reconnection.ts`, listening to Echo's `connector.pusher.connection` state events, triggering a provided refresh callback on reconnect.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Not Applicable in this work package — this is a frontend pattern; the "state refresh" endpoint itself belongs to whichever future context needs it.

## 11. Web Frontend Requirements

The `useRealtimeReconnection` hook itself, tested against a simulated disconnect/reconnect cycle.

## 12. Flutter Requirements

Not Applicable in Phase 1.

## 13. Authorization and Access Control

Not Applicable directly — the refresh call itself goes through normal API authorization.

## 14. Security Requirements

Not Applicable directly.

## 15. Privacy and Data-Governance Requirements

Not Applicable directly.

## 16. Audit and Activity Events

Not Applicable directly.

## 17. Event, Queue, Notification, and Real-Time Requirements

This work package documents the authoritative-server-state-over-broadcast-continuity principle for real-time UI, extending ADR-0012's server-authority rule (previously applied to offline mobile) to the real-time web context.

## 18. Offline and Synchronization Requirements

Not Applicable directly — this is about WebSocket reconnection, distinct from mobile offline sync, though conceptually related (both distrust client-side continuity assumptions).

## 19. Observability Requirements

Reconnection events may be logged client-side for debugging (not sent to the server by default in Phase 1).

## 20. Testing Requirements

Frontend test simulating a disconnect/reconnect cycle, confirming the refresh callback fires exactly once per reconnection.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record the hook and the server-authority-over-broadcast-continuity principle in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement `useRealtimeReconnection` hook | WP-09-09, WP-09-10 complete | Frontend test passes |

## 24. Acceptance Criteria

- **AC-01:** Given a simulated WebSocket disconnect followed by reconnect, when it occurs, then the provided refresh callback fires exactly once.
- **AC-02:** Given the hook's documentation, when reviewed, then it explicitly states missed events during disconnection are never assumed recoverable from the broadcast channel alone.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-09-09, WP-09-10 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): test output.

## 28. Rollback and Recovery Considerations

Not Applicable — new, additive frontend hook.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-09-11-01 | A future feature assumes broadcast continuity across a reconnection, missing an update | Medium | This hook's pattern is documented explicitly as the required approach for any future real-time UI | Lead architect |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-09-11 — Reconnection and State Refresh Foundation.

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
- Do not implement any specific state-refresh endpoint — hook/pattern only.
- Never assume missed broadcast events are recoverable from the channel itself after reconnection.
```
