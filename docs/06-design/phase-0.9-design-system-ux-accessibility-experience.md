# PMMS Phase 0.9 — Design System, UX, Accessibility, and Cross-Platform Experience Architecture

## 1. Document Control

| Field | Value |
|---|---|
| Document title | PMMS Phase 0.9 — Design System, UX, Accessibility, and Cross-Platform Experience Architecture |
| Project | Provincial Meet Management System (PMMS) |
| Phase | Phase 0.9 — Design System, UX, Accessibility, and Cross-Platform Experience Architecture |
| Version | 0.9.0 |
| Status | Draft Complete — Pending Product, UX, Accessibility, Domain, and Engineering Validation |
| Date | 2026-07-14 |
| Intended audience | UI/UX designers, design-system engineers, React developers, Flutter developers, Laravel developers, accessibility specialists, QA engineers, product owners, tournament managers, technical officials, committee representatives, communications teams, project leadership |
| Document owner | To be identified (UX lead / design-system owner) |
| Review roles | To be identified — UX lead, design-system owner, accessibility specialist, content-design lead, React/Flutter design-system engineers, domain reviewers, DepEd Leadership |
| Related documents | All 27 supporting documents in this directory (see [README.md](README.md)); [../01-architecture/](../01-architecture/); [../02-data/](../02-data/); [../03-security/](../03-security/); [../04-quality/](../04-quality/); [../05-devops/](../05-devops/); [../../.ai/decisions/ADR-0009-design-system-ux-accessibility-and-cross-platform-experience.md](../../.ai/decisions/ADR-0009-design-system-ux-accessibility-and-cross-platform-experience.md) |

### Change History

| Version | Date | Description |
|---|---|---|
| 0.9.0 | 2026-07-14 | Initial Phase 0.9 draft: experience vision/principles, user groups, information architecture/navigation, product surfaces/dashboards, cross-platform design system, design tokens, color/theme/surface system, typography/iconography/content style, responsive/touch/keyboard/motion, accessibility architecture, React and Flutter experience direction, public portal/kiosk/scoreboard experience, data-visualization standards, form/validation/draft experience, status/feedback/error/offline/sync patterns, high-integrity approval/certification/publication UX, sports/tournament/scoring/results components, accreditation/QR/device/shared-station experience, committee/logistics/medical/finance experience, search/filter/import/export/file experience, privacy/security/sensitive-data experience, AI-assisted experience, content design/terminology/help/onboarding, design-system governance/versioning, UX research/validation/quality gates, and open decisions — built from the approved Phase 0.1–0.8 foundation and this repository's confirmed shadcn/ui/Tailwind foundation. |

---

## 2. Executive Summary

Phase 0.8 defined how PMMS is built, deployed, and operated. Phase 0.9 defines how a human actually experiences it — the layer every prior phase's architecture ultimately exists to serve, and the layer where an inconsistent, confusing, or inaccessible interface could quietly undermine six phases of rigorous backend design.

**Why PMMS needs a unified experience architecture before page implementation.** Without one, an implementation team building 28 information domains across nine product surfaces would invent terminology, state visualization, and interaction patterns independently per screen — producing exactly the same inconsistency this project's every prior centralization effort (bounded contexts, authorization model, data classification, security controls, quality gates, deployment discipline) exists to prevent, now expressed as a confusing, untrustworthy user experience.

