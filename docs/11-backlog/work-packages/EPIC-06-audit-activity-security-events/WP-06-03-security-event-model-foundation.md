# WP-06-03 — Security Event Model Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-06-03 | Title | Security Event Model Foundation |
| Epic | EPIC-06 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 48 |
| Target release group | Foundation Release C | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | BC-32 Audit and Compliance |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Implements `security_events` — the third and final distinct event model, for security-relevant occurrences (failed logins, authorization denials worth tracking, security-hold changes) that are neither a business-data change (audit) nor a user-facing activity (activity history), consumed by WP-03-07's authentication listeners as the first real producer.

## 3. Architecture Sources

ADR-0006.

## 4. Scope

Implement `security_events` table (id, event_type, severity, subject_user_id nullable, ip_address, user_agent, details, occurred_at).

## 5. Explicit Exclusions

Does not implement alerting/notification on security events (a future operational concern); does not implement the recording service itself (WP-06-06).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-02-05 | Hard |

## 7. Current-State Inspection

No security-event table exists.

## 8. Proposed Implementation Direction

Proposed `App\Domain\Audit\SecurityEvent` entity; `security_events` table, append-only, with a `severity` enum (Low/Medium/High/Critical, matching Phase 0.13's severity scale for consistency).

## 9. Database Changes

Proposed `security_events` table: `id`, `event_type`, `severity`, `subject_user_id` (FK, nullable), `ip_address`, `user_agent`, `details` (JSON, nullable), `occurred_at`, `created_at` (no `updated_at`/`deleted_at`). Index: `severity`, `subject_user_id`, `occurred_at`.

## 10. Backend Requirements

Entity shell only in this work package.

## 11. Web Frontend Requirements

Not Applicable in this work package.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable directly — read access is WP-06-08's scope.

## 14. Security Requirements

`details` JSON must never contain a raw password or credential value (same discipline as WP-03-07's failed-login listener).

## 15. Privacy and Data-Governance Requirements

`ip_address`/`user_agent` are classified per WP-14-01 once it exists; masking applies per WP-14-02.

## 16. Audit and Activity Events

This work package **is** the Security Event model itself.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable in this work package.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

High/Critical severity events are candidates for future alerting (WP-13-08 integration point, not built here).

## 20. Testing Requirements

Unit test confirming append-only structure; test confirming no credential value can be stored in `details` (a validation guard, if feasible, or a documented convention if not enforceable at the schema level).

## 21. Test Data Requirements

New `SecurityEventFactory` (proposed).

## 22. Documentation Updates

Record the model and severity scale in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Design and implement `security_events` migration | WP-02-05 complete | Migration reversible |
| TASK-02 | Implement `SecurityEvent` entity | TASK-01 | Unit tests pass |

## 24. Acceptance Criteria

- **AC-01:** Given the `security_events` table, when inspected, then it has no `updated_at` or `deleted_at` column.
- **AC-02:** Given the `severity` field, when populated, then only Low/Medium/High/Critical values are accepted.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-02-05 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): migration status, test output.

## 28. Rollback and Recovery Considerations

Migration reversible; no existing data.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-06-03-01 | A future listener stores a credential value in `details` | Critical | Code review discipline plus WP-15-09 security review explicitly checks producers of this table | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-06-03 — Security Event Model Foundation.

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
- Never allow a credential value to be stored in the details field.
```
