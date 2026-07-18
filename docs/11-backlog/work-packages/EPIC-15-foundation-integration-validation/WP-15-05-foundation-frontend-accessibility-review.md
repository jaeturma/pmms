# WP-15-05 — Foundation Frontend Accessibility Review

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-15-05 | Title | Foundation Frontend Accessibility Review |
| Epic | EPIC-15 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P2 | Implementation sequence | 148 |
| Target release group | Foundation Release F | Pilot relevance | Required |
| Architecture readiness classification | Verification Only | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Accessibility reviewer, Design reviewer |

## 2. Purpose

Verifies the web design-system foundation (EPIC-11) against the provisional WCAG 2.1 AA assumption (DEC-GENERAL-01, DX-01 pending): keyboard operability, focus management, contrast, semantics, and state announcements across every foundation component and page — evidence-based, so when DX-01 is finally decided, the delta from AA is known rather than guessed.

## 3. Architecture Sources

[../../../10-review/design-ux-accessibility-and-cross-platform-review.md](../../../10-review/design-ux-accessibility-and-cross-platform-review.md) (method), [../../../06-design/accessibility-architecture.md](../../../06-design/accessibility-architecture.md), ADR-0009; reviews WP-11-01..13 output and every foundation page.

## 4. Scope

Re-run WP-11-12's accessibility baseline checks in the integrated application (not component isolation); keyboard-only traversal of every foundation page/flow (shell, navigation, context switcher, forms, tables, dialogs, admin pages from WP-05-11/WP-06-07/WP-07-10/WP-13-09); automated scan (axe-core or equivalent already selected in EPIC-11) across integrated pages; contrast verification on both themes against the token set; screen-reader smoke pass on critical flows (login, context switch, form submit with errors); findings register; review record for WP-15-12 including the explicit conformance statement ("verified against provisional AA; DX-01 pending").

## 5. Explicit Exclusions

Does not fix accessibility defects (EPIC-11 work packages do); does not certify formal WCAG conformance (DX-01 undecided; no external audit); does not review Flutter accessibility (WP-15-07).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-11-01 through WP-11-13 at Implementation Complete | Hard |
| All page-producing epics complete | Hard |

## 7. Current-State Inspection

Reviews the integrated application's real pages — component-level passes from WP-11-12 do not substitute for integrated verification.

## 8. Proposed Implementation Direction

Scripted automated scans + structured manual keyboard/screen-reader protocol; per-page verdict table.

## 9. Database Changes

Database Changes: None.

## 10. Backend Requirements

Not Applicable.

## 11. Web Frontend Requirements

Review execution across all foundation pages; findings filed to owning work packages.

## 12. Flutter Requirements

Not Applicable (WP-15-07).

## 13. Authorization and Access Control

Permission-denied and empty states included in the review (accessible failure states matter as much as success states).

## 14. Security Requirements

Not Applicable.

## 15. Privacy and Data-Governance Requirements

Not Applicable.

## 16. Audit and Activity Events

Not Applicable.

## 17. Event, Queue, Notification, and Real-Time Requirements

Toast/flash and real-time-update announcements (aria-live regions from WP-11-08/WP-11-11) verified.

## 18. Offline and Synchronization Requirements

Offline/stale-state components' announcements verified.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Automated scan results per page; manual protocol records; screen-reader smoke notes.

## 21. Test Data Requirements

Synthetic data sufficient to render populated tables/forms.

## 22. Documentation Updates

Review record with per-page verdicts and the provisional-AA conformance statement to the sign-off evidence set.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Automated scans across integrated pages | EPIC-11 + pages complete | Scan reports collected |
| TASK-02 | Keyboard-only traversal protocol | TASK-01 | Per-page verdicts recorded |
| TASK-03 | Contrast (both themes) and screen-reader smoke pass | TASK-01 | Records complete |
| TASK-04 | Findings register and review record | TASK-02, TASK-03 | Record in evidence format |

## 24. Acceptance Criteria

- **AC-01:** Given every foundation page, when scanned and traversed keyboard-only, then each has a recorded verdict with findings filed for failures.
- **AC-02:** Given both themes, when contrast is verified against the token set, then results are recorded per token pair used in practice.
- **AC-03:** Given the review record, when submitted, then it states conformance as "verified against provisional WCAG 2.1 AA (DEC-GENERAL-01); DX-01 pending" — no stronger claim.

## 25. Definition of Ready

Per [../../phase-1-definition-of-ready.md](../../phase-1-definition-of-ready.md). EPIC-11 and page-producing epics at Implementation Complete.

## 26. Definition of Done

Per [../../phase-1-definition-of-done.md](../../phase-1-definition-of-done.md). All three criteria verified.

## 27. Completion Evidence

Per [../../phase-1-completion-evidence-standard.md](../../phase-1-completion-evidence-standard.md): scan reports, protocol records, verdict table, findings register.

## 28. Rollback and Recovery Considerations

Not Applicable — review only.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-15-05-01 | DX-01 lands above AA later, invalidating the baseline's sufficiency | Medium | Evidence recorded per criterion makes the AA→AAA delta computable rather than a re-audit from zero | Accessibility reviewer |

## 30. Open Decisions

| Decision ID | Question | Blocking Status |
|---|---|---|
| DEC-WP-15-05-01 | DX-01 — final WCAG conformance target | Non-blocking for the review — provisional AA per DEC-GENERAL-01 |

## 31. Future Claude Code Execution Prompt

```text
Implement WP-15-05 — Foundation Frontend Accessibility Review.

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
- Verification only — accessibility fixes route to EPIC-11 work packages.
- Review the integrated application, not isolated components.
- Claim only provisional-AA verification — never formal WCAG conformance.
```
