# PMMS Design Tokens and Visual Language

**Status:** Draft Complete — Pending Product, UX, Accessibility, Domain, and Engineering Validation
**Related:** [cross-platform-design-system-architecture.md](cross-platform-design-system-architecture.md) · [color-theme-and-surface-system.md](color-theme-and-surface-system.md)

This document defines design-token categories, spacing/sizing/radius/elevation scales, and surface hierarchy conceptually. **No token file (TypeScript, CSS, JSON, or Dart) is created here**, per working rule 12 — every value below describes a category and, where the repository already demonstrates a concrete value, cites it as evidence rather than inventing a new one.

---

## 1. Confirmed Existing Token Foundation (Repository Evidence)

This repository's `resources/css/app.css` already defines a complete shadcn/ui-standard token set using Tailwind 4's `@theme` directive and OKLCH color values: `--background`, `--foreground`, `--card`, `--popover`, `--primary`, `--secondary`, `--muted`, `--accent`, `--destructive`, `--border`, `--input`, `--ring`, five `--chart-*` values, eight `--sidebar-*` values, and a single `--radius` primitive (`0.625rem`) from which `--radius-lg`/`--radius-md`/`--radius-sm` derive. A `.dark` class provides the dark-theme override for every one of these. **This is the unbranded shadcn starter-kit default palette** — PMMS-specific brand colors (Section, [color-theme-and-surface-system.md, Section 2](color-theme-and-surface-system.md#2-branding-direction-candidate-requires-validation)) are layered onto this same token structure, not a replacement structure.

## 2. Token Architecture

### Primitive Tokens

Base colors · font families (currently `Instrument Sans`, per the confirmed `--font-sans` value) · font sizes · spacing values · radius values (currently a single `--radius: 0.625rem` primitive) · shadow values · motion durations.

### Semantic Tokens

Background · surface · elevated surface · text · muted text · border · focus · primary action · secondary action · success · warning · danger · information · **provisional · certified · published · held · offline · conflict**.

The last six — provisional, certified, published, held, offline, conflict — are **PMMS-specific semantic tokens with no shadcn/ui equivalent**, required directly by working rule 33's mandate that official score entry, result certification, eligibility decisions, and medal tallying always show explicit state. These are new semantic additions this design system introduces on top of the existing `--success`/`--warning`/`--destructive`-style tokens shadcn's default palette already provides.

### Component Tokens

Button · input · table · card · dialog · sidebar (already partially defined via `--sidebar-*`) · scoreboard · medal tally · status badge.

**No token file is created in this phase** — restated absolutely; Sections above name the categories a future `resources/css/app.css` extension and a future Flutter `ThemeData` must both implement consistently.

## 3. Typography Scale (Conceptual)

Display · heading · body · label · caption · numeric/scoreboard · monospaced (for data where alignment matters, e.g., timing values). Full typographic requirements (readability, tabular numbers, text scaling) are detailed in [typography-iconography-and-content-style.md, Section 1](typography-iconography-and-content-style.md#1-typography). This document names the scale's *categories*; specific size/weight/line-height values are an implementation-phase decision informed by accessibility testing (per [../04-quality/accessibility-usability-and-compatibility-testing.md](../04-quality/accessibility-usability-and-compatibility-testing.md)), not fixed here.

## 4. Spacing and Density Scale

A conceptual spacing scale supports five density modes:

| Density Mode | Primary Use |
|---|---|
| Comfortable | Default administrative screens, forms |
| Compact | Dense administrative tables, high-volume review screens |
| Public display | Public portal, kiosk |
| Touch | Mobile, tablet, scanner interfaces |
| Mobile | Flutter application default |

### Rules

Dense administrative screens may use compact mode · touch targets must remain accessible regardless of density (restated from [responsive-touch-keyboard-and-device-behavior.md, Section 4](responsive-touch-keyboard-and-device-behavior.md#4-touch-interaction)) · score-entry interfaces should minimize unnecessary vertical movement, per [sports-tournament-scoring-and-results-components.md, Section 4](sports-tournament-scoring-and-results-components.md#4-score-entry-interfaces) · mobile must not reproduce desktop density · users may receive density preferences in a later implementation phase · **critical information must not be hidden solely to reduce density** — restated absolutely, density is a visual-efficiency choice, never an information-suppression mechanism.

## 5. Sizing and Border-Radius Scale

A conceptual sizing scale (icon sizes, control heights, touch-target minimums) and a border-radius scale extending the confirmed `--radius: 0.625rem` primitive — `--radius-lg`/`--radius-md`/`--radius-sm` already derive from it in the existing `app.css`. No new radius primitive is introduced without evidence it's needed; the existing single-primitive-with-derived-scale approach is confirmed sufficient for a "new-york" style shadcn implementation.

## 6. Elevation and Shadow

A conceptual elevation scale (flat, raised, floating, overlay) communicating layering — cards sit above the background, dialogs/drawers/tooltips sit above cards, and so on. Elevation is expressed through shadow and, secondarily, subtle background-tone shifts — never through z-index alone without a corresponding visual cue, since a purely positional layering cue is invisible to a screen-reader user and easy to miss visually under time pressure.

## 7. Surface Hierarchy

Background → Surface (card, panel) → Elevated surface (popover, dropdown) → Overlay (dialog, drawer, tooltip) — a consistent four-level hierarchy every component's visual weight is checked against. The existing token set's `--card`, `--popover`, and (implicitly) overlay-level components already establish three of these four levels; this document names the conceptual model the existing tokens already partially implement.

## 8. Open Questions

See [design-open-decisions.md](design-open-decisions.md) — notably whether the six new PMMS-specific semantic state tokens (provisional/certified/published/held/offline/conflict) are added directly to `app.css`'s existing token structure or introduced as a separate PMMS-specific token layer, and final typography-scale sizing pending accessibility validation.