**Why administrative, public, mobile, offline, kiosk, and scoreboard interfaces require different interaction models.** A tournament manager reconfiguring a bracket under deadline pressure, a guardian checking a result on a low-bandwidth phone connection, and a scanner operator validating credentials in direct sunlight are not the same user in different clothes — restated from [experience-vision-and-design-principles.md, Section 5](experience-vision-and-design-principles.md#5-experience-contexts)'s nineteen distinct experience contexts, each with its own environment, connectivity, time-pressure, and error-cost profile.

**Why high-integrity workflows must visually distinguish draft, provisional, validated, certified, held, superseded, and published states.** Restated absolutely per working rule 33 — an interface that cannot show a user, at a glance, whether a result is certified or merely provisional directly undermines the institutional-trust guarantee Phases 0.2, 0.5, and 0.6 built at the data and authorization layers.

**Why role, scope, assignment, data classification, and connectivity affect the user experience.** The same "Scoring" destination means something different in reach and consequence depending on who's looking at it and from where — the interface must reflect Phase 0.3's authorization model and Phase 0.5's classification model honestly, never merely hiding what a frontend-only check happens to filter.

**Why React and Flutter must share design foundations without forcing identical layouts.** Restated from [cross-platform-design-system-architecture.md, Section 2](cross-platform-design-system-architecture.md#2-shared-foundations) — a user moving between the administrative web portal and the field-use mobile app should never have to relearn what "certified" means, even though a mobile bottom-navigation pattern and a desktop sidebar are appropriately different.

**Why accessibility, low-bandwidth use, field conditions, and time-critical operations are core design constraints.** Restated absolutely per working rule 37 — these are not edge cases layered on afterward; they are the actual operating conditions of a platform serving DepEd meets across the Philippines' varied connectivity and device landscape.

**Why the PMMS design system must be treated as a maintained internal product.** A design system without governance, versioning, and deprecation discipline (per [design-system-governance-documentation-and-versioning.md](design-system-governance-documentation-and-versioning.md)) decays into exactly the same per-screen inconsistency this phase exists to prevent — restated from the same "documentation is part of operations" principle Phase 0.8 established for infrastructure, now applied to design.

---

## 3. Experience Vision

Trustworthy · clear · fast to understand · efficient under time pressure · role-appropriate · accessible · responsive · mobile-capable · offline-aware · privacy-respecting · audit-aware · consistent · flexible · visually professional · appropriate for government and public use · suitable for commercial deployment. Full detail: [experience-vision-and-design-principles.md, Section 1](experience-vision-and-design-principles.md#1-experience-vision).

## 4. UX Principles

Twenty principles — clarity before decoration, integrity before speed, show state explicitly, make authority visible, prevent rather than merely report errors, preserve user work, support recovery, design for field conditions, design for intermittent connectivity, minimize cognitive load, progressive disclosure, keep common actions close, make dangerous actions difficult to trigger accidentally, immediate feedback, visible delayed processing, plain language, never color alone, respect privacy by default, keep public/internal information separated, consistent cross-platform terminology. Full detail: [experience-vision-and-design-principles.md, Section 2](experience-vision-and-design-principles.md#2-ux-principles).

## 5. Design Principles

Fifteen principles — shared foundations with platform-appropriate components, semantic tokens, accessible defaults, responsive by design, touch/keyboard equivalence, data density with readability, state-driven interfaces, consistent spacing/typography, minimal decorative complexity in operational screens, selective glass effects, high outdoor readability, clear hierarchy, predictable patterns, reuse before one-off components, traceable design decisions. Full detail: [experience-vision-and-design-principles.md, Section 3](experience-vision-and-design-principles.md#3-design-principles).

## 6. Experience Contexts

Nineteen usage contexts (office administration through post-event reporting), each with environment/connectivity/time-pressure/data-sensitivity/error-cost dimensions. Full detail: [experience-vision-and-design-principles.md, Section 5](experience-vision-and-design-principles.md#5-experience-contexts).

## 7. User Groups and Experience Needs

Twenty-seven user groups mapped from the Phase 0.3 role catalog to shared experience needs, presented as **proto-personas explicitly requiring pilot validation**, never treated as researched personas without that validation. Full detail: [user-groups-personas-and-contexts.md, Sections 1–2](user-groups-personas-and-contexts.md#1-user-groups-and-experience-needs).

---

## 8. Information Architecture

Twenty-eight primary information domains, each mapped to its owning bounded context — navigation is role- and assignment-aware without changing terminology unpredictably. Full detail: [information-architecture-and-navigation.md, Section 2](information-architecture-and-navigation.md#2-information-architecture).

## 9. Navigation Architecture

Twelve candidate navigation patterns and nine rules — **hiding an inaccessible destination is a usability convenience, never a security boundary**, restated absolutely per working rule 25. Full detail: [information-architecture-and-navigation.md, Section 3](information-architecture-and-navigation.md#3-navigation-architecture).

## 10. Page and Workspace Hierarchy

`Platform → Organization → Meet → Operational Workspace → Committee/Delegation/Sport/Venue/Tournament Context → Task or Record` — at every level, the user always understands their current organization, meet, role/assignment, scope, record state, connectivity state, and data classification. Full detail: [information-architecture-and-navigation.md, Section 1](information-architecture-and-navigation.md#1-workspace-hierarchy). Page hierarchy is a structural framework, **not a finalized screen inventory**, per working rule 17.

## 11. Product Surface Architecture

Nine product surfaces (Administrative Portal through Report/Print Experience), each with a distinct experience character grounded in the Phase 0.4 runtime boundaries. Full detail: [product-surfaces-and-workspace-architecture.md, Section 1](product-surfaces-and-workspace-architecture.md#1-product-surfaces).

## 12. Dashboard Architecture

Seven dashboard layers (Personal through Public) — **dashboards prioritize actionable information over decorative metrics**, with every widget required to answer at least one of eight defined questions. Full detail: [product-surfaces-and-workspace-architecture.md, Section 2](product-surfaces-and-workspace-architecture.md#2-dashboard-architecture).

---

## 13. Cross-Platform Design System

Named the **PMMS Arena Design System** — shared foundations (color, typography, spacing, state vocabulary, terminology, accessibility, data visualization) implemented once and consumed by both React/shadcn and Flutter, which may differ in navigation, layout density, and platform-native conventions. Full detail: [cross-platform-design-system-architecture.md, Sections 1–3](cross-platform-design-system-architecture.md#1-design-system-name).

## 14. Component Taxonomy

The full component taxonomy (form/data-entry, tabular/visual data, feedback/messaging, navigation, state surfaces, accountability interfaces, high-integrity workflow, sports-specific, venue/schedule, accreditation/device, committee/support) indexed to its detailing document — not duplicated across documents. Full detail: [cross-platform-design-system-architecture.md, Section 4](cross-platform-design-system-architecture.md#4-component-taxonomy).

## 15. Design Token Architecture

Primitive, semantic, and component token categories — including six **new PMMS-specific semantic state tokens** (provisional, certified, published, held, offline, conflict) with no shadcn/ui equivalent, layered onto the confirmed existing `app.css` OKLCH token foundation. Full detail: [design-tokens-and-visual-language.md, Sections 1–2](design-tokens-and-visual-language.md#1-confirmed-existing-token-foundation-repository-evidence).

## 16. Spacing, Sizing, Radius, and Elevation

Five density modes (Comfortable through Mobile), a sizing/radius scale extending the confirmed `--radius: 0.625rem` primitive, and a four-level surface hierarchy (Background → Surface → Elevated Surface → Overlay). Full detail: [design-tokens-and-visual-language.md, Sections 4–7](design-tokens-and-visual-language.md#4-spacing-and-density-scale).

## 17. Color Architecture and Branding

Eight color categories with six rules (semantic consistency across themes, never color alone, validated contrast, no semantic override by sport/committee color, simplified public-display palettes) — restated absolutely. A candidate navy/maroon/gold branding direction **requires validation**, and no protected DepEd mark is ever used without approved guidance. Full detail: [color-theme-and-surface-system.md, Sections 1–2](color-theme-and-surface-system.md#1-color-architecture).

## 18. Theme Architecture

Six themes (Light, Dark, High-Contrast, Scoreboard, Kiosk, Print) — Light and Dark already functional in this repository via `use-appearance.tsx`; every theme preserves semantic state meaning. Full detail: [color-theme-and-surface-system.md, Section 4](color-theme-and-surface-system.md#4-theme-architecture).

## 19. Glass UI Rules

Appropriate for landing/hero/overview surfaces; avoided for dense tables, score entry, eligibility review, medical records, finance, critical dialogs, outdoor displays, and low-performance devices — restated absolutely per working rule 27. Full detail: [color-theme-and-surface-system.md, Section 5](color-theme-and-surface-system.md#5-glass-ui-rules).

## 20. Typography, Iconography, and Photography

Typography prioritizes readability and tabular numbers, building on the confirmed Instrument Sans foundation with no proprietary font requiring distribution. Iconography uses the confirmed Lucide set with mandatory accessible names and paired labels for critical actions. Photography requires approved usage and minor protection — **no automatic public display merely because an image exists**. Full detail: [typography-iconography-and-content-style.md](typography-iconography-and-content-style.md).

---

## 21. Responsive Architecture

Eight conceptual layout ranges (small mobile through scoreboard) with per-component adaptation rules — **no breakpoint pixel values hard-coded without real-device evidence.** Full detail: [responsive-touch-keyboard-and-device-behavior.md, Section 1](responsive-touch-keyboard-and-device-behavior.md#1-responsive-architecture).

## 22. Mobile Adaptation

Nine mobile-adaptation rules prioritizing task completion, progressive disclosure, and preserved offline/sync visibility over information density. Full detail: [responsive-touch-keyboard-and-device-behavior.md, Section 2](responsive-touch-keyboard-and-device-behavior.md#2-mobile-adaptation).

## 23. Keyboard and Touch Interaction

Keyboard: logical tab order, visible focus, no keyboard traps, discoverable shortcuts. Touch: adequate targets, no hover dependency (restated absolutely per working rule 29), no required drag-and-drop (restated per working rule 30), field-condition consideration. Full detail: [responsive-touch-keyboard-and-device-behavior.md, Sections 3–4](responsive-touch-keyboard-and-device-behavior.md#3-keyboard-interaction).

## 24. Motion and Animation

Motion serves orientation, state transition, feedback, and loading; avoided for decoration, blocking interaction, or high-pressure score entry. Reduced-motion preferences are respected absolutely. Full detail: [responsive-touch-keyboard-and-device-behavior.md, Section 5](responsive-touch-keyboard-and-device-behavior.md#5-motion-and-animation).

## 25. Accessibility Architecture

WCAG used as a **candidate reference requiring final validation** — no compliance claimed until tested. Full accessibility dimension coverage (perceivable, operable, understandable, robust) and screen-reader architecture (fourteen requirements, including throttled real-time announcements). Full detail: [accessibility-architecture.md, Sections 1–2](accessibility-architecture.md#1-accessibility-architecture).

## 26. Focus Management

Eleven focus-management rules — **background updates never steal focus from active user interaction**, restated absolutely as the section's governing rule. Full detail: [accessibility-architecture.md, Section 3](accessibility-architecture.md#3-focus-management).

---

## 27. React Web Experience Direction

Server-authoritative permissions (restated absolutely — frontend hiding is never the security boundary), no secrets/oversensitive data in Inertia props, building on the confirmed `app-sidebar.tsx`/`use-appearance.tsx` foundation. Full detail: [react-web-experience-architecture.md](react-web-experience-architecture.md).

## 28. Flutter Mobile Experience Direction

Task-focused flows, offline-first indicators, the eleven-state offline/sync vocabulary, shared-device individual-authentication requirement, and low-bandwidth optimization — `mobile/` does not yet exist in this repository. Full detail: [flutter-mobile-experience-architecture.md](flutter-mobile-experience-architecture.md).

## 29. Cross-Platform Consistency

React and Flutter share terminology, semantic colors, status meanings, validation wording, privacy rules, and offline-state vocabulary; they may differ in navigation, layout, touch patterns, and native device integration. Full detail: [cross-platform-design-system-architecture.md, Section 3](cross-platform-design-system-architecture.md#3-platform-specific-implementations).

---

## 30. Public Portal Experience

Fast, mobile-friendly, no-login-required, privacy-filtered exclusively — **never publishes provisional, held, restricted, or superseded data**, restated absolutely per working rule 36. Full detail: [public-portal-kiosk-scoreboard-and-display-experience.md, Section 1](public-portal-kiosk-scoreboard-and-display-experience.md#1-public-portal-experience).

## 31. Kiosk Experience

Large touch targets, minimal navigation, session reset, privacy timeout, no protected-data persistence — **minimizes privacy exposure absolutely**, per working rule 40. Full detail: [public-portal-kiosk-scoreboard-and-display-experience.md, Section 2](public-portal-kiosk-scoreboard-and-display-experience.md#2-kiosk-experience).

## 32. Public Scoreboard and Venue Display

Prioritizes distance readability, high contrast, and explicit provisional-versus-certified distinction over decoration — restated absolutely per working rule 39; no dense navigation or administrative controls. Full detail: [public-portal-kiosk-scoreboard-and-display-experience.md, Section 3](public-portal-kiosk-scoreboard-and-display-experience.md#3-public-scoreboard-and-venue-display-scoreboard-standards).

## 33. Report and Print Experience

Official, structured, reproducible — a printed document reflects the exact certified/published version it was generated from, per the Phase 0.5 versioning model. Full detail: [public-portal-kiosk-scoreboard-and-display-experience.md, Section 4](public-portal-kiosk-scoreboard-and-display-experience.md#4-report-and-print-experience).

## 34. Device and Browser Compatibility

A candidate, not-yet-finalized compatibility baseline (evergreen browsers, an Android minimum-version pending device procurement) — **no matrix finalized without evidence**. Full detail: [public-portal-kiosk-scoreboard-and-display-experience.md, Section 5](public-portal-kiosk-scoreboard-and-display-experience.md#5-device-and-browser-compatibility).

---

## 35. Table and Data Grid Standards

Eight pattern choices and thirteen support requirements — **avoid placing too many actions in each row.** Full detail: [dashboard-table-chart-and-data-visualization-standards.md, Section 1](dashboard-table-chart-and-data-visualization-standards.md#1-table-and-data-grid-standards).

## 36. Chart Standards

Ten chart-type choices (donut/pie only when justified), ten required chart elements, categorical-palette discipline extending the confirmed five `--chart-*` tokens, and mandatory accessible alternatives. Full detail: [dashboard-table-chart-and-data-visualization-standards.md, Section 2](dashboard-table-chart-and-data-visualization-standards.md#2-chart-standards).

## 37. Real-Time, Freshness, and State Indication

Live indicators, data freshness, last-update time, and version indication on every dashboard/chart/projection surface — **certified and published are always distinctly labeled states**, restated absolutely per working rule 33 extended to visualization. Full detail: [dashboard-table-chart-and-data-visualization-standards.md, Sections 3–4](dashboard-table-chart-and-data-visualization-standards.md#3-real-time-data-display).

---

## 38. Form Architecture

Nine form categories and eleven requirements (clear labels, input preservation, accessible errors, reason capture where required). Full detail: [form-validation-draft-and-workflow-experience.md, Section 2](form-validation-draft-and-workflow-experience.md#2-form-architecture).

## 39. Validation Experience

Client validation for immediate feedback; **server validation is always authoritative**, restated from every prior phase's server-authority principle — internal exception messages are never exposed. Full detail: [form-validation-draft-and-workflow-experience.md, Section 3](form-validation-draft-and-workflow-experience.md#3-validation-experience).

## 40. Draft and Autosave Experience

Six rules — **draft is never submission; offline draft is never server-accepted; high-integrity decisions always require explicit final action.** Full detail: [form-validation-draft-and-workflow-experience.md, Section 4](form-validation-draft-and-workflow-experience.md#4-draft-and-autosave-experience).

## 41. Multi-Step Workflows and Bulk Actions

Step indicators, per-step validation, save-and-continue, and a review step before submission; bulk actions always preview effect and report per-item success/failure. Full detail: [form-validation-draft-and-workflow-experience.md, Sections 5–6](form-validation-draft-and-workflow-experience.md#5-multi-step-workflow-experience).

## 42. Personalization, Density, and Localization Readiness

Density settings layer on presentation only, never altering data/authorization; localization readiness (externalized strings) is a readiness property, not an active commitment. Full detail: [form-validation-draft-and-workflow-experience.md, Sections 7–8](form-validation-draft-and-workflow-experience.md#7-personalization-and-density-settings).

---

## 43. Status Vocabulary and Feedback Components

Every status carries a plain-language label, semantic tone, icon, accessible description, allowed actions, and material context — **high-integrity states are visually distinct, always.** Alerts, notifications, dialogs, and drawers build on the confirmed `alert.tsx`/`sonner.tsx`/`dialog.tsx`/`sheet.tsx` foundation. Full detail: [status-feedback-error-offline-and-sync-patterns.md, Sections 1–2](status-feedback-error-offline-and-sync-patterns.md#1-status-vocabulary-and-status-badges).

## 44. Empty, Loading, and Error States

Six differentiated empty-state categories, ten loading-state contexts (never implying success before server confirmation), and twelve error categories each with a defined recovery path. Full detail: [status-feedback-error-offline-and-sync-patterns.md, Sections 3–5](status-feedback-error-offline-and-sync-patterns.md#3-empty-states).

## 45. Offline, Sync, and Conflict States

Eleven cross-platform connectivity states (Online through Authorization expired) — **local draft is never shown as officially accepted; the server is always the final authority.** Conflicts route to human review for high-integrity data, never mechanical auto-resolution. Full detail: [status-feedback-error-offline-and-sync-patterns.md, Sections 6–7](status-feedback-error-offline-and-sync-patterns.md#6-offline-and-sync-experience).

## 46. Success and Destructive-Action Confirmation

Success confirmation states specifically what happened; destructive-action confirmation requires deliberate acknowledgment, states consequence in plain language, and is spatially separated from routine confirmations. Full detail: [status-feedback-error-offline-and-sync-patterns.md, Section 9](status-feedback-error-offline-and-sync-patterns.md#9-success-and-destructive-action-confirmation).

---

## 47. High-Integrity Action Experience

Every approve/certify/publish/revoke/override action shows authority, scope, state, consequence, reason, evidence, confirmation, audit notice, resulting state, and next responsible role — restated absolutely from working rule 33. Full detail: [high-integrity-approval-certification-and-publication-ux.md, Section 1](high-integrity-approval-certification-and-publication-ux.md#1-high-integrity-action-experience).

## 48. Approval and Certification Interfaces

**Review, recommend, approve, certify, publish, and override are never conflated into one generic button** — SoD conflicts block the action, not merely warn. Full detail: [high-integrity-approval-certification-and-publication-ux.md, Section 2](high-integrity-approval-certification-and-publication-ux.md#2-approval-and-certification-interfaces).

## 49. Audit-History and Change-History Interfaces

A chronological, append-only timeline view for every high-integrity record — never a single "last modified by" field that discards earlier history. Full detail: [high-integrity-approval-certification-and-publication-ux.md, Section 3](high-integrity-approval-certification-and-publication-ux.md#3-audit-history-and-change-history-interfaces).

## 50. Correction and Supersession UX

**No silent editing of a finalized record is ever presented as possible** — a correction request flow with side-by-side comparison, impacted-record listing, and full audit history. Full detail: [high-integrity-approval-certification-and-publication-ux.md, Section 4](high-integrity-approval-certification-and-publication-ux.md#4-correction-and-supersession-ux).

## 51. Publication UX

Shows source state, certification status, privacy filtering, public-field preview, and existing published version before any publish/unpublish/correct/supersede/schedule action. Full detail: [high-integrity-approval-certification-and-publication-ux.md, Section 5](high-integrity-approval-certification-and-publication-ux.md#5-publication-ux).

## 52. Protest and Appeal UX

Filing, evidence, result hold, review, decision, and appeal — **no official deadline or authority invented**, restated absolutely per working rule 34, marked pending [Phase 0.1 OD-09](../00-product/open-decisions.md#od-09--protest-and-appeal-authority). Full detail: [high-integrity-approval-certification-and-publication-ux.md, Section 6](high-integrity-approval-certification-and-publication-ux.md#6-protest-and-appeal-ux).

---

## 53. Tournament Bracket, Match, Heat, and Lane Interfaces

Support for single/double elimination, round robin, pools, and classification rounds, always with an accessible non-visual alternative to purely graphical bracket diagrams. Full detail: [sports-tournament-scoring-and-results-components.md, Sections 1–3](sports-tournament-scoring-and-results-components.md#1-tournament-bracket-standards).

## 54. Score Entry, Timing, and Measurement Interfaces

Prioritizes assigned context, clear numeric input, precision/unit discipline, lock state, and correction history — **minimizes distractions and accidental navigation** in the platform's highest-time-pressure surface. Full detail: [sports-tournament-scoring-and-results-components.md, Sections 4–5](sports-tournament-scoring-and-results-components.md#4-score-entry-interfaces).

## 55. Result Boards and Medal Tally Standards

Result boards default to certified-only display for any non-official audience; medal tally cites its ranking rule and never assumes final rules without validation, per working rule 34. Full detail: [sports-tournament-scoring-and-results-components.md, Sections 6–7](sports-tournament-scoring-and-results-components.md#6-result-boards).

## 56. Athlete Profiles, Rosters, and Officials Assignment

Internal versus public profile distinction — **never exposes medical details, guardian data, eligibility evidence, or protected contact information publicly**, restated absolutely per working rule 26. Full detail: [sports-tournament-scoring-and-results-components.md, Sections 9–10](sports-tournament-scoring-and-results-components.md#9-athlete-profile-experience).

## 57. Venue Boards and Schedule Timelines

Time-ordered, venue/sport-filterable schedule presentation with an accessible table alternative to any visual timeline. Full detail: [sports-tournament-scoring-and-results-components.md, Section 11](sports-tournament-scoring-and-results-components.md#11-venue-boards-and-schedule-timelines).

---

## 58. Accreditation Card Experience

Name, role, delegation, meet, public credential number, QR code, validity, and status — **the QR code never exposes internal IDs or sensitive data directly.** Full detail: [accreditation-qr-device-and-shared-station-experience.md, Section 1](accreditation-qr-device-and-shared-station-experience.md#1-accreditation-card-experience).

## 59. QR Scanner Experience

A deliberately minimal five-step flow (Ready → Scan → Processing → Result → Automatic reset) with large status, strong audio/vibration feedback, and privacy-limited display. Full detail: [accreditation-qr-device-and-shared-station-experience.md, Section 2](accreditation-qr-device-and-shared-station-experience.md#2-qr-scanner-experience).

## 60. Shared-Device and Station Experience

Every shift change requires individual re-authentication — **device trust never substitutes for operator identity.** Full detail: [accreditation-qr-device-and-shared-station-experience.md, Section 3](accreditation-qr-device-and-shared-station-experience.md#3-shared-device-and-station-experience).

---

## 61. Committee Experience Architecture

Twelve committees, each with documented primary tasks, time pressure, mobile/offline need, and sensitive-data profile — core experience needs, not complete screen inventories, per working rule 16. Full detail: [committee-logistics-medical-finance-and-support-experience.md, Section 1](committee-logistics-medical-finance-and-support-experience.md#1-committee-experience-architecture).

## 62. Medical Experience

The platform's strongest privacy boundary — summary versus detailed views are architecturally distinct, emergency access is a separate elevated path, and **no clinical protocol is defined here.** Full detail: [committee-logistics-medical-finance-and-support-experience.md, Section 2](committee-logistics-medical-finance-and-support-experience.md#2-medical-experience).

## 63. Finance Experience

Clear amounts/currency, encoder-approver interface separation restated absolutely from SOD-06, correction/reversal history — **no public display except approved summaries.** Full detail: [committee-logistics-medical-finance-and-support-experience.md, Section 3](committee-logistics-medical-finance-and-support-experience.md#3-finance-experience).

## 64. Logistics, Security, ICT, and Media Interfaces

Billeting, food-distribution, transport, security-incident, ICT-support, and media/public-information interfaces, each built from the shared table/QR/timeline/publication patterns already established. Full detail: [committee-logistics-medical-finance-and-support-experience.md, Section 4](committee-logistics-medical-finance-and-support-experience.md#4-medical-alert-billeting-food-distribution-transport-security-incident-ict-support-and-media-interfaces).

---

## 65. Search Experience

Scope-aware, privacy-aware results — **no leakage through suggestions**, restated absolutely; a search never reveals the existence of an unauthorized record even partially. Full detail: [search-filter-import-export-and-file-experience.md, Section 1](search-filter-import-export-and-file-experience.md#1-search-experience).

## 66. Filter, Saved-View, and Pagination Experience

Visible active filters, no hidden filters causing unexplained missing data, per-user saved views, and universal pagination. Full detail: [search-filter-import-export-and-file-experience.md, Sections 2, 6–7](search-filter-import-export-and-file-experience.md#2-filter-experience).

## 67. Import Experience

Template selection/version, validation, preview, row-level errors, and audit reference — the fourteen-stage Phase 0.5 import lifecycle given interface expression. Full detail: [search-filter-import-export-and-file-experience.md, Section 3](search-filter-import-export-and-file-experience.md#3-import-experience).

## 68. Export Experience

Classification shown before export, reason capture, background generation, expiry, and server-side redaction — **never a client-side redaction afterthought.** Full detail: [search-filter-import-export-and-file-experience.md, Section 4](search-filter-import-export-and-file-experience.md#4-export-experience).

## 69. File Experience

Upload, scan-status gating (a file the malware scanner hasn't cleared is never presented as downloadable), classification, version — **never exposes raw MinIO object paths.** Full detail: [search-filter-import-export-and-file-experience.md, Section 5](search-filter-import-export-and-file-experience.md#5-file-experience).

---

## 70. Privacy-Aware and Security-Aware Experience

Masked fields, restricted sections, sensitive-view audit notices, public-view previews before publication — every authorization-reflecting interface element is understood as a usability convenience layered on independent server-side enforcement, restated absolutely per working rule 25. Full detail: [privacy-security-and-sensitive-data-experience.md, Sections 1–2](privacy-security-and-sensitive-data-experience.md#1-privacy-aware-experience).

## 71. Sensitive-Data Masking and Public-Data Filtering

Eleven masking/redaction techniques applied consistently across UI, reports, exports, logs, and public projections — **no component displays a field without confirming its classification-appropriate treatment.** Full detail: [privacy-security-and-sensitive-data-experience.md, Section 3](privacy-security-and-sensitive-data-experience.md#3-sensitive-data-masking-and-public-data-filtering).

## 72. Minor-Athlete Presentation Rules

Restricted public-profile content, controlled photo publication, age-appropriate language, and privacy-aware search — restated absolutely from Phase 0.6's minor-athlete governance. Full detail: [privacy-security-and-sensitive-data-experience.md, Section 4](privacy-security-and-sensitive-data-experience.md#4-minor-athlete-presentation-rules).

## 73. Medical, Eligibility, Finance, and Audit-Data Interface Restrictions

Indexed to their respective owning documents rather than duplicated, ensuring each sensitive domain's interface rules stay synchronized with a single source. Full detail: [privacy-security-and-sensitive-data-experience.md, Section 5](privacy-security-and-sensitive-data-experience.md#5-medical-eligibility-finance-and-audit-data-interface-restrictions-cross-reference).

---

## 74. AI-Assisted Experience Architecture

Eight allowed patterns (duplicate suggestions through anomaly alerts), each requiring an AI-generated label, source references, confidence/limitations, and explicit accept/reject/edit controls. Full detail: [ai-assisted-experience-architecture.md, Sections 1–2](ai-assisted-experience-architecture.md#1-allowed-ai-assisted-interface-patterns).

## 75. AI Consequential-Action Restrictions

**AI never presents itself as the final decision-maker for eligibility, scores, results, protests, medals, medical decisions, permissions, credential revocation, or financial approvals** — restated absolutely and without exception. Full detail: [ai-assisted-experience-architecture.md, Section 3](ai-assisted-experience-architecture.md#3-ai-consequential-action-restrictions-absolute).

## 76. AI Error Recovery and Disablement

Every AI-assisted workflow degrades gracefully to its ordinary non-AI path when AI is unavailable, low-confidence, or disabled — an AI suggestion is always an addition, never a dependency. Full detail: [ai-assisted-experience-architecture.md, Sections 4–5](ai-assisted-experience-architecture.md#4-ai-error-recovery).

---

## 77. Content Architecture and Terminology Governance

Clear, direct, actionable content avoiding vague messages ("Something went wrong," "Invalid input," "Action failed"); twelve approved domain terms used consistently, never interchangeably; eight distinctions the interface must never blur (score vs. result, validated vs. certified vs. published, role vs. assignment, correction vs. supersession). Full detail: [content-design-terminology-help-and-onboarding.md, Sections 1–2](content-design-terminology-help-and-onboarding.md#1-content-architecture).

## 78. Date, Time, Score, and Measurement Formatting

Follows the Phase 0.5 timestamp/precision/unit standards exactly — never an independently-invented display format. Full detail: [content-design-terminology-help-and-onboarding.md, Section 3](content-design-terminology-help-and-onboarding.md#3-date-time-score-and-measurement-formatting).

## 79. Error, Confirmation, and Notification Message Standards

Structured messages stating what happened, what was preserved, and what the user can do — notifications carry only minimum necessary content, never Restricted/Highly Restricted-tier data in a preview. Full detail: [content-design-terminology-help-and-onboarding.md, Sections 4, 6](content-design-terminology-help-and-onboarding.md#4-error-confirmation-and-notification-message-standards).

## 80. Help Architecture and Onboarding

Contextual help, glossary linking directly to the Phase 0.2 domain glossary, and role/assignment-specific onboarding for nine distinct user categories — never a generic one-size-fits-all product tour. Full detail: [content-design-terminology-help-and-onboarding.md, Sections 7–9](content-design-terminology-help-and-onboarding.md#7-help-architecture).

---

## 81. Design-System Governance

Eight candidate governance roles and six decision-rights areas — every component owned, every change reviewed. Full detail: [design-system-governance-documentation-and-versioning.md, Section 1](design-system-governance-documentation-and-versioning.md#1-design-system-governance).

## 82. Component Lifecycle, Ownership, Versioning, and Deprecation

An eight-stage lifecycle (Proposed → Removed), independent design-system versioning from the application release version, and a deprecation discipline requiring a documented replacement and migration guide before removal. Full detail: [design-system-governance-documentation-and-versioning.md, Sections 2–3, 5–6](design-system-governance-documentation-and-versioning.md#2-component-lifecycle).

## 83. Design Quality Gates and UX Debt

Six pre-Approved-status gates (accessibility, content/terminology, cross-platform consistency, responsive behavior, no privacy/security exposure, token compliance) and a UX-debt tracking discipline mirroring Phase 0.7's quality-debt model. Full detail: [design-system-governance-documentation-and-versioning.md, Sections 7–8](design-system-governance-documentation-and-versioning.md#7-design-quality-gates).

## 84. UX Research, Validation, and Quality Gates

Every proto-persona validated against real users during the pilot; **verification (was it built to spec?) is distinguished absolutely from validation (does it solve the real problem?)**, restated from Phase 0.7's identical distinction. Full detail: [ux-research-validation-and-quality-gates.md](ux-research-validation-and-quality-gates.md).

---

## 85. Risks, Assumptions, Tradeoffs, and Alternatives Considered

### Key Risks
- **Branding-palette approval remains unresolved** ([DX-02](design-open-decisions.md#dx-02--branding-palette-approval)) — blocking every brand-carrying visual token's finalization.
- **WCAG conformance target unset** ([DX-01](design-open-decisions.md#dx-01--wcag-conformance-target)) — every accessibility-related design-quality gate depends on it.
- **Sport-specific interface detail remains blocked** (DX-14/DX-15/DX-16, mirroring OD-10/OD-12/OD-09) — the same sports-policy dependency chain every prior phase has carried forward.
- **Proto-personas are unvalidated** — every user-group profile in this package requires pilot confirmation before being treated as design-authoritative.

### Key Assumptions
- The Phase 0.1–0.8 foundation remains stable enough to anchor an experience architecture without near-term restructuring.
- This repository's confirmed shadcn/ui "new-york" style, OKLCH token foundation, and existing app-shell components (`app-sidebar.tsx`, `use-appearance.tsx`) remain the confirmed starting point for React implementation.
- A controlled pilot (per Phase 0.7) occurs before general availability, providing the first genuine usability-validation and branding-approval opportunity multiple sections of this package depend on.

### Key Tradeoffs
- **Extending the existing neutral shadcn palette rather than replacing the token architecture** (Section 17) trades a faster branding rollout for accepting the confirmed foundation's structural choices — assessed as the lower-risk path given a working, tested foundation already exists.
- **Deferring Storybook/component-preview tooling** (Section 84, DX-06) trades near-term documentation richness for avoiding premature tooling investment before component volume justifies it.
- **Six new semantic state tokens added to an otherwise-standard shadcn structure** (Section 15) trades token-count growth for the explicit state-visibility working rule 33 requires — assessed as necessary, not optional, given PMMS's high-integrity domains.

### Alternatives Considered
1. **Begin page implementation directly from the existing shadcn/ui starter kit without a dedicated experience-architecture phase.** Rejected — would repeat the exact per-screen inconsistency risk every prior phase's centralization work exists to prevent, now at the interface layer specifically.
2. **Adopt a third-party enterprise design system wholesale instead of building PMMS Arena on the existing shadcn foundation.** Rejected — this repository already has a working, tested shadcn/Tailwind foundation; replacing it would discard confirmed, functional work for no demonstrated benefit.
3. **Treat React and Flutter as requiring pixel-identical components.** Rejected — restated absolutely per the phase's own working instruction; platform-appropriate divergence is a feature, not a defect, as long as shared semantics (terminology, color meaning, state vocabulary) hold.
4. **Finalize a complete screen inventory now to give implementation teams a definitive scope.** Rejected — directly violates working rule 17; the information architecture and component taxonomy give implementation teams a structure to build within, without prematurely locking every specific screen before real requirements and pilot feedback refine them.
5. **Claim WCAG AA compliance now to reassure stakeholders.** Rejected — directly violates the "no compliance claimed until tested" discipline restated from Phase 0.6; AA is a recommended target (DX-01), not an achieved status.

## 86. Recommended Direction

> Build the PMMS Arena Design System as a direct extension of this repository's confirmed shadcn/ui "new-york" + Tailwind 4 + OKLCH token foundation — adding PMMS-specific semantic state tokens, a validated institutional branding palette, and platform-appropriate Flutter parity — while treating every high-integrity workflow's state visibility, every sensitive-data masking rule, and every accessibility requirement as non-negotiable product requirements rather than enhancements layered on afterward.

## 87. Phase 0.9 Deliverables

- 27 documents in `docs/06-design/` (this document + 27 supporting documents, listed in [README.md](README.md)).
- Updates to [../01-architecture/README.md](../01-architecture/README.md), [../02-data/README.md](../02-data/README.md), [../03-security/README.md](../03-security/README.md), [../04-quality/README.md](../04-quality/README.md), and [../05-devops/README.md](../05-devops/README.md) referencing this package.
- Updates to `.ai/project-context.md`, `.ai/current-phase.md`, `.ai/architecture.md`.
- New `.ai/design-system-rules.md`, `.ai/ux-rules.md`, `.ai/accessibility-rules.md`, `.ai/content-design-rules.md`, `.ai/data-visualization-rules.md`, `.ai/mobile-experience-rules.md`.
- New `.ai/decisions/ADR-0009-design-system-ux-accessibility-and-cross-platform-experience.md`.

## 88. Phase 0.9 Acceptance Criteria

- [x] Experience vision, UX principles, and design principles documented.
- [x] Nineteen experience contexts and twenty-seven user groups documented as proto-personas requiring validation.
- [x] Information architecture (28 domains), navigation architecture, and workspace hierarchy documented.
- [x] Nine product surfaces and seven dashboard layers documented.
- [x] Cross-platform design system named and structured; component taxonomy indexed across all documents.
- [x] Design tokens, color/theme/surface system, typography/iconography/content style documented against confirmed repository evidence.
- [x] Responsive, touch, keyboard, and motion/animation rules documented — no breakpoint finalized without evidence.
- [x] Accessibility architecture, screen-reader behavior, and focus management documented — no compliance claimed until tested.
- [x] React and Flutter experience direction documented, preserving server-side authority and offline-first principles.
- [x] Public portal, kiosk, scoreboard, and print experience documented — no provisional/held/restricted data ever published.
- [x] Table, chart, and data-visualization standards documented, including mandatory accessible alternatives.
- [x] Form, validation, draft, and multi-step workflow experience documented.
- [x] Status, feedback, error, offline, and sync patterns documented across all eleven connectivity states.
- [x] High-integrity approval, certification, correction, supersession, publication, and protest UX documented — no silent editing of finalized records.
- [x] Sports, tournament, scoring, and results components documented — no sport-specific rule invented.
- [x] Accreditation, QR scanner, device, and shared-station experience documented.
- [x] Committee, logistics, medical, and finance experience documented — strongest privacy boundary applied to medical.
- [x] Search, filter, import, export, and file experience documented.
- [x] Privacy-aware and security-aware experience documented — frontend hiding never treated as authorization.
- [x] AI-assisted experience architecture documented — AI never the final decision-maker for any consequential action.
- [x] Content design, terminology governance, help, and onboarding documented.
- [x] Design-system governance, component lifecycle, versioning, and quality gates documented.
- [x] UX research/validation model documented, distinguishing verification from validation.
- [x] Open decisions recorded (24 items, cross-referenced against all prior phases).
- [x] AI workspace updated.
- [x] No React component, Flutter widget, Tailwind class, shadcn config, route, design token in code, logo, image, icon, or Figma file generated.
- [x] No UI/design-system package installed; no existing implementation screen redesigned; no complete screen inventory finalized as approved.
- [x] Documents internally consistent (cross-reference verified — see completion report).

## 89. Preparation Requirements for Phase 0.10

Phase 0.10 (the next phase — likely the first phase authorizing actual component/screen implementation) can proceed once it has:

- This package's design tokens, color/theme system, component taxonomy, and terminology governance as a binding reference for every React and Flutter implementation decision.
- Every prior phase's `.ai/` rule files plus this phase's new `.ai/design-system-rules.md`, `.ai/ux-rules.md`, `.ai/accessibility-rules.md`, `.ai/content-design-rules.md`, `.ai/data-visualization-rules.md`, and `.ai/mobile-experience-rules.md` as the complete AI-facing implementation guardrail set.
- Resolution (or an accepted interim plan) for the highest-priority open decisions: **DX-02** (branding-palette approval, blocking every visual-identity decision), **DX-01** (WCAG conformance target), and the sport-specific-detail cluster (DX-14/DX-15/DX-16, blocked on OD-10/OD-12/OD-09).
- Confirmation of whether Phase 0.10 begins React component implementation, Flutter scaffolding, or both — this package deliberately did not assume which.

Phase 0.9 does not itself perform any of Phase 0.10's work — this section exists so Phase 0.10 does not need to rediscover these foundations.

## Next Phase

```text
Phase 0.10 — (to be named by the next phase's own prompt)
```

Phase 0.10 is not started as part of this task, per working rule 41.
