# WP-13-09 — Operational Diagnostics Interface

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-13-09 | Title | Operational Diagnostics Interface |
| Epic | EPIC-13 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P2 | Implementation sequence | 132 |
| Target release group | Foundation Release E | Pilot relevance | Required (pilot only) |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Engineering lead, Security reviewer |

## 2. Purpose

Gives authorized operators (meet ICT committee, system administrators) one authenticated screen showing the WP-13-04 readiness surface — application, database, Redis, queue, scheduler, Reverb, storage — so meet-day diagnosis does not require terminal access, per ADR-0008's operational-readiness intent and the committee/ICT experience architecture.

## 3. Architecture Sources

[../../../05-devops/](../../../05-devops/), [../../../06-design/committee-logistics-medical-finance-and-support-experience.md](../../../06-design/committee-logistics-medical-finance-and-support-experience.md), ADR-0008, ADR-0009.

## 4. Scope

An authenticated, authorization-gated Inertia page (proposed: under a system-administration route group) rendering the WP-13-04 registry output: per-check status, latency, last-checked time, aggregate state; manual refresh; permission-gated via a dedicated permission through WP-05-07; built from EPIC-11 components.

## 5. Explicit Exclusions

Does not implement historical trends, charts, or metric storage (future operational analytics); does not implement remediation actions (restart worker, replay job — WP-09-05's governance covers replay separately); does not expose Horizon's own dashboard decisions (WP-09-02 owns Horizon access rules); does not build alerting.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-13-03 through WP-13-08 | Hard |
| WP-05-07 | Hard (permission gating) |
| WP-10-01..09, WP-11-01..11 | Hard (page conventions and components) |

## 7. Current-State Inspection

No operational interface exists; readiness data is endpoint-only after WP-13-04..07.

## 8. Proposed Implementation Direction

Inertia page (proposed: `resources/js/pages/system/diagnostics.tsx`) consuming a controller that runs the health registry; permission (proposed: `system.diagnostics.view`) seeded via the WP-05-02 catalog conventions.

## 9. Database Changes

Database Changes: None beyond the proposed permission row via existing WP-05-02 seeding conventions.

## 10. Backend Requirements

Controller exposing registry results to the page; permission gate; no state mutation.

## 11. Web Frontend Requirements

One page using EPIC-11 components (status badges, table, page header, empty/error states per WP-11-11); no new component primitives.

## 12. Flutter Requirements

Not Applicable — diagnostics is a web/desk capability in Phase 1.

## 13. Authorization and Access Control

Access requires the dedicated permission through the WP-05-07 Authorization Decision Service; unauthorized users receive the standard permission-denied state (WP-10-09); the page never renders for unauthenticated sessions.

## 14. Security Requirements

The page shows check names, statuses, latencies, and timestamps only — the same no-connection-detail rule as WP-13-04; view access to diagnostics is itself a sensitive capability (reveals infrastructure posture) and is granted narrowly.

## 15. Privacy and Data-Governance Requirements

Not Applicable — no personal data on this surface.

## 16. Audit and Activity Events

Viewing diagnostics is recorded per the sensitive-view audit conventions (WP-06-05) — infrastructure posture is operationally sensitive.

## 17. Event, Queue, Notification, and Real-Time Requirements

Manual refresh only in Phase 1 — no live push of health state (would itself depend on Reverb being healthy).

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

This is the human-facing surface of the EPIC-13 readiness framework.

## 20. Testing Requirements

Feature tests: authorized user sees per-check statuses; unauthorized user gets permission-denied; unauthenticated request is redirected; response contains no connection detail; sensitive-view audit event recorded.

## 21. Test Data Requirements

Standard synthetic users/permissions via existing factories from EPIC-03/05.

## 22. Documentation Updates

Record the diagnostics route, permission, and audit behavior in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Controller exposing health-registry results | WP-13-04..08 complete | Registry output rendered |
| TASK-02 | Permission-gated Inertia page with EPIC-11 components | TASK-01, WP-11 components available | Authorized/unauthorized tests pass |
| TASK-03 | Wire sensitive-view audit event | TASK-02 | Audit event test passes |

## 24. Acceptance Criteria

- **AC-01:** Given an authorized operator, when the diagnostics page loads, then every registered health check appears with status, latency, and last-checked time.
- **AC-02:** Given a user without the diagnostics permission, when they request the page, then they receive the standard permission-denied state and no health data.
- **AC-03:** Given any rendered state, when the page output is inspected, then it contains no hostnames, connection strings, or credentials.
- **AC-04:** Given an authorized view, when it occurs, then a sensitive-view audit event is recorded per WP-06-05.

## 25. Definition of Ready

Per [../../phase-1-definition-of-ready.md](../../phase-1-definition-of-ready.md). WP-13-03..08, WP-05-07, and the required EPIC-10/11 work packages complete.

## 26. Definition of Done

Per [../../phase-1-definition-of-done.md](../../phase-1-definition-of-done.md). All four criteria verified.

## 27. Completion Evidence

Per [../../phase-1-completion-evidence-standard.md](../../phase-1-completion-evidence-standard.md): page screenshot/capture, authorization test output, audit-event evidence.

## 28. Rollback and Recovery Considerations

Additive page + permission; removal restores prior state. Permission row removal via standard seeding conventions.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-13-09-01 | Diagnostics permission granted broadly out of convenience, exposing infrastructure posture | Medium | Narrow-grant guidance in the permission catalog; WP-15-03 authorization review checks grants | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-13-09 — Operational Diagnostics Interface.

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
- Read-only interface — no remediation actions, no state mutation.
- No connection detail on the page — statuses, latencies, and timestamps only.
- Access must be permission-gated through the Authorization Decision Service and audited as a sensitive view.
```
