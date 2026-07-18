# WP-12-07 — PMMS Arena Flutter Theme Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-12-07 | Title | PMMS Arena Flutter Theme Foundation |
| Epic | EPIC-12 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 118 |
| Target release group | Foundation Release E | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | UX reviewer |

## 2. Purpose

Ports WP-11-01's web design tokens into a Flutter `ThemeData` configuration, using the web tokens as the single source of truth so the mobile app visually matches PMMS Arena rather than looking like a different product.

## 3. Architecture Sources

[../../../../06-design/](../../../../06-design/), ADR-0009.

## 4. Scope

Implement `lib/core/theme/pmms_theme.dart` mapping WP-11-01's token values (colors, spacing, radius) into Flutter's `ThemeData`/`ColorScheme`; support light/dark per WP-11-02.

## 5. Explicit Exclusions

Does not finalize brand colors (same DX-02 dependency as WP-11-01); does not implement every widget's custom styling — base theme only.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-12-02, WP-11-01 | Hard |

## 7. Current-State Inspection

Default `flutter create` `ThemeData` as the starting point.

## 8. Proposed Implementation Direction

Manual token-value mirroring (no automated cross-platform token pipeline in Phase 1 — a future enhancement if token count grows significantly).

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Not Applicable.

## 11. Web Frontend Requirements

Not Applicable — this work package consumes WP-11-01's existing values as reference, doesn't modify web code.

## 12. Flutter Requirements

`pmms_theme.dart` with light/dark `ThemeData`.

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

Widget test/screenshot confirming the theme applies correctly in both light and dark mode.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record the theme mapping and its manual-mirroring limitation in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement `pmms_theme.dart` mirroring WP-11-01's tokens | WP-12-02, WP-11-01 complete | Screenshots captured |

## 24. Acceptance Criteria

- **AC-01:** Given the Flutter theme, when compared to WP-11-01's web tokens, then the color values match (using the same neutral placeholder palette).

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-12-02, WP-11-01 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Criterion verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): screenshots comparing web and mobile.

## 28. Rollback and Recovery Considerations

Not Applicable — new, additive theme file.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-12-07-01 | Manual token mirroring drifts from the web source over time as tokens change | Medium | Documented as a known limitation; a future automated pipeline is a candidate enhancement | UX reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-12-07 — PMMS Arena Flutter Theme Foundation.

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
- Use the same neutral placeholder palette as WP-11-01 — do not invent a different one for mobile.
```
