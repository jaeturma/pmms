# PMMS Design System, UX, Accessibility, and Cross-Platform Experience Documentation — `docs/06-design/`

This directory contains the Phase 0.9 (design system, UX, accessibility, and cross-platform experience architecture) documentation for the **Provincial Meet Management System (PMMS)**. It builds directly on the bounded contexts (Phase 0.2), authorization model (Phase 0.3), application/runtime architecture (Phase 0.4), data/persistence architecture (Phase 0.5), security/privacy/audit/governance architecture (Phase 0.6), quality-engineering architecture (Phase 0.7), and DevOps/operations architecture (Phase 0.8) to define how PMMS is actually experienced by every user group across web, mobile, public, and field surfaces.

**No React component, Flutter widget, Tailwind class, shadcn/ui configuration, route, page code, design token in code, logo, image, icon, illustration, Figma file, screenshot, prototype, or implementation code is contained in this directory.** It is design and experience architecture documentation only, per the Phase 0.9 working rules. No existing implementation screen is redesigned, and no complete screen inventory is finalized as approved.

## Purpose

Phase 0.9 exists to define, once and consistently, how a human experiences PMMS — before 28 information domains across 9 product surfaces are implemented independently and inconsistently. See [phase-0.9-design-system-ux-accessibility-experience.md, Section 2](phase-0.9-design-system-ux-accessibility-experience.md#2-executive-summary) for the full rationale.

## Document Index

| Document | Purpose |
|---|---|
| [phase-0.9-design-system-ux-accessibility-experience.md](phase-0.9-design-system-ux-accessibility-experience.md) | Primary Phase 0.9 document: vision/principles, user groups, IA/navigation, product surfaces/dashboards, design system/tokens, color/theme, typography, responsive/accessibility, React/Flutter direction, public/kiosk/scoreboard experience, data visualization, forms, status/feedback/offline patterns, high-integrity UX, sports components, accreditation/QR, committee/medical/finance experience, search/export, privacy, AI-assisted UX, content design, governance, validation, open decisions, acceptance/exit criteria |
| [experience-vision-and-design-principles.md](experience-vision-and-design-principles.md) | Experience vision, UX/design principles, 19 experience contexts |
| [user-groups-personas-and-contexts.md](user-groups-personas-and-contexts.md) | 27 user groups mapped from Phase 0.3 roles — proto-personas requiring validation |
| [information-architecture-and-navigation.md](information-architecture-and-navigation.md) | Workspace hierarchy, 28 information domains, navigation architecture |
| [product-surfaces-and-workspace-architecture.md](product-surfaces-and-workspace-architecture.md) | 9 product surfaces, dashboard architecture and content rules |
| [cross-platform-design-system-architecture.md](cross-platform-design-system-architecture.md) | PMMS Arena Design System name, shared foundations, component taxonomy index |
| [design-tokens-and-visual-language.md](design-tokens-and-visual-language.md) | Token architecture grounded in the confirmed existing `app.css` foundation, spacing/density/elevation scales |
| [color-theme-and-surface-system.md](color-theme-and-surface-system.md) | Color architecture, branding direction, 6 themes, glass UI rules |
| [typography-iconography-and-content-style.md](typography-iconography-and-content-style.md) | Typography, iconography, photography/media rules |
| [responsive-touch-keyboard-and-device-behavior.md](responsive-touch-keyboard-and-device-behavior.md) | Responsive layout ranges, mobile adaptation, keyboard/touch interaction, motion |
| [accessibility-architecture.md](accessibility-architecture.md) | Accessibility dimensions, screen-reader architecture, focus management |
| [react-web-experience-architecture.md](react-web-experience-architecture.md) | React/Inertia web experience direction |
| [flutter-mobile-experience-architecture.md](flutter-mobile-experience-architecture.md) | Flutter mobile, offline, shared-device, low-bandwidth experience |
| [public-portal-kiosk-scoreboard-and-display-experience.md](public-portal-kiosk-scoreboard-and-display-experience.md) | Public portal, kiosk, scoreboard, print, and compatibility standards |
| [dashboard-table-chart-and-data-visualization-standards.md](dashboard-table-chart-and-data-visualization-standards.md) | Table/data-grid standards, chart standards, freshness/state indication |
| [form-validation-draft-and-workflow-experience.md](form-validation-draft-and-workflow-experience.md) | Form architecture, validation, drafts/autosave, multi-step workflows |
| [status-feedback-error-offline-and-sync-patterns.md](status-feedback-error-offline-and-sync-patterns.md) | Status vocabulary, feedback components, error/empty/loading states, offline/sync/conflict patterns |
| [high-integrity-approval-certification-and-publication-ux.md](high-integrity-approval-certification-and-publication-ux.md) | Approval/certification/correction/supersession/publication/protest interfaces |
| [sports-tournament-scoring-and-results-components.md](sports-tournament-scoring-and-results-components.md) | Brackets, heats/lanes, score entry, results, medal tally, athlete profiles |
| [accreditation-qr-device-and-shared-station-experience.md](accreditation-qr-device-and-shared-station-experience.md) | Accreditation cards, QR scanning, shared-device/station patterns |
| [committee-logistics-medical-finance-and-support-experience.md](committee-logistics-medical-finance-and-support-experience.md) | 12 committee experience profiles, dedicated medical and finance depth |
| [search-filter-import-export-and-file-experience.md](search-filter-import-export-and-file-experience.md) | Search, filters, saved views, import/export, file handling |
| [privacy-security-and-sensitive-data-experience.md](privacy-security-and-sensitive-data-experience.md) | Privacy-aware/security-aware design, masking, minor-athlete presentation rules |
| [ai-assisted-experience-architecture.md](ai-assisted-experience-architecture.md) | AI-assisted interface patterns and absolute consequential-action restrictions |
| [content-design-terminology-help-and-onboarding.md](content-design-terminology-help-and-onboarding.md) | Content architecture, terminology governance, help/onboarding/notifications |
| [design-system-governance-documentation-and-versioning.md](design-system-governance-documentation-and-versioning.md) | Governance roles, component lifecycle, versioning, deprecation, UX debt |
| [ux-research-validation-and-quality-gates.md](ux-research-validation-and-quality-gates.md) | Usability validation, design review, verification-vs-validation, design quality gates |
| [design-open-decisions.md](design-open-decisions.md) | 24 unresolved design decisions (DX-01–DX-24), cross-referenced against Phase 0.1–0.8 open decisions |

## Reading Order

1. [phase-0.9-design-system-ux-accessibility-experience.md](phase-0.9-design-system-ux-accessibility-experience.md) — read first; establishes vision and cross-references every supporting document.
2. [experience-vision-and-design-principles.md](experience-vision-and-design-principles.md), [user-groups-personas-and-contexts.md](user-groups-personas-and-contexts.md) — who PMMS serves and why design choices matter.
3. [information-architecture-and-navigation.md](information-architecture-and-navigation.md), [product-surfaces-and-workspace-architecture.md](product-surfaces-and-workspace-architecture.md) — the structural skeleton.
4. [cross-platform-design-system-architecture.md](cross-platform-design-system-architecture.md), [design-tokens-and-visual-language.md](design-tokens-and-visual-language.md), [color-theme-and-surface-system.md](color-theme-and-surface-system.md), [typography-iconography-and-content-style.md](typography-iconography-and-content-style.md) — the visual and token foundation.
5. [responsive-touch-keyboard-and-device-behavior.md](responsive-touch-keyboard-and-device-behavior.md), [accessibility-architecture.md](accessibility-architecture.md) — device and accessibility requirements.
6. [react-web-experience-architecture.md](react-web-experience-architecture.md), [flutter-mobile-experience-architecture.md](flutter-mobile-experience-architecture.md) — platform-specific direction.
7. [public-portal-kiosk-scoreboard-and-display-experience.md](public-portal-kiosk-scoreboard-and-display-experience.md), [dashboard-table-chart-and-data-visualization-standards.md](dashboard-table-chart-and-data-visualization-standards.md) — display and data-visualization surfaces.
8. [form-validation-draft-and-workflow-experience.md](form-validation-draft-and-workflow-experience.md), [status-feedback-error-offline-and-sync-patterns.md](status-feedback-error-offline-and-sync-patterns.md) — interaction and feedback patterns.
9. [high-integrity-approval-certification-and-publication-ux.md](high-integrity-approval-certification-and-publication-ux.md), [sports-tournament-scoring-and-results-components.md](sports-tournament-scoring-and-results-components.md), [accreditation-qr-device-and-shared-station-experience.md](accreditation-qr-device-and-shared-station-experience.md), [committee-logistics-medical-finance-and-support-experience.md](committee-logistics-medical-finance-and-support-experience.md) — domain-specific interfaces.
10. [search-filter-import-export-and-file-experience.md](search-filter-import-export-and-file-experience.md), [privacy-security-and-sensitive-data-experience.md](privacy-security-and-sensitive-data-experience.md), [ai-assisted-experience-architecture.md](ai-assisted-experience-architecture.md) — cross-cutting data and AI patterns.
11. [content-design-terminology-help-and-onboarding.md](content-design-terminology-help-and-onboarding.md), [design-system-governance-documentation-and-versioning.md](design-system-governance-documentation-and-versioning.md), [ux-research-validation-and-quality-gates.md](ux-research-validation-and-quality-gates.md) — content, governance, and validation.
12. [design-open-decisions.md](design-open-decisions.md) — read last; everything still unresolved.

## Status Legend

| Status | Meaning |
|---|---|
| Draft Complete — Pending Product, UX, Accessibility, Domain, and Engineering Validation | Phase 0.9 status: content complete, no formal product/UX/accessibility/domain/engineering sign-off yet |
| Validated | Reviewed and confirmed by identified stakeholders (not yet reached for any document) |
| Superseded | Replaced by a later-phase document (not yet applicable) |

## Ownership and Review Expectations

A document owner (UX lead / design-system owner) and reviewer set (accessibility specialist, content-design lead, React/Flutter design-system engineers, domain reviewers, DepEd Leadership) are to be identified — see [../00-product/stakeholder-register.md](../00-product/stakeholder-register.md). Until formal review occurs, this documentation should be treated as a structured, defensible proposal built from the approved Phase 0.1–0.8 foundation and this repository's confirmed shadcn/ui/Tailwind foundation, not as an approved specification.

## Relationship to Phase 0.2 Through 0.8

This directory preserves, and never redefines: Phase 0.2's bounded-context ownership, Phase 0.3's roles/scopes/assignments/authorization boundaries, Phase 0.4's frontend and Flutter architectural boundaries, Phase 0.5's data classification/history/publication-state rules, Phase 0.6's security/privacy/audit/minor-athlete/sensitive-data controls, Phase 0.7's quality/accessibility/validation requirements, and Phase 0.8's environment/operational/low-bandwidth/meet-day/venue constraints. Every document in this directory adds experience and interface design around those foundations — none of them is altered. Frontend permission hiding is never treated as authorization; the server remains the sole authority for every consequential decision.

## Relationship to Phase 0.10

**Phase 0.10 — AI-Assisted Platform Architecture is now complete** — see [../07-ai/README.md](../07-ai/README.md), superseding this section's earlier expectation that Phase 0.10 would begin component/screen implementation. It consumed this directory's [ai-assisted-experience-architecture.md](ai-assisted-experience-architecture.md) (the 8 allowed AI-assisted interface patterns and absolute consequential-action restrictions) as the UX layer its own AI governance model assumes, and extended it with the underlying AI Gateway, risk classification, human-in-the-loop, and evaluation architecture that governs which capabilities are ever allowed to reach that UX layer at all. No interface pattern defined in this directory was altered by Phase 0.10's work.

## Relationship to Phase 0.11

**Phase 0.11 — Event-Driven Workflows, Notifications, Messaging, and Responsible Automation Architecture is now complete** — see [../08-workflows/README.md](../08-workflows/README.md). It consumed this directory's [status-feedback-error-offline-and-sync-patterns.md](status-feedback-error-offline-and-sync-patterns.md) offline/sync connectivity-state vocabulary and [high-integrity-approval-certification-and-publication-ux.md](high-integrity-approval-certification-and-publication-ux.md)'s approval/certification/publication distinction as the UX-layer foundation its own workflow state-machine and human-task architecture assumes. No interface pattern defined in this directory was altered by Phase 0.11's work.

## Relationship to Phase 0.12

**Phase 0.12 — Performance, Scalability, Multi-Tenancy, Disaster Recovery, and Enterprise Readiness Architecture is now complete** — see [../09-enterprise/README.md](../09-enterprise/README.md). It consumed this directory's [status-feedback-error-offline-and-sync-patterns.md](status-feedback-error-offline-and-sync-patterns.md) to define the "degraded state must always be visible" requirement for backpressure/load-shedding UX, and referenced [privacy-security-and-sensitive-data-experience.md](privacy-security-and-sensitive-data-experience.md) for tenant-branding-versus-accessibility governance. No interface pattern defined in this directory was altered by Phase 0.12's work.

## Relationship to Phase 0.13

**Phase 0.13 — Architecture Validation, Gap Analysis, Technical Debt, and Final Architecture Review is now complete** — see [../10-review/README.md](../10-review/README.md), superseding this section's earlier expectation. It confirmed "never publishes provisional, held, restricted, or superseded data" as the most consistently repeated public-data rule in the entire corpus, and identified all 27 user-group proto-personas from this directory as the design architecture's largest evidence gap, requiring pilot validation — see [../10-review/design-ux-accessibility-and-cross-platform-review.md](../10-review/design-ux-accessibility-and-cross-platform-review.md). DX-01 (WCAG target) and DX-02 (branding palette) remain tracked as Priority 1/2 blockers. No interface pattern defined in this directory was altered by Phase 0.13's work.

## Relationship to Phase 0.14

The next phase is not yet started, per working rule 8 of Phase 0.13. See [../10-review/phase-0.13-architecture-validation-gap-analysis-final-review.md, Section 47](../10-review/phase-0.13-architecture-validation-gap-analysis-final-review.md#47-next-phase).

## Update Rules

1. Changes to the component taxonomy (Section, [cross-platform-design-system-architecture.md, Section 4](cross-platform-design-system-architecture.md#4-component-taxonomy)) should be reflected in every document it indexes.
2. A resolved branding-palette decision (DX-02) should be propagated into [color-theme-and-surface-system.md](color-theme-and-surface-system.md) and [design-tokens-and-visual-language.md](design-tokens-and-visual-language.md), both of which depend on it.
3. Changes to terminology (Section, [content-design-terminology-help-and-onboarding.md, Section 2](content-design-terminology-help-and-onboarding.md#2-terminology-governance)) should be cross-checked against [../01-architecture/domain-glossary.md](../01-architecture/domain-glossary.md) first, never diverging from it.
4. Resolving an item in [design-open-decisions.md](design-open-decisions.md) should update its `Status` field and, where it changes a rule, be reflected back into the relevant document.
5. Keep `.ai/project-context.md`, `.ai/architecture.md`, `.ai/design-system-rules.md`, `.ai/ux-rules.md`, `.ai/accessibility-rules.md`, `.ai/content-design-rules.md`, `.ai/data-visualization-rules.md`, and `.ai/mobile-experience-rules.md` (see [../../.ai/](../../.ai/)) in sync with material changes to this directory, since those files are the primary context AI-assisted development tools use for this project.
