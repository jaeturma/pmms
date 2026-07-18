# WP-09-05 — Failed Job and Replay Governance Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-09-05 | Title | Failed Job and Replay Governance Foundation |
| Epic | EPIC-09 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P2 | Implementation sequence | 81 |
| Target release group | Foundation Release D | Pilot relevance | Required (pilot only) |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | DevOps lead |

## 2. Purpose

Documents and configures the failed-jobs table (Horizon's own, backed by `failed_jobs`) and a governance rule for manual replay — a failed job must never be silently retried indefinitely, and replay must be a deliberate, reviewed action, not automatic.

## 3. Architecture Sources

ADR-0008.

## 4. Scope

Configure Horizon's retry/backoff defaults conservatively (limited retries, exponential backoff); document the manual-replay governance rule (who may replay, via `php artisan horizon:*` CLI in Phase 1, no UI yet).

## 5. Explicit Exclusions

Does not implement an admin UI for failed-job management (CLI-only in Phase 1); does not implement automatic infinite-retry logic.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-09-02 | Hard |

## 7. Current-State Inspection

Horizon's default failed-job handling exists per WP-09-02, without Phase 1-specific retry/backoff tuning yet.

## 8. Proposed Implementation Direction

Configure conservative retry limits (proposed: 3 attempts, exponential backoff) at the job-class level; document the CLI-based replay governance process.

## 9. Database Changes

Database Changes: None (uses Horizon/Laravel's existing `failed_jobs` table).

## 10. Backend Requirements

Retry/backoff configuration per job class.

## 11. Web Frontend Requirements

Not Applicable in Phase 1.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

CLI-based replay requires server access, an implicit but coarse-grained authorization boundary in Phase 1 (formal in-app authorization deferred to a future admin UI).

## 14. Security Requirements

Failed-job payloads must not be replayed blindly without review, since the underlying condition that caused failure may still be relevant (e.g., a since-revoked authorization).

## 15. Privacy and Data-Governance Requirements

Failed-job payloads containing sensitive data must be retained only as long as operationally necessary (aligned with WP-06-08's retention-readiness posture).

## 16. Audit and Activity Events

Not Applicable directly in Phase 1 (CLI actions are not yet wired to the audit system).

## 17. Event, Queue, Notification, and Real-Time Requirements

This work package governs how EPIC-09's own failure mode is handled.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Failed-job counts feed WP-13-05's queue health signal.

## 20. Testing Requirements

Integration test confirming a job that exceeds its retry limit lands in `failed_jobs` rather than retrying indefinitely.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record the retry/backoff configuration and replay governance in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Configure retry/backoff defaults | WP-09-02 complete | Test confirms retry-limit behavior |
| TASK-02 | Document CLI-based replay governance | TASK-01 | Documented |

## 24. Acceptance Criteria

- **AC-01:** Given a job that fails repeatedly, when it exceeds its configured retry limit, then it lands in `failed_jobs` rather than retrying indefinitely.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-09-02 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Criterion verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): test output.

## 28. Rollback and Recovery Considerations

Configuration change, reversible.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-09-05-01 | A failed job is replayed blindly, re-triggering an already-invalid action | Medium | Documented governance rule requiring review before replay | DevOps lead |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-09-05 — Failed Job and Replay Governance Foundation.

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
- Do not implement automatic infinite retry.
- Do not implement an admin UI for failed jobs — CLI-only in Phase 1.
```
