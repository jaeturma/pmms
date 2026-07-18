# WP-11-12 — Accessibility and Keyboard Baseline

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-11-12 | Title | Accessibility and Keyboard Baseline |
| Epic | EPIC-11 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Large |
| Priority | P1 | Implementation sequence | 110 |
| Target release group | Foundation Release D | Pilot relevance | Required |
| Architecture readiness classification | Requires Decision (DX-01) | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | UX reviewer, QA lead |

## 2. Purpose

Applies an accessibility pass across every WP-11-01 through WP-11-11 component against a provisional WCAG 2.1 AA target (DEC-GENERAL-01, since DX-01 remains formally unresolved), covering keyboard navigation, focus management, color contrast, and ARIA labeling — the largest and most consequential single work package in EPIC-11, per Phase 0.13's finding that no accessibility testing has occurred anywhere in the project to date.

## 3. Architecture Sources

[../../../../06-design/](../../../../06-design/), [../../../10-review/design-ux-accessibility-and-cross-platform-review.md](../../../10-review/design-ux-accessibility-and-cross-platform-review.md).

## 4. Scope

Run automated accessibility tooling (axe or equivalent) against every component from WP-11-04 through WP-11-11; perform a manual keyboard-only navigation pass through the assignment admin page (WP-05-11) as the representative end-to-end flow; fix findings up to the provisional WCAG 2.1 AA target.

## 5. Explicit Exclusions

Does not certify final WCAG conformance (DX-01 remains open — this is a provisional baseline, explicitly re-verifiable); does not audit any page beyond what Phase 1 has built.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-11-04 through WP-11-11 | Hard |
| DX-01 (final WCAG target) | Soft — provisional AA target used |

## 7. Current-State Inspection

Every component built across WP-11-04 through WP-11-11, plus WP-05-11's assignment admin page as the representative flow.

## 8. Proposed Implementation Direction

Automated tooling pass plus manual keyboard-navigation review; fixes applied directly to the audited components (not deferred).

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Not Applicable.

## 11. Web Frontend Requirements

Accessibility fixes applied across WP-11-04 through WP-11-11's components (ARIA labels, focus-trap in dialogs, keyboard-operable dropdowns, sufficient contrast).

## 12. Flutter Requirements

Not Applicable in this work package — WP-12-12 performs the analogous Flutter accessibility pass.

## 13. Authorization and Access Control

Not Applicable directly.

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

Automated axe (or equivalent) scan of every audited component; manual keyboard-only navigation test of the WP-05-11 admin flow (tab order, focus visibility, no keyboard trap).

## 21. Test Data Requirements

Reuses existing test fixtures. Do not create new test data.

## 22. Documentation Updates

Record the accessibility audit results and remaining known gaps (if any) in `.ai/architecture.md` addendum, explicitly flagged as provisional pending DX-01.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Run automated accessibility scan across WP-11-04..11 components | WP-11-04..11 complete | Scan results captured |
| TASK-02 | Perform manual keyboard-only navigation pass through WP-05-11's flow | TASK-01 | Findings documented |
| TASK-03 | Fix findings up to WCAG 2.1 AA | TASK-01..02 | Re-scan confirms fixes |

## 24. Acceptance Criteria

- **AC-01:** Given the automated scan, when run against WP-11-04 through WP-11-11's components, then zero Critical or Serious-severity axe findings remain unresolved.
- **AC-02:** Given the manual keyboard-only navigation test, when performed on WP-05-11's flow, then every interactive element is reachable and operable via keyboard alone, with visible focus indication.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-11-04 through WP-11-11 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified; results explicitly labeled provisional pending DX-01.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): automated scan output (before/after), manual test notes.

## 28. Rollback and Recovery Considerations

Fixes are applied to existing components; reversible via normal Git history if a fix introduces a regression.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-11-12-01 | DX-01's eventual formal target exceeds the provisional AA baseline, requiring rework | Medium | Explicitly flagged as provisional; re-verification is a defined, expected follow-up, not a surprise | UX reviewer |

## 30. Open Decisions

| Decision ID | Question | Blocking Status |
|---|---|---|
| DEC-WP-11-12-01 | Final WCAG conformance target (DX-01) | Non-blocking — see DEC-GENERAL-01 |

## 31. Future Claude Code Execution Prompt

```text
Implement WP-11-12 — Accessibility and Keyboard Baseline.

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
- Do not claim final WCAG certification — results are provisional pending DX-01.
- Fix, do not merely report, every Critical/Serious accessibility finding.
```
