# WP-06-02 — Activity History Model Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-06-02 | Title | Activity History Model Foundation |
| Epic | EPIC-06 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 47 |
| Target release group | Foundation Release C | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | BC-32 Audit and Compliance |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Domain reviewer |

## 2. Purpose

Implements `activity_history` — a human-readable, user-facing timeline entry ("You granted the Registrar role to Jane Cruz"), distinct from the machine-oriented, before/after-state `audit_events` table (WP-06-01). Activity History is meant to be displayed to users; Audit Events are meant for compliance/investigation.

## 3. Architecture Sources

ADR-0006.

## 4. Scope

Implement `activity_history` table (id, actor_id, summary_text, entity_type, entity_id, occurred_at) — a lighter-weight, display-oriented record, generated alongside (not instead of) an audit event.

## 5. Explicit Exclusions

Does not implement the UI displaying this timeline (a later, non-Phase-1 module); does not replace `audit_events`.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-02-05 | Hard |

## 7. Current-State Inspection

No activity-history table exists.

## 8. Proposed Implementation Direction

Proposed `App\Domain\Audit\ActivityHistoryEntry` entity; `activity_history` table, append-only like `audit_events` but with a human-readable `summary_text` rather than raw before/after JSON.

## 9. Database Changes

Proposed `activity_history` table: `id`, `actor_id` (FK to users, nullable for system actions), `summary_text`, `entity_type`, `entity_id`, `occurred_at`, `created_at` (no `updated_at`/`deleted_at`). Index: (`entity_type`, `entity_id`), `actor_id`.

## 10. Backend Requirements

Entity shell only in this work package; WP-06-06's recording service produces both an audit event and an activity-history entry together for user-facing actions.

## 11. Web Frontend Requirements

Not Applicable in this work package.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable directly — read access to a user's own activity history vs. others' is a future UI concern.

## 14. Security Requirements

`summary_text` must be generated from a safe template, never directly interpolating unescaped user input (XSS-adjacent concern once displayed).

## 15. Privacy and Data-Governance Requirements

`summary_text` must not leak more detail than the viewing user is entitled to see — enforced at read-time by whichever future UI displays it, not by this table's structure.

## 16. Audit and Activity Events

This work package **is** the Activity History model itself.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable in this work package.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Unit test confirming append-only structure; test confirming `summary_text` generation is template-based, not raw concatenation.

## 21. Test Data Requirements

New `ActivityHistoryEntryFactory` (proposed).

## 22. Documentation Updates

Record the model in `.ai/architecture.md` addendum, restating the audit-vs-activity distinction.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Design and implement `activity_history` migration | WP-02-05 complete | Migration reversible |
| TASK-02 | Implement `ActivityHistoryEntry` entity with template-based summary generation | TASK-01 | Unit tests pass |

## 24. Acceptance Criteria

- **AC-01:** Given the `activity_history` table, when inspected, then it has no `updated_at` or `deleted_at` column.
- **AC-02:** Given `summary_text` generation, when tested with an input containing HTML-special characters, then the resulting text is safely escapable by the eventual frontend renderer (no raw unescaped injection point created here).

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
| RISK-WP-06-02-01 | A future UI renders `summary_text` without escaping, creating an XSS vector | Medium | WP-15-09 security review checks the eventual rendering path once built | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-06-02 — Activity History Model Foundation.

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
- Do not implement the display UI — model only.
- summary_text generation must be template-based, never raw string concatenation of user input.
```
