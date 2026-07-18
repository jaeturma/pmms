# WP-09-06 — Scheduler and Heartbeat Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-09-06 | Title | Scheduler and Heartbeat Foundation |
| Epic | EPIC-09 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Small |
| Priority | P1 | Implementation sequence | 82 |
| Target release group | Foundation Release D | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | Cross-cutting |
| Secondary affected contexts | WP-08-03 | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | DevOps lead |

## 2. Purpose

Configures Laravel's task scheduler and registers the first real scheduled task — WP-08-03's orphaned-temporary-upload cleanup — giving PMMS its first working scheduled job and confirming the scheduler itself runs correctly.

## 3. Architecture Sources

Approved technology direction.

## 4. Scope

Configure `bootstrap/app.php`'s scheduling closure (Laravel 13 convention); register a `CleanupOrphanedUploadsCommand` (proposed, implementing WP-08-03's cleanup) running daily; document a scheduler heartbeat check.

## 5. Explicit Exclusions

Does not implement any other scheduled task beyond the one named above; does not implement a scheduler-monitoring service (WP-13-05's scope, consuming this work package's heartbeat).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-09-02, WP-08-03 | Hard |

## 7. Current-State Inspection

`routes/console.php` exists (Laravel 13 default); no scheduled tasks registered.

## 8. Proposed Implementation Direction

`CleanupOrphanedUploadsCommand` registered via `Schedule::command()` in `bootstrap/app.php`; a `scheduler:heartbeat` command (proposed) writing a timestamp to cache/Redis every run, consumed by WP-13-05.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

One console command implementing WP-08-03's cleanup logic; one heartbeat command.

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable — scheduled tasks run as a system actor, not a user context.

## 14. Security Requirements

Not Applicable beyond standard command-execution hygiene.

## 15. Privacy and Data-Governance Requirements

Not Applicable directly.

## 16. Audit and Activity Events

Cleanup actions may be recorded as a system-actor audit event once wired, following WP-06-04's system-actor convention.

## 17. Event, Queue, Notification, and Real-Time Requirements

This is the scheduler foundation every future scheduled task builds on.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Heartbeat write is the direct input to WP-13-05's scheduler health signal.

## 20. Testing Requirements

Unit test for `CleanupOrphanedUploadsCommand`'s logic; integration test confirming the heartbeat command writes correctly.

## 21. Test Data Requirements

Reuses `FileMetadataFactory` for cleanup-command testing. Do not create new test data.

## 22. Documentation Updates

Record the scheduler configuration and heartbeat mechanism in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement `CleanupOrphanedUploadsCommand` and register it in the scheduler | WP-09-02, WP-08-03 complete | Unit tests pass |
| TASK-02 | Implement `scheduler:heartbeat` command | TASK-01 | Integration test passes |

## 24. Acceptance Criteria

- **AC-01:** Given orphaned temporary uploads past the age threshold (per WP-08-03), when the cleanup command runs, then they are removed.
- **AC-02:** Given the heartbeat command, when it runs, then it writes a current timestamp readable by WP-13-05's future health check.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-09-02, WP-08-03 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): test output.

## 28. Rollback and Recovery Considerations

Scheduled task registration is reversible (remove from `bootstrap/app.php`).

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-09-06-01 | The system cron trigger (`php artisan schedule:run`) is never actually configured on the host, so the scheduler silently never runs | Medium | Documented as an environment precondition for WP-01-06/WP-13-05 to verify | DevOps lead |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-09-06 — Scheduler and Heartbeat Foundation.

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
- Do not implement any scheduled task beyond the cleanup and heartbeat commands specified.
```
