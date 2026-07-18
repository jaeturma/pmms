# PMMS AI-Assisted Platform Architecture Documentation — `docs/07-ai/`

This directory contains the Phase 0.10 (AI-assisted platform architecture) documentation for the **Provincial Meet Management System (PMMS)**. It builds directly on the product vision (Phase 0.1), bounded contexts (Phase 0.2), authorization model (Phase 0.3), application/runtime architecture (Phase 0.4), data/persistence architecture (Phase 0.5), security/privacy/audit/governance architecture (Phase 0.6), quality-engineering architecture (Phase 0.7), DevOps/operations architecture (Phase 0.8), and design/UX/accessibility architecture (Phase 0.9) to define how PMMS may, under strict human authority, use artificial intelligence to assist authorized users — without ever becoming the accountable decision-maker for any high-integrity outcome.

**No AI implementation code, controller, service, job, agent, or API is contained in this directory.** No AI SDK is installed, no embeddings or vector database are created, no production prompt is deployed, no AI provider or model is selected, and no repository or production data is uploaded to any external AI service. It is AI architecture documentation only, per the Phase 0.10 working rules. Thirteen candidate AI capabilities are evaluated in detail — **none is approved for implementation by this package.**

## Purpose

Phase 0.10 exists to define, once and consistently, how AI may assist PMMS's users — before any AI capability is built ad hoc, capability by capability, without a shared gateway, risk classification, authority model, or evaluation discipline. See [phase-0.10-ai-assisted-platform-architecture.md, Section 2](phase-0.10-ai-assisted-platform-architecture.md#2-executive-summary) for the full rationale.

## Document Index

| Document | Purpose |
|---|---|
| [phase-0.10-ai-assisted-platform-architecture.md](phase-0.10-ai-assisted-platform-architecture.md) | Primary Phase 0.10 document: AI vision/principles/governance, use-case and risk classification, human-in-the-loop and authority model, platform/gateway/provider/model abstraction, prompt and structured-output architecture, RAG and knowledge governance, AI security/privacy/identity/audit, explainability/confidence, evaluation, observability/cost, vendor risk, capability-specific requirements for all 13 use cases, mobile/offline/public AI boundaries, incident/change/release governance, open decisions, acceptance/exit criteria |
| [ai-vision-principles-and-governance.md](ai-vision-principles-and-governance.md) | AI vision statement, 20 principles, governance roles and decision rights, absolute prohibited actions |
| [ai-use-case-and-risk-classification.md](ai-use-case-and-risk-classification.md) | 13 candidate use cases (UC-01–UC-13) classified by risk tier; Tier 0–3 model; prohibited autonomous actions |
| [human-in-the-loop-and-authority-model.md](human-in-the-loop-and-authority-model.md) | 10-stage human-in-the-loop lifecycle, decision ownership, separation of duties, review fatigue |
| [ai-platform-and-service-architecture.md](ai-platform-and-service-architecture.md) | 15 conceptual AI platform components and request-flow architecture |
| [ai-gateway-provider-and-model-abstraction.md](ai-gateway-provider-and-model-abstraction.md) | AI Gateway responsibilities, provider-neutral abstraction, Model Registry schema |
| [prompt-context-and-structured-output-architecture.md](prompt-context-and-structured-output-architecture.md) | 9 prompt layers, Prompt Registry, structured outputs, restricted tool-use model |
| [retrieval-knowledge-and-semantic-search-architecture.md](retrieval-knowledge-and-semantic-search-architecture.md) | 14-stage RAG lifecycle, ingestion/chunking concepts, citation requirements |
| [policy-rulebook-and-source-governance.md](policy-rulebook-and-source-governance.md) | Verified-source governance, citation standards, knowledge freshness |
| [ai-security-privacy-and-data-minimization.md](ai-security-privacy-and-data-minimization.md) | Threats, data minimization, minor-athlete protection, sensitive-document handling matrix |
| [ai-identity-authorization-scope-and-audit.md](ai-identity-authorization-scope-and-audit.md) | Dual-identity model, AI service identity, permissions/scopes, AI audit events |
| [ai-explainability-confidence-and-user-review.md](ai-explainability-confidence-and-user-review.md) | Explainability, confidence/uncertainty categories, content labeling, correction/feedback |
| [ai-evaluation-testing-and-quality-assurance.md](ai-evaluation-testing-and-quality-assurance.md) | 17 evaluation dimensions, evaluation datasets, model/prompt/retrieval evaluation |
| [ai-observability-cost-quotas-and-operations.md](ai-observability-cost-quotas-and-operations.md) | Observability metrics, cost/quota management, rate limiting, failure/fallback, disablement |
| [ai-provider-vendor-and-third-party-risk.md](ai-provider-vendor-and-third-party-risk.md) | AI-specific vendor assessment, training-use restriction, model-change risk |
| [athlete-eligibility-document-review-assistance.md](athlete-eligibility-document-review-assistance.md) | UC-01 (Tier 3) — eligibility document review assistance |
| [duplicate-athlete-detection-architecture.md](duplicate-athlete-detection-architecture.md) | UC-02 (Tier 2) — duplicate athlete detection |
| [schedule-conflict-and-tournament-recommendation-architecture.md](schedule-conflict-and-tournament-recommendation-architecture.md) | UC-03, UC-04 (Tier 2) — schedule conflict detection, tournament scheduling recommendations |
| [narrative-summary-and-natural-language-reporting.md](narrative-summary-and-natural-language-reporting.md) | UC-05, UC-11 (Tier 1–2) — narrative summaries, natural-language report generation |
| [incident-classification-and-operational-assistance.md](incident-classification-and-operational-assistance.md) | UC-06 (Tier 2) — incident classification |
| [medal-performance-and-result-anomaly-analytics.md](medal-performance-and-result-anomaly-analytics.md) | UC-07, UC-08 (Tier 1–2 / Tier 3) — medal/performance analytics, result anomaly detection |
| [helpdesk-and-committee-knowledge-assistants.md](helpdesk-and-committee-knowledge-assistants.md) | UC-09, UC-10, UC-12 (Tier 1) — helpdesk, committee knowledge, policy/rulebook search |
| [venue-and-schedule-risk-prediction.md](venue-and-schedule-risk-prediction.md) | UC-13 (Tier 3) — venue and schedule risk prediction |
| [mobile-offline-and-public-ai-boundaries.md](mobile-offline-and-public-ai-boundaries.md) | Offline AI boundaries, mobile AI boundaries, public AI restrictions |
| [ai-incident-response-change-and-release-governance.md](ai-incident-response-change-and-release-governance.md) | AI incident response, change management, 18-item release-gate list |
| [ai-open-decisions.md](ai-open-decisions.md) | 21 unresolved AI decisions (AX-01–AX-21), cross-referenced against Phase 0.1–0.9 open decisions |

