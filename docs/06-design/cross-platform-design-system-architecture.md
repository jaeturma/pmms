# PMMS Cross-Platform Design System Architecture

**Status:** Draft Complete — Pending Product, UX, Accessibility, Domain, and Engineering Validation
**Related:** [../01-architecture/react-inertia-architecture.md](../01-architecture/react-inertia-architecture.md) · [../01-architecture/flutter-architecture.md](../01-architecture/flutter-architecture.md) · [design-tokens-and-visual-language.md](design-tokens-and-visual-language.md)

This document establishes the internal design system name, shared cross-platform foundations, and the full component taxonomy every other Phase 0.9 document details. **No component, package, or Storybook configuration is created here.**

---

## 1. Design System Name

```text
PMMS Arena Design System
```

"Arena" reflects the platform's competition-operations identity without invoking any specific sport, organization mark, or protected symbol — a candidate name pending branding validation, per [color-theme-and-surface-system.md, Section 2](color-theme-and-surface-system.md#2-branding-direction-candidate-requires-validation).

## 2. Shared Foundations

Semantic color tokens · typography · spacing · radius · elevation · motion · iconography · state vocabulary · content terminology · accessibility rules · component behavior · data visualization · responsive principles — every one of these is defined **once**, in this `docs/06-design/` package, and consumed by both the React/shadcn implementation and the Flutter implementation. Neither platform invents its own accessibility rule, status color, or terminology independently.

## 3. Platform-Specific Implementations

| Layer | React (shadcn/ui) | Flutter |
|---|---|---|
| Component library | shadcn/ui "new-york" style, already scaffolded in this repository (`components.json`: `baseColor: "neutral"`, `cssVariables: true`, `iconLibrary: "lucide"`) | Flutter's Material/Cupertino-adapted widget set, themed to the shared tokens — not yet scaffolded (`mobile/` does not exist) |
| Styling mechanism | Tailwind CSS 4 utility classes + CSS custom properties (`resources/css/app.css`'s existing OKLCH token set) | Flutter `ThemeData`, populated from the same semantic token values |
| Icon set | Lucide (already the confirmed `iconLibrary`) | A Flutter-compatible equivalent icon set, mapped 1:1 to Lucide's semantic icon names where practical |
| Theming mechanism | CSS class-based (`.dark`, already implemented via `use-appearance.tsx`) | Flutter's native light/dark `ThemeMode` |

**Do not require identical components when platform conventions differ** — restated absolutely; a React `<Sheet>` (already present as `sheet.tsx`) and a Flutter bottom sheet serve the same semantic purpose without needing pixel-identical implementations.

## 4. Component Taxonomy

The full component taxonomy below maps every component category named in the Phase 0.9 prompt to the document that defines its detailed behavior — this document is the index; behavior is not duplicated here.

| Category | Components | Detailed In |
|---|---|---|
| Form and data-entry | Form components, data-entry components, selection components, date/time controls, file-upload components, QR components | [form-validation-draft-and-workflow-experience.md](form-validation-draft-and-workflow-experience.md) |
| Tabular and visual data | Tables, data grids, charts, dashboards, status badges | [dashboard-table-chart-and-data-visualization-standards.md](dashboard-table-chart-and-data-visualization-standards.md) |
| Feedback and messaging | Alerts, notifications, dialogs, drawers, success confirmation, destructive-action confirmation | [status-feedback-error-offline-and-sync-patterns.md](status-feedback-error-offline-and-sync-patterns.md) |
| Navigation | Navigation components, search, filters, pagination, command palettes | [information-architecture-and-navigation.md](information-architecture-and-navigation.md), [search-filter-import-export-and-file-experience.md](search-filter-import-export-and-file-experience.md) |
| State surfaces | Empty states, loading states, skeletons, error states, permission-denied states, offline states, conflict states, sync states | [status-feedback-error-offline-and-sync-patterns.md](status-feedback-error-offline-and-sync-patterns.md) |
| Accountability interfaces | Audit-history interfaces, change-history interfaces | [high-integrity-approval-certification-and-publication-ux.md](high-integrity-approval-certification-and-publication-ux.md) |
| High-integrity workflow | Approval interfaces, certification interfaces, publication interfaces, correction interfaces, supersession interfaces, protest interfaces | [high-integrity-approval-certification-and-publication-ux.md](high-integrity-approval-certification-and-publication-ux.md) |
| Sports-specific | Tournament brackets, match cards, heat sheets, lane assignments, score-entry panels, timing displays, measurement entry, scoreboards, result boards, medal tally boards, team standings, athlete profiles, team rosters, officials assignment | [sports-tournament-scoring-and-results-components.md](sports-tournament-scoring-and-results-components.md) |
| Venue and schedule | Venue boards, schedule timelines | [sports-tournament-scoring-and-results-components.md](sports-tournament-scoring-and-results-components.md) |
| Accreditation and device | Accreditation cards, QR scanning | [accreditation-qr-device-and-shared-station-experience.md](accreditation-qr-device-and-shared-station-experience.md) |
| Committee and support | Medical-alert interfaces, billeting interfaces, food-distribution interfaces, transport interfaces, security incident interfaces, finance interfaces, ICT support interfaces, media and public information interfaces | [committee-logistics-medical-finance-and-support-experience.md](committee-logistics-medical-finance-and-support-experience.md) |

## 5. Component Governance (Cross-Reference)

Component lifecycle, ownership, versioning, and deprecation are governed by [design-system-governance-documentation-and-versioning.md](design-system-governance-documentation-and-versioning.md) — not duplicated here.

## 6. Relationship to Existing Repository Components

The 25 shadcn/ui components already present in this repository (`alert.tsx`, `avatar.tsx`, `badge.tsx`, `breadcrumb.tsx`, `button.tsx`, `card.tsx`, `checkbox.tsx`, `collapsible.tsx`, `dialog.tsx`, `dropdown-menu.tsx`, `icon.tsx`, `input-otp.tsx`, `input.tsx`, `label.tsx`, `navigation-menu.tsx`, `select.tsx`, `separator.tsx`, `sheet.tsx`, `sidebar.tsx`, `skeleton.tsx`, `sonner.tsx`, `spinner.tsx`, `toggle-group.tsx`, `toggle.tsx`, `tooltip.tsx`) are the confirmed starting inventory this design system extends — none is modified in this documentation-only phase, per working rule 15, but every future PMMS-specific component (score-entry panel, medal tally board, QR scan result) is designed to compose with, not replace, this existing foundation.

## 7. Open Questions

See [design-open-decisions.md](design-open-decisions.md) — notably whether Flutter's icon set achieves full Lucide parity or requires a documented substitution list, and Storybook/component-preview tooling adoption timing (explicitly not created in this phase, per working rule 10).
