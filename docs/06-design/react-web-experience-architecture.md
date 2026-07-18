# PMMS React Web Experience Architecture

**Status:** Draft Complete — Pending Product, UX, Accessibility, Domain, and Engineering Validation
**Related:** [../01-architecture/react-inertia-architecture.md](../01-architecture/react-inertia-architecture.md) · [cross-platform-design-system-architecture.md](cross-platform-design-system-architecture.md)

This document defines the React/Inertia web experience direction. **No React component, route, or page is created here.**

---

## 1. React Web Experience Direction

| Element | Direction |
|---|---|
| Responsive administrative workspace | Per [responsive-touch-keyboard-and-device-behavior.md](responsive-touch-keyboard-and-device-behavior.md) |
| Feature-oriented layouts | Mirroring [../01-architecture/react-inertia-architecture.md](../01-architecture/react-inertia-architecture.md)'s feature-oriented directory grouping — the UI structure follows the same bounded-context organization as the code |
| Stable navigation | The existing `app-sidebar.tsx`/`app-header.tsx` shell is the confirmed foundation, per [information-architecture-and-navigation.md, Section 5](information-architecture-and-navigation.md#5-relationship-to-existing-repository-structure) |
| Dense but readable tables | Per [dashboard-table-chart-and-data-visualization-standards.md](dashboard-table-chart-and-data-visualization-standards.md) |
| Keyboard productivity | Per [responsive-touch-keyboard-and-device-behavior.md, Section 3](responsive-touch-keyboard-and-device-behavior.md#3-keyboard-interaction) |
| Inertia page transitions | Focus-managed per [accessibility-architecture.md, Section 3](accessibility-architecture.md#3-focus-management) |
| Server-controlled page state | Restated absolutely from [../01-architecture/react-inertia-architecture.md, Section 5](../01-architecture/react-inertia-architecture.md#5-rules) — the server, never the client, is authoritative for page data |
| Real-time reconciliation | Per [status-feedback-error-offline-and-sync-patterns.md, Section 8](status-feedback-error-offline-and-sync-patterns.md#8-real-time-experience-cross-reference) |
| Accessible dialogs and drawers | Building on the existing `dialog.tsx`/`sheet.tsx` components, per [accessibility-architecture.md](accessibility-architecture.md) |
| Theme support | The existing `use-appearance.tsx`/`appearance-tabs.tsx` Light/Dark foundation, extended per [color-theme-and-surface-system.md, Section 4](color-theme-and-surface-system.md#4-theme-architecture) |
| Print support | Per the Print theme in [color-theme-and-surface-system.md, Section 7](color-theme-and-surface-system.md#7-print-styles) |

## 2. Server-Authoritative Permissions (Restated)

Frontend permission checks are usability controls only, never the security boundary — restated absolutely from [../01-architecture/react-inertia-architecture.md, Section 5](../01-architecture/react-inertia-architecture.md#5-rules) and [../03-security/application-api-and-client-security.md, Section 5](../03-security/application-api-and-client-security.md#5-react-and-inertia-security). Every UI element that hides based on role/permission does so for clarity, while the server independently enforces the actual restriction on every request — restated per working rule 25.

## 3. Sensitive-Data Handling in React Props

No secret, and no Restricted/Highly Restricted-tier field beyond what the specific page's authorized viewer needs, ever appears in an Inertia page prop — restated absolutely from [../03-security/application-api-and-client-security.md, Section 5](../03-security/application-api-and-client-security.md#5-react-and-inertia-security), directly informing working rule 26's prohibition on exposing protected information through page props.

## 4. React and Flutter Cross-Platform Consistency (Cross-Reference)

Shared terminology, semantic colors, status meanings, and permission behavior are defined once in [cross-platform-design-system-architecture.md](cross-platform-design-system-architecture.md) and consumed identically by this document and [flutter-mobile-experience-architecture.md](flutter-mobile-experience-architecture.md) — not redefined here. React and Flutter may differ in navigation, layout, interaction density, platform controls, back behavior, touch patterns, native permissions, and device integration, per [cross-platform-design-system-architecture.md, Section 3](cross-platform-design-system-architecture.md#3-platform-specific-implementations).

## 5. Open Questions

See [design-open-decisions.md](design-open-decisions.md) — notably whether a command palette (a candidate power-user accelerator) is adopted, and final print-stylesheet scope.