## Reading Order

1. [phase-0.10-ai-assisted-platform-architecture.md](phase-0.10-ai-assisted-platform-architecture.md) — read first; establishes vision and cross-references every supporting document.
2. [ai-vision-principles-and-governance.md](ai-vision-principles-and-governance.md), [ai-use-case-and-risk-classification.md](ai-use-case-and-risk-classification.md), [human-in-the-loop-and-authority-model.md](human-in-the-loop-and-authority-model.md) — why AI is governed this way, and by whom.
3. [ai-platform-and-service-architecture.md](ai-platform-and-service-architecture.md), [ai-gateway-provider-and-model-abstraction.md](ai-gateway-provider-and-model-abstraction.md), [prompt-context-and-structured-output-architecture.md](prompt-context-and-structured-output-architecture.md) — the platform skeleton every capability routes through.
4. [retrieval-knowledge-and-semantic-search-architecture.md](retrieval-knowledge-and-semantic-search-architecture.md), [policy-rulebook-and-source-governance.md](policy-rulebook-and-source-governance.md) — grounded knowledge and citation discipline.
5. [ai-security-privacy-and-data-minimization.md](ai-security-privacy-and-data-minimization.md), [ai-identity-authorization-scope-and-audit.md](ai-identity-authorization-scope-and-audit.md) — data protection and identity boundaries.
6. [ai-explainability-confidence-and-user-review.md](ai-explainability-confidence-and-user-review.md), [ai-evaluation-testing-and-quality-assurance.md](ai-evaluation-testing-and-quality-assurance.md), [ai-observability-cost-quotas-and-operations.md](ai-observability-cost-quotas-and-operations.md), [ai-provider-vendor-and-third-party-risk.md](ai-provider-vendor-and-third-party-risk.md) — trust, quality, operations, and vendor governance.
7. The eight capability-specific documents ([athlete-eligibility-document-review-assistance.md](athlete-eligibility-document-review-assistance.md) through [venue-and-schedule-risk-prediction.md](venue-and-schedule-risk-prediction.md)) — the 13 candidate use cases in detail.
8. [mobile-offline-and-public-ai-boundaries.md](mobile-offline-and-public-ai-boundaries.md), [ai-incident-response-change-and-release-governance.md](ai-incident-response-change-and-release-governance.md) — surface-specific boundaries and operational governance.
9. [ai-open-decisions.md](ai-open-decisions.md) — read last; everything still unresolved.

## Status Legend

| Status | Meaning |
|---|---|
| Draft Complete — Pending AI, Security, Privacy, Domain, Sports, Quality, and Engineering Validation | Phase 0.10 status: content complete, no formal sign-off yet |
| Validated | Reviewed and confirmed by identified stakeholders (not yet reached for any document) |
| Superseded | Replaced by a later-phase document (not yet applicable) |

## Ownership and Review Expectations

A document owner (AI governance owner) and reviewer set (Security owner, Privacy owner, Domain reviewers, Sports reviewers, Quality owner, Evaluation lead, DepEd Leadership) are to be identified — see [../00-product/stakeholder-register.md](../00-product/stakeholder-register.md). Until formal review occurs, this documentation should be treated as a structured, defensible proposal built from the approved Phase 0.1–0.9 foundation, not as an approved specification, and **not as approval for any specific AI capability to be implemented.**

