# WP-11-06 — Page Header and Content Layout Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-11-06 | Title | Page Header and Content Layout Foundation |
| Epic | EPIC-11 | Phase | Phase 1 | Status | Planned — Not Started |
| Complexity | Medium | Priority | P1 | Implementation sequence | 104 |
| Target release group | Foundation Release D | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | UX reviewer |

## 2. Purpose

Extends the starter kit's existing `heading.tsx`/`breadcrumbs.tsx` into a consistent page-header pattern (title, breadcrumb, primary action button) and a content-layout container (consistent max-width/padding), used by every future admin page.

## 3. Architecture Sources

[../../../../06-design/](../../../../06-design/).

## 4. Scope

Extend `heading.tsx` into a `PageHeader` component (title, description, breadcrumb, action-slot); define a `ContentLayout` container component.

## 5. Explicit Exclusions

Does not implement page-specific content — layout/header shell only.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-11-04 | Hard |

## 7. Current-State Inspection

`resources/js/components/heading.tsx`, `breadcrumbs.tsx` exist as building blocks.

## 8. Proposed Implementation Direction

`PageHeader`/`ContentLayout` components composing the existing `heading.tsx`/`breadcrumbs.tsx`.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Not Applicable.

## 11. Web Frontend Requirements

`PageHeader` and `ContentLayout` components.

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

Not Applicable.

## 20. Testing Requirements

Visual regression screenshots; component render test.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record the components in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement `PageHeader` and `ContentLayout` | WP-11-04 complete | Screenshots captured |

## 24. Acceptance Criteria

- **AC-01:** Given `PageHeader`, when rendered with title/breadcrumb/action, then all three display correctly.
- **AC-02:** Given `ContentLayout`, when used across multiple pages, then it produces consistent max-width/padding.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-11-04 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): screenshots.

## 28. Rollback and Recovery Considerations

Not Applicable — new, additive components.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-11-06-01 | Inconsistent layout adoption across future pages if this component isn't used | Low | Documented as the standard pattern; WP-15-05 reviews | UX reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-11-06 — Page Header and Content Layout Foundation.

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
- Layout/header shell only — no page-specific content.
```
