# EPIC-11 — PMMS Arena Web Design System Foundation

**Phase:** Phase 1 — Core Foundation Implementation
**Release Group:** Foundation Release D
**Status:** Planned — Not Started

## Purpose

Implement the minimum reusable React design-system foundation required for later modules — design tokens, theme, typography, application shell, and core components — building on the starter kit's existing partial shadcn/ui-style component set rather than starting from scratch.

## Architecture Sources

[../../../../06-design/](../../../../06-design/), ADR-0009.

## Work Packages

| ID | Title | Complexity | Priority |
|---|---|---|---|
| [WP-11-01](WP-11-01-design-token-implementation-foundation.md) | Design Token Implementation Foundation | Medium | P1 |
| [WP-11-02](WP-11-02-light-dark-and-semantic-theme-foundation.md) | Light, Dark, and Semantic Theme Foundation | Medium | P1 |
| [WP-11-03](WP-11-03-typography-and-iconography-foundation.md) | Typography and Iconography Foundation | Small | P1 |
| [WP-11-04](WP-11-04-application-shell-and-navigation-foundation.md) | Application Shell and Navigation Foundation | Large | P1 |
| [WP-11-05](WP-11-05-organization-and-meet-context-switcher-foundation.md) | Organization and Meet Context Switcher Foundation | Medium | P1 |
| [WP-11-06](WP-11-06-page-header-and-content-layout-foundation.md) | Page Header and Content Layout Foundation | Medium | P1 |
| [WP-11-07](WP-11-07-form-control-foundation.md) | Form Control Foundation | Medium | P1 |
| [WP-11-08](WP-11-08-button-badge-alert-and-feedback-foundation.md) | Button, Badge, Alert, and Feedback Foundation | Medium | P1 |
| [WP-11-09](WP-11-09-table-and-pagination-foundation.md) | Table and Pagination Foundation | Medium | P1 |
| [WP-11-10](WP-11-10-dialog-drawer-and-confirmation-foundation.md) | Dialog, Drawer, and Confirmation Foundation | Medium | P1 |
| [WP-11-11](WP-11-11-loading-empty-error-offline-and-stale-state-components.md) | Loading, Empty, Error, Offline, and Stale-State Components | Medium | P1 |
| [WP-11-12](WP-11-12-accessibility-and-keyboard-baseline.md) | Accessibility and Keyboard Baseline | Large | P1 |
| [WP-11-13](WP-11-13-design-system-documentation-and-test-foundation.md) | Design-System Documentation and Test Foundation | Medium | P2 |

## Dependencies

WP-01-03 (Hard), WP-10-02, WP-10-05, WP-10-07 (Hard).

## Completion Outcome

Design tokens, light/dark/semantic themes, typography/iconography, application shell/navigation, context switcher, layout, form/button/table/dialog/feedback/empty-state components, and an accessibility/keyboard baseline.

## Deferred Items

Every future sport-specific component (brackets, heat sheets, scoreboards); branding-palette final approval (DX-02) — a neutral placeholder palette is used pending approval.

## Risks

RISK-EPIC11-01 — WCAG target unresolved (DX-01) could require rework of WP-11-12 if the eventual target exceeds the provisional AA assumption (DEC-GENERAL-01).
