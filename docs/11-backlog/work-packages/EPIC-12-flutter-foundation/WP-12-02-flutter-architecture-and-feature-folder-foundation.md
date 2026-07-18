# WP-12-02 — Flutter Architecture and Feature Folder Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-12-02 | Title | Flutter Architecture and Feature Folder Foundation |
| Epic | EPIC-12 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 113 |
| Target release group | Foundation Release E | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | Cross-cutting (mobile) |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Engineering lead |

## 2. Purpose

Establishes a feature-folder structure (`lib/features/<feature>/...`, mirroring the web's bounded-context convention from WP-02-01) for the Flutter project, replacing the default `flutter create` single-file structure.

## 3. Architecture Sources

[../../../../01-architecture/flutter-architecture.md](../../../../01-architecture/flutter-architecture.md).

## 4. Scope

Restructure `mobile/lib/` into `lib/core/` (shared) and `lib/features/<feature>/` folders; document the convention mirroring WP-02-01's web-side namespace approach for consistency across platforms.

## 5. Explicit Exclusions

Does not implement any real feature folder's content beyond `auth` (WP-12-05 populates it).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-12-01 | Hard |

## 7. Current-State Inspection

`mobile/lib/main.dart` (default `flutter create` template) as the starting point.

## 8. Proposed Implementation Direction

`lib/core/` (shared widgets, services, theme) and `lib/features/` (empty, ready for `auth` in WP-12-05).

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Not Applicable.

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Restructured `lib/` folder convention.

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

Not Applicable in this work package.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Confirm the restructured project still builds and the default test passes.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record the folder convention in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Restructure `lib/` into `core/`/`features/` | WP-12-01 complete | Build succeeds |

## 24. Acceptance Criteria

- **AC-01:** Given the restructured project, when built, then it succeeds without error.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-12-01 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Criterion verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): build output.

## 28. Rollback and Recovery Considerations

Reversible via Git history if the restructure introduces a build failure.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-12-02-01 | Convention diverges from the web-side namespace approach, causing confusion | Low | Explicitly designed to mirror WP-02-01 | Engineering lead |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-12-02 — Flutter Architecture and Feature Folder Foundation.

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
- Do not implement any feature content beyond the empty folder structure.
```