## Relationship to Phase 0.1 Through 0.9

This directory preserves, and never redefines: Phase 0.1's product vision and open decisions, Phase 0.2's bounded-context ownership, Phase 0.3's roles/scopes/assignments/authorization boundaries, Phase 0.4's application/runtime boundaries, Phase 0.5's data classification/history/publication-state rules, Phase 0.6's security/privacy/audit/minor-athlete/sensitive-data controls (this package's Section 5 sensitive-document matrix directly extends Phase 0.6's classification model), Phase 0.7's quality/evaluation discipline, Phase 0.8's operational/deployment/incident-response discipline, and Phase 0.9's AI-assisted experience UX patterns (this package supplies the governance those patterns assume). Every document in this directory adds AI-specific governance around those foundations — none of them is altered. **AI never approves, certifies, publishes, revokes, resolves, merges, deletes, or alters any high-integrity record**, restated absolutely throughout this package.

## Relationship to Phase 0.11

**Phase 0.11 — Event-Driven Workflows, Notifications, Messaging, and Responsible Automation Architecture is now complete** — see [../08-workflows/README.md](../08-workflows/README.md), superseding this section's earlier expectation that Phase 0.11 would not yet be started. It consumed this directory's human-in-the-loop lifecycle and AI authority model as the binding constraint on every AI-assisted workflow touchpoint — see [../08-workflows/offline-mobile-device-public-and-ai-workflow-boundaries.md, Section 5](../08-workflows/offline-mobile-device-public-and-ai-workflow-boundaries.md#5-ai-workflow-boundaries) and [../08-workflows/responsible-automation-and-authority-boundaries.md, Section 5](../08-workflows/responsible-automation-and-authority-boundaries.md#5-ai-assisted-automation-boundaries). No AI governance rule defined in this directory was altered by Phase 0.11's work — no AI capability gained any new authority through workflow or automation architecture.

## Relationship to Phase 0.12

**Phase 0.12 — Performance, Scalability, Multi-Tenancy, Disaster Recovery, and Enterprise Readiness Architecture is now complete** — see [../09-enterprise/README.md](../09-enterprise/README.md). It extended this directory's intersection-not-union AI access model with a tenant dimension — see [../09-enterprise/tenant-aware-runtime-workflow-event-and-ai-boundaries.md, Section 11](../09-enterprise/tenant-aware-runtime-workflow-event-and-ai-boundaries.md#11-tenant-aware-ai). No AI governance rule defined in this directory was altered by Phase 0.12's work — no AI capability remains approved for implementation, and none gained any new authority through multi-tenancy or scaling architecture.

## Relationship to Phase 0.13

**Phase 0.13 — Architecture Validation, Gap Analysis, Technical Debt, and Final Architecture Review is now complete** — see [../10-review/README.md](../10-review/README.md), superseding this section's earlier expectation. It confirmed the AI advisory-only principle as "the single most consistently and absolutely restated rule in the entire 12-phase corpus," with zero exception or weakening found anywhere — see [../10-review/ai-governance-and-decision-support-review.md](../10-review/ai-governance-and-decision-support-review.md). All 13 candidate capabilities are confirmed Priority 5 (correctly deferred), blocked externally on zero verified policy sources rather than any internal architectural weakness. No AI governance rule defined in this directory was altered by Phase 0.13's work.

## Relationship to Phase 0.14

The next phase is not yet started, per working rule 8 of Phase 0.13. See [../10-review/phase-0.13-architecture-validation-gap-analysis-final-review.md, Section 47](../10-review/phase-0.13-architecture-validation-gap-analysis-final-review.md#47-next-phase).

## Update Rules

1. Resolving an item in [ai-open-decisions.md](ai-open-decisions.md) (especially **AX-01** first-pilot selection or **AX-02** provider evaluation) should update its `Status` field and, where it changes a rule, be reflected back into the relevant document.
2. A capability moving from "candidate" to "approved for pilot" status updates its row in [ai-use-case-and-risk-classification.md, Section 1](ai-use-case-and-risk-classification.md#1-use-case-classification) and its own capability-specific document's Authority Table `Feature-flag state` field — never silently, and never without passing the full release-gate list in [ai-incident-response-change-and-release-governance.md, Section 3](ai-incident-response-change-and-release-governance.md#3-ai-release-gates).
3. Any change to a sensitive-data AI-access rule (Section 5 of [ai-security-privacy-and-data-minimization.md](ai-security-privacy-and-data-minimization.md)) must be cross-checked against [../02-data/information-classification-and-privacy.md](../02-data/information-classification-and-privacy.md) first, never diverging from it.
4. Keep `.ai/project-context.md`, `.ai/architecture.md`, `.ai/ai-rules.md`, `.ai/ai-security-rules.md`, `.ai/ai-prompt-rules.md`, `.ai/ai-retrieval-rules.md`, `.ai/ai-evaluation-rules.md`, and `.ai/ai-ux-rules.md` (see [../../.ai/](../../.ai/)) in sync with material changes to this directory, since those files are the primary context AI-assisted development tools use for this project.
