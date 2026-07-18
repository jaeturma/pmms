# WP-14-04 — Secure Export Readiness

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-14-04 | Title | Secure Export Readiness |
| Epic | EPIC-14 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Small |
| Priority | P2 | Implementation sequence | 137 |
| Target release group | Foundation Release E | Pilot relevance | Required (pilot only) |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | Cross-cutting |
| Secondary affected contexts | All | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Establishes the export conventions later modules must follow — classification-aware field inclusion, authorization gating, export audit — as readiness (contracts and guidance), before any real export feature exists, so the first module to ship an export inherits safe defaults rather than inventing them under deadline pressure.

## 3. Architecture Sources

[../../../02-data/import-export-and-data-exchange.md](../../../02-data/import-export-and-data-exchange.md), [../../../03-security/data-sharing-export-and-public-disclosure-controls.md](../../../03-security/data-sharing-export-and-public-disclosure-controls.md), ADR-0005, ADR-0006.

## 4. Scope

An export-definition contract (proposed: interface declaring exportable fields with their WP-14-01 classifications and the permission required); a base export service skeleton enforcing: authorization check via WP-05-07 before generation, classification-tier ceiling per export (fields above the ceiling are excluded, not masked-in), mandatory WP-06-05 export-audit event; conventions documentation. One reference implementation against a foundation model (proposed: organization list export) to prove the pattern.

## 5. Explicit Exclusions

Does not implement domain exports (athlete lists, results, medical — future modules); does not implement import (separate architecture); does not implement scheduled/bulk export infrastructure; does not decide retention of generated export files (PD-04 open decision — generated exports are transient in Phase 1).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-14-01 | Hard |
| WP-05-07 | Hard (authorization gating) |
| WP-06-06 | Hard (export audit events) |

## 7. Current-State Inspection

No export capability exists in the starter kit.

## 8. Proposed Implementation Direction

Contract + abstract service in the shared kernel; CSV as the single Phase 1 format (proposed); reference export wired to an existing authorized listing.

## 9. Database Changes

Database Changes: None.

## 10. Backend Requirements

Contract, base service (authorize → filter by ceiling → generate → audit), reference implementation, conventions.

## 11. Web Frontend Requirements

A minimal download trigger on the reference listing using existing EPIC-11 components — no new export UI framework.

## 12. Flutter Requirements

Not Applicable — no mobile export in Phase 1.

## 13. Authorization and Access Control

No export generates without a passing WP-05-07 decision for the export-specific permission; scope rules apply (a user exports only what their scope lets them see).

## 14. Security Requirements

Fields above the export's classification ceiling are excluded entirely (absent columns), never included masked — masked values in files leak format and existence; generated files are delivered via authorized download conventions (WP-08-06 patterns where files persist, direct streaming otherwise).

## 15. Privacy and Data-Governance Requirements

Every export carries a recorded purpose (free-text or coded reason captured at request time) per the data-sharing controls; personal-data exports are the audit events reviewers will scrutinize (WP-15-04).

## 16. Audit and Activity Events

Mandatory export-audit event per WP-06-05: who, what definition, ceiling, row count, purpose, correlation ID.

## 17. Event, Queue, Notification, and Real-Time Requirements

Synchronous generation only in Phase 1 (small exports); queued export generation is a future decision.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Export generation logs through the structured channel with correlation ID.

## 20. Testing Requirements

Tests: unauthorized export blocked; above-ceiling fields absent from output; audit event recorded with required fields; reference export produces correct CSV for authorized scope only.

## 21. Test Data Requirements

Synthetic organizations via existing factories.

## 22. Documentation Updates

Record the export contract, ceiling rule, and audit requirement in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement export contract and base service | WP-14-01, WP-05-07, WP-06-06 complete | Unit tests pass |
| TASK-02 | Implement reference export with audit | TASK-01 | Feature tests pass |
| TASK-03 | Document conventions | TASK-01 | Recorded per Section 22 |

## 24. Acceptance Criteria

- **AC-01:** Given a user without the export permission, when they request the reference export, then generation is blocked and no file is produced.
- **AC-02:** Given an export definition with a classification ceiling, when generated, then fields above the ceiling are entirely absent from the output.
- **AC-03:** Given any completed export, when audit records are checked, then an export event exists with actor, definition, ceiling, row count, purpose, and correlation ID.

## 25. Definition of Ready

Per [../../phase-1-definition-of-ready.md](../../phase-1-definition-of-ready.md). WP-14-01, WP-05-07, WP-06-06 complete.

## 26. Definition of Done

Per [../../phase-1-definition-of-done.md](../../phase-1-definition-of-done.md). All three criteria verified.

## 27. Completion Evidence

Per [../../phase-1-completion-evidence-standard.md](../../phase-1-completion-evidence-standard.md): reference export sample, audit-event evidence, test output.

## 28. Rollback and Recovery Considerations

Additive contract + one reference feature; removal restores prior state.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-14-04-01 | A future module ships an ad-hoc export bypassing the contract | High | Conventions named in the DoD checklist for any WP touching exports; WP-15-04 review sweeps for `Response::streamDownload`/CSV generation outside the contract | Security reviewer |

## 30. Open Decisions

| Decision ID | Question | Blocking Status |
|---|---|---|
| DEC-WP-14-04-01 | Retention of generated export files (tied to PD-04) | Non-blocking — exports transient in Phase 1 |

## 31. Future Claude Code Execution Prompt

```text
Implement WP-14-04 — Secure Export Readiness.

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
- Above-ceiling fields are excluded, never included masked.
- No export without a passing authorization decision and a recorded audit event.
- One reference export only — no domain exports.
```
