# PMMS AI Provider, Vendor, and Third-Party Risk

**Status:** Draft Complete — Pending AI, Security, Privacy, Domain, Sports, Quality, and Engineering Validation
**Related:** [../03-security/vendor-and-third-party-risk.md](../03-security/vendor-and-third-party-risk.md) · [ai-gateway-provider-and-model-abstraction.md](ai-gateway-provider-and-model-abstraction.md)

This document extends [../03-security/vendor-and-third-party-risk.md](../03-security/vendor-and-third-party-risk.md)'s general vendor-assessment framework with AI-provider-specific evaluation criteria. **No AI provider is approved by this document** — restated absolutely per working rule 9.

---

## 1. AI-Specific Vendor Assessment Areas

Extends [../03-security/vendor-and-third-party-risk.md, Section 1](../03-security/vendor-and-third-party-risk.md#1-vendor-assessment-areas) with AI-specific questions: data access (what PMMS data would this provider's model see) · retention (how long the provider retains submitted prompts/responses) · **training use** (does the provider use PMMS data to train or fine-tune its own models — a specific, named concern beyond ordinary vendor data-retention) · residency (where processing occurs geographically) · subprocessors (does the provider itself rely on further sub-vendors) · security (the provider's own security posture and incident history) · privacy (the provider's data-protection commitments) · availability (uptime/SLA track record) · model changes (how the provider communicates and versions model updates — a moving target unlike a static SaaS feature) · exit plan (what happens to PMMS's prompt library/evaluation data if the relationship ends) · portability (can PMMS retrieve its own data from the provider) · cost (pricing model and predictability) · contract readiness (a data-processing agreement or equivalent).

## 2. Training-Use Restriction

**A provider's use of PMMS data to train or improve its own models is a specific, named blocking concern** — no provider is approved for any use case unless its terms explicitly exclude PMMS submissions from model training, or PMMS's plan/tier disables this by default. This restates and makes explicit the general "no external AI data sharing without governance" principle from working rule 37, given how consequential this specific practice is for an institutional platform handling minor-athlete and other sensitive data.

## 3. Assessment Process (Cross-Reference)

Follows the identical eight-step process already established in [../03-security/vendor-and-third-party-risk.md, Section 2](../03-security/vendor-and-third-party-risk.md#2-assessment-process) — purpose justification, data-scope determination, assessment execution, risk review, approval decision, contractual protection, ongoing oversight, offboarding — not redefined here, only applied to AI providers specifically.

## 4. Currently Approved AI Providers

**None.** Restated absolutely, mirroring [../03-security/vendor-and-third-party-risk.md, Section 3](../03-security/vendor-and-third-party-risk.md#3-currently-approved-vendors) — no AI provider is currently approved for PMMS use.

## 5. Candidate Future AI Provider Categories

Anticipated, not committed: a hosted large-language-model provider (for generative/RAG capabilities) · a hosted embeddings provider (if semantic search is adopted) · a document-parsing/OCR service (for eligibility-document assistance) · a local/self-hosted model deployment (evaluated as a data-residency mitigation, per [ai-security-privacy-and-data-minimization.md, Section 7](ai-security-privacy-and-data-minimization.md#7-data-residency-readiness)).

Each, when actually proposed, goes through the Section 3 assessment process before approval.

## 6. Model-Change Risk

Unlike a typical SaaS vendor, an AI provider may silently update the underlying model behind a stable-seeming API — a named risk requiring the Model Registry (per [ai-gateway-provider-and-model-abstraction.md, Section 3](ai-gateway-provider-and-model-abstraction.md#3-model-registry)) to pin specific model versions rather than "latest," and requiring re-evaluation (per [ai-evaluation-testing-and-quality-assurance.md](ai-evaluation-testing-and-quality-assurance.md)) whenever a provider forces a version change.

## 7. Open Questions

See [ai-open-decisions.md](ai-open-decisions.md) — notably which AI-provider category is evaluated first (informing assessment prioritization) and whether a formal AI-specific vendor-questionnaire supplement to [../03-security/vendor-and-third-party-risk.md](../03-security/vendor-and-third-party-risk.md) is adopted.
