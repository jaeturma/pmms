# PMMS Typography, Iconography, and Content Style

**Status:** Draft Complete — Pending Product, UX, Accessibility, Domain, and Engineering Validation
**Related:** [design-tokens-and-visual-language.md, Section 3](design-tokens-and-visual-language.md#3-typography-scale-conceptual) · [../03-security/minor-athlete-and-guardian-data-governance.md](../03-security/minor-athlete-and-guardian-data-governance.md)

This document defines typography requirements, iconography rules, and photography/media rules. **No font file, icon set, or image asset is created here**, per working rules 12–13.

---

## 1. Typography

### Typography Needs

Display · heading · body · label · caption · numeric/scoreboard · monospaced data where useful (e.g., aligned timing/measurement columns).

### Rules

1. **Prioritize readability** over decorative typographic flourish, especially in operational and public-facing surfaces.
2. **Support long labels** — delegation names, event names, and committee titles vary widely in length; typography must not assume short labels.
3. **Avoid overly narrow fonts** — condensed typefaces reduce legibility, particularly at distance (scoreboards) or for users with low vision.
4. **Use tabular numbers for scores, timing, and tallies where appropriate** — misaligned numeric columns in a medal tally or results table are a comprehension risk, not merely an aesthetic one.
5. **Support text scaling** — restated from [accessibility-architecture.md](accessibility-architecture.md); no layout breaks when a user increases browser/OS text size.
6. **Avoid all-capital paragraphs** — reduces readability and increases the perception of shouting.
7. **Use uppercase selectively for compact status labels** — e.g., a badge reading "CERTIFIED," never a full sentence.
8. **Public scoreboards require distance-readable sizing** — restated absolutely from [public-portal-kiosk-scoreboard-and-display-experience.md, Section 3](public-portal-kiosk-scoreboard-and-display-experience.md#3-public-scoreboard-and-venue-display-scoreboard-standards).

### Font Selection

**No proprietary font requiring distribution is chosen** — restated per the phase's own working instruction. This repository's confirmed `--font-sans: 'Instrument Sans', ...` (per `app.css`) is a freely-licensed, web-safe-fallback-equipped choice already in place; any future PMMS-specific typography decision (e.g., a dedicated numeric/scoreboard face) is evaluated against the same licensing-freedom requirement.

## 2. Iconography

### Rules

1. **Consistent icon family** — restated from [cross-platform-design-system-architecture.md, Section 3](cross-platform-design-system-architecture.md#3-platform-specific-implementations); Lucide is the confirmed React icon set (`iconLibrary: "lucide"` in `components.json`), with a Flutter-compatible equivalent mapped as closely as practical.
2. **Icons paired with labels for critical actions** — a certify/publish/revoke action is never icon-only.
3. **Accessible names** — every icon carries an accessible name (`aria-label` or equivalent), never a purely decorative, unlabeled glyph on an interactive element.
4. **Stable meaning** — the same icon always represents the same concept platform-wide; an icon is never reused for two unrelated meanings.
5. **No icon-only destructive action without tooltip and accessible label** — restated absolutely.
6. **Avoid ambiguous sports icons where labels are clearer** — a generic "trophy" or "medal" icon is a decorative accent, never a substitute for a text label identifying the specific sport/event.
7. **Use status icons alongside color and text** — restated from [color-theme-and-surface-system.md, Section 1](color-theme-and-surface-system.md#1-color-architecture), Rule 2.
8. **Differentiate upload, publish, certify, approve, and validate** — restated from [content-design-terminology-help-and-onboarding.md, Section 2](content-design-terminology-help-and-onboarding.md#2-terminology-governance); each of these five distinct high-integrity actions has its own distinct icon, never a shared generic "checkmark."

## 3. Photography and Media

| Concern | Direction |
|---|---|
| Athlete photographs | Approved usage required before display; minor-protection rules (Section, below) apply absolutely |
| Team photos | Same approved-usage requirement |
| Event photos | Approved for public gallery use only after the media-publication workflow (per [../03-security/data-sharing-export-and-public-disclosure-controls.md, "Public Disclosure Controls"](../03-security/data-sharing-export-and-public-disclosure-controls.md#public-disclosure-controls)) completes |
| Media assets | Classified and access-controlled per [../02-data/object-metadata-and-file-lifecycle.md](../02-data/object-metadata-and-file-lifecycle.md) |
| Public banners | Institutional, non-decorative-only content |
| Venue imagery | Informational (maps, directions), not merely decorative |

### Requirements

Approved usage (restated absolutely — no automatic public display merely because an image exists, per Section rule below) · privacy-aware display · **minor protection** (restated absolutely from [../03-security/minor-athlete-and-guardian-data-governance.md, Section 3](../03-security/minor-athlete-and-guardian-data-governance.md#3-photo-and-media-publication-governance) — a minor athlete's photo requires the documented approval step before publication, never automatic) · alternative text (every meaningful image carries accessible alt text) · appropriate cropping (respecting subject dignity and context) · image optimization (for the low-bandwidth contexts named throughout this project) · placeholder behavior (a missing/unapproved image degrades to a neutral placeholder, never a broken-image state) · restricted download where applicable (per the object's classification) · **no automatic public display merely because an image exists** — restated absolutely as the single most important rule in this section, directly extending the minor-athlete photo-publication-governance requirement to every image category.

## 4. Open Questions

See [design-open-decisions.md](design-open-decisions.md) — notably final typographic scale values (pending accessibility validation) and whether a dedicated scoreboard/numeric typeface is introduced beyond the existing Instrument Sans foundation.
