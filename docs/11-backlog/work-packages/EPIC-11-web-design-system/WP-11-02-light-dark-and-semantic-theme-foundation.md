# WP-11-02 — Light, Dark, and Semantic Theme Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-11-02 | Title | Light, Dark, and Semantic Theme Foundation |
| Epic | EPIC-11 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 100 |
| Target release group | Foundation Release D | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | UX reviewer |

## 2. Purpose

Extends the starter kit's existing `use-appearance.tsx` hook (already supporting light/dark/system) with PMMS Arena's semantic token layer (`--color-success`, `--color-danger`, etc.), ensuring every future component uses semantic names rather than raw colors.

## 3. Architecture Sources

[../../../../06-design/](../../../../06-design/), ADR-0009.

## 4. Scope

Define semantic color tokens (success/danger/warning/info) for both light and dark themes; confirm the existing `use-appearance.tsx`/`appearance-tabs.tsx` continue working unmodified.

## 5. Explicit Exclusions

Does not replace the existing theme-switching mechanism; does not implement any additional theme beyond light/dark/system (already supported).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-11-01 | Hard |

## 7. Current-State Inspection

`resources/js/hooks/use-appearance.tsx`, `resources/js/components/appearance-tabs.tsx` already implement light/dark/system switching — this work package extends the token layer they toggle between, not the switching mechanism itself.

## 8. Proposed Implementation Direction

Semantic tokens defined per-theme in `resources/css/tokens.css` (WP-11-01), toggled via the existing `data-appearance` attribute mechanism already present.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Not Applicable.

## 11. Web Frontend Requirements

Semantic token CSS extending WP-11-01's file.

## 12. Flutter Requirements

Not Applicable directly — WP-12-07's Flutter theme mirrors these same semantic names.

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

Visual regression screenshots for both light and dark theme; confirmation that existing appearance-switching tests (if any) still pass.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record semantic token names in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Define semantic color tokens for light and dark themes | WP-11-01 complete | Screenshots captured |
| TASK-02 | Confirm existing appearance-switching behavior unaffected | TASK-01 | Confirmed |

## 24. Acceptance Criteria

- **AC-01:** Given both light and dark themes, when rendered, then all semantic tokens (success/danger/warning/info) resolve to appropriately contrasting colors in each.
- **AC-02:** Given the existing `use-appearance.tsx` hook, when this work package completes, then it remains functionally unchanged.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-11-01 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): light/dark screenshots.

## 28. Rollback and Recovery Considerations

Not Applicable — additive CSS only.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-11-02-01 | Semantic tokens have insufficient contrast in dark mode | Medium | WP-11-12's accessibility baseline re-checks contrast explicitly | UX reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-11-02 — Light, Dark, and Semantic Theme Foundation.

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
- Do not replace the existing use-appearance.tsx switching mechanism — extend the token layer only.
```
