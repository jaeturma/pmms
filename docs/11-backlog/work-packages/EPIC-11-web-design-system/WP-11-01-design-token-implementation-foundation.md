# WP-11-01 — Design Token Implementation Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-11-01 | Title | Design Token Implementation Foundation |
| Epic | EPIC-11 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 99 |
| Target release group | Foundation Release D | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | Cross-cutting (design system) |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | UX reviewer |

## 2. Purpose

Implements PMMS Arena's design-token layer (color, spacing, radius, shadow scales) as Tailwind 4 CSS custom properties, using a neutral placeholder palette pending DX-02's brand approval — the foundation every later EPIC-11 component builds on.

## 3. Architecture Sources

[../../../../06-design/](../../../../06-design/), ADR-0009, [../../../../dataviz skill's palette guidance if applicable].

## 4. Scope

Define token scales in `resources/css/` (Tailwind 4's CSS-based token approach, per the existing `@tailwindcss/vite` setup); a neutral placeholder color palette (grayscale + one accent), spacing scale, radius scale.

## 5. Explicit Exclusions

Does not finalize brand colors (DX-02 remains open); does not implement any component yet (WP-11-02 onward).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-01-03 | Hard |

## 7. Current-State Inspection

Existing Tailwind 4 setup (`tailwind.config` is CSS-based per v4's convention, `@tailwindcss/vite` plugin present); existing `resources/js/components/ui/` primitives use Tailwind utility classes directly, without a formalized token layer yet.

## 8. Proposed Implementation Direction

Define CSS custom properties for tokens in a new `resources/css/tokens.css` (proposed), consumed by Tailwind 4's `@theme` directive; existing components incrementally migrate to reference tokens rather than hard-coded utility values (not a forced mass-migration in this work package — new components use tokens from the start).

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Not Applicable.

## 11. Web Frontend Requirements

New `resources/css/tokens.css`; Tailwind `@theme` configuration referencing it.

## 12. Flutter Requirements

Not Applicable directly — WP-12-07's Flutter theme derives from these same token values as its source of truth.

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

Visual regression baseline (screenshot) confirming tokens render correctly; not exhaustive per-component testing (too early — no components built yet).

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record the token scale and its provisional/neutral status (pending DX-02) in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Define token CSS custom properties (color, spacing, radius, shadow) | WP-01-03 complete | Tokens defined |
| TASK-02 | Wire tokens into Tailwind 4's `@theme` | TASK-01 | Build succeeds |

## 24. Acceptance Criteria

- **AC-01:** Given the token file, when reviewed, then it uses a neutral placeholder palette, explicitly not presented as final branding.
- **AC-02:** Given the Tailwind build, when run with tokens wired in, then it completes without error.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-01-03 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): screenshots of the token palette, build output.

## 28. Rollback and Recovery Considerations

Not Applicable — new, additive CSS file.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-11-01-01 | Placeholder palette is mistaken for final branding by a later reviewer or stakeholder | Low | AC-01's explicit labeling; DX-02 tracked as the resolving decision | UX reviewer |

## 30. Open Decisions

| Decision ID | Question | Blocking Status |
|---|---|---|
| DEC-WP-11-01-01 | Final brand palette (DX-02) | Non-blocking — neutral placeholder used |

## 31. Future Claude Code Execution Prompt

```text
Implement WP-11-01 — Design Token Implementation Foundation.

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
- Use a neutral placeholder palette only — do not attempt to finalize brand colors (DX-02 remains open).
```
