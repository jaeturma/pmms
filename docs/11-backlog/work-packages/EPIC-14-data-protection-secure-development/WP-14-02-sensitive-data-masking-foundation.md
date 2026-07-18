# WP-14-02 — Sensitive Data Masking Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-14-02 | Title | Sensitive Data Masking Foundation |
| Epic | EPIC-14 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 135 |
| Target release group | Foundation Release E | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | Cross-cutting (shared kernel) |
| Secondary affected contexts | All | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Implements the masking service that renders classified values safely for display and diagnostics (e.g., `j***@example.com`, `09** *** **12`) using the WP-14-01 vocabulary — so every later surface that must show "something without everything" (support views, audit review, diagnostics) reuses one tested implementation.

## 3. Architecture Sources

[../../../03-security/](../../../03-security/) (sensitive-data controls), [../../../06-design/privacy-security-and-sensitive-data-experience.md](../../../06-design/privacy-security-and-sensitive-data-experience.md), ADR-0006.

## 4. Scope

A masking service in the shared kernel (proposed: `App\SharedKernel\Classification\Masker`) with per-type strategies (email, phone, name, generic string, date) keyed by classification tier; deterministic, non-reversible output; helper integration for classified attributes (mask an attribute according to its WP-14-01 classification); documented usage guidance.

## 5. Explicit Exclusions

Does not implement log redaction (WP-14-03 — a different pipeline with different requirements); does not implement encryption or hashing at rest (persistence rules govern that separately); does not decide which UI screens show masked versus full values (each module's authorization + design decision, using this service); does not implement unmasking/reveal flows.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-14-01 | Hard |

## 7. Current-State Inspection

No masking utilities exist in the starter kit.

## 8. Proposed Implementation Direction

Strategy-per-type masking with a single entry point; pure functions, no state; property-based tests for non-reversibility characteristics where practical.

## 9. Database Changes

Database Changes: None — masking is a presentation/diagnostic transform, never a storage transform.

## 10. Backend Requirements

Masking service, per-type strategies, classified-attribute helper, guidance.

## 11. Web Frontend Requirements

Not Applicable in this work package — masked values arrive already masked from the server; the frontend must never receive full values with instructions to mask client-side.

## 12. Flutter Requirements

Not Applicable — same server-side rule applies to mobile responses.

## 13. Authorization and Access Control

Masking is not authorization: a masked value may still be sensitive in aggregate. Callers must authorize access first, then decide masked/full presentation — guidance states this explicitly.

## 14. Security Requirements

Masked output must be non-reversible (no length-preserving full leaks, no reversible encoding); masking failures fail closed (return fully-masked placeholder, never the raw value).

## 15. Privacy and Data-Governance Requirements

Strategies must handle the categories the Phase 0.6 corpus names as most sensitive — minor-athlete identity, medical, guardian contact — even though those modules arrive later; the strategy set is the reusable safeguard.

## 16. Audit and Activity Events

Not Applicable — WP-06-05 governs when viewing masked/full data is audited.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable directly — WP-14-03 owns log-side treatment.

## 20. Testing Requirements

Unit tests per strategy (format, non-reversibility, edge cases: empty, unicode, very short values); fail-closed test (strategy error yields placeholder, not raw value); helper test (attribute masked per its classification).

## 21. Test Data Requirements

Synthetic values only.

## 22. Documentation Updates

Record strategies, entry point, and the "authorize first, mask second" rule in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement masking service and strategies | WP-14-01 complete | Strategy unit tests pass |
| TASK-02 | Implement classified-attribute helper | TASK-01 | Helper test passes |
| TASK-03 | Verify fail-closed behavior | TASK-01 | Fail-closed test passes |

## 24. Acceptance Criteria

- **AC-01:** Given each supported type, when masked, then output matches the documented format and cannot reconstruct the input.
- **AC-02:** Given a strategy failure (simulated), when masking is requested, then a fully-masked placeholder is returned — never the raw value.
- **AC-03:** Given a classified attribute, when the helper renders it, then the strategy applied corresponds to its WP-14-01 classification.

## 25. Definition of Ready

Per [../../phase-1-definition-of-ready.md](../../phase-1-definition-of-ready.md). WP-14-01 complete.

## 26. Definition of Done

Per [../../phase-1-definition-of-done.md](../../phase-1-definition-of-done.md). All three criteria verified.

## 27. Completion Evidence

Per [../../phase-1-completion-evidence-standard.md](../../phase-1-completion-evidence-standard.md): strategy test output, fail-closed evidence.

## 28. Rollback and Recovery Considerations

Pure code additions; no data migration.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-14-02-01 | A later screen sends full values to the client and masks in JavaScript | High | Server-side-only rule documented here and enforced by WP-10-04's prop-minimization review plus WP-14-10 regression | Security reviewer |
| RISK-WP-14-02-02 | Masking mistaken for authorization, exposing masked-but-sensitive aggregates | Medium | "Authorize first, mask second" guidance; WP-15-03/04 reviews check usage | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-14-02 — Sensitive Data Masking Foundation.

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
- Masking is server-side only — never ship full values to a client for client-side masking.
- Fail closed: on any masking error, return a placeholder, never the raw value.
- Masking is not authorization — do not present it as an access control.
```
