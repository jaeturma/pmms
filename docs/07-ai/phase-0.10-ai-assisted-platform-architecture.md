# PMMS Phase 0.10 — AI-Assisted Platform Architecture

## 1. Document Control

| Field | Value |
|---|---|
| Document title | PMMS Phase 0.10 — AI-Assisted Platform Architecture |
| Project | Provincial Meet Management System (PMMS) |
| Phase | Phase 0.10 — AI-Assisted Platform Architecture |
| Version | 0.10.0 |
| Status | Draft Complete — Pending AI, Security, Privacy, Domain, Sports, Quality, and Engineering Validation |
| Date | 2026-07-14 |
| Intended audience | AI governance owner, security and privacy reviewers, domain and sports reviewers, quality and evaluation leads, Laravel/React/Flutter engineers, committee representatives, DepEd Leadership |
| Document owner | To be identified (AI governance owner) |
| Review roles | To be identified — AI governance owner, Security owner, Privacy owner, Domain reviewers, Sports reviewers, Quality owner, Evaluation lead, DepEd Leadership |
| Related documents | All 24 supporting documents in this directory (see [README.md](README.md)); [../00-product/](../00-product/) through [../06-design/](../06-design/); [../../.ai/decisions/ADR-0010-ai-assisted-platform-architecture.md](../../.ai/decisions/ADR-0010-ai-assisted-platform-architecture.md) |

### Change History

| Version | Date | Description |
|---|---|---|
| 0.10.0 | 2026-07-14 | Initial Phase 0.10 draft: AI vision/principles/governance, use-case and risk classification, human-in-the-loop and authority model, AI platform/gateway/provider/model abstraction, prompt and structured-output architecture, retrieval-augmented generation and knowledge governance, AI security/privacy/data-minimization, AI identity/authorization/audit, explainability/confidence/user review, AI evaluation and quality assurance, AI observability/cost/quotas, AI provider and vendor risk, thirteen capability-specific architecture requirements, mobile/offline/public AI boundaries, AI incident response/change/release governance, and open decisions — built from the approved Phase 0.1–0.9 foundation with no AI implementation, provider selection, or production data sharing performed. |

---

## 2. Executive Summary

Phase 0.9 defined how a human experiences PMMS. Phase 0.10 defines how PMMS may, in the future and only under strict human authority, use artificial intelligence to make that experience faster and more informative — without ever becoming the accountable decision-maker for any high-integrity outcome.

**Why PMMS needs an AI architecture before any AI capability exists.** Introducing AI ad hoc, capability by capability, without a shared gateway, risk classification, authority model, and evaluation discipline would repeat the same fragmentation this project's every prior centralization effort (bounded contexts, authorization, data classification, security controls, quality gates, deployment discipline, design system) exists to prevent — now with the added danger that an ungoverned AI feature can fabricate information, leak sensitive data, or quietly narrow whose judgment actually decides an outcome.

**Why human accountability must remain absolute.** Restated from working rules 15–16 across every document in this package: AI may draft, classify, summarize, detect, compare, and recommend; **AI never approves, certifies, publishes, revokes, resolves, merges, deletes, or alters any high-integrity record.** Every one of the thirteen candidate capabilities evaluated in this package is bound by this line without exception.

**Why this phase evaluates candidate capabilities rather than approving them.** Thirteen candidate AI capabilities are examined in detail — each with its potential assistance, absolute prohibitions, and authority boundaries — but **none is approved for implementation by this document.** No provider is selected, no model is chosen, no prompt is deployed to production, and no embedding or vector database is created.

**Why the AI Gateway pattern is central.** A single controlled entry point (per [ai-platform-and-service-architecture.md](ai-platform-and-service-architecture.md) and [ai-gateway-provider-and-model-abstraction.md](ai-gateway-provider-and-model-abstraction.md)) — not scattered per-feature integrations — is the only way to consistently enforce authorization, data minimization, audit, evaluation, and fallback across every present and future AI capability.

