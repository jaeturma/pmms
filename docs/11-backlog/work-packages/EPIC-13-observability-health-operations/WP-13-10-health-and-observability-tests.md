# WP-13-10 — Health and Observability Tests

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-13-10 | Title | Health and Observability Tests |
| Epic | EPIC-13 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P2 | Implementation sequence | 133 |
| Target release group | Foundation Release E | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Engineering lead, Quality reviewer |

## 2. Purpose

Closes out EPIC-13 with an epic-level verification suite — the Release E validation checkpoint for observability named in [../../phase-1-execution-sequence.md, Section 5](../../phase-1-execution-sequence.md#5-validation-checkpoints) — consolidating and extending the per-work-package tests of WP-13-01 through WP-13-09 into one coherent, repeatable observability regression suite.

## 3. Architecture Sources

[../../../04-quality/](../../../04-quality/), ADR-0007, ADR-0008.

## 4. Scope

Consolidated regression suite covering: structured-record shape and field completeness (WP-13-01); end-to-end correlation across request → job → error response (WP-13-02); public liveness minimalism (WP-13-03); readiness access restriction and no-leak output (WP-13-04); each dependency check's healthy/degraded/failure behavior under simulation (WP-13-05/06/07); safe-error no-leak guarantees for HTML and JSON (WP-13-08); diagnostics-page authorization and audit (WP-13-09); one documented invocation running the whole observability suite.

## 5. Explicit Exclusions

Does not add new observability features; does not implement load, soak, or chaos testing (future operational testing); does not create CI workflow YAML (WP-01-07 convention governs that later).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-13-01 through WP-13-09 | Hard |

## 7. Current-State Inspection

Each EPIC-13 work package ships its own tests; no consolidated suite or documented single invocation exists until this work package.

## 8. Proposed Implementation Direction

Group the suite under a dedicated Pest test directory/group (proposed: `tests/Feature/Observability/` with a `--group=observability` marker) so the epic's regression state is checkable in one command.

## 9. Database Changes

Database Changes: None.

## 10. Backend Requirements

Test consolidation and gap-filling only — any behavior change discovered belongs to the owning work package as a defect, not to this one.

## 11. Web Frontend Requirements

Not Applicable beyond the WP-13-09 page assertions already in scope.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

The suite re-verifies every access restriction in the epic (readiness endpoint, diagnostics page) as standing regressions.

## 14. Security Requirements

The no-leak assertions (no traces, no connection detail, no version/environment disclosure) are the security core of this suite.

## 15. Privacy and Data-Governance Requirements

Redaction-dependent assertions (WP-13-08) re-run here as regressions.

## 16. Audit and Activity Events

Re-verifies the WP-13-09 sensitive-view audit event and the absence of audit noise from probes (WP-13-03/WP-13-07).

## 17. Event, Queue, Notification, and Real-Time Requirements

Includes the queue/scheduler/Reverb simulated-failure scenarios.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

This suite is the proof the observability foundation works and stays working.

## 20. Testing Requirements

This work package **is** the epic test suite. Its own verification: the full suite passes from a clean checkout via the documented invocation; every EPIC-13 acceptance criterion maps to at least one test (a coverage-mapping table in the epic README).

## 21. Test Data Requirements

Synthetic only, reusing existing factories; simulated failures via configuration/fakes, never by degrading real services.

## 22. Documentation Updates

Add the acceptance-criteria-to-test mapping table and the documented invocation to the epic README; note the suite in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Consolidate and group all EPIC-13 tests | WP-13-01..09 complete | Single invocation runs the suite |
| TASK-02 | Fill assertion gaps against each WP's acceptance criteria | TASK-01 | Mapping table complete |
| TASK-03 | Verify clean-checkout pass and document invocation | TASK-02 | Suite passes from clean state |

## 24. Acceptance Criteria

- **AC-01:** Given a clean checkout, when the documented observability suite invocation runs, then all tests pass.
- **AC-02:** Given the mapping table, when reviewed, then every acceptance criterion of WP-13-01 through WP-13-09 maps to at least one passing test.
- **AC-03:** Given the suite, when run, then no test degrades or depends on a real external service — all failure scenarios are simulated.

## 25. Definition of Ready

Per [../../phase-1-definition-of-ready.md](../../phase-1-definition-of-ready.md). WP-13-01 through WP-13-09 complete.

## 26. Definition of Done

Per [../../phase-1-definition-of-done.md](../../phase-1-definition-of-done.md). All three criteria verified.

## 27. Completion Evidence

Per [../../phase-1-completion-evidence-standard.md](../../phase-1-completion-evidence-standard.md): full suite output, mapping table, documented invocation.

## 28. Rollback and Recovery Considerations

Not Applicable — test-only work package.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-13-10-01 | Consolidation quietly weakens an assertion (e.g., replacing a no-leak content check with a status-code check) | Medium | Mapping table review requires assertion-level fidelity, not test-name fidelity | Quality reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-13-10 — Health and Observability Tests.

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
- Tests only — behavior changes belong to the owning work package as defects.
- All failure scenarios simulated — never degrade a real service to test.
- Do not create CI workflow files.
```
