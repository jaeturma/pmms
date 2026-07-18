# PMMS Experience Vision and Design Principles

**Status:** Draft Complete — Pending Product, UX, Accessibility, Domain, and Engineering Validation
**Related:** [../00-product/phase-0.1-product-foundation.md](../00-product/phase-0.1-product-foundation.md) · [user-groups-personas-and-contexts.md](user-groups-personas-and-contexts.md) · [../04-quality/accessibility-usability-and-compatibility-testing.md](../04-quality/accessibility-usability-and-compatibility-testing.md)

This document defines PMMS's experience vision, UX principles, design principles, and the operational contexts every design decision must serve. **No component, token, or visual asset is created here.**

---

## 1. Experience Vision

The PMMS experience should be: trustworthy · clear · fast to understand · efficient under time pressure · role-appropriate · accessible · responsive · mobile-capable · offline-aware · privacy-respecting · audit-aware · consistent · flexible · visually professional · appropriate for government and public use · suitable for commercial deployment.

This restates, at the interface layer, the same "commercial-quality, institutional-trust" direction established in [../00-product/phase-0.1-product-foundation.md, Section 18](../00-product/phase-0.1-product-foundation.md#18-commercial-quality-product-direction) — a platform whose data model, security architecture, and quality gates are all designed to institutional standards must present an interface that looks and behaves like it deserves that trust.

## 2. UX Principles

1. **Clarity before decoration** — every visual choice serves comprehension first.
2. **Integrity before speed** — a faster path that risks a wrong eligibility approval or an unclear result state is never preferred over a slightly slower, unambiguous one.
3. **Show state explicitly** — draft, provisional, validated, certified, held, superseded, and published are never visually indistinguishable.
4. **Make authority visible** — a user always understands whose action produced what they're viewing, and whether they themselves have authority to act.
5. **Prevent rather than merely report errors** — validation and constraint feedback happen before submission wherever feasible, not only after.
6. **Preserve user work** — a lost form entry, a discarded draft, or an unsaved offline record is a design failure, not an acceptable inconvenience.
7. **Support recovery** — every error state has a path forward, never a dead end.
8. **Design for field conditions** — glare, gloves, one-handed operation, and shared devices are normal operating conditions, not edge cases.
9. **Design for intermittent connectivity** — restated absolutely from every prior phase; the interface never assumes a stable connection.
10. **Minimize cognitive load** — especially in time-pressured contexts (live scoring, gate scanning).
11. **Use progressive disclosure** — advanced/rare options are available but not default-visible clutter.
12. **Keep common actions close** — the action a role performs most often is never buried.
13. **Make dangerous actions difficult to trigger accidentally** — a destructive or high-integrity action requires deliberate confirmation, never a stray click.
14. **Make system feedback immediate** — an action's acknowledgment is instantaneous even when its full processing is not.
15. **Make delayed processing visible** — a queued export, a syncing record, or a pending certification is never silently invisible.
16. **Use plain language** — restated from [content-design-terminology-help-and-onboarding.md](content-design-terminology-help-and-onboarding.md).
17. **Never use color alone** — restated absolutely, per working rule 28.
18. **Respect privacy by default** — the interface never over-exposes data because doing so was easier to build.
19. **Keep public and internal information clearly separated** — a user is never confused about whether what they're viewing is publicly visible.
20. **Provide consistent cross-platform terminology** — the same word means the same thing on web and mobile, always.

## 3. Design Principles

1. **Shared foundations, platform-appropriate components** — React and Flutter share semantics, not identical pixel layouts.
2. **Semantic design tokens** — every color/spacing/type choice carries meaning, never a bare hex value.
3. **Accessible defaults** — the default state of any component meets accessibility requirements without extra configuration.
4. **Responsive by design** — no component is designed desktop-only and retrofitted later.
5. **Touch and keyboard equivalence** — every interaction achievable by touch is achievable by keyboard, and vice versa.
6. **Data density with readability** — administrative screens favor information density, but never at the cost of legibility.
7. **State-driven interfaces** — a component's appearance is derived from actual record state, never merely from local UI state pretending to reflect it.
8. **Consistent spacing and typography** — restated from [design-tokens-and-visual-language.md](design-tokens-and-visual-language.md).
9. **Minimal decorative complexity in operational screens** — glass effects, gradients, and heavy motion are reserved for low-stakes, non-dense surfaces per [color-theme-and-surface-system.md, Section 5](color-theme-and-surface-system.md#5-glass-ui-rules).
10. **Selective glass effects** — used deliberately, never as a default treatment for every surface.
11. **High readability for outdoor displays** — restated absolutely per working rule 39.
12. **Clear visual hierarchy** — the most important information on any screen is visually dominant, not merely first in the DOM.
13. **Predictable interaction patterns** — the same gesture/control always produces the same class of outcome across the platform.
14. **Design-system reuse before one-off components** — a new one-off component is a last resort, not a default.
15. **Design decisions traceable to user and operational needs** — every non-obvious design choice in this package cites the context or user group it serves.

## 4. Why PMMS Needs an Experience Architecture Before Implementation

Six phases of architecture (Phases 0.2–0.8) established what PMMS's data, authorization, security, quality, and operations must guarantee. None of them specified how a human — a tournament manager under time pressure, a scanner operator in bright sunlight, a guardian checking a public result on a low-bandwidth connection — actually experiences the system. Without this phase, an implementation team would design 34 modules' worth of screens independently, producing exactly the same inconsistency risk every prior phase's centralization existed to prevent — now expressed as unpredictable terminology, inconsistent state visualization, and accessibility gaps discovered only after real users struggle with them.

## 5. Experience Contexts

Nineteen major usage contexts, each with a distinct environment/device/connectivity/time-pressure/data-sensitivity/skill/accessibility/error-cost/recovery profile:

| Context | Environment | Connectivity | Time Pressure | Data Sensitivity | Error Cost |
|---|---|---|---|---|---|
| Office administration | Indoor, desk-based | Stable | Low–Moderate | Varies | Low–Moderate |
| Secretariat operations | Indoor, desk-based | Stable | Moderate | Confidential–Restricted | Moderate |
| Eligibility review | Indoor, desk-based | Stable | Moderate (deadline-driven) | Restricted | High |
| Tournament preparation | Indoor/venue, mixed | Stable–Intermittent | Moderate–High | Internal | Moderate |
| Live competition | Venue, outdoor/indoor | Intermittent–Offline | Critical | Internal–Public | Critical |
| Score encoding | Venue-side | Intermittent–Offline | Critical | Internal | Critical |
| Result certification | Venue or office | Stable–Intermittent | High | Internal | Critical |
| Public viewing | Anywhere | Variable, often low-bandwidth | Low | Public | Low |
| Venue operations | Venue | Intermittent | High | Internal | Moderate–High |
| QR scanning | Gate/entry point, outdoor | Intermittent–Offline | Critical (throughput) | Restricted | High (security) |
| Shared workstation | Venue/office | Stable–Intermittent | Moderate | Varies | Moderate (privacy) |
| Kiosk | Public venue area | Stable | Low | Public only | Low (privacy-critical) |
| Outdoor scoreboard | Venue, direct sunlight | N/A (display only) | N/A | Public | Low |
| Mobile field work | Venue, moving | Intermittent–Offline | High | Varies | Moderate–High |
| Offline venue work | Venue, no connectivity | Offline | Critical | Internal | Critical (sync-dependent) |
| Medical response | Venue | Intermittent | Critical | Highly Restricted | Critical (safety) |
| Security incident | Venue | Intermittent | Critical | Highly Restricted | Critical |
| Finance review | Office | Stable | Low–Moderate | Restricted | Moderate |
| Post-event reporting | Office | Stable | Low | Internal–Public | Low–Moderate |

Full per-context detail, including user skill and recovery-need dimensions, is developed alongside the relevant user-group entries in [user-groups-personas-and-contexts.md](user-groups-personas-and-contexts.md).

## 6. Open Questions

See [design-open-decisions.md](design-open-decisions.md) for every unresolved vision- and principle-level question this document depends on.
