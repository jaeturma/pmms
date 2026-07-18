# WP-09-02 — Queue and Horizon Baseline

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-09-02 | Title | Queue and Horizon Baseline |
| Epic | EPIC-09 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 78 |
| Target release group | Foundation Release D | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | Cross-cutting |
| Secondary affected contexts | All | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | DevOps lead |

## 2. Purpose

Installs and configures Laravel Horizon, switching `QUEUE_CONNECTION` to `redis`, giving PMMS its first real queue-processing capability and dashboard — the approved technology direction's Horizon requirement, absent from the current starter kit.

## 3. Architecture Sources

Approved technology direction (Laravel Horizon), ADR-0004.

## 4. Scope

Install `laravel/horizon` via Composer; configure `config/horizon.php` with sensible Phase 1 defaults (a single supervisor, modest process count appropriate for pilot scale); switch `QUEUE_CONNECTION=redis`.

## 5. Explicit Exclusions

Does not implement any specific job (later epics provide their own); does not configure production-scale supervisor tuning (a future, evidence-gated decision per ADR-0012).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-09-01 | Hard |

## 7. Current-State Inspection

`composer.json` does not include `laravel/horizon`; `QUEUE_CONNECTION=database` in `.env.example`.

## 8. Proposed Implementation Direction

Standard Horizon installation (`composer require laravel/horizon`, `php artisan horizon:install`); `config/horizon.php` with a single `default` supervisor for Phase 1.

## 9. Database Changes

Database Changes: None (Horizon uses Redis for its own state, not MySQL).

## 10. Backend Requirements

Horizon package installed and configured; `QUEUE_CONNECTION=redis` in `.env.example`.

## 11. Web Frontend Requirements

Not Applicable — Horizon ships its own dashboard, not integrated into PMMS's own Inertia UI in Phase 1.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Horizon's dashboard access must be gated (Laravel's `Horizon::auth()` gate, restricted to admin-equivalent users once EPIC-05 is complete — this work package occurs after EPIC-05 in sequence, so the gate can use `AuthorizationDecisionService` directly).

## 14. Security Requirements

Horizon dashboard must never be publicly accessible without authentication.

## 15. Privacy and Data-Governance Requirements

Not Applicable directly.

## 16. Audit and Activity Events

Not Applicable directly in this work package.

## 17. Event, Queue, Notification, and Real-Time Requirements

This work package **is** the queue foundation every later notification/job work package depends on.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Horizon's own dashboard is the primary queue observability surface; WP-13-05 adds a lighter-weight health-check endpoint on top.

## 20. Testing Requirements

Integration test confirming a simple test job is dispatched, processed, and completes successfully via the `redis` queue connection.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record the Horizon installation and configuration in `.ai/engineering-baseline.md`.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Install `laravel/horizon` and run `horizon:install` | WP-09-01 complete | Package installed |
| TASK-02 | Configure `config/horizon.php` with Phase 1 defaults | TASK-01 | Configuration reviewed |
| TASK-03 | Switch `QUEUE_CONNECTION=redis` and verify a test job processes | TASK-02 | Test job completes |
| TASK-04 | Gate Horizon dashboard access | WP-05-07 complete | Unauthorized access denied |

## 24. Acceptance Criteria

- **AC-01:** Given the `redis` queue connection, when a test job is dispatched, then it is processed successfully by Horizon.
- **AC-02:** Given an unauthorized user, when they attempt to access the Horizon dashboard, then they are denied.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-09-01 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): test-job output, dashboard-access test output.

## 28. Rollback and Recovery Considerations

Reverting `QUEUE_CONNECTION` to `database` is possible if Horizon proves problematic, though this is not the intended path; per ADR-0008's feature-disablement discipline.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-09-02-01 | Horizon dashboard left unauthenticated/exposed | Critical | AC-02 explicitly tests denial | DevOps lead |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-09-02 — Queue and Horizon Baseline.

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
- Horizon dashboard must be authenticated and gated before this work package is considered done.
- Do not tune for production scale — Phase 1 defaults only.
```
