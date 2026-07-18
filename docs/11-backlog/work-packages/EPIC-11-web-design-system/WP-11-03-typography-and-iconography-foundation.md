# WP-11-03 — Typography and Iconography Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-11-03 | Title | Typography and Iconography Foundation |
| Epic | EPIC-11 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Small |
| Priority | P1 | Implementation sequence | 101 |
| Target release group | Foundation Release D | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | UX reviewer |

## 2. Purpose

Defines the type scale (heading/body/caption sizes) and confirms `lucide-react` (already a dependency) as the standard icon set, extending the existing `resources/js/components/ui/icon.tsx` wrapper.

## 3. Architecture Sources

[../../../../06-design/](../../../../06-design/).

## 4. Scope

Define a type scale in token form; confirm `icon.tsx`'s existing pattern is the standard for all future icon usage (no second icon library introduced).

## 5. Explicit Exclusions

Does not select a custom font beyond the system/existing font stack (branding-dependent, DX-02).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-11-01 | Hard |

## 7. Current-State Inspection

`resources/js/components/ui/icon.tsx` already wraps `lucide-react`; no formal type scale exists yet.

## 8. Proposed Implementation Direction

Type-scale tokens added to WP-11-01's token file; documentation confirming `icon.tsx` as the sole icon-rendering pattern.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Not Applicable.

## 11. Web Frontend Requirements

Type-scale CSS tokens; icon-usage documentation.

## 12. Flutter Requirements

Not Applicable directly — WP-12-07 selects an equivalent icon set for Flutter separately.

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

Visual regression screenshot of the type scale.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record the type scale and icon convention in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Define type-scale tokens | WP-11-01 complete | Tokens defined |
| TASK-02 | Document `icon.tsx`/lucide-react as the sole icon convention | TASK-01 | Documented |

## 24. Acceptance Criteria

- **AC-01:** Given the type scale, when reviewed, then it covers heading (h1-h4), body, and caption sizes consistently.
- **AC-02:** Given the icon convention, when documented, then it explicitly prohibits introducing a second icon library.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-11-01 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): type-scale screenshot.

## 28. Rollback and Recovery Considerations

Not Applicable — additive tokens/documentation.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-11-03-01 | A future component introduces a second icon library for convenience | Low | Documented convention as standing reference; WP-15-05 reviews | UX reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-11-03 — Typography and Iconography Foundation.

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
- Do not introduce a second icon library — lucide-react via the existing icon.tsx wrapper only.
```
