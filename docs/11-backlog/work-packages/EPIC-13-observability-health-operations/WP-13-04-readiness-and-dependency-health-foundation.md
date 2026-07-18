# WP-13-04 — Readiness and Dependency Health Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-13-04 | Title | Readiness and Dependency Health Foundation |
| Epic | EPIC-13 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 127 |
| Target release group | Foundation Release E | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Engineering lead, Security reviewer |

## 2. Purpose

Adds an extensible, access-restricted readiness surface on top of WP-13-03's liveness endpoint: a dependency-check framework reporting per-dependency status (starting with database and Redis), which WP-13-05 (queue/scheduler), WP-13-06 (Reverb), and WP-13-07 (storage) plug into rather than each inventing its own health mechanism.

## 3. Architecture Sources

[../../../05-devops/](../../../05-devops/) (operations and observability architecture), ADR-0008.

## 4. Scope

Implement a dependency-check contract (proposed: a `HealthCheck` interface with name, check result, optional latency) and a registry that runs registered checks; database and Redis checks as the first two implementations; a readiness endpoint returning aggregate and per-check status; access restriction on the readiness endpoint (authenticated + authorized, or network-level token — decided at implementation against WP-05-07 availability); degraded-versus-down status semantics.

## 5. Explicit Exclusions

Does not implement queue/scheduler, Reverb, or storage checks (WP-13-05/06/07 add them via this framework); does not implement alerting, paging, or scheduled polling (operational, future); does not expose readiness detail on the public liveness endpoint.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-13-03 | Hard |

## 7. Current-State Inspection

No readiness or dependency-check surface exists; WP-13-03 provides only static liveness.

## 8. Proposed Implementation Direction

`App\Support\Health\HealthCheck` contract and `HealthCheckRegistry` (proposed); readiness controller returning JSON aggregate; database check via lightweight query, Redis check via ping.

## 9. Database Changes

Database Changes: None (the database check reads, never writes).

## 10. Backend Requirements

Check contract, registry, two initial checks, readiness endpoint with restricted access, degraded/down semantics.

## 11. Web Frontend Requirements

Not Applicable in this work package — WP-13-09 builds the diagnostics interface.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

The readiness endpoint must not be publicly accessible — per-dependency status is internal diagnostic detail (RISK-EPIC13-01). Access mechanism is decided at implementation time and recorded; whatever the mechanism, an unauthorized request receives no per-check detail.

## 14. Security Requirements

Check results must not include connection strings, hostnames, credentials, or stack traces — status, name, and latency only.

## 15. Privacy and Data-Governance Requirements

Not Applicable — no personal data in check results.

## 16. Audit and Activity Events

Not Applicable for routine checks; access-control failures on the readiness endpoint fall under the standard security-event conventions (WP-06-03) once wired.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable in this work package.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

This work package is the extensible readiness framework; every later dependency check registers into it.

## 20. Testing Requirements

Tests for: aggregate healthy when all checks pass; aggregate degraded/down when a check fails (simulated); unauthorized access receives no per-check detail; check results contain no connection detail.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record the check contract, registry, status semantics, and access mechanism in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement check contract and registry | WP-13-03 complete | Unit tests pass |
| TASK-02 | Implement database and Redis checks | TASK-01 | Both report correctly |
| TASK-03 | Implement restricted readiness endpoint | TASK-01 | Unauthorized access yields no detail |
| TASK-04 | Implement degraded/down aggregate semantics | TASK-02 | Simulated failure test passes |

## 24. Acceptance Criteria

- **AC-01:** Given all registered checks passing, when the readiness endpoint is requested with authorization, then aggregate status is healthy with per-check detail.
- **AC-02:** Given a simulated Redis failure, when the endpoint is requested, then the aggregate reflects the failure and the Redis check reports it, without exposing connection detail.
- **AC-03:** Given an unauthorized request, when it hits the readiness endpoint, then no per-check detail is returned.
- **AC-04:** Given a new check implementing the contract, when registered, then it appears in the readiness output without endpoint changes.

## 25. Definition of Ready

Per [../../phase-1-definition-of-ready.md](../../phase-1-definition-of-ready.md). WP-13-03 complete.

## 26. Definition of Done

Per [../../phase-1-definition-of-done.md](../../phase-1-definition-of-done.md). All four criteria verified.

## 27. Completion Evidence

Per [../../phase-1-completion-evidence-standard.md](../../phase-1-completion-evidence-standard.md): readiness response captures (healthy and simulated-failure), access-restriction test output.

## 28. Rollback and Recovery Considerations

Additive; removing the endpoint and registry restores prior state. No data migration.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-13-04-01 | Readiness endpoint left publicly accessible in a deployment | High | AC-03 regression test; WP-15-09 security review re-verifies | Security reviewer |
| RISK-WP-13-04-02 | Checks perform expensive operations, making readiness itself a load source | Medium | Checks are lightweight by contract (ping/trivial query); latency captured to detect drift | Engineering lead |

## 30. Open Decisions

| Decision ID | Question | Blocking Status |
|---|---|---|
| DEC-WP-13-04-01 | Access mechanism for the readiness endpoint (session-authorized vs. token) | Non-blocking — decided and recorded at implementation |

## 31. Future Claude Code Execution Prompt

```text
Implement WP-13-04 — Readiness and Dependency Health Foundation.

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
- The readiness endpoint must never be publicly accessible with per-check detail.
- Check results must not contain connection strings, hostnames, credentials, or stack traces.
- Implement only database and Redis checks — later checks arrive via WP-13-05/06/07.
```
