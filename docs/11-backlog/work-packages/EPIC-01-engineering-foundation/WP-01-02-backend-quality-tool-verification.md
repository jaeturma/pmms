# WP-01-02 — Backend Quality Tool Verification

## 1. Work Package Identity

| Field | Value |
|---|---|
| Work Package ID | WP-01-02 |
| Title | Backend Quality Tool Verification |
| Epic | EPIC-01 — Engineering Foundation and Repository Baseline |
| Phase | Phase 1 — Core Foundation Implementation |
| Status | Planned — Not Started |
| Complexity | Small |
| Priority | P1 |
| Implementation sequence | 2 |
| Target release group | Foundation Release A |
| Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation |
| Primary bounded context | None (cross-cutting) |
| Secondary affected contexts | All backend work |
| Product owner | To be identified |
| Technical owner | To be identified |
| Required reviewers | Engineering lead, QA lead |

## 2. Purpose

Verifies Larastan/PHPStan, Pint, and PestPHP are correctly configured and runnable, so every later backend work package can rely on `composer run lint:check`, `composer run types:check`, and `composer run test` actually working, per [phase-1-quality-gates.md, Section 1](../../../phase-1-quality-gates.md#1-local-gates).

## 3. Architecture Sources

[../../../../04-quality/](../../../../04-quality/), [../../../phase-1-quality-gates.md](../../../phase-1-quality-gates.md).

## 4. Scope

- Run `composer run lint:check` (Pint) and record result.
- Run `composer run types:check` (Larastan/PHPStan via `phpstan.neon`) and record result.
- Run `composer run test` (Pest, via `phpunit.xml`) against the existing default test suite and record result.
- Confirm `phpstan.neon`'s configured analysis level.

## 5. Explicit Exclusions

- Does not change PHPStan level, Pint ruleset, or PestPHP configuration.
- Does not fix any pre-existing lint/analysis/test failure beyond documenting it.
- Does not add new tests.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-01-01 | Hard |

## 7. Current-State Inspection

Inspect `phpstan.neon`, `pint.json`, `phpunit.xml`, `tests/` (existing Feature/Auth, Feature/Settings, Unit test files from the starter kit), and `composer.json`'s `scripts` block (`lint`, `lint:check`, `types:check`, `test`, `ci:check`).

## 8. Proposed Implementation Direction

Documentation-only; no code layer affected. Record results in `.ai/engineering-baseline.md` (extends WP-01-01's document).

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Not Applicable (verification-only).

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable.

## 14. Security Requirements

Not Applicable.

## 15. Privacy and Data-Governance Requirements

Not Applicable.

## 16. Audit and Activity Events

Not Applicable.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Recorded tool-run output serves as the observability baseline for backend quality tooling.

## 20. Testing Requirements

Existing default test suite must pass as-is (or its failures are documented, not silently ignored) — this is the verification itself.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Extend `.ai/engineering-baseline.md` with backend quality-tool results.

## 23. Tasks

| Task ID | Description | Expected Files/Areas | Preconditions | Verification | Notes |
|---|---|---|---|---|---|
| TASK-01 | Run `composer run lint:check` | None (read-only) | WP-01-01 complete | Output captured | — |
| TASK-02 | Run `composer run types:check` | None (read-only) | WP-01-01 complete | Output captured | Record PHPStan level from `phpstan.neon` |
| TASK-03 | Run `composer run test` | None (read-only) | WP-01-01 complete | Output captured | Record pass/fail count |
| TASK-04 | Append results to `.ai/engineering-baseline.md` | `.ai/engineering-baseline.md` | TASK-01..03 complete | File updated | — |

## 24. Acceptance Criteria

- **AC-01:** Given the repository, when `composer run lint:check` is run, then its pass/fail result is recorded verbatim.
- **AC-02:** Given `phpstan.neon`, when `composer run types:check` is run, then the configured analysis level and result are recorded.
- **AC-03:** Given the existing default test suite, when `composer run test` is run, then the pass/fail count is recorded, including any pre-existing failure.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-01-01 must be Implementation Complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Work-package-specific: all three tool results recorded, including any failure, not just successes.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): verbatim output of all three commands.

## 28. Rollback and Recovery Considerations

Not Applicable — documentation-only change.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Detection | Escalation | Residual Concern |
|---|---|---|---|---|---|---|
| RISK-WP-01-02-01 | Pre-existing starter-kit test failure is masked rather than documented | Low | AC-03 requires recording the count including failures | Review of completion evidence | QA lead | Low |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-01-02 — Backend Quality Tool Verification.

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
- Do not change phpstan.neon, pint.json, or phpunit.xml.
- Document any failing check rather than silently fixing or hiding it.
```
