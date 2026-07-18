# WP-06-01 — Audit Event Model Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-06-01 | Title | Audit Event Model Foundation |
| Epic | EPIC-06 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 46 |
| Target release group | Foundation Release C | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | BC-32 Audit and Compliance |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Implements the `audit_events` table — an append-only record of "what changed and who changed it," distinct from Activity History (WP-06-02, a user-facing timeline) and Security Events (WP-06-03, security-relevant occurrences) — per ADR-0006's explicit three-way distinction, the "most consistently and repeatedly restated data rule" in the entire Phase 0 corpus per Phase 0.13's data review.

## 3. Architecture Sources

[../../../../03-security/](../../../../03-security/), ADR-0005, ADR-0006, [../../../10-review/data-database-history-and-persistence-review.md](../../../10-review/data-database-history-and-persistence-review.md).

## 4. Scope

Implement `audit_events` table (id, event_type, entity_type, entity_id, before_state nullable, after_state nullable, occurred_at) — append-only, no update/delete capability at the application layer.

## 5. Explicit Exclusions

Does not implement Activity History (WP-06-02) or Security Events (WP-06-03) — three distinct tables; does not implement the recording service (WP-06-06); does not implement actor/context metadata (WP-06-04) — this work package builds the bare event shell only.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-02-05 | Hard |

## 7. Current-State Inspection

No audit table exists in the starter kit.

## 8. Proposed Implementation Direction

Proposed `App\Domain\Audit\AuditEvent` entity; `audit_events` table with no `updated_at` column at all (append-only enforced structurally, not just by convention) and no soft-delete.

## 9. Database Changes

Proposed `audit_events` table: `id`, `event_type`, `entity_type`, `entity_id`, `before_state` (JSON, nullable), `after_state` (JSON, nullable), `occurred_at`, `created_at` (no `updated_at`, no `deleted_at`). Index: (`entity_type`, `entity_id`), `occurred_at`. Ownership: Audit and Compliance context, per PD-XX's cross-context audit-write pattern (every context writes here, none owns the writing context's business data).

## 10. Backend Requirements

Entity is intentionally minimal in this work package — no application-layer command yet (WP-06-06 provides the actual recording API).

## 11. Web Frontend Requirements

Not Applicable in this work package.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable directly — who may *read* audit events is WP-06-08's scope.

## 14. Security Requirements

The table structure itself must make update/delete impossible at the Eloquent-model layer (no `updated_at` timestamp, `Model::preventSilentlyDiscardingAttributes()` and no delete method exposed on the entity).

## 15. Privacy and Data-Governance Requirements

`before_state`/`after_state` JSON payloads may contain sensitive data — classification and masking rules apply once WP-14-01/02 exist; this work package's schema supports but does not yet enforce that.

## 16. Audit and Activity Events

This work package **is** the Audit Event model itself.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable in this work package.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable directly.

## 20. Testing Requirements

Unit test confirming the entity has no update/delete capability; migration test.

## 21. Test Data Requirements

New `AuditEventFactory` (proposed) for future test use by other work packages.

## 22. Documentation Updates

Record the three-way distinction (audit/activity/security) and this table's shape in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Design and implement `audit_events` migration (no update/delete columns) | WP-02-05 complete | Migration reversible |
| TASK-02 | Implement `AuditEvent` entity with structurally-enforced immutability | TASK-01 | Unit test confirms no update/delete method |

## 24. Acceptance Criteria

- **AC-01:** Given the `audit_events` table, when inspected, then it has no `updated_at` or `deleted_at` column.
- **AC-02:** Given the `AuditEvent` entity, when its public API is reviewed, then no update or delete method exists.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-02-05 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): migration status, entity API review.

## 28. Rollback and Recovery Considerations

Migration reversible (drops table); no existing data.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-06-01-01 | A future work package conflates this table with Activity History, storing user-facing timeline entries here | High | AC-01/AC-02 plus WP-15-04's review explicitly re-check this distinction | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-06-01 — Audit Event Model Foundation.

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
- Table and entity must be structurally append-only — no update or delete capability.
- Do not conflate this with Activity History or Security Events — three distinct models.
```
