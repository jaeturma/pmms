# PMMS Dashboard, Table, Chart, and Data Visualization Standards

**Status:** Draft Complete — Pending Product, UX, Accessibility, Domain, and Engineering Validation
**Related:** [product-surfaces-and-workspace-architecture.md, Section 2](product-surfaces-and-workspace-architecture.md#2-dashboard-architecture) · [../02-data/public-reporting-and-projection-data.md](../02-data/public-reporting-and-projection-data.md) · [accessibility-architecture.md](accessibility-architecture.md)

This document defines table/data-grid standards, chart standards, and data-freshness/state-visualization standards used across every dashboard and report surface. **No table library, charting library, or component is selected or created here.**

---

## 1. Table and Data Grid Standards

### When to Use Which Pattern

Simple table (a short, static list) · data grid (sortable/filterable/paginated large datasets) · card list (mobile or low-density contexts) · hierarchical table (nested committee/venue structures) · matrix (cross-tabulated data, e.g., delegation × sport entry counts) · timeline (schedule-oriented views, per [sports-tournament-scoring-and-results-components.md](sports-tournament-scoring-and-results-components.md)) · bracket (tournament-specific, per the same document) · board view (kanban-style status tracking, e.g., protest case pipeline).

### Table Support Requirements (Where Appropriate)

Sorting · filtering · pagination · search · column visibility toggling · sticky headers · row actions · bulk selection · export (per [search-filter-import-export-and-file-experience.md](search-filter-import-export-and-file-experience.md)) · responsive behavior (per [responsive-touch-keyboard-and-device-behavior.md, Section 1](responsive-touch-keyboard-and-device-behavior.md#1-responsive-architecture)) · keyboard navigation · accessible headers (per [accessibility-architecture.md, Section 2](accessibility-architecture.md#2-screen-reader-architecture)) · empty states (per [status-feedback-error-offline-and-sync-patterns.md](status-feedback-error-offline-and-sync-patterns.md)) · loading states (same).

**Avoid placing too many actions in each row** — restated absolutely; a row with more than a small handful of inline actions is a candidate for consolidation into a menu or a detail view, not an ever-growing action toolbar.

## 2. Chart Standards

### When to Use Which Chart Type

Bar chart (categorical comparison) · line chart (trend over time) · area chart (cumulative trend) · stacked bar (part-to-whole over categories) · donut or pie **only when justified** (a small number of categories, part-to-whole emphasis genuinely needed — never the default choice) · scatter plot (correlation) · heat map (density across two dimensions) · timeline (schedule/event density) · progress chart (completion tracking, e.g., meet readiness) · medal visualization (per [sports-tournament-scoring-and-results-components.md, Section 7](sports-tournament-scoring-and-results-components.md#7-medal-tally-standards)).

### Every Chart Includes

Clear title · time period · source (which projection/read model, per [../02-data/public-reporting-and-projection-data.md, Section 2](../02-data/public-reporting-and-projection-data.md#2-read-models-and-analytics)) · freshness (Section 4 below) · legend · accessible summary (a text alternative conveying the chart's key takeaway, per [accessibility-architecture.md](accessibility-architecture.md)) · table alternative where needed · correct units · no misleading axes (a truncated y-axis or an inconsistent scale is never used to exaggerate a trend) · privacy-safe aggregation (per [privacy-security-and-sensitive-data-experience.md](privacy-security-and-sensitive-data-experience.md) — small-group/small-cell suppression, restated from [../03-security/data-sharing-export-and-public-disclosure-controls.md, "Masking, Redaction, and De-Identification"](../03-security/data-sharing-export-and-public-disclosure-controls.md#masking-redaction-and-de-identification)).

### Color Usage in Charts

Extends the existing five `--chart-*` tokens (per [design-tokens-and-visual-language.md, Section 1](design-tokens-and-visual-language.md#1-confirmed-existing-token-foundation-repository-evidence)) — a consistent categorical palette used the same way across every chart, never reassigned per-chart. Sport/committee colors (per [color-theme-and-surface-system.md, Section 1](color-theme-and-surface-system.md#1-color-architecture)) may supplement but never replace the semantic-state palette (success/warning/danger/etc.) within a chart depicting status.

### Accessible Charts

Every chart has a non-visual equivalent (a summary sentence, an accessible data table, or both) — a chart is never the *only* way to access the data it represents, restated from working rule 36's spirit extended to visualization specifically (chart alternatives are explicitly named in the Phase 0.7 accessibility-testing scope, per [../04-quality/accessibility-usability-and-compatibility-testing.md](../04-quality/accessibility-usability-and-compatibility-testing.md)).

## 3. Real-Time Data Display

Live indicators · data freshness · last-update time · reconnection state · delayed data · stale data · version changes · user-triggered refresh · polling fallback (per [../01-architecture/realtime-architecture.md](../01-architecture/realtime-architecture.md)) · update highlighting (a brief, non-distracting visual cue marking what just changed) · screen-reader announcements (per [accessibility-architecture.md, Section 2](accessibility-architecture.md#2-screen-reader-architecture)).

**Avoid overwhelming users with continuous animations or notifications** — restated absolutely; a live-updating dashboard communicates freshness without becoming visually exhausting.

## 4. Data Freshness, Version, and State Indication

Every dashboard widget, chart, and public projection surface displays: how fresh its data is (a timestamp or a relative "updated Xm ago" indicator) · which source version it reflects (per [../02-data/temporal-history-and-versioning-model.md, Section 4](../02-data/temporal-history-and-versioning-model.md#4-versioning-and-supersession)) · whether it's a live/authoritative view or a rebuildable projection (per [../02-data/public-reporting-and-projection-data.md](../02-data/public-reporting-and-projection-data.md)).

### Public Versus Provisional Data

A screen never blends published/certified data with provisional/draft data without an unambiguous visual and textual distinction — restated absolutely from working rule 33 and [high-integrity-approval-certification-and-publication-ux.md](high-integrity-approval-certification-and-publication-ux.md).

### Certified Versus Published States

"Certified" (the result has passed the authoritative validation/certification workflow) and "Published" (the certified result has been made publicly visible) are **distinct states, always labeled distinctly** — restated absolutely from [content-design-terminology-help-and-onboarding.md, Section 2](content-design-terminology-help-and-onboarding.md#2-terminology-governance); a certified-but-not-yet-published result is never displayed as if it were already public, and a published result always indicates the certification it was published from.

## 5. Open Questions

See [design-open-decisions.md](design-open-decisions.md) — notably final charting-library selection (React) and its Flutter equivalent (both implementation-phase decisions, not made here) and the specific "stale" threshold (how old before data is flagged stale) per data category.
