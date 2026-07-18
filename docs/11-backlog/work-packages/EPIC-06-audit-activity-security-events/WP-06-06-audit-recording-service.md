# WP-06-06 — Audit Recording Service

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-06-06 | Title | Audit Recording Service |
| Epic | EPIC-06 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Large |
| Priority | P1 | Implementation sequence | 51 |
| Target release group | Foundation Release C | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | BC-32 Audit and Compliance |
| Secondary affected contexts | All | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Implements the single canonical recording service every other context calls to write an audit event, activity-history entry, and/or security event — the concrete API WP-03-07's authentication listeners and every future context's domain-event listeners consume, ensuring consistent metadata population (WP-06-04) across the entire application rather than each context writing to the tables directly.

## 3. Architecture Sources

ADR-0006.

## 4. Scope

Implement `AuditRecorder::recordAuditEvent()`, `recordActivity()`, `recordSecurityEvent()` (proposed API) — each auto-populating WP-06-04's shared metadata; retrofit WP-03-07's listeners (built earlier in the sequence, before this service existed) to use it.

## 5. Explicit Exclusions

Does not implement the query/review interface (WP-06-07); does not implement retention enforcement (WP-06-08).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-06-01, WP-06-02, WP-06-03, WP-06-04, WP-06-05 | Hard |

## 7. Current-State Inspection

WP-03-07's listeners were built earlier in sequence with direct references to WP-06-01/06-03's tables — this work package's completion requires retrofitting those listeners to call the new service instead.

## 8. Proposed Implementation Direction

Proposed `App\Domain\Audit\AuditRecorder` service, injected wherever recording is needed; a single service class with three focused methods, not a generic "record anything" method (per WP-02-06's dependency-rule discipline — explicit is better than magic).

## 9. Database Changes

Database Changes: None (writes to existing tables from WP-06-01/02/03).

## 10. Backend Requirements

`AuditRecorder` service; retrofit of WP-03-07's listeners to call it instead of writing directly.

## 11. Web Frontend Requirements

Not Applicable in this work package.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable directly — recording itself requires no special authorization (every context may record, subject to WP-06-05's convention for what's worth recording).

## 14. Security Requirements

Recording must never throw an exception that aborts the calling operation — a failed audit write must be logged as its own error (WP-13-08) but must not block the underlying business action from completing (recording failure should not become a denial-of-service vector against the application itself), while still being treated as a serious operational concern requiring escalation.

## 15. Privacy and Data-Governance Requirements

The service applies WP-06-05's sensitive-view/export convention consistently.

## 16. Audit and Activity Events

This work package **is** the recording mechanism for all three event types.

## 17. Event, Queue, Notification, and Real-Time Requirements

Recording may optionally be queued for high-volume scenarios (documented as a future optimization, synchronous by default in Phase 1 for simplicity and immediate consistency).

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

A failed recording attempt is itself logged via WP-13-01 with high severity.

## 20. Testing Requirements

Unit tests for all three recording methods; integration test confirming WP-03-07's listeners now use this service; test confirming a simulated recording failure does not abort the calling operation but is logged.

## 21. Test Data Requirements

Reuses existing factories. Do not create new test data.

## 22. Documentation Updates

Record the service's API and retrofit in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement `AuditRecorder` service with three recording methods | WP-06-01..05 complete | Unit tests pass |
| TASK-02 | Retrofit WP-03-07's listeners to use the service | TASK-01 | WP-03-07's existing tests still pass |
| TASK-03 | Implement non-blocking failure handling | TASK-01 | Simulated-failure test passes |

## 24. Acceptance Criteria

- **AC-01:** Given a call to `recordAuditEvent()`, when it succeeds, then the event is correctly persisted with full metadata.
- **AC-02:** Given a simulated database failure during recording, when it occurs, then the calling operation still completes successfully, and the failure is logged separately.
- **AC-03:** Given WP-03-07's listeners after retrofit, when the full EPIC-03 test suite is re-run, then it still passes.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-06-01 through WP-06-05 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). All three criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): test output for all three criteria, before/after diff of WP-03-07's listeners.

## 28. Rollback and Recovery Considerations

The retrofit of WP-03-07's listeners is reversible (revert to direct table writes) if the service introduces a regression, though this is not the intended path.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-06-06-01 | Non-blocking failure handling is implemented too permissively, silently swallowing recording failures without adequate escalation | High | AC-02 requires the failure to be logged, not merely swallowed; WP-15-04 reviews this explicitly | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-06-06 — Audit Recording Service.

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
- Recording failure must never abort the calling business operation, but must always be logged, never silently swallowed.
- Retrofit WP-03-07's listeners; do not leave two parallel recording mechanisms.
```
