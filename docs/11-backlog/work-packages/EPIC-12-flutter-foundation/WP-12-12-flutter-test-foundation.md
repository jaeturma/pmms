# WP-12-12 — Flutter Test Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-12-12 | Title | Flutter Test Foundation |
| Epic | EPIC-12 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P2 | Implementation sequence | 123 |
| Target release group | Foundation Release E | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Engineering lead, Quality reviewer |

## 2. Purpose

Establishes the Flutter test foundation — unit, widget, and integration-test conventions, a coverage baseline, and quality-gate wiring — closing out EPIC-12 the same way WP-03-08, WP-05-12, WP-09-12, and WP-11-13 close out their epics: with an epic-level verification work package that later mobile modules inherit rather than reinvent. This is the Release E validation checkpoint for the mobile foundation named in [../../phase-1-execution-sequence.md, Section 5](../../phase-1-execution-sequence.md#5-validation-checkpoints).

## 3. Architecture Sources

[../../../01-architecture/testing-architecture.md](../../../01-architecture/testing-architecture.md), [../../../04-quality/](../../../04-quality/), ADR-0007, ADR-0009. Note: QD-02 (Flutter test-framework selection) remains formally open from Phase 0.7; this work package proceeds on the framework baseline established in WP-01-05/WP-12-01 and records the confirmed selection.

## 4. Scope

Establish `test/` directory conventions mirroring WP-12-02's feature-folder structure; unit tests for WP-12-03 (environment/API configuration), WP-12-04 (secure storage wrapper, mocked), WP-12-08 (connectivity-state transitions), and WP-12-11 (logging redaction — re-verifying its AC-01 as a standing regression); widget tests for WP-12-05 (authentication shell states) and WP-12-06 (assignment-aware home skeleton states: loading, empty, populated, permission-limited); a semantics/accessibility smoke check on the WP-12-07 theme (contrast tokens, tap-target sizes) under the provisional WCAG 2.1 AA assumption (DEC-GENERAL-01); a documented, repeatable `flutter test` invocation with coverage output; and a recorded coverage baseline for the foundation code.

## 5. Explicit Exclusions

Does not implement device-farm, emulator-matrix, or platform-channel integration testing infrastructure (a future, separately-authorized operational decision); does not test offline-store or sync-queue behavior beyond the architectural-skeleton surface WP-12-09/WP-12-10 actually created (their spike outcomes govern later, deeper tests); does not set a numeric coverage *target* (quality thresholds remain a Phase 0.7 open decision) — it records a baseline only; does not create CI workflow YAML (deferred per EPIC-01, WP-01-07 convention).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-12-01 through WP-12-11 | Hard |
| DX-01 (WCAG conformance target) | Soft — provisional WCAG 2.1 AA assumption per DEC-GENERAL-01 |

## 7. Current-State Inspection

No Flutter project exists in the repository today (`mobile/` is absent — RISK-EPIC12-01); by the time this work package starts, WP-12-01 through WP-12-11 will have created the project and its foundation features. This work package must inspect what those work packages actually produced, not what they proposed.

## 8. Proposed Implementation Direction

`mobile/test/` mirroring `lib/` feature folders (proposed); shared test helpers under `test/support/` (proposed) for fakes of the WP-12-03 API client and WP-12-04 secure-storage wrapper; `flutter test --coverage` as the documented invocation.

## 9. Database Changes

Database Changes: None.

## 10. Backend Requirements

Not Applicable.

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Test directory conventions, unit/widget test suites for the foundation features listed in Section 4, shared fakes/helpers, coverage baseline output.

## 13. Authorization and Access Control

Widget tests must assert that the WP-12-06 home skeleton treats capability flags as display hints only — no test may encode the assumption that client-side visibility is authorization, per the same rule WP-10-05 enforces on the web side.

## 14. Security Requirements

Test fakes must never embed real credentials, tokens, or production endpoints; the WP-12-11 redaction guard is re-run here as a standing regression test.

## 15. Privacy and Data-Governance Requirements

Test fixtures use obviously-synthetic identities only, per [../../phase-1-quality-gates.md](../../phase-1-quality-gates.md) test-data rules — no real athlete, official, or minor data in any fixture.

## 16. Audit and Activity Events

Not Applicable.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Tests cover only the skeleton surfaces WP-12-09/WP-12-10 created (store interface contracts, queue-entry shape) — not sync-conflict behavior, which has no implementation yet.

## 19. Observability Requirements

Coverage output is the observable artifact; the documented test invocation becomes part of the local quality gate for all future mobile work.

## 20. Testing Requirements

This work package **is** the mobile test foundation. Its own verification: the full suite passes from a clean checkout using only the documented invocation; the coverage baseline report is generated and recorded.

## 21. Test Data Requirements

Synthetic fixtures and fakes only, created inside `test/support/` (proposed). Do not create backend test data.

## 22. Documentation Updates

Record the Flutter test conventions, the documented invocation, and the coverage baseline in `.ai/architecture.md` addendum; record the confirmed test-framework selection against QD-02 in the epic README.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Establish `test/` conventions and shared fakes/helpers | WP-12-01..11 complete | Structure mirrors `lib/` feature folders |
| TASK-02 | Unit tests for configuration, secure storage, connectivity, logging redaction | TASK-01 | All pass; redaction regression included |
| TASK-03 | Widget tests for authentication shell and home skeleton states | TASK-01 | All UI states covered and passing |
| TASK-04 | Theme semantics/accessibility smoke check (provisional AA) | TASK-01 | Contrast/tap-target checks pass |
| TASK-05 | Document invocation and record coverage baseline | TASK-02..04 | Suite passes from clean checkout; baseline recorded |

## 24. Acceptance Criteria

- **AC-01:** Given a clean checkout, when the documented test invocation is run, then all foundation tests pass without manual setup beyond the documented steps.
- **AC-02:** Given the authentication shell and home skeleton, when their widget tests run, then loading, empty, populated, error, and permission-limited states are each asserted.
- **AC-03:** Given the WP-12-11 logging redaction guard, when the suite runs, then the redaction regression test is present and passing.
- **AC-04:** Given the test suite output, when coverage is generated, then a coverage baseline for `lib/` foundation code is recorded in the epic documentation.

## 25. Definition of Ready

Per [../../phase-1-definition-of-ready.md](../../phase-1-definition-of-ready.md). WP-12-01 through WP-12-11 complete.

## 26. Definition of Done

Per [../../phase-1-definition-of-done.md](../../phase-1-definition-of-done.md). All four criteria verified.

## 27. Completion Evidence

Per [../../phase-1-completion-evidence-standard.md](../../phase-1-completion-evidence-standard.md): full test output, coverage baseline report, documented invocation.

## 28. Rollback and Recovery Considerations

Not Applicable — new, additive test infrastructure only.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-12-12-01 | Coverage baseline is mistaken for a coverage target, blocking later work on an unvalidated number | Medium | Section 5 explicitly excludes target-setting; baseline is descriptive only | Quality reviewer |
| RISK-WP-12-12-02 | WP-12-09/WP-12-10 spike outcomes change the skeleton surface after tests are written | Medium | This work package runs last in EPIC-12 (sequence 123) precisely so it tests what was actually built | Engineering lead |

## 30. Open Decisions

| Decision ID | Question | Blocking Status |
|---|---|---|
| DEC-WP-12-12-01 | Final WCAG conformance target (DX-01) — provisional AA assumed | Non-blocking for Phase 1 (DEC-GENERAL-01) |
| DEC-WP-12-12-02 | Numeric coverage thresholds (Phase 0.7 quality-threshold decision) | Non-blocking — baseline only in Phase 1 |

## 31. Future Claude Code Execution Prompt

```text
Implement WP-12-12 — Flutter Test Foundation.

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
- Record a coverage baseline only — do not set or enforce a numeric coverage target.
- Test fixtures must be obviously synthetic — no real or realistic personal data.
- Do not create CI workflow files — local, documented invocation only.
```
