# PMMS Color, Theme, and Surface System

**Status:** Draft Complete — Pending Product, UX, Accessibility, Domain, and Engineering Validation
**Related:** [design-tokens-and-visual-language.md](design-tokens-and-visual-language.md) · [../04-quality/accessibility-usability-and-compatibility-testing.md](../04-quality/accessibility-usability-and-compatibility-testing.md)

This document defines color architecture, branding direction, theme architecture, and glass UI rules. **No color value, theme file, or branding asset is created here.**

---

## 1. Color Architecture

| Category | Purpose |
|---|---|
| Brand colors | PMMS institutional identity, per Section 2 |
| Neutral colors | The existing OKLCH neutral scale already in `app.css` (`baseColor: "neutral"` per `components.json`) |
| Semantic colors | Success/warning/danger/information, plus the six PMMS-specific state tokens from [design-tokens-and-visual-language.md, Section 2](design-tokens-and-visual-language.md#2-token-architecture) |
| Data visualization colors | The existing five `--chart-*` tokens, extended per [dashboard-table-chart-and-data-visualization-standards.md](dashboard-table-chart-and-data-visualization-standards.md) |
| Sports colors | Per-sport visual differentiation (e.g., in a multi-sport schedule view) — a candidate, non-authoritative categorical palette |
| Committee colors | Per-committee visual differentiation in dashboards — same candidate, non-authoritative status |
| Public-display colors | A simplified, higher-contrast subset for the Public Portal and Scoreboard surfaces |
| Theme-specific values | Every color category above resolves differently per theme (Section 4), never a single hard-coded value |

### Rules

1. **Semantic meaning remains consistent across themes** — "danger" is always the danger color, in light, dark, high-contrast, or scoreboard theme; only its literal value shifts.
2. **Color is never the only state signal** — restated absolutely per working rule 28; every color-coded state also carries an icon, text label, or pattern.
3. **Contrast must be validated** — every color pairing meets the (pending) accessibility conformance target from [accessibility-architecture.md](accessibility-architecture.md) before being finalized, not assumed compliant by inspection.
4. **Sports or committee colors cannot override danger, warning, success, or certification semantics** — a sport's assigned color never gets reused to mean "error" elsewhere on the same screen, avoiding a false pattern-match.
5. **Public displays use simplified, high-contrast palettes** — restated from [public-portal-kiosk-scoreboard-and-display-experience.md](public-portal-kiosk-scoreboard-and-display-experience.md).
6. **Excessive gradients and neon effects are avoided in operational screens** — decorative color treatments are reserved for low-stakes surfaces per Section 5's glass-UI rules.

## 2. Branding Direction (Candidate, Requires Validation)

A professional visual identity appropriate for DepEd sports operations, using a candidate palette direction:

Deep navy or institutional blue · maroon or deep red accents · golden yellow or championship gold · neutral grays · white or near-white surfaces.

**This is a candidate palette requiring branding validation** — restated absolutely; no specific hex/OKLCH value is finalized in this document. **The official DepEd seal or any protected mark is never used without approved usage guidance** — restated absolutely per working rule 13's prohibition on generating logos/protected marks, extended here as an explicit rule against *using* one without permission even once available.

## 3. Relationship to the Existing Token Foundation

The confirmed `app.css` foundation (Section, [design-tokens-and-visual-language.md, Section 1](design-tokens-and-visual-language.md#1-confirmed-existing-token-foundation-repository-evidence)) is neutral-toned by default (`baseColor: "neutral"`). The candidate branding direction in Section 2 above is intended to populate `--primary` and related brand-carrying tokens once approved — replacing the current neutral `--primary: oklch(0.205 0 0)` value with an approved institutional color, not restructuring the token architecture itself.

## 4. Theme Architecture

| Theme | Purpose |
|---|---|
| Light | Primary administrative and daytime theme — the current `:root` default in `app.css` |
| Dark | Low-light, command-center, and user-preference option — the current `.dark` class default, already functional via the confirmed `use-appearance.tsx` hook and `appearance-tabs.tsx` component |
| High-Contrast | Accessibility and outdoor-use option — not yet implemented; a candidate third theme mode |
| Scoreboard | Large-format public display — a distinct visual treatment, per [public-portal-kiosk-scoreboard-and-display-experience.md, Section 3](public-portal-kiosk-scoreboard-and-display-experience.md#3-public-scoreboard-and-venue-display-scoreboard-standards) |
| Kiosk | Touch-oriented, simplified navigation — per [public-portal-kiosk-scoreboard-and-display-experience.md, Section 2](public-portal-kiosk-scoreboard-and-display-experience.md#2-kiosk-experience) |
| Print | Official documents and printable reports, per [dashboard-table-chart-and-data-visualization-standards.md](dashboard-table-chart-and-data-visualization-standards.md) |

**All themes preserve semantic state meaning** — restated absolutely; a "certified" badge is recognizably the same semantic color family whether viewed in Light, Dark, or High-Contrast theme, even though its exact rendered value differs.

## 5. Glass UI Rules

### Appropriate Uses

Landing page hero sections · public portal feature areas · overview cards · non-critical visual emphasis · selected dashboard summaries.

### Avoided or Limited Uses

Dense tables · score entry · eligibility review · medical records · finance · critical dialogs · outdoor displays · low-performance mobile devices · accessibility-sensitive surfaces.

**Glass effects must not reduce contrast or performance** — restated absolutely per working rule 27's prohibition on decorative glass effects reducing clarity on high-density or safety-critical interfaces. A glass treatment is a deliberate, evaluated choice for a specific low-stakes surface, never a default applied platform-wide.

## 6. Outdoor-Display and Kiosk-Display Modes (Cross-Reference)

Outdoor-display mode (maximizing contrast and distance-readability for scoreboards) and kiosk-display mode (maximizing touch-target size and privacy) are both detailed in [public-portal-kiosk-scoreboard-and-display-experience.md](public-portal-kiosk-scoreboard-and-display-experience.md) — this document establishes that both are distinct theme variants (Section 4), not merely responsive breakpoints of the administrative theme.

## 7. Print Styles

A dedicated Print theme (Section 4) governs official document and report rendering — high-contrast, no interactive-only affordances (no hover states, no collapsed content that can't be expanded on paper), and no reliance on color alone (restated from Section 1, Rule 2) since printed output is frequently monochrome. Full print-specific data-display rules: [dashboard-table-chart-and-data-visualization-standards.md](dashboard-table-chart-and-data-visualization-standards.md).

## 8. Open Questions

See [design-open-decisions.md](design-open-decisions.md) — notably final branding-palette approval (Section 2, blocking every subsequent visual-identity decision) and whether High-Contrast is a genuinely separate theme or a set of contrast-boosting overrides applied to Light/Dark.
