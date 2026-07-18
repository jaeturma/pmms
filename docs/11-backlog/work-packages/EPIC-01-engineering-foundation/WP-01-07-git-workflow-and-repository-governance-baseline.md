# WP-01-07 — Git Workflow and Repository Governance Baseline

## 1. Work Package Identity

| Field | Value |
|---|---|
| Work Package ID | WP-01-07 |
| Title | Git Workflow and Repository Governance Baseline |
| Epic | EPIC-01 — Engineering Foundation and Repository Baseline |
| Phase | Phase 1 |
| Status | Planned — Not Started |
| Complexity | Medium |
| Priority | P1 |
| Implementation sequence | 7 |
| Target release group | Foundation Release A |
| Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints |
| Primary bounded context | None (cross-cutting) |
| Secondary affected contexts | All |
| Product owner | To be identified |
| Technical owner | To be identified |
| Required reviewers | Engineering lead, DevOps lead |

## 2. Purpose

Documents the Git branching convention, commit-message convention, and CI/CD quality-gate conventions (addressing GAP-16 from Phase 0.13) that every future work package execution session will follow, without yet creating any `.github/workflows` file — matching the working rule prohibiting CI-workflow creation in Phase 0.14/this work package's documentation stage.

## 3. Architecture Sources

[../../../../05-devops/](../../../../05-devops/), [../../../10-review/architecture-gap-register.md](../../../10-review/architecture-gap-register.md) (GAP-16), [../../../phase-1-quality-gates.md](../../../phase-1-quality-gates.md).

## 4. Scope

Document a proposed branch-naming convention (e.g., `wp/<id>-<slug>`, proposed); document a proposed commit-message convention referencing work-package IDs; document the CI conventions from [phase-1-quality-gates.md](../../../phase-1-quality-gates.md) as the target for a future `.github/workflows` file; confirm current `git status`/`git log` baseline.

## 5. Explicit Exclusions

Does not create any `.github/workflows/*.yml` file; does not create branches or commits; does not alter GitHub repository settings.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-01-01 | Hard |

## 7. Current-State Inspection

Confirmed at authoring time: no `.github/` directory exists; `git log --oneline` shows a single commit (`d84b22d Initial Laravel 13 installation`); `git status` shows deleted `.claude/skills/*` and `.github/*` files plus untracked `.ai/`/`docs/` (pre-existing from earlier phases, unrelated to this work package).

## 8. Proposed Implementation Direction

Documentation-only. Record conventions in `.ai/engineering-baseline.md` and reference [phase-1-quality-gates.md](../../../phase-1-quality-gates.md) as the CI target specification for a future, separately-authorized work package to implement as actual `.github/workflows` YAML.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Not Applicable.

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable.

## 14. Security Requirements

Document that GitHub branch-protection and required-status-check settings are a future, separately-authorized action, not performed here.

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

Not Applicable — documentation only.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Create or extend a Git-workflow section in `.ai/engineering-baseline.md`; reference [phase-1-quality-gates.md](../../../phase-1-quality-gates.md).

## 23. Tasks

| Task ID | Description | Expected Files/Areas | Preconditions | Verification | Notes |
|---|---|---|---|---|---|
| TASK-01 | Document proposed branch-naming and commit-message conventions | `.ai/engineering-baseline.md` | WP-01-01 complete | Section added | Proposed, not enforced |
| TASK-02 | Record current `git log`/`git status` baseline | None (read-only) | None | Output captured | — |
| TASK-03 | Cross-reference [phase-1-quality-gates.md](../../../phase-1-quality-gates.md) as the future CI target spec | `.ai/engineering-baseline.md` | None | Link added | — |

## 24. Acceptance Criteria

- **AC-01:** Given this work package, when complete, then a proposed branch/commit convention is documented in `.ai/engineering-baseline.md`.
- **AC-02:** Given the repository, when `git log`/`git status` are run, then the baseline is recorded verbatim.
- **AC-03:** Given [phase-1-quality-gates.md](../../../phase-1-quality-gates.md), when referenced, then no `.github/workflows` file is created by this work package.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-01-01 must be Implementation Complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Conventions documented; no CI file created.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): the documented conventions themselves, plus `git log`/`git status` output.

## 28. Rollback and Recovery Considerations

Not Applicable — documentation-only change.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Detection | Escalation | Residual Concern |
|---|---|---|---|---|---|---|
| RISK-WP-01-07-01 | A future session mistakes this work package's documentation for authorization to create `.github/workflows` | Low | Section 5 explicitly excludes CI-file creation | Review of any PR adding `.github/workflows` before an explicit later authorization | Engineering lead | Low |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-01-07 — Git Workflow and Repository Governance Baseline.

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
- Do not create any .github/workflows file.
- Do not create branches or commits.
- Do not alter GitHub repository settings.
```
