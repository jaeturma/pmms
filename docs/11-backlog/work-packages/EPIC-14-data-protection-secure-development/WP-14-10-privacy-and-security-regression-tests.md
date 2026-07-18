# WP-14-10 — Privacy and Security Regression Tests

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-14-10 | Title | Privacy and Security Regression Tests |
| Epic | EPIC-14 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P2 | Implementation sequence | 143 |
| Target release group | Foundation Release E | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | Cross-cutting |
| Secondary affected contexts | All | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer, Quality reviewer |

## 2. Purpose

Closes out EPIC-14 with the consolidated privacy/security regression suite — the Release E validation checkpoint for the safeguard layer named in [../../phase-1-execution-sequence.md, Section 5](../../phase-1-execution-sequence.md#5-validation-checkpoints) — and adds the cross-cutting sweeps no single work package owns: classification coverage, redaction category sweeps, and the "no later epic bypassed the safeguards" checks that RISK-EPIC14-01 depends on.

## 3. Architecture Sources

[../../../03-security/security-testing-and-assurance.md](../../../03-security/security-testing-and-assurance.md), [../../../04-quality/](../../../04-quality/), ADR-0006, ADR-0007.

## 4. Scope

Consolidated suite covering WP-14-01..09's acceptance criteria as standing regressions, plus cross-cutting sweeps: classification-awareness architecture test (models touching personal data implement the WP-14-01 contract); redaction category sweep (each never-log category simulated end-to-end through real logging paths); masking-bypass sweep (no server response ships full values for client-side masking — checked against WP-10-04's prop rules); export-bypass sweep (no CSV/download generation outside the WP-14-04 contract); impersonation grant-absence sweep; rate-limiter coverage sweep (every route group names a limiter or documents why not); one documented invocation for the whole suite with an acceptance-criteria mapping table.

## 5. Explicit Exclusions

Does not add safeguard features (defects route to owning work packages); does not perform penetration testing or dynamic scanning (Phase 0.6's security-testing architecture schedules that separately, post-foundation); does not create CI workflow YAML.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-14-01 through WP-14-09 | Hard |

## 7. Current-State Inspection

Each EPIC-14 work package ships its own tests; the cross-cutting sweeps and single invocation exist only after this work package.

## 8. Proposed Implementation Direction

Dedicated Pest group (proposed: `tests/Feature/Security/` + `--group=security`) combining feature tests and architecture tests (Pest arch or PHPStan-rule equivalents per WP-02-06's tooling decision).

## 9. Database Changes

Database Changes: None.

## 10. Backend Requirements

Test consolidation, sweep implementation, mapping table.

## 11. Web Frontend Requirements

Masking-bypass sweep asserts against rendered Inertia props.

## 12. Flutter Requirements

Not Applicable — WP-12-12 owns mobile regressions; the API-side guarantees tested here protect mobile clients equally.

## 13. Authorization and Access Control

Grant-absence and authorization-adjacent sweeps re-verified as regressions.

## 14. Security Requirements

This suite is the mechanical answer to RISK-EPIC14-01 — its sweeps must be structured so a later epic's shortcut *fails a test*, not just violates a convention.

## 15. Privacy and Data-Governance Requirements

Classification coverage and redaction sweeps are the standing privacy safeguards; their gaps are findings, not footnotes.

## 16. Audit and Activity Events

Re-verifies export and impersonation audit events as regressions.

## 17. Event, Queue, Notification, and Real-Time Requirements

Queue-path redaction re-verified (worker logs).

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Suite output is the recurring evidence artifact for WP-15-04/WP-15-09 reviews.

## 20. Testing Requirements

This work package **is** the suite. Its own verification: passes from clean checkout via documented invocation; mapping table covers every WP-14-01..09 acceptance criterion; each sweep demonstrably fails when its safeguard is violated (verified by intentional-violation tests run against a temporary branch state or in-test fakes, then reverted).

## 21. Test Data Requirements

Synthetic only; sensitive-looking values clearly fake.

## 22. Documentation Updates

Mapping table and invocation in the epic README; suite noted in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Consolidate WP-14-01..09 regressions under one group | WP-14-01..09 complete | Single invocation runs all |
| TASK-02 | Implement cross-cutting sweeps | TASK-01 | Sweeps pass; intentional-violation checks fail correctly |
| TASK-03 | Build acceptance-criteria mapping table | TASK-01 | Table complete |

## 24. Acceptance Criteria

- **AC-01:** Given a clean checkout, when the documented invocation runs, then the full privacy/security suite passes.
- **AC-02:** Given the mapping table, when reviewed, then every WP-14-01..09 acceptance criterion maps to at least one passing test.
- **AC-03:** Given each sweep, when its safeguard is intentionally violated in a controlled check, then the sweep fails — proving the sweeps detect, not just decorate.

## 25. Definition of Ready

Per [../../phase-1-definition-of-ready.md](../../phase-1-definition-of-ready.md). WP-14-01 through WP-14-09 complete.

## 26. Definition of Done

Per [../../phase-1-definition-of-done.md](../../phase-1-definition-of-done.md). All three criteria verified.

## 27. Completion Evidence

Per [../../phase-1-completion-evidence-standard.md](../../phase-1-completion-evidence-standard.md): suite output, mapping table, intentional-violation demonstration notes.

## 28. Rollback and Recovery Considerations

Not Applicable — test-only work package.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-14-10-01 | Sweeps assert conventions structurally but miss a semantic bypass (RISK-EPIC14-01's residual) | High | AC-03's detect-not-decorate requirement; WP-15-04/09 human reviews remain mandatory on top | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-14-10 — Privacy and Security Regression Tests.

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
- Tests only — safeguard defects route to their owning work packages.
- Every sweep must demonstrably fail under intentional violation — detection, not decoration.
- No penetration testing or dynamic scanning here.
```
