# PMMS Form, Validation, Draft, and Workflow Experience

**Status:** Draft Complete — Pending Product, UX, Accessibility, Domain, and Engineering Validation
**Related:** [accessibility-architecture.md](accessibility-architecture.md) · [../02-data/high-integrity-data-model.md](../02-data/high-integrity-data-model.md) · [status-feedback-error-offline-and-sync-patterns.md](status-feedback-error-offline-and-sync-patterns.md)

This document defines form architecture, validation behavior, draft/autosave experience, and multi-step workflow experience. **No form component or validation library is created here.**

---

## 1. Form Component Categories

Form components · data-entry components · selection components (single/multi-select, radio, checkbox — building on the existing `checkbox.tsx`, `select.tsx`, `toggle.tsx`, `toggle-group.tsx`) · date and time controls · file-upload components (per [search-filter-import-export-and-file-experience.md, Section 5](search-filter-import-export-and-file-experience.md#5-file-experience)) · QR components (per [accreditation-qr-device-and-shared-station-experience.md](accreditation-qr-device-and-shared-station-experience.md)).

## 2. Form Architecture

### Categories

Short forms · long forms · multi-step forms · review forms · approval forms · high-integrity decision forms (per [high-integrity-approval-certification-and-publication-ux.md](high-integrity-approval-certification-and-publication-ux.md)) · bulk-entry forms · mobile quick-entry forms · offline forms.

### Requirements

Clear labels · help text · required-field indication · field-level validation · form-level summary · preservation of input (a validation error never discards what the user already typed) · keyboard support · accessible errors (per [accessibility-architecture.md, Section 3](accessibility-architecture.md#3-focus-management)) · safe defaults (never a default that silently commits a consequential choice) · confirmation for consequential actions · reason capture where required (per Phase 0.6's audit requirements) · draft support where appropriate (Section 4 below).

## 3. Validation Experience

Client validation provides immediate feedback; **server validation is always authoritative** — restated from every prior phase's server-authority principle, applied to form validation specifically. Field errors · cross-field errors · state errors (e.g., attempting an action a record's current state doesn't permit) · authorization errors · conflict errors · duplicate warnings · rule-source references where appropriate (an eligibility-requirement validation cites its source, per [../02-data/test-seed-and-reference-data-strategy.md, Section 1](../02-data/test-seed-and-reference-data-strategy.md#1-reference-and-seed-data-classification)).

**Internal exception messages are never exposed** — restated absolutely from [../01-architecture/observability-and-error-handling.md](../01-architecture/observability-and-error-handling.md); a validation error is always a plain-language, actionable message per [content-design-terminology-help-and-onboarding.md](content-design-terminology-help-and-onboarding.md).

## 4. Draft and Autosave Experience

### Candidate Use Cases

Athlete registration · eligibility review notes · committee reports · incident reports · long financial forms · media drafts.

### Rules

1. **Autosave status is always visible** — a user knows whether their work is currently saved, saving, or unsaved.
2. **Draft is not submission** — a draft record is never mistaken for, or treated identically to, a formally submitted one, restated from [high-integrity-approval-certification-and-publication-ux.md](high-integrity-approval-certification-and-publication-ux.md)'s state-vocabulary discipline.
3. **Offline draft is not server-accepted** — restated absolutely from [flutter-mobile-experience-architecture.md, Section 2](flutter-mobile-experience-architecture.md#2-offline-mobile-experience).
4. **Failed autosave is visible** — a silent autosave failure that later loses work is a design failure, per [experience-vision-and-design-principles.md, Section 2](experience-vision-and-design-principles.md#2-ux-principles), Principle 6.
5. **High-integrity decisions require explicit final action** — an eligibility approval or result certification is never "autosaved" into effect; it requires a deliberate, confirmed submission per [high-integrity-approval-certification-and-publication-ux.md](high-integrity-approval-certification-and-publication-ux.md).
6. **Draft ownership and collaboration rules require validation** — whether two users can co-edit the same draft, and how conflicts in that scenario are handled, is not resolved in this document (tracked in [design-open-decisions.md](design-open-decisions.md)).

## 5. Multi-Step Workflow Experience

Step indicator · completion state · validation per step (a step's own errors are resolved before advancing, where the workflow requires it) · save and continue · back navigation (without losing entered data) · review step (a final summary before submission) · submission confirmation · permission changes mid-flow (handled gracefully if a user's authority changes while a multi-step flow is in progress) · deadline behavior (per the relevant domain's rules, never invented here) · return for correction · re-entry after rejection.

**Avoid unnecessary multi-step flows for simple tasks** — restated absolutely; a single-field update is never forced through a wizard designed for a genuinely complex workflow.

## 6. Bulk Actions

A bulk action (e.g., bulk-approving multiple registrations) always shows: exactly what's selected · a preview of the resulting effect · confirmation before an irreversible bulk change · per-item success/failure reporting after execution (a bulk action that partially fails never silently reports blanket success).

## 7. Personalization and Density Settings

Density settings (per [design-tokens-and-visual-language.md, Section 4](design-tokens-and-visual-language.md#4-spacing-and-density-scale)) and other personalization (saved views, per [search-filter-import-export-and-file-experience.md](search-filter-import-export-and-file-experience.md)) are user preferences layered on top of the shared design system — never altering the underlying data or authorization behavior, only its presentation.

## 8. Localization Readiness

PMMS's interface text is structured to support future localization (externalized strings, no hard-coded English concatenated into logic) even though a specific additional language is not committed to in this phase — a readiness property, not an active multi-language commitment, mirroring the same "readiness, not commitment" discipline Phase 0.8 applied to multi-organization support.

## 9. Open Questions

See [design-open-decisions.md](design-open-decisions.md) — notably draft-collaboration/co-editing behavior and whether localization becomes an active near-term requirement or remains readiness-only.
