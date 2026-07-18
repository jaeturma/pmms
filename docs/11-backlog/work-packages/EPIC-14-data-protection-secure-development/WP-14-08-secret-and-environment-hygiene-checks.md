# WP-14-08 — Secret and Environment Hygiene Checks

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-14-08 | Title | Secret and Environment Hygiene Checks |
| Epic | EPIC-14 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Small |
| Priority | P1 | Implementation sequence | 141 |
| Target release group | Foundation Release E | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Turns WP-01-06's environment/secret baseline into repeatable, mechanical checks: no secret in the repository, no config read of `env()` outside config files, `.env.example` complete and placeholder-only, and a documented secret-rotation starting point — so hygiene survives beyond the one-time baseline review.

## 3. Architecture Sources

[../../../03-security/cryptography-key-and-secret-management.md](../../../03-security/cryptography-key-and-secret-management.md), [../../../05-devops/](../../../05-devops/) (environment rules), ADR-0006, ADR-0008; extends WP-01-06.

## 4. Scope

A repeatable hygiene check (proposed: a Pest architecture/test suite plus a documented manual sweep) verifying: no `env()` calls outside `config/`; `.env.example` lists every variable the app reads, with placeholder values only; no secret-shaped strings (keys, tokens, DSNs with credentials) in tracked files (lightweight pattern sweep, documented limitations); `APP_KEY` presence/rotation implications documented; a secret inventory (which secrets exist, where they live, who rotates them) started as a living document.

## 5. Explicit Exclusions

Does not install a secret-manager service or external scanning tool (future, separately-authorized); does not rotate any real secret (operational act); does not create CI workflow YAML (the checks are runnable locally; CI wiring follows WP-01-07's conventions later).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-01-06 | Hard |

## 7. Current-State Inspection

WP-01-06 established the baseline once; nothing re-verifies it continuously; `.env.example` drift is the classic failure.

## 8. Proposed Implementation Direction

Pest architecture test for the `env()`-outside-config rule; a test comparing `.env.example` keys against config-referenced env keys; a documented grep-pattern sweep for secret shapes; `docs`-adjacent secret inventory (no values, locations and owners only).

## 9. Database Changes

Database Changes: None.

## 10. Backend Requirements

Test suite additions and the inventory document.

## 11. Web Frontend Requirements

Verify no secret or server-only env value is exposed through Vite's client-visible env mechanism (`VITE_`-prefixed variables reviewed).

## 12. Flutter Requirements

Not Applicable in this work package — WP-12-03 owns mobile configuration hygiene under the same principles.

## 13. Authorization and Access Control

Not Applicable.

## 14. Security Requirements

The inventory contains locations and owners, never values; pattern-sweep limitations documented (it catches shapes, not all secrets).

## 15. Privacy and Data-Governance Requirements

Not Applicable.

## 16. Audit and Activity Events

Not Applicable.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

The hygiene checks are themselves tests: `env()`-placement architecture test, `.env.example` completeness test, `VITE_` exposure review, pattern-sweep execution with recorded result.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record check invocations and the inventory location in `.ai/architecture.md` addendum; update `.ai/secure-development-rules.md` cross-reference if needed.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement env-placement and .env.example tests | WP-01-06 complete | Tests pass |
| TASK-02 | Execute and document secret-shape sweep | TASK-01 | Clean result or findings ticketed |
| TASK-03 | Create secret inventory (locations/owners only) | TASK-01 | Inventory exists, no values |
| TASK-04 | Review VITE_-exposed variables | TASK-01 | No server-only value exposed |

## 24. Acceptance Criteria

- **AC-01:** Given the codebase, when the architecture test runs, then no `env()` call exists outside `config/`.
- **AC-02:** Given `.env.example`, when compared with config-referenced variables, then it is complete and contains placeholders only.
- **AC-03:** Given the secret-shape sweep, when executed, then the result is clean or every finding is remediated/ticketed, and the sweep's limitations are documented.
- **AC-04:** Given the secret inventory, when reviewed, then it lists locations and owners without containing any secret value.

## 25. Definition of Ready

Per [../../phase-1-definition-of-ready.md](../../phase-1-definition-of-ready.md). WP-01-06 complete.

## 26. Definition of Done

Per [../../phase-1-definition-of-done.md](../../phase-1-definition-of-done.md). All four criteria verified.

## 27. Completion Evidence

Per [../../phase-1-completion-evidence-standard.md](../../phase-1-completion-evidence-standard.md): test output, sweep result, inventory document.

## 28. Rollback and Recovery Considerations

Not Applicable — tests and documentation only.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-14-08-01 | Pattern sweep breeds false confidence — a real secret that matches no pattern goes unnoticed | Medium | Limitations documented; inventory + rotation ownership is the compensating control | Security reviewer |

## 30. Open Decisions

| Decision ID | Question | Blocking Status |
|---|---|---|
| DEC-WP-14-08-01 | External secret-scanning tool adoption | Non-blocking — local checks suffice for Phase 1 |

## 31. Future Claude Code Execution Prompt

```text
Implement WP-14-08 — Secret and Environment Hygiene Checks.

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
- The inventory records locations and owners — never a secret value.
- Do not rotate real secrets or install external tools.
- Document the sweep's limitations honestly — no false-confidence claims.
```
