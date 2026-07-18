# WP-06-04 — Actor, Effective User, Device, Context, and Correlation Metadata

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-06-04 | Title | Actor, Effective User, Device, Context, and Correlation Metadata |
| Epic | EPIC-06 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 49 |
| Target release group | Foundation Release C | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | BC-32 Audit and Compliance |
| Secondary affected contexts | All | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Defines the shared metadata contract every one of WP-06-01/02/03's event tables must carry — actor (who initiated), effective user (distinct from actor during impersonation, not built in Phase 1 but the column exists for future readiness), device (WP-03-06's skeleton), organization/meet context (WP-04-05), and correlation ID (WP-02-08) — so all three event tables are structurally consistent rather than each inventing its own metadata shape.

## 3. Architecture Sources

ADR-0006, ADR-0012.

## 4. Scope

Add `organization_id`, `meet_id` (both nullable), `correlation_id` columns to all three event tables via follow-on migrations; document the actor/effective-user distinction (effective-user column added but always equal to actor in Phase 1, since impersonation is not built).

## 5. Explicit Exclusions

Does not implement impersonation itself; does not implement device-ID population (the column exists, populated null until WP-12-04 exists).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-06-01, WP-06-02, WP-06-03, WP-02-08, WP-04-05 | Hard |

## 7. Current-State Inspection

The three event tables exist per WP-06-01/02/03, without the shared metadata columns yet.

## 8. Proposed Implementation Direction

Three follow-on migrations (one per table) adding the same column set; a shared `HasEventMetadata` trait (proposed) providing consistent population logic across all three.

## 9. Database Changes

Proposed additions to `audit_events`, `activity_history`, `security_events`: `organization_id` (nullable FK), `meet_id` (nullable FK), `correlation_id`, `device_id` (nullable, populated null until EPIC-12), `effective_user_id` (nullable, defaults to `actor_id`/`subject_user_id`).

## 10. Backend Requirements

`HasEventMetadata` trait auto-populating `organization_id`/`meet_id` from `CurrentContext` (WP-04-05) and `correlation_id` from `CorrelationContext` (WP-02-08) at event-creation time.

## 11. Web Frontend Requirements

Not Applicable in this work package.

## 12. Flutter Requirements

Not Applicable directly.

## 13. Authorization and Access Control

Not Applicable directly.

## 14. Security Requirements

Metadata population must never fail silently — if context or correlation cannot be resolved, the event is still recorded (never blocked), but with an explicit null/unknown marker, not a guessed value.

## 15. Privacy and Data-Governance Requirements

Not Applicable beyond what WP-06-01/02/03 already established.

## 16. Audit and Activity Events

This work package defines the contract WP-06-06's recording service implements against.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable directly.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

`correlation_id` on every event is the direct link back to the originating request/job log entry (WP-13-02 integration point).

## 20. Testing Requirements

Unit tests: metadata correctly populated from context when available; metadata gracefully null (not erroring) when context unavailable (e.g., a system/CLI-initiated event).

## 21. Test Data Requirements

Extends existing factories (`AuditEventFactory`, etc.) with metadata states. Do not create new test data.

## 22. Documentation Updates

Record the shared metadata contract in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Add shared metadata columns to all three event tables via migrations | WP-06-01..03 complete | Migrations reversible |
| TASK-02 | Implement `HasEventMetadata` trait | WP-02-08, WP-04-05 complete | Unit tests pass |

## 24. Acceptance Criteria

- **AC-01:** Given an event created within a resolved request context, when recorded, then `organization_id`, `meet_id`, and `correlation_id` are correctly populated.
- **AC-02:** Given an event created by a system/CLI process with no resolvable context, when recorded, then metadata fields are null rather than the event creation failing.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-06-01, WP-06-02, WP-06-03, WP-02-08, WP-04-05 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified across all three event tables.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): migration status, test output, example populated event record (redacted).

## 28. Rollback and Recovery Considerations

Migrations reversible (drop the added columns); no existing event data at this point in the sequence.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-06-04-01 | Metadata population failure blocks event recording entirely, causing a silent audit gap | Critical | AC-02 explicitly tests the no-context case does not fail | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-06-04 — Actor, Effective User, Device, Context, and Correlation Metadata.

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
- Metadata population failure must never block event recording — populate null, do not fail the operation.
- Do not implement impersonation.
```
