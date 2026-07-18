# WP-13-03 — Application Health Endpoint Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-13-03 | Title | Application Health Endpoint Foundation |
| Epic | EPIC-13 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Small |
| Priority | P1 | Implementation sequence | 126 |
| Target release group | Foundation Release E | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Engineering lead, Security reviewer |

## 2. Purpose

Provides a minimal liveness endpoint answering one question — "is the application process serving requests?" — as the anchor the readiness/dependency-health work packages (WP-13-04 through WP-13-07) extend, per ADR-0008's health-endpoint architecture.

## 3. Architecture Sources

[../../../05-devops/](../../../05-devops/) (operations and observability architecture), ADR-0008.

## 4. Scope

Implement an unauthenticated liveness endpoint (proposed: `GET /up` or equivalent, using Laravel's built-in health route if suitable) returning HTTP 200 with a minimal, static body; document what the endpoint does and — more importantly — does not reveal.

## 5. Explicit Exclusions

Does not check any dependency (database, Redis, storage — WP-13-04 through WP-13-07); does not expose version, environment, configuration, or diagnostic detail (RISK-EPIC13-01); does not implement uptime monitoring or alerting (operational, future).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-01-01 | Hard |

## 7. Current-State Inspection

Laravel 13 ships a default health route configuration; inspect whether the starter kit has it enabled and what it currently returns before adding anything.

## 8. Proposed Implementation Direction

Prefer the framework's built-in health route (verified, not assumed) with a minimal response; route registration in `bootstrap/app.php` (proposed).

## 9. Database Changes

Database Changes: None.

## 10. Backend Requirements

One route, minimal static response, no dependency checks.

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Intentionally unauthenticated — which is precisely why its response must contain nothing but liveness.

## 14. Security Requirements

Response must not include framework version, environment name, hostname, or any configuration value; rate limiting per WP-14-05's baseline applies to prevent abuse as a probe target.

## 15. Privacy and Data-Governance Requirements

Not Applicable — the response is static.

## 16. Audit and Activity Events

Not Applicable — liveness probes are not audited (they would flood the audit log; per the WP-06-01 distinction they are not business events).

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

This endpoint is the base of the health surface; WP-13-04 adds authenticated depth.

## 20. Testing Requirements

Feature test asserting HTTP 200 and that the response body contains no version/environment/config values; test asserting the endpoint responds without a database connection where feasible (liveness must not depend on dependencies).

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record the health-surface design (liveness vs. readiness split) in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Verify/enable minimal liveness endpoint | WP-01-01 complete | Returns 200 with minimal static body |
| TASK-02 | Confirm no internal detail in response | TASK-01 | Feature test passes |

## 24. Acceptance Criteria

- **AC-01:** Given the liveness endpoint, when requested without authentication, then it returns HTTP 200 with a minimal body.
- **AC-02:** Given the response body, when inspected, then it contains no version, environment, hostname, or configuration detail.
- **AC-03:** Given a database outage simulation (where testable), when the endpoint is requested, then it still answers — liveness is independent of dependencies.

## 25. Definition of Ready

Per [../../phase-1-definition-of-ready.md](../../phase-1-definition-of-ready.md). WP-01-01 complete.

## 26. Definition of Done

Per [../../phase-1-definition-of-done.md](../../phase-1-definition-of-done.md). All three criteria verified.

## 27. Completion Evidence

Per [../../phase-1-completion-evidence-standard.md](../../phase-1-completion-evidence-standard.md): endpoint response capture, test output.

## 28. Rollback and Recovery Considerations

Single route; removal is trivial. No data migration.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-13-03-01 | Diagnostic detail creeps into the public endpoint in a later work package | High | AC-02 is a standing regression test; WP-13-10 and WP-15-09 re-verify | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-13-03 — Application Health Endpoint Foundation.

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
- The public endpoint must reveal nothing beyond liveness — no version, environment, or configuration detail.
- Do not add dependency checks here — they belong to WP-13-04 through WP-13-07.
```