**Why retrieval must be grounded and citation-based rather than generative-only.** Restated from [retrieval-knowledge-and-semantic-search-architecture.md](retrieval-knowledge-and-semantic-search-architecture.md) and [policy-rulebook-and-source-governance.md](policy-rulebook-and-source-governance.md) — an AI answer about DepEd policy or sports rules is only as trustworthy as its source, and PMMS never treats an unverified document or generated content as official policy.

**Why sensitive data requires enhanced, not default, AI access.** Restated absolutely from [ai-security-privacy-and-data-minimization.md](ai-security-privacy-and-data-minimization.md) — minor-athlete data, medical data, authentication data, and financial data each carry their own AI-access rule, several of them absolute exclusions, none of them a default "AI can see everything a human role can see."

**Why this phase deliberately leaves quality thresholds and provider selection undefined.** Per this phase's own governing instruction: no numeric evaluation threshold, no AI SDK, no provider, and no model is selected here — those decisions require real evaluation data this documentation-only phase cannot fabricate without inventing evidence.

---

## 3. AI Vision

> PMMS uses AI to reduce administrative burden, improve information discovery, identify possible risks and inconsistencies, assist authorized users with analysis and drafting, and support better operational decisions without replacing accountable human officials or established rules.

Full detail: [ai-vision-principles-and-governance.md, Section 1](ai-vision-principles-and-governance.md#1-vision).

## 4. AI Principles

Twenty principles restating and extending the platform-wide guarantees of Phases 0.1–0.9 into AI-specific form — human accountability, deterministic-first for authoritative validation, grounded and cited retrieval, data minimization, classification-aware access, explicit labeling, reviewability, and graceful degradation to non-AI workflows. Full detail: [ai-vision-principles-and-governance.md, Section 2](ai-vision-principles-and-governance.md#2-principles).

## 5. AI Capability Categories

Six categories: Knowledge Assistance, Document Assistance, Detection and Classification, Recommendations, Generative Assistance, Analytics and Prediction — every one of the thirteen candidate use cases maps to exactly one primary category. Full detail: [ai-use-case-and-risk-classification.md, Section 1](ai-use-case-and-risk-classification.md#1-use-case-classification).

## 6. AI Risk Classification

Four-tier model — Tier 0 (No AI, deterministic only: score calculation, permission evaluation, result certification, medal-tally calculation, credential validity, access-control enforcement), Tier 1 (Low-Risk Assistance), Tier 2 (Moderate-Risk Recommendation), Tier 3 (High-Risk Decision Support) — plus an absolute, no-tier **Prohibited Autonomous Actions** category outside the entire tier system. Full detail: [ai-use-case-and-risk-classification.md, Section 2](ai-use-case-and-risk-classification.md#2-risk-tiers).

## 7. Human-in-the-Loop Model

```text
User Request → Authorization Check → Data Minimization → AI Processing →
Grounded Output → Confidence and Limitation Display → Human Review →
Accept, Reject, Edit, or Escalate → Authorized Application Action → Audit
```

**AI outputs never directly update authoritative state** — restated absolutely. Full detail: [human-in-the-loop-and-authority-model.md, Section 1](human-in-the-loop-and-authority-model.md#1-human-in-the-loop-lifecycle).

## 8. AI Authority Model

Decision ownership, acceptance/editing/rejection/escalation dispositions, separation of duties for AI-assisted actions, and accountability — an AI suggestion never collapses the distinction between the person who requested it and the person authorized to act on it. Full detail: [human-in-the-loop-and-authority-model.md, Sections 2–6](human-in-the-loop-and-authority-model.md#2-stage-detail).

## 9. AI Platform Architecture

Fifteen conceptual components (AI Gateway, AI Request Policy Engine, Context and Authorization Resolver, Data Minimization Layer, Prompt Registry, Knowledge Retrieval Service, Model Provider Adapter, Structured Output Validator, Safety and Policy Filter, Citation and Provenance Service, Human Review Workflow, AI Audit Service, Evaluation Service, Cost and Quota Service, AI Feature Flag Service, AI Observability) and their request-flow relationship. Full detail: [ai-platform-and-service-architecture.md, Sections 1–2](ai-platform-and-service-architecture.md#1-conceptual-components).

## 10. AI Gateway

The single controlled entry point for every AI capability — authentication, authorization, scope validation, request classification, data minimization, provider/prompt/model selection, rate limiting, quota enforcement, correlation, response validation, audit, and fallback. Full detail: [ai-gateway-provider-and-model-abstraction.md, Section 1](ai-gateway-provider-and-model-abstraction.md#1-ai-gateway).

## 11. Provider and Model Abstraction

A provider-neutral interface across nine dimensions (model capability metadata, hosted/local support, cost, latency, data-retention terms, residency, approved-use status). **No provider is selected in this document.** Full detail: [ai-gateway-provider-and-model-abstraction.md, Section 2](ai-gateway-provider-and-model-abstraction.md#2-provider-and-model-abstraction).

## 12. Model Registry

A fourteen-field schema tracking every candidate model's identity, version, provider, approved use cases, risk-tier eligibility, cost class, and retirement status — currently empty, pending AX-21. Full detail: [ai-gateway-provider-and-model-abstraction.md, Section 3](ai-gateway-provider-and-model-abstraction.md#3-model-registry).

## 13. Prompt Architecture

Nine prompt layers (system instruction, capability instruction, domain rule, user context, authorization context, retrieved evidence, user question, required output schema, safety constraints) governed by a versioned, reviewed Prompt Registry. Full detail: [prompt-context-and-structured-output-architecture.md, Sections 1–3](prompt-context-and-structured-output-architecture.md#1-prompt-layers).

## 14. Structured Output

Mandatory structured-output fields (result type, summary, evidence, source references, confidence/uncertainty, limitations, recommended human action, model version, prompt version, generated time) for every AI response feeding a review workflow. Full detail: [prompt-context-and-structured-output-architecture.md, Section 5](prompt-context-and-structured-output-architecture.md#5-structured-outputs).

## 15. Tool Use and Agents

AI tools may search, query, and retrieve within authorized scope, and may draft content; AI tools must never directly write authoritative records, approve, certify, alter scores, resolve protests, modify permissions, revoke credentials, or run database commands. Multi-agent autonomous workflows are avoided for initial implementation, restated absolutely. Full detail: [prompt-context-and-structured-output-architecture.md, Sections 6–7](prompt-context-and-structured-output-architecture.md#6-tool-use-and-agents).

## 16. Retrieval-Augmented Generation

```text
Source Approval → Ingestion → Classification → Parsing → Chunking → Metadata →
Indexing → Retrieval Authorization → Query → Reranking → Context Assembly →
Generation → Citation → Human Review
```

Full detail: [retrieval-knowledge-and-semantic-search-architecture.md, Section 1](retrieval-knowledge-and-semantic-search-architecture.md#1-rag-lifecycle).

## 17. Knowledge Sources

Candidate source categories (DepEd issuances, official sports rulebooks, approved meet guidelines, committee procedures) each requiring documented provenance, verification status, version, and effectivity before use — cross-referenced against [../03-security/policy-source-registry.md](../03-security/policy-source-registry.md)'s POL-01–13, currently all unverified. Full detail: [policy-rulebook-and-source-governance.md, Section 1](policy-rulebook-and-source-governance.md#1-verified-sources).

## 18. Policy and Rulebook Search

Citation standards, conflicting-source surfacing (the system exposes a conflict rather than silently choosing), and the absolute exclusion of unverified sources from anything presented as official policy. Full detail: [policy-rulebook-and-source-governance.md, Sections 2, 4](policy-rulebook-and-source-governance.md#2-citation-standards).

## 19. Knowledge Freshness

A nine-element freshness model (source version, effectivity date, last-verified date, superseded notice, staleness indicator) applied to every knowledge source used in retrieval. Full detail: [policy-rulebook-and-source-governance.md, Section 3](policy-rulebook-and-source-governance.md#3-knowledge-freshness).

## 20. AI Data Access

AI data access is the **intersection**, never the union, of the requesting user's authorization and the AI service identity's own restricted scope — an AI output never reveals data the requesting user could not access directly. Full detail: [ai-identity-authorization-scope-and-audit.md, Sections 1, 3](ai-identity-authorization-scope-and-audit.md#1-requesting-user-and-service-identity).

## 21. Sensitive-Data Restrictions

Seven categories with individual AI-access rules: eligibility evidence, medical data (excluded from general AI use by default), guardian data, financial data, security-audit records, and authentication data (**never sent to any AI service, under any circumstance**) — restated absolutely. Full detail: [ai-security-privacy-and-data-minimization.md, Section 5](ai-security-privacy-and-data-minimization.md#5-sensitive-document-handling).

## 22. AI Service Identity

A non-human execution identity distinct from the requesting user — no human role, no DBA access, no unrestricted object storage, environment-separated, rotated, revocable, audited, and owned by a named role. Full detail: [ai-identity-authorization-scope-and-audit.md, Section 2](ai-identity-authorization-scope-and-audit.md#2-ai-service-identity).

## 23. Explainability

Seven required explanation elements for any AI output feeding a human decision — **no invented mathematical confidence is displayed.** Full detail: [ai-explainability-confidence-and-user-review.md, Section 1](ai-explainability-confidence-and-user-review.md#1-explainability).

## 24. Confidence and Uncertainty

Five categories (Strong, Moderate, Limited, Insufficient, Conflicting evidence) — **AI must be able to decline when evidence is insufficient**, restated absolutely. Full detail: [ai-explainability-confidence-and-user-review.md, Section 2](ai-explainability-confidence-and-user-review.md#2-confidence-and-uncertainty).

## 25. AI User Experience

Builds directly on [../06-design/ai-assisted-experience-architecture.md](../06-design/ai-assisted-experience-architecture.md)'s eight allowed AI-assisted interface patterns and absolute consequential-action restrictions — this phase does not restate Phase 0.9's UX architecture, only its AI-governance implications. Full detail: [ai-explainability-confidence-and-user-review.md, Section 3](ai-explainability-confidence-and-user-review.md#3-ai-user-experience-cross-reference).

## 26. AI-Generated Content Labeling

Content may be labeled draft, assisted, suggested, detected, summary, or recommendation; content must never be labeled official, certified, approved, or confirmed unless a human has actually taken that authorized action. Full detail: [ai-explainability-confidence-and-user-review.md, Section 4](ai-explainability-confidence-and-user-review.md#4-ai-generated-content-labeling).

## 27. AI Audit Events

Extends [../03-security/audit-and-security-event-architecture.md, Section 7 "AI-Assistance Auditing"](../03-security/audit-and-security-event-architecture.md#7-ai-assistance-auditing) with the full request/response/review/disposition lifecycle for every AI interaction. Full detail: [ai-identity-authorization-scope-and-audit.md, Section 8](ai-identity-authorization-scope-and-audit.md#8-ai-audit-events).

## 28. AI Evaluation Strategy

Seventeen evaluation dimensions spanning quality, safety, model, prompt, retrieval, and recommendation evaluation — **every threshold is a quality gate to be defined, no numeric value is invented in this phase.** Full detail: [ai-evaluation-testing-and-quality-assurance.md, Section 1](ai-evaluation-testing-and-quality-assurance.md#1-evaluation-dimensions).

## 29. Evaluation Datasets

Synthetic and de-identified datasets only, spanning ten evaluation categories matching the thirteen use cases, each golden dataset citing its approved expected results. Full detail: [ai-evaluation-testing-and-quality-assurance.md, Section 2](ai-evaluation-testing-and-quality-assurance.md#2-evaluation-datasets).

## 30. AI Release Gates

An eighteen-item gate list (approved use case, risk tier, data-owner/security/privacy/prompt review, knowledge-source verification, evaluation dataset, quality threshold, failure-mode/authorization/prompt-injection/hallucination testing, cost review, observability, feature flag, rollback plan, user guidance, human-review workflow) — **no AI capability bypasses these gates, regardless of its risk tier.** Full detail: [ai-incident-response-change-and-release-governance.md, Section 3](ai-incident-response-change-and-release-governance.md#3-ai-release-gates).

## 31. AI Observability

Eighteen tracked metrics (requests, latency, error rate, fallback rate, confidence distribution, human-override rate, cost, and more) feeding the same observability discipline established in Phase 0.8. Full detail: [ai-observability-cost-quotas-and-operations.md, Section 1](ai-observability-cost-quotas-and-operations.md#1-ai-observability).

## 32. Cost and Quota Management

Per-user/organization/meet quotas, capability budgets, model cost classes, caching-where-safe, and expensive-model approval — **cost controls never expose one tenant's usage to another.** Full detail: [ai-observability-cost-quotas-and-operations.md, Section 2](ai-observability-cost-quotas-and-operations.md#2-cost-and-quota-management).

## 33. Failure and Fallback

Every AI failure mode degrades gracefully to an existing non-AI workflow (retry → alternate model → deterministic logic → keyword search → manual workflow → source-documents-only → disable capability) — restated absolutely. AI disablement via feature flag is the primary, fastest incident-response mechanism. Full detail: [ai-observability-cost-quotas-and-operations.md, Sections 4–5](ai-observability-cost-quotas-and-operations.md#4-failure-and-fallback).

## 34. Offline and Mobile AI

No final consequential AI decision occurs offline; mobile AI requests pass through the same AI Gateway path as web requests, never a separate less-controlled path. Full detail: [mobile-offline-and-public-ai-boundaries.md, Sections 1–2](mobile-offline-and-public-ai-boundaries.md#1-offline-ai-boundaries).

## 35. Public AI

Public AI uses only Public-tier data and knowledge, with architectural (not prompt-based) defense against scope escalation — the Context and Authorization Resolver determines scope before any data is retrieved into a request's context. Full detail: [mobile-offline-and-public-ai-boundaries.md, Sections 3–4](mobile-offline-and-public-ai-boundaries.md#3-public-ai).

---

## 36. Capability-Specific Architecture Requirements

Thirteen candidate AI capabilities are evaluated below, grouped into eight capability documents. **None is approved for implementation by this phase.**

### 36.1 Athlete Eligibility Document Review Assistance (UC-01, Tier 3)

An eight-stage workflow (`Authorized Reviewer Selects Case → Authorized Documents Retrieved → Documents Classified → Approved Checklist and Rule Version Loaded → AI Extracts and Compares → Findings Generated → Source and Confidence Displayed → Reviewer Accepts, Edits, Rejects, or Requests Clarification → Official Decision Uses Normal Eligibility Workflow`) — AI extracts and compares only; the official eligibility decision always uses the existing, unmodified eligibility workflow. Full detail: [athlete-eligibility-document-review-assistance.md](athlete-eligibility-document-review-assistance.md).

### 36.2 Duplicate Athlete Detection (UC-02, Tier 2)

Candidate-generation and matching assistance only — merge and unmerge decisions remain entirely a human authority, consistent with Phase 0.5's alias/never-delete persistence model. Naming-variation and bias risk specific to Philippine naming conventions requires dedicated evaluation (AX-11). Full detail: [duplicate-athlete-detection-architecture.md](duplicate-athlete-detection-architecture.md).

### 36.3 Schedule Conflict Detection and Tournament Scheduling Recommendations (UC-03, UC-04, Tier 2)

Conflict detection is deterministic-first by design; AI assists only where deterministic rules are insufficient. Scheduling recommendations always require review and approval — **AI must not silently modify official schedules.** Full detail: [schedule-conflict-and-tournament-recommendation-architecture.md](schedule-conflict-and-tournament-recommendation-architecture.md).

### 36.4 Automatic Narrative Event Summaries and Natural-Language Report Generation (UC-05, UC-11, Tier 1–2)

Six summary categories (Public daily, Internal operational, Committee, Sport, Executive, Post-event), each with its own classification and data restriction. Natural-language report generation follows a defined lifecycle (`Natural-Language Request → Authorization and Scope Resolution → Approved Report Intent → Query Plan or Read-Model Selection → Data Retrieval → Privacy Filter → Summary or Visualization Recommendation → User Review → Export Through Normal Workflow`) — **AI must not generate arbitrary SQL or access unapproved tables.** Full detail: [narrative-summary-and-natural-language-reporting.md](narrative-summary-and-natural-language-reporting.md).

### 36.5 Incident Classification (UC-06, Tier 2)

Twelve candidate incident categories with a defined six-field AI output. Medical and security incidents have an absolute boundary — AI classification never substitutes for clinical judgment or Security Coordinator authority. Full detail: [incident-classification-and-operational-assistance.md](incident-classification-and-operational-assistance.md).

### 36.6 Medal and Performance Analytics and Result Anomaly Detection (UC-07, UC-08, Tier 1–2 / Tier 3)

Data-state discipline prevents blending raw, validated, certified, and provisional results. Anomaly detection creates a review alert only, never alters data — **"anomaly is not accusation"** is restated absolutely, with deliberately neutral output language. Full detail: [medal-performance-and-result-anomaly-analytics.md](medal-performance-and-result-anomaly-analytics.md).

### 36.7 Helpdesk Assistant, Committee Knowledge Assistant, and Policy/Rulebook Search (UC-09, UC-10, UC-12, Tier 1)

PMMS's three knowledge-assistance capabilities, all built on the RAG lifecycle (Section 16). Answers cite approved knowledge sources; retrieval remains committee- and role-aware; **no fabricated citation.** Full detail: [helpdesk-and-committee-knowledge-assistants.md](helpdesk-and-committee-knowledge-assistants.md).

### 36.8 Venue and Schedule Risk Prediction (UC-13, Tier 3)

The most speculative capability in this package — risk predictions are explainable and advisory only; **predictive risk is never a sole basis for exclusion or disciplinary action**, restated absolutely. Feeds the existing meet-day readiness checklist rather than replacing it. Full detail: [venue-and-schedule-risk-prediction.md](venue-and-schedule-risk-prediction.md).

---

## 37. AI Provider and Vendor Risk

Extends Phase 0.6's general vendor-risk framework with AI-specific assessment areas, most notably the **training-use restriction** — a provider must not use PMMS data to train its own models. **No AI provider is currently approved.** Full detail: [ai-provider-vendor-and-third-party-risk.md](ai-provider-vendor-and-third-party-risk.md).

## 38. AI Incident Response, Change, and Release Governance

AI-specific incident categories (hallucinated rule presented as authoritative, privacy leakage, prompt-injection success, unsafe content, authorization bypass, provider outage, bias finding, runaway cost), AI-specific change management (every change re-runs golden-dataset evaluation), and the eighteen-item release-gate list from Section 30. Full detail: [ai-incident-response-change-and-release-governance.md](ai-incident-response-change-and-release-governance.md).

---

## 39. Risks, Assumptions, Tradeoffs, and Alternatives Considered

### Key Risks
- **No AI provider is approved and no provider-evaluation process yet exists** ([AX-02](ai-open-decisions.md#ax-02--ai-provider-evaluation-process-design)) — blocking every downstream implementation decision in this package.
- **Quality thresholds are entirely undefined** ([AX-03](ai-open-decisions.md#ax-03--quality-threshold-values-per-evaluation-dimension)) — deliberately, per this phase's own governing instruction, but this leaves every release gate's numeric bar unset until real evaluation data exists.
- **Duplicate-detection bias risk for Philippine naming conventions is unevaluated** ([AX-07](ai-open-decisions.md#ax-07--bias-evaluation-process-design), [AX-11](ai-open-decisions.md#ax-11--matching-algorithm-evaluation-for-philippine-naming-conventions)) — a real fairness risk that requires specialist input beyond this documentation phase's scope.
- **Medical-incident classification remains blocked on the unresolved Phase 0.1 OD-15** ([AX-12](ai-open-decisions.md#ax-12--medical-incident-classification-exclusion-cross-reference)).
- **Mobile AI rollout is blocked on Phase 0.9's unresolved DX-18** ([AX-17](ai-open-decisions.md#ax-17--mobile-ai-rollout-sequencing-cross-reference)) — `mobile/` does not yet exist in this repository.

### Key Assumptions
- The Phase 0.1–0.9 foundation (bounded contexts, authorization, data classification, security controls, quality gates, deployment discipline, design system) remains stable enough to anchor an AI architecture without near-term restructuring.
- A controlled pilot (per Phase 0.7) occurs before any AI capability reaches general availability, providing the first genuine evaluation data multiple sections of this package depend on.
- No AI capability is assumed to launch simultaneously with this phase's completion — every capability requires its own separate approval, per Section 36's "none is approved" discipline.

### Key Tradeoffs
- **Grounded retrieval (RAG) over pure generative response** (Section 16) trades implementation complexity for citation-backed trustworthiness — assessed as necessary given PMMS's policy/rulebook use cases, where an ungrounded answer is actively dangerous.
- **A single AI Gateway over per-feature AI integrations** (Section 10) trades initial engineering effort for consistent, centrally-enforced authorization, audit, and fallback across every current and future capability.
- **Deferring vector-database adoption** ([AX-04](ai-open-decisions.md#ax-04--vector-database-adoption)) trades semantic-search recall for avoiding a significant infrastructure commitment before keyword search is demonstrated insufficient.
- **Excluding medical data from general AI use by default** (Section 21) trades potential clinical-assistance value for the stronger privacy guarantee this platform's medical domain requires.

### Alternatives Considered
1. **Approve and implement the first AI capability directly within this phase.** Rejected — directly violates this phase's own working rules; no provider, model, or production prompt may be selected or deployed here.
2. **Allow each of the thirteen candidate capabilities to define its own ad hoc AI integration.** Rejected — would repeat exactly the fragmentation risk the AI Gateway pattern exists to prevent, with no consistent authorization, audit, or evaluation discipline.
3. **Grant the AI service identity the same access as the human role it assists.** Rejected — directly violates the intersection-not-union access model (Section 20); an AI service identity is always more restricted than any human role it serves.
4. **Invent placeholder numeric quality thresholds now to give evaluation work a concrete target.** Rejected — a fabricated threshold is worse than an explicitly undefined one; it would misrepresent unvalidated confidence as an evaluated standard.
5. **Treat anomaly detection findings as evidence of wrongdoing pending human review.** Rejected — directly violates working rules 32–33; anomaly detection produces a review alert only, never a factual accusation.

## 40. Recommended Direction

> Build the AI Gateway, human-in-the-loop authority model, and grounded-retrieval knowledge architecture as the shared foundation every future AI capability must pass through — evaluating and piloting the lowest-risk Tier 1 knowledge-assistance capabilities first, deferring every Tier 3 capability until real evaluation data and at least one pilot meet cycle exist to ground its predictions, and treating "no capability is approved without passing every release gate" as a non-negotiable platform guarantee rather than a checklist to be relaxed under delivery pressure.

## 41. Phase 0.10 Deliverables

- 24 supporting documents plus this main document in `docs/07-ai/` (see [README.md](README.md)).
- Updates to [../01-architecture/README.md](../01-architecture/README.md), [../02-data/README.md](../02-data/README.md), [../03-security/README.md](../03-security/README.md), [../04-quality/README.md](../04-quality/README.md), [../05-devops/README.md](../05-devops/README.md), and [../06-design/README.md](../06-design/README.md) referencing this package.
- Updates to `.ai/project-context.md`, `.ai/current-phase.md`, `.ai/architecture.md`.
- New `.ai/ai-rules.md`, `.ai/ai-security-rules.md`, `.ai/ai-prompt-rules.md`, `.ai/ai-retrieval-rules.md`, `.ai/ai-evaluation-rules.md`, `.ai/ai-ux-rules.md`.
- New `.ai/decisions/ADR-0010-ai-assisted-platform-architecture.md`.

## 42. Phase 0.10 Acceptance Criteria

- [x] AI vision, principles, and governance model documented.
- [x] Thirteen candidate AI capabilities classified by risk tier — none approved for implementation.
- [x] Human-in-the-loop lifecycle and AI authority model documented — AI outputs never directly update authoritative state.
- [x] AI platform architecture, AI Gateway, and provider/model abstraction documented — no provider or model selected.
- [x] Model Registry schema documented — currently empty.
- [x] Prompt architecture, prompt registry, structured output, and restricted tool-use model documented.
- [x] Retrieval-augmented generation lifecycle, knowledge-source governance, and policy/rulebook search documented — no fabricated citation permitted.
- [x] AI security, privacy, and data-minimization requirements documented, including the sensitive-document handling matrix.
- [x] AI identity, authorization, scope, and audit model documented — intersection-not-union access enforced.
- [x] Explainability, confidence/uncertainty, and AI-generated content labeling documented.
- [x] AI evaluation, quality assurance, and evaluation-dataset strategy documented — no numeric threshold invented.
- [x] AI observability, cost/quota management, and failure/fallback behavior documented.
- [x] AI provider and vendor risk assessment extended — no provider currently approved.
- [x] Thirteen capability-specific architecture requirements documented across eight capability documents.
- [x] Mobile, offline, and public AI boundaries documented.
- [x] AI incident response, change management, and release-gate discipline documented.
- [x] Open decisions recorded (21 items, cross-referenced against all prior phases).
- [x] AI workspace updated with six new `.ai/ai-*.md` rule files and ADR-0010.
- [x] No AI implementation code, controller, service, job, agent, or API created.
- [x] No AI SDK installed; no embeddings or vector database created; no production prompt deployed; no AI provider selected.
- [x] No repository or production data uploaded to any external AI service.
- [x] Documents internally consistent (cross-reference verified — see completion report).

## 43. Preparation Requirements for Phase 0.11

Phase 0.11 (the next phase — scope to be defined by its own prompt) can proceed once it has:

- This package's AI Gateway, risk-tier classification, and human-in-the-loop authority model as the binding reference for any future AI-related implementation decision.
- Every prior phase's `.ai/` rule files plus this phase's new `.ai/ai-rules.md`, `.ai/ai-security-rules.md`, `.ai/ai-prompt-rules.md`, `.ai/ai-retrieval-rules.md`, `.ai/ai-evaluation-rules.md`, and `.ai/ai-ux-rules.md` as the complete AI-facing implementation guardrail set.
- Resolution (or an accepted interim plan) for the highest-priority open decisions: **AX-02** (AI provider evaluation process, blocking any provider approval), **AX-01** (first pilot use-case selection), and **AX-03** (quality-threshold methodology).
- Confirmation of whether Phase 0.11 begins actual React/Flutter component implementation (as Phase 0.9 originally anticipated) or continues architecture/documentation work — this package deliberately does not assume which, consistent with the "no proceeding into implementation" instruction governing this phase.

Phase 0.10 does not itself perform any of Phase 0.11's work — this section exists so Phase 0.11 does not need to rediscover these foundations.

## Next Phase

```text
Phase 0.11 — (to be named by the next phase's own prompt)
```

Phase 0.11 is not started as part of this task, per working rule 40.
