# PMMS Responsive, Touch, Keyboard, and Device Behavior

**Status:** Draft Complete — Pending Product, UX, Accessibility, Domain, and Engineering Validation
**Related:** [../04-quality/accessibility-usability-and-compatibility-testing.md](../04-quality/accessibility-usability-and-compatibility-testing.md) · [accessibility-architecture.md](accessibility-architecture.md)

This document defines responsive layout ranges, mobile adaptation rules, keyboard interaction, touch interaction, and motion/animation rules. **No CSS breakpoint, media query, or component code is created here.**

---

## 1. Responsive Architecture

### Major Layout Ranges (Conceptual)

Small mobile · large mobile · tablet · laptop · desktop · wide operations display · kiosk · scoreboard.

**No final breakpoint pixel values are hard-coded in this documentation** — restated per the phase's own working instruction to review Tailwind's actual defaults and real devices before finalizing. Tailwind CSS 4 (the confirmed technology direction) ships sensible default breakpoints; this document names the conceptual ranges a future implementation validates against real device testing, per [../04-quality/accessibility-usability-and-compatibility-testing.md, Section 3](../04-quality/accessibility-usability-and-compatibility-testing.md#3-compatibility-testing).

### Adaptation Rules by Component Category

| Component | Small-Screen Adaptation |
|---|---|
| Navigation | Sidebar collapses to a mobile-appropriate pattern (the existing `sidebar.tsx`/`use-mobile.tsx` hook already provides this mechanism) |
| Tables | Restated from Section 2 below |
| Forms | Single-column stacking, preserved field grouping |
| Dialogs | Full-screen or near-full-screen on small viewports rather than a small centered modal |
| Charts | Simplified axis labeling, vertical stacking of legend |
| Brackets | Per [sports-tournament-scoring-and-results-components.md, Section 1](sports-tournament-scoring-and-results-components.md#1-tournament-bracket-standards) — horizontal-scroll or a paginated round-by-round view |
| Scoreboards | Not applicable in the traditional responsive sense — a scoreboard targets a specific large-format display, per [public-portal-kiosk-scoreboard-and-display-experience.md](public-portal-kiosk-scoreboard-and-display-experience.md) |
| Side panels | Convert to a full-screen drawer/sheet on small viewports |
| Bulk actions | A persistent, reachable action bar rather than requiring horizontal scroll to reach |
| Filters | Collapse into a dedicated filter sheet rather than consuming permanent screen width |

## 2. Mobile Adaptation

On smaller screens: prioritize task completion over information density · reduce simultaneous information · use progressive disclosure · replace wide tables with cards, grouped rows, or horizontal scroll only where necessary (never as the default first choice) · preserve important context (the active meet/scope indicator never disappears to save space) · keep primary actions reachable (never requiring a scroll to find the one action a user came to perform) · avoid destructive actions near routine actions (adequate spacing, per Section 4) · support one-handed scanning where practical (primary actions within thumb reach) · preserve offline and sync visibility — restated absolutely, a mobile layout never sacrifices the connectivity/sync-state indicator to save space, given how load-bearing that indicator is per [status-feedback-error-offline-and-sync-patterns.md](status-feedback-error-offline-and-sync-patterns.md).

## 3. Keyboard Interaction

Logical tab order · visible focus (restated from [accessibility-architecture.md, Section 3](accessibility-architecture.md#3-focus-management)) · skip links · keyboard-accessible menus · keyboard-accessible dialogs · keyboard-accessible data grids · keyboard shortcuts for high-volume work where appropriate (e.g., rapid score entry) · Escape behavior (closes the topmost dialog/drawer without side effects) · Enter and Space behavior (activates the focused control, consistent with native semantics) · **no keyboard traps** — restated absolutely, a user can always Tab or Escape out of any component · shortcut discoverability (a documented, discoverable list, not a hidden power-user secret) · shortcut conflict prevention (no PMMS shortcut silently overrides a browser or OS-native shortcut).

## 4. Touch Interaction

Adequate target sizes (meeting the pending accessibility conformance target, per [accessibility-architecture.md](accessibility-architecture.md)) · spacing between destructive and primary actions — restated absolutely per working rule 40's spirit ("do not require drag-and-drop as the only way..."), extended here: a "Revoke" and a "Renew" action are never adjacent without deliberate separation · **no hover dependency** — restated absolutely per working rule 29; every hover-revealed affordance has a touch-accessible equivalent · swipe actions only as optional accelerators, never the sole path to a required action · long-press not required for critical actions · clear press feedback (visible state change on touch, not just on release) · scanner and kiosk optimized controls, per [accreditation-qr-device-and-shared-station-experience.md](accreditation-qr-device-and-shared-station-experience.md) · glove or field-condition consideration where relevant — larger, more forgiving touch targets for outdoor/field-use interfaces specifically.

## 5. Motion and Animation

### Appropriate Uses

Orientation (indicating where a new element came from) · state transition (a card expanding, a status changing) · feedback (a button press, a save confirmation) · loading · context change (a drawer sliding in).

### Avoided Uses

Decorative continuous animation · motion that blocks interaction (a user must never wait for an animation to finish before acting) · large parallax effects · rapid flashing (an accessibility hazard, per [accessibility-architecture.md](accessibility-architecture.md)) · **motion in high-pressure score entry** — restated absolutely, a scoring interface never adds animated delay between an entry and its confirmation · motion that obscures data changes (an update must be perceivable, not hidden behind a transition effect).

**Reduced-motion preferences are supported** — restated absolutely; every motion effect in Sections above respects the OS/browser-level "prefers-reduced-motion" signal, substituting an instant state change for an animated one.

## 6. Device and Browser Compatibility (Cross-Reference)

Supported browsers, device classes, and network-condition compatibility are detailed in [public-portal-kiosk-scoreboard-and-display-experience.md](public-portal-kiosk-scoreboard-and-display-experience.md) and [flutter-mobile-experience-architecture.md](flutter-mobile-experience-architecture.md) respectively — **no compatibility matrix is finalized without evidence**, restated per working rule from the Phase 0.9 prompt's "Compatibility Testing" guidance.

## 7. Open Questions

See [design-open-decisions.md](design-open-decisions.md) — notably final responsive breakpoint values (pending real-device testing) and whether keyboard-shortcut support is a launch requirement or a post-pilot enhancement.
