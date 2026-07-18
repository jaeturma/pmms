# WP-11-04 — Application Shell and Navigation Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-11-04 | Title | Application Shell and Navigation Foundation |
| Epic | EPIC-11 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Large |
| Priority | P1 | Implementation sequence | 102 |
| Target release group | Foundation Release D | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | Cross-cutting |
| Secondary affected contexts | EPIC-05, EPIC-07 | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | UX reviewer |

## 2. Purpose

Extends the starter kit's existing `app-shell.tsx`, `app-sidebar.tsx`, `app-header.tsx`, and `nav-main.tsx` (already implementing a working sidebar-based shell) with PMMS-specific navigation entries and permission-aware menu visibility (using WP-10-05's capability contract), rather than building a shell from scratch.

## 3. Architecture Sources

[../../../../06-design/](../../../../06-design/), ADR-0009.

## 4. Scope

Extend `nav-main.tsx`'s navigation-item list with PMMS sections (Assignments, Reference Data — the two admin areas built so far); filter visible items using `useCapabilities()` from WP-10-05.

## 5. Explicit Exclusions

Does not implement navigation for any out-of-Phase-1-scope module (no sports/registration nav items); does not replace the existing shell/sidebar components.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-11-02, WP-11-03, WP-10-05 | Hard |

## 7. Current-State Inspection

`resources/js/components/app-shell.tsx`, `app-sidebar.tsx`, `app-sidebar-header.tsx`, `app-header.tsx`, `nav-main.tsx`, `nav-footer.tsx`, `nav-user.tsx`, `breadcrumbs.tsx` all exist and work in the starter kit — this work package extends the navigation data, not the shell mechanics.

## 8. Proposed Implementation Direction

Extend `nav-main.tsx`'s navigation configuration with capability-filtered items; no structural change to the shell components themselves.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Not Applicable — frontend-only work package.

## 11. Web Frontend Requirements

Extended navigation configuration; capability-based filtering applied via `useCapabilities()`.

## 12. Flutter Requirements

Not Applicable — Flutter's navigation shell is EPIC-12's own concern.

## 13. Authorization and Access Control

Navigation-item visibility is advisory only (per WP-10-05's discipline) — hiding a nav item does not substitute for the underlying page's own authorization check.

## 14. Security Requirements

Not Applicable beyond WP-10-05's restated discipline.

## 15. Privacy and Data-Governance Requirements

Not Applicable.

## 16. Audit and Activity Events

Not Applicable directly.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Frontend test confirming nav items are correctly shown/hidden based on capability; visual regression screenshots.

## 21. Test Data Requirements

Reuses existing factories. Do not create new test data.

## 22. Documentation Updates

Record the extended navigation structure in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Extend `nav-main.tsx` with PMMS navigation entries | WP-11-02, WP-11-03 complete | Screenshots captured |
| TASK-02 | Apply capability-based filtering | WP-10-05 complete | Frontend test passes |

## 24. Acceptance Criteria

- **AC-01:** Given a user without the assignment-management capability, when they view the sidebar, then the Assignments nav item is hidden.
- **AC-02:** Given the existing shell components, when this work package completes, then they remain structurally unchanged (extended data only).

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-11-02, WP-11-03, WP-10-05 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): screenshots, test output.

## 28. Rollback and Recovery Considerations

Extension is additive; existing shell mechanics preserved.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-11-04-01 | Nav-item hiding is mistaken for actual access control by a future reviewer | Medium | AC-01's phrasing and WP-10-05's restated discipline | UX reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-11-04 — Application Shell and Navigation Foundation.

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
- Extend the existing shell/nav components — do not rebuild them from scratch.
- Do not add navigation for any out-of-Phase-1 module.
```
