# PMMS Accessibility Architecture

**Status:** Draft Complete — Pending Product, UX, Accessibility, Domain, and Engineering Validation
**Related:** [../04-quality/accessibility-usability-and-compatibility-testing.md](../04-quality/accessibility-usability-and-compatibility-testing.md) · [responsive-touch-keyboard-and-device-behavior.md](responsive-touch-keyboard-and-device-behavior.md)

This document defines PMMS's accessibility architecture, screen-reader behavior, and focus management. **Accessibility is treated as a core product requirement, not an enhancement** — restated absolutely per working rule 37. **No compliance is claimed until tested and formally validated.**

---

## 1. Accessibility Architecture

WCAG is used as a **candidate reference requiring final validation** — restated per the phase's own instruction; no specific conformance level (A/AA/AAA) is claimed as achieved in this documentation, consistent with [../03-security/compliance-control-framework.md, Section 1](../03-security/compliance-control-framework.md#1-framework-candidates)'s compliance-language discipline extended to accessibility specifically.

Covered dimensions: perceivable content · operable controls · understandable workflows · robust semantics · keyboard access (per [responsive-touch-keyboard-and-device-behavior.md, Section 3](responsive-touch-keyboard-and-device-behavior.md#3-keyboard-interaction)) · screen readers (Section 2 below) · contrast (per [color-theme-and-surface-system.md, Section 1](color-theme-and-surface-system.md#1-color-architecture)) · text scaling (per [typography-iconography-and-content-style.md, Section 1](typography-iconography-and-content-style.md#1-typography)) · motion (per [responsive-touch-keyboard-and-device-behavior.md, Section 5](responsive-touch-keyboard-and-device-behavior.md#5-motion-and-animation)) · focus (Section 3 below) · error identification · form labels · table semantics · chart alternatives (per [dashboard-table-chart-and-data-visualization-standards.md, "Accessible Charts"](dashboard-table-chart-and-data-visualization-standards.md#accessible-charts)) · time-limit handling · live-region behavior · modal behavior · language attributes · kiosk accessibility · scoreboard readability.

## 2. Screen-Reader Architecture

| Requirement | Direction |
|---|---|
| Semantic headings | A logical, non-skipping heading hierarchy on every page |
| Landmark regions | Navigation, main content, and complementary regions are programmatically identified |
| Accessible names | Every interactive control has a name a screen reader can announce, restated from [typography-iconography-and-content-style.md, Section 2](typography-iconography-and-content-style.md#2-iconography) |
| Descriptions | Supplementary context (e.g., a field's help text) is programmatically associated, not merely visually adjacent |
| Live-region announcements | State changes a sighted user perceives visually (a new notification, a completed sync) are announced to screen-reader users too |
| Dynamic update behavior | An update to a table/dashboard doesn't silently happen off-screen from an assistive-technology perspective |
| Table headers | Data tables use proper header semantics, never a purely visual table-like layout |
| Sort announcements | Changing a table's sort order is announced |
| Validation summaries | A form's error summary is announced and navigable, not just visually listed |
| Dialog titles | Every dialog/drawer has an announced title on open |
| Score update announcements | A live-scoring or scoreboard update is announced via a throttled live region — never silent, never overwhelming |
| Real-time throttling | Rapid successive real-time updates are throttled for announcement purposes, avoiding a screen-reader user being flooded |
| QR scan result announcements | The Section, [accreditation-qr-device-and-shared-station-experience.md](accreditation-qr-device-and-shared-station-experience.md), granted/denied/warning result is announced audibly, not solely displayed visually |
| Offline-state announcements | A connectivity-state change is announced, restated from [status-feedback-error-offline-and-sync-patterns.md](status-feedback-error-offline-and-sync-patterns.md) |

## 3. Focus Management

Page transition focus (an Inertia page transition moves focus to a sensible starting point, typically the new page's heading) · dialog open and close (focus moves into the dialog on open, returns to the triggering element on close) · drawer open and close (same pattern) · validation error focus (focus moves to the first error or the error summary) · successful submission focus (focus moves to a confirmation message or the resulting record) · added row focus (a newly-added table row receives focus or an announcement) · deleted row recovery (focus moves sensibly to an adjacent row or an undo affordance, never lost entirely) · real-time update behavior (restated absolutely below) · route-change behavior in Inertia and Flutter navigation (both platforms manage focus deliberately on navigation, not left to browser/OS default).

**Avoid stealing focus during background updates** — restated absolutely as the single most important focus-management rule; a real-time score update or a background sync completing must never yank keyboard focus away from what the user is actively doing.

## 4. Accessibility Testing Expectations (Cross-Reference)

Accessibility testing (automated checks, manual screen-reader testing, keyboard-only testing) is defined in full in [../04-quality/accessibility-usability-and-compatibility-testing.md](../04-quality/accessibility-usability-and-compatibility-testing.md) (Phase 0.7) and [ux-research-validation-and-quality-gates.md](ux-research-validation-and-quality-gates.md) — not duplicated here. This document defines the architectural *requirements*; that document defines how they're *verified*.

## 5. High-Contrast Support

A candidate dedicated theme mode (per [color-theme-and-surface-system.md, Section 4](color-theme-and-surface-system.md#4-theme-architecture)) boosting contrast beyond the standard Light/Dark themes, serving both low-vision users and outdoor-display readability — the same underlying need (maximum perceivable contrast) serves two different contexts (an accessibility preference and an environmental condition), and this document treats them as related but not identical: a user's explicit High-Contrast preference is respected regardless of device, while outdoor-display mode (per [public-portal-kiosk-scoreboard-and-display-experience.md](public-portal-kiosk-scoreboard-and-display-experience.md)) is a context-driven default for scoreboard surfaces specifically.

## 6. Kiosk and Scoreboard Accessibility

Kiosk accessibility (touch-target size, session-timeout consideration for users who need more time, screen-reader support where kiosk hardware provides it) and scoreboard readability (distance-legible text, high contrast, no reliance on fine detail) are both named accessibility concerns in their own right, not merely usability concerns — restated from working rule 39's readability-over-decoration priority for public/outdoor displays, extended here to include users with visual or motor accessibility needs at these surfaces specifically.

## 7. Open Questions

See [design-open-decisions.md](design-open-decisions.md) — notably the specific WCAG conformance target PMMS commits to (A, AA, or AAA — none selected in this document) and the timeline for formal accessibility audit/validation.
