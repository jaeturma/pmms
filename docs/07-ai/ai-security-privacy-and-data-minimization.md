# PMMS AI Security, Privacy, and Data Minimization

**Status:** Draft Complete — Pending AI, Security, Privacy, Domain, Sports, Quality, and Engineering Validation
**Related:** [../03-security/ai-security-privacy-and-governance.md](../03-security/ai-security-privacy-and-governance.md) · [../03-security/minor-athlete-and-guardian-data-governance.md](../03-security/minor-athlete-and-guardian-data-governance.md) · [../03-security/medical-eligibility-finance-and-sensitive-data-controls.md](../03-security/medical-eligibility-finance-and-sensitive-data-controls.md)

This document extends Phase 0.6's AI security/privacy architecture with data-minimization detail for every candidate AI capability. **No secret, personal data, eligibility document, medical data, financial data, audit data, or authentication data is exposed by this document** — restated absolutely per working rule 12.

---

## 1. Threats (Cross-Reference)

Restated unchanged from [../03-security/ai-security-privacy-and-governance.md, Section 1](../03-security/ai-security-privacy-and-governance.md#1-ai-security-threats): prompt injection · indirect prompt injection · sensitive-data leakage · over-permission · hallucinated rules · data poisoning · unauthorized action · model or vendor compromise · insecure tool use · excessive retention · cross-tenant leakage · untraceable recommendations. Not redefined here.

### Prompt Injection

A user or a retrieved document may contain content deliberately crafted to make an AI feature deviate from its intended task — restated from [../03-security/ai-security-privacy-and-governance.md, Section 1](../03-security/ai-security-privacy-and-governance.md#1-ai-security-threats). Treated as untrusted input at every layer, per [prompt-context-and-structured-output-architecture.md, Sections 3–4](prompt-context-and-structured-output-architecture.md#3-system-prompts-and-user-prompts).

## 2. Data Minimization

Every AI request assembles only the specific records/fields the specific request needs — never a standing broad dataset, never "just in case" context. This restates [../03-security/privacy-by-design-architecture.md, Section 2](../03-security/privacy-by-design-architecture.md#2-privacy-by-design-controls)'s minimization principle, applied specifically to AI context assembly (the Data Minimization Layer, per [ai-platform-and-service-architecture.md, Section 1](ai-platform-and-service-architecture.md#1-conceptual-components)).

## 3. Privacy Controls

Restated from [../03-security/privacy-by-design-architecture.md](../03-security/privacy-by-design-architecture.md), applied to AI: classification-aware data assembly · redaction of fields not needed for the specific request · no AI feature receives more data than the requesting user's own authorization already permits, restated per working rule 22.

## 4. Minor-Athlete Protections

**Enhanced protection, restated absolutely from working rule 35.** AI use of minor-athlete data is permitted only when required for the specific, authorized workflow (e.g., UC-01 eligibility document review, where the athlete's identity is inherently part of the record under review) — never for a general-purpose assistant's convenience. No minor-athlete data is sent to an AI capability beyond what [../03-security/minor-athlete-and-guardian-data-governance.md](../03-security/minor-athlete-and-guardian-data-governance.md) already permits a human in the same role to see.

## 5. Sensitive-Document Handling

| Category | AI Access Rule |
|---|---|
| Eligibility evidence | Restricted to UC-01 (the eligibility-review capability) specifically — never available to a general-purpose assistant, restated absolutely |
| Medical data | **Excluded from general AI use by default** — restated absolutely per working rule 36; no medical detail is ever sent to a helpdesk, committee, or narrative-generation capability |
| Guardian data | Excluded unless required for an authorized workflow, restated from the main document's data-access boundary (Section, [phase-0.10-ai-assisted-platform-architecture.md, Section 21](phase-0.10-ai-assisted-platform-architecture.md#21-sensitive-data-restrictions)) |
| Finance | Restricted to finance-specific assistants only, mirroring [../03-security/medical-eligibility-finance-and-sensitive-data-controls.md, "Finance Data Governance"](../03-security/medical-eligibility-finance-and-sensitive-data-controls.md#finance-data-governance) |
| Security and audit records | Restricted, excluded from general summarization — a narrative-generation capability never has standing access to Highly Restricted security-incident detail |
| Authentication data | **Never sent to any AI service, under any circumstance** — restated absolutely, the single most non-negotiable rule in this document |

## 6. External-Provider Data Sharing

**Requires explicit governance, restated absolutely per working rule 37.** No PMMS data is uploaded to an external AI service without an approved [ai-provider-vendor-and-third-party-risk.md](ai-provider-vendor-and-third-party-risk.md) assessment confirming the provider's data-handling terms are acceptable for the specific classification tier involved. This restates and does not weaken working rule 11's prohibition on uploading repository data to an external AI service during this documentation phase itself.

## 7. Data-Residency Readiness

Where an AI provider processes data outside an acceptable region, this is a specific, named risk requiring review — restated from [../03-security/ai-security-privacy-and-governance.md, Section 2](../03-security/ai-security-privacy-and-governance.md#2-ai-privacy-and-governance). No residency commitment is made in this document; local-model hosting (per [ai-gateway-provider-and-model-abstraction.md, Section 2](ai-gateway-provider-and-model-abstraction.md#2-provider-and-model-abstraction)) is a candidate mitigation for the highest-sensitivity use cases if residency proves a blocking concern.

## 8. AI Data Retention, Prompt, and Response Retention

Prompt and response logging **minimizes sensitive content** — restated absolutely per working rule 38. Full prompts/responses are not retained by default for any request touching Restricted-or-above data; the AI Audit Service (per [ai-identity-authorization-scope-and-audit.md](ai-identity-authorization-scope-and-audit.md)) instead retains a safe reference (a hash or a pointer to the source records involved) rather than the raw content itself, except where specifically approved for evaluation purposes under the same governance as any other sensitive-data retention exception. Retention periods themselves remain placeholders pending DepEd/legal input, exactly mirroring [../02-data/retention-archival-and-disposal.md](../02-data/retention-archival-and-disposal.md)'s treatment of every other data category.

## 9. Cross-Tenant Isolation

If PMMS ever supports multiple organizations (per [../00-product/open-decisions.md, OD-02](../00-product/open-decisions.md#od-02--single-organization-versus-multi-organization)), AI request context, retrieval, and cost tracking never blend across organizational boundaries — restated as a readiness property, not a currently-active concern, mirroring Phase 0.8's identical "readiness, not commitment" treatment of multi-organization support generally.

## 10. Output Leakage

An AI response never reveals data the requesting user could not access directly — restated absolutely per working rule 22. This applies even indirectly: a summary or aggregate statistic must not allow a user to infer a Restricted-tier fact they couldn't otherwise see (e.g., a "delegation X has 1 athlete flagged for medical review" leak through an aggregate count).

## 11. Open Questions

See [ai-open-decisions.md](ai-open-decisions.md) — notably the specific safe-reference mechanism for prompt/response audit logging and whether local-model hosting is pursued for medical/eligibility-adjacent use cases specifically.
