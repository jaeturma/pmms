# WP-05-06 — Time-Valid Assignment Rules

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-05-06 | Title | Time-Valid Assignment Rules |
| Epic | EPIC-05 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 39 |
| Target release group | Foundation Release B | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | BC-02 Identity/Access |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Extends WP-05-05's assignment model with an optional effective-from/effective-until window (e.g., a role granted only for the duration of a specific meet), per [assignment-model.md](../../../../01-architecture/assignment-model.md)'s time-validity rules, so a temporary role (like an event-specific volunteer role) automatically stops granting authority without requiring a manual revocation.

## 3. Architecture Sources

[../../../../01-architecture/assignment-model.md](../../../../01-architecture/assignment-model.md).

## 4. Scope

Add `effective_from`/`effective_until` nullable columns to `assignments`; implement the time-validity check as part of the authority evaluation contract WP-05-07 will call.

## 5. Explicit Exclusions

Does not implement the Authorization Decision Service itself (WP-05-07); does not implement any scheduled job to proactively expire assignments (checked lazily at evaluation time in Phase 1, not swept).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-05-05 | Hard |

## 7. Current-State Inspection

`assignments` table exists per WP-05-05, without time-validity columns yet.

## 8. Proposed Implementation Direction

Add `effective_from`/`effective_until` to `assignments` via a follow-on migration; implement an `Assignment::isCurrentlyValid(): bool` method checking both revocation status and the time window against the current time.

## 9. Database Changes

Proposed migration adding `effective_from` (nullable, default null = immediately effective) and `effective_until` (nullable, default null = no expiry) to `assignments`.

## 10. Backend Requirements

`Assignment::isCurrentlyValid()` checks `revoked_at IS NULL AND (effective_from IS NULL OR effective_from <= now) AND (effective_until IS NULL OR effective_until >= now)`.

## 11. Web Frontend Requirements

Not Applicable in this work package.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

`isCurrentlyValid()` is the exact check WP-05-07's Authorization Decision Service calls before granting any authority.

## 14. Security Requirements

An expired assignment must never grant authority, even if `revoked_at` was never explicitly set — time validity is evaluated independently of revocation.

## 15. Privacy and Data-Governance Requirements

Not Applicable.

## 16. Audit and Activity Events

Not Applicable directly beyond WP-05-05's existing assignment audit events.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable — no proactive expiry notification in Phase 1 (lazy evaluation only).

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Unit tests: assignment with future `effective_from` is not yet valid; assignment past `effective_until` is no longer valid; assignment with no time bounds is valid indefinitely (until revoked).

## 21. Test Data Requirements

Extends `AssignmentFactory` with time-bounded states. Do not create standalone test data.

## 22. Documentation Updates

Record time-validity rule in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Add `effective_from`/`effective_until` columns via migration | WP-05-05 complete | Migration reversible |
| TASK-02 | Implement `isCurrentlyValid()` with all three time-validity scenarios tested | TASK-01 | Unit tests pass |

## 24. Acceptance Criteria

- **AC-01:** Given an assignment with `effective_from` in the future, when evaluated now, then it is not currently valid.
- **AC-02:** Given an assignment with `effective_until` in the past, when evaluated now, then it is not currently valid, even though `revoked_at` is null.
- **AC-03:** Given an assignment with no time bounds and no revocation, when evaluated, then it is currently valid.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-05-05 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). All three criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): test output for all three scenarios.

## 28. Rollback and Recovery Considerations

Migration reversible (drops the two new columns); existing assignments default to unbounded validity, preserving current behavior.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-05-06-01 | Lazy evaluation (no sweep job) means an expired assignment still exists as a queryable "active-looking" row until actually evaluated | Low | Documented as an accepted Phase 1 simplification; a sweep job is a future enhancement, not required for correctness since evaluation is always checked live | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-05-06 — Time-Valid Assignment Rules.

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
- Do not implement a scheduled sweep job — lazy evaluation only in Phase 1.
```
