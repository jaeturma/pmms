# PMMS AI-Assisted Experience Architecture

**Status:** Draft Complete — Pending Product, UX, Accessibility, Domain, and Engineering Validation
**Related:** [../03-security/ai-security-privacy-and-governance.md](../03-security/ai-security-privacy-and-governance.md) · [high-integrity-approval-certification-and-publication-ux.md](high-integrity-approval-certification-and-publication-ux.md)

This document defines the interface expression of Phase 0.6's absolute AI action boundary. **No AI feature, model integration, or component is created here.**

---

## 1. Allowed AI-Assisted Interface Patterns

Suggested duplicate matches (per [../02-data/identity-resolution-and-duplicate-management.md](../02-data/identity-resolution-and-duplicate-management.md)) · missing-requirement suggestions · conflict detection · schedule recommendations · draft narratives (e.g., a committee report starting point) · search assistance · summary generation · anomaly alerts.

## 2. Required Elements of Every AI Output

Every AI-generated or AI-assisted output displays, where appropriate: an **AI-generated or AI-assisted label** (never presented as if it were human-authored or authoritative-by-default) · source references (what data informed this suggestion) · confidence or uncertainty (the AI's own signal of how reliable this specific suggestion is, where the underlying model provides one) · limitations (what this suggestion does not account for) · review action (explicit accept/reject/edit controls, never a passive display the user must know to distrust) · accept, reject, or edit controls · data-use notice (what data was sent to produce this suggestion, consistent with [../03-security/ai-security-privacy-and-governance.md, Section 2](../03-security/ai-security-privacy-and-governance.md#2-ai-privacy-and-governance)'s minimum-necessary-data principle) · timestamp · model or analysis version where material (supporting the audit trail already required by [../03-security/audit-and-security-event-architecture.md, Section 7](../03-security/audit-and-security-event-architecture.md#7-ai-assistance-auditing)).

## 3. AI Consequential-Action Restrictions (Absolute)

**AI must never present itself as the final decision-maker for:** eligibility · scores · results · protests · medals · medical decisions · permissions · credential revocation · financial approvals — restated absolutely and without exception from [../03-security/ai-security-privacy-and-governance.md, Section 3](../03-security/ai-security-privacy-and-governance.md#3-ai-action-boundaries-absolute-prohibitions).

**Human confirmation always uses the ordinary authorized workflow** — an AI suggestion that a human accepts is submitted through the exact same [high-integrity-approval-certification-and-publication-ux.md](high-integrity-approval-certification-and-publication-ux.md) interface pattern as if the human had originated it without AI involvement, never a shortcut path that bypasses the authority/scope/reason/confirmation requirements every other high-integrity action requires.

## 4. AI Error Recovery

When an AI feature is unavailable, produces a low-confidence or clearly wrong suggestion, or is disabled, the interface degrades gracefully to the ordinary, fully-functional non-AI workflow — restated from [../03-security/ai-security-privacy-and-governance.md, Section 2](../03-security/ai-security-privacy-and-governance.md#2-ai-privacy-and-governance), "every AI-assisted workflow has a non-AI fallback path." An AI error is never a workflow-blocking error.

## 5. AI Disablement

Every AI-assisted interface element can be disabled platform-wide or per-context without requiring a broader interface redesign — restated absolutely; the presence of an AI suggestion is always an *addition* to an otherwise-complete manual workflow, never a dependency the workflow requires to function.

## 6. Visual Treatment

AI-assisted content is visually distinguished (a consistent badge/label treatment, per [color-theme-and-surface-system.md](color-theme-and-surface-system.md)'s semantic-token discipline) from human-authored or server-authoritative content — a user should never need to guess whether what they're looking at came from a person or a model.

## 7. Open Questions

See [design-open-decisions.md](design-open-decisions.md) — notably which specific AI use cases (per [../03-security/security-open-decisions.md, SD-20](../03-security/security-open-decisions.md#sd-20--ai-use-case-approval-for-initial-implementation)) are approved for initial implementation, blocked on [Phase 0.1 OD-29](../00-product/open-decisions.md#od-29--ai-service-restrictions).
