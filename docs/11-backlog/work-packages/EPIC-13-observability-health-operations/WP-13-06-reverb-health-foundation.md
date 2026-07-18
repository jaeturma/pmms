# WP-13-06 — Reverb Health Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-13-06 | Title | Reverb Health Foundation |
| Epic | EPIC-13 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Small |
| Priority | P1 | Implementation sequence | 129 |
| Target release group | Foundation Release E | Pilot relevance | Required (pilot only) |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Engineering lead |

## 2. Purpose

Registers a Reverb (WebSocket) health check into the WP-13-04 framework so real-time delivery failure — silent from the server's perspective, since clients simply stop receiving — becomes operationally visible before scoreboard/live-results features depend on it.

## 3. Architecture Sources

[../../../08-workflows/realtime-broadcast-and-reverb-message-architecture.md](../../../08-workflows/realtime-broadcast-and-reverb-message-architecture.md), [../../../09-enterprise/reverb-websocket-and-realtime-scaling.md](../../../09-enterprise/reverb-websocket-and-realtime-scaling.md), ADR-0004, ADR-0011.

## 4. Scope

A `HealthCheck` implementation verifying the Reverb server is reachable/accepting connections (proposed: lightweight connection or ping against the configured Reverb host/port), registered via WP-13-04; degraded semantics when Reverb is configured but unreachable; explicitly "not configured" (not "down") semantics when Reverb is absent in an environment.

## 5. Explicit Exclusions

Does not modify Reverb configuration or channels (WP-09-09/WP-09-10); does not measure broadcast latency or delivery rates (future operational metric); does not implement client-side reconnection behavior (WP-09-11).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-13-04 | Hard |
| WP-09-09 | Hard |

## 7. Current-State Inspection

By this point WP-09-09 provides the Reverb baseline; no health visibility exists for it.

## 8. Proposed Implementation Direction

`ReverbHealthCheck` (proposed) using the configured connection settings; distinguishes unreachable (down) from unconfigured (not applicable) per environment.

## 9. Database Changes

Database Changes: None.

## 10. Backend Requirements

One check implementation with reachable/unreachable/unconfigured semantics.

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Inherited from WP-13-04's restricted readiness endpoint.

## 14. Security Requirements

Check output must not expose the Reverb host, port, or application credentials — status only.

## 15. Privacy and Data-Governance Requirements

Not Applicable.

## 16. Audit and Activity Events

Not Applicable.

## 17. Event, Queue, Notification, and Real-Time Requirements

Observes the real-time layer only; must not broadcast anything.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Makes real-time infrastructure failure visible server-side rather than only as silent client staleness.

## 20. Testing Requirements

Test: check reports down when Reverb is configured but unreachable (simulated); reports healthy when reachable; reports unconfigured (without failing readiness) when absent.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record the check and its three-state semantics in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement Reverb reachability check with three-state semantics | WP-13-04, WP-09-09 complete | Simulated tests for all three states pass |
| TASK-02 | Register check in the readiness registry | TASK-01 | Appears in readiness output |

## 24. Acceptance Criteria

- **AC-01:** Given Reverb configured but unreachable, when readiness is requested, then the Reverb check reports down without exposing host/port detail.
- **AC-02:** Given Reverb reachable, when readiness is requested, then the check reports healthy.
- **AC-03:** Given Reverb not configured in an environment, when readiness is requested, then the check reports unconfigured without degrading the aggregate.

## 25. Definition of Ready

Per [../../phase-1-definition-of-ready.md](../../phase-1-definition-of-ready.md). WP-13-04 and WP-09-09 complete.

## 26. Definition of Done

Per [../../phase-1-definition-of-done.md](../../phase-1-definition-of-done.md). All three criteria verified.

## 27. Completion Evidence

Per [../../phase-1-completion-evidence-standard.md](../../phase-1-completion-evidence-standard.md): readiness output in each state, test output.

## 28. Rollback and Recovery Considerations

Additive check; deregistration restores prior state.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-13-06-01 | Unconfigured state misread as healthy, hiding a misconfigured pilot environment | Medium | Three-state semantics explicit in output; WP-15-06 review verifies pilot environments report reachable, not unconfigured | Engineering lead |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-13-06 — Reverb Health Foundation.

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
- Status only in check output — never expose Reverb host, port, or credentials.
- Distinguish down from unconfigured — do not fail readiness for an intentionally absent Reverb.
- Never broadcast from a health check.
```
