# WP-12-01 — Flutter Project Baseline Verification

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-12-01 | Title | Flutter Project Baseline Verification |
| Epic | EPIC-12 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 112 |
| Target release group | Foundation Release E | Pilot relevance | Required |
| Architecture readiness classification | Requires Decision (Flutter workspace absent) | Primary bounded context | Cross-cutting (mobile) |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Engineering lead |

## 2. Purpose

Creates the `mobile/` Flutter project from scratch (`flutter create`), since repository inspection confirmed no such directory exists — the actual project-creation step WP-01-05 flagged as a precondition, distinct from that work package's toolchain-only verification.

## 3. Architecture Sources

[../../../../01-architecture/flutter-architecture.md](../../../../01-architecture/flutter-architecture.md), approved technology direction (Flutter).

## 4. Scope

Run `flutter create mobile` (or equivalent) at the repository root; confirm the generated default app builds and runs on at least one target (Android emulator or web, whichever is available in the execution environment); commit the generated project structure as the Phase 1 baseline.

## 5. Explicit Exclusions

Does not implement any PMMS-specific feature or screen (WP-12-02 onward); does not configure CI for Flutter (WP-01-07's future scope).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-01-05 | Hard |

## 7. Current-State Inspection

Confirmed at authoring time: `mobile/` does not exist. Must be re-confirmed at execution time (per WP-01-05's own finding) before running `flutter create`, in case another process has since created it.

## 8. Proposed Implementation Direction

Standard `flutter create mobile --org <proposed reverse-domain>` at the repository root; verify the generated default counter-app builds.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Not Applicable.

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

The generated `mobile/` project itself, confirmed to build and run the default template.

## 13. Authorization and Access Control

Not Applicable.

## 14. Security Requirements

Not Applicable at this bare-scaffold stage.

## 15. Privacy and Data-Governance Requirements

Not Applicable.

## 16. Audit and Activity Events

Not Applicable.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable in this work package.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Confirm the default `flutter test` (widget test template) passes as generated.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record the Flutter project creation and verified build in `.ai/engineering-baseline.md`.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Confirm `mobile/` still does not exist | WP-01-05 complete | Confirmed |
| TASK-02 | Run `flutter create mobile` | TASK-01 | Project generated |
| TASK-03 | Verify the default app builds and the default test passes | TASK-02 | Build and test succeed |

## 24. Acceptance Criteria

- **AC-01:** Given the repository, when `flutter create mobile` is run, then a working Flutter project is generated.
- **AC-02:** Given the generated project, when `flutter test` is run, then the default widget test passes.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-01-05 complete (Flutter SDK confirmed available).

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): build output, test output.

## 28. Rollback and Recovery Considerations

The generated project can be deleted entirely if this approach needs to be redone; no other work package depends on this until it's confirmed working.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-12-01-01 | Flutter SDK unavailable despite WP-01-05's check (environment changed between work packages) | High | Re-verify Flutter SDK availability as TASK-01's precondition before proceeding | Engineering lead |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-12-01 — Flutter Project Baseline Verification.

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
- Do not implement any PMMS-specific feature — bare scaffold verification only.
- Re-confirm mobile/ does not already exist before running flutter create.
```
