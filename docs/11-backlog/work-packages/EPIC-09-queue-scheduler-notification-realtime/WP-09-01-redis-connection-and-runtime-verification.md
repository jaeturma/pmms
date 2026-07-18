# WP-09-01 — Redis Connection and Runtime Verification

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-09-01 | Title | Redis Connection and Runtime Verification |
| Epic | EPIC-09 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Small |
| Priority | P1 | Implementation sequence | 77 |
| Target release group | Foundation Release D | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | DevOps lead |

## 2. Purpose

Verifies Redis connectivity against the `REDIS_CLIENT`/`REDIS_HOST`/`REDIS_PORT` variables already present (but unused by default connections) in `.env.example`, before Queue/Horizon/Cache/Broadcast are switched to use it.

## 3. Architecture Sources

Approved technology direction (Redis).

## 4. Scope

Configure and verify `phpredis` connectivity; confirm `Redis::ping()` succeeds against the configured instance.

## 5. Explicit Exclusions

Does not provision a Redis server (environment action, referenced in WP-01-06); does not switch `QUEUE_CONNECTION`/`CACHE_STORE`/`BROADCAST_CONNECTION` yet (WP-09-02/09-09's scope).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-01-01 | Hard |

## 7. Current-State Inspection

`.env.example`'s `REDIS_CLIENT=phpredis`, `REDIS_HOST=127.0.0.1`, `REDIS_PORT=6379` present but unused by default connections (queue/cache/session/broadcast all use non-Redis drivers per WP-01-06's finding).

## 8. Proposed Implementation Direction

Configuration verification only; no code change beyond confirming connectivity.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Confirm `Redis::ping()` succeeds.

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable.

## 14. Security Requirements

Confirm Redis is not exposed on a public interface without authentication in any non-local environment (a documentation note, not enforceable from this work package alone).

## 15. Privacy and Data-Governance Requirements

Not Applicable — Redis remains transient, never authoritative for personal data.

## 16. Audit and Activity Events

Not Applicable.

## 17. Event, Queue, Notification, and Real-Time Requirements

This work package is the connectivity prerequisite for WP-09-02 (queue) and WP-09-09 (Reverb).

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Redis connectivity feeds WP-13-05's queue-health check.

## 20. Testing Requirements

Integration test confirming `Redis::ping()` succeeds against the configured instance.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record verified Redis connectivity in `.ai/engineering-baseline.md`.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Configure and verify Redis connectivity | WP-01-01 complete | `Redis::ping()` succeeds |

## 24. Acceptance Criteria

- **AC-01:** Given a configured Redis instance, when `Redis::ping()` is called, then it succeeds.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-01-01 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Criterion verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): ping test output.

## 28. Rollback and Recovery Considerations

Not Applicable — configuration verification only.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-09-01-01 | No Redis instance is available in the execution environment, blocking this and all downstream EPIC-09 work packages | High | Flagged explicitly as an environment precondition, same discipline as WP-08-01's MinIO check | DevOps lead |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-09-01 — Redis Connection and Runtime Verification.

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
- Do not provision a Redis server — configuration verification only.
- Do not switch QUEUE_CONNECTION/CACHE_STORE/BROADCAST_CONNECTION yet.
```
