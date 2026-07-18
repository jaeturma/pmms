# PMMS AI Gateway, Provider, and Model Abstraction

**Status:** Draft Complete — Pending AI, Security, Privacy, Domain, Sports, Quality, and Engineering Validation
**Related:** [ai-platform-and-service-architecture.md](ai-platform-and-service-architecture.md) · [../03-security/vendor-and-third-party-risk.md](../03-security/vendor-and-third-party-risk.md)

This document defines the AI Gateway's responsibilities, provider/model abstraction, and the model registry. **No AI provider is selected without approved evaluation** — restated absolutely per working rule 9.

---

## 1. AI Gateway

### Responsibilities

Central entry point · authentication · authorization · scope validation · request classification (mapping to a use case and risk tier, per [ai-use-case-and-risk-classification.md](ai-use-case-and-risk-classification.md)) · data minimization · provider selection · prompt version selection · model selection · rate limiting · quota enforcement · correlation (per [../05-devops/observability-logging-metrics-tracing-and-alerting.md, Section 5](../05-devops/observability-logging-metrics-tracing-and-alerting.md#5-tracing-and-correlation-readiness)) · response validation · audit · fallback.

**All AI capabilities go through the gateway or an equivalent controlled application boundary** — restated absolutely from [ai-platform-and-service-architecture.md, Section 4](ai-platform-and-service-architecture.md#4-relationship-to-the-ai-gateway).

## 2. Provider and Model Abstraction

| Element | Direction |
|---|---|
| Provider-neutral interface | Every AI capability calls an abstracted interface, never a specific provider's SDK directly — restated per working rule 8, no AI SDK is installed in this phase |
| Model capability metadata | Text generation, embeddings, classification, extraction, reranking, structured output — each model's supported capabilities recorded, not assumed |
| Hosted model support | A candidate deployment model — evaluated, not committed |
| Local model readiness | A candidate deployment model for data-residency-sensitive use cases — evaluated, not committed, per [ai-security-privacy-and-data-minimization.md, "Data-Residency Readiness"](ai-security-privacy-and-data-minimization.md#7-data-residency-readiness) |
| Cost metadata | Per-model cost class, feeding [ai-observability-cost-quotas-and-operations.md, Section 2](ai-observability-cost-quotas-and-operations.md#2-cost-and-quota-management) |
| Latency metadata | Per-model expected latency, informing fallback decisions (Section, [ai-observability-cost-quotas-and-operations.md, Section 4](ai-observability-cost-quotas-and-operations.md#4-failure-and-fallback)) |
| Data-retention terms | What the provider does with submitted data, a required field before approval, per [ai-provider-vendor-and-third-party-risk.md](ai-provider-vendor-and-third-party-risk.md) |
| Residency information | Where the provider processes data geographically |
| Approved-use status | Whether this specific model is currently approved for PMMS use, and for which risk tiers |

**No provider is selected in this document.**

## 3. Model Registry

| Field | Purpose |
|---|---|
| Model ID | A stable internal reference, independent of the provider's own naming |
| Provider | Which vendor/host supplies this model |
| Model family | The underlying model lineage |
| Version | The specific model version in use |
| Capability | Text generation / embeddings / classification / extraction / reranking / structured output |
| Approved use cases | Which of the 13 use cases in [ai-use-case-and-risk-classification.md](ai-use-case-and-risk-classification.md) this model is approved for |
| Prohibited use cases | Explicitly excluded use cases (e.g., a model not approved for any Tier 3 use case) |
| Data classification allowance | The highest classification tier this model may process |
| Context-window limit | A technical constraint informing prompt/retrieval design |
| Cost category | Feeding cost governance |
| Evaluation status | Whether this model has passed the evaluation requirements in [ai-evaluation-testing-and-quality-assurance.md](ai-evaluation-testing-and-quality-assurance.md) |
| Security review | Whether Security has reviewed this model/provider |
| Privacy review | Whether Privacy has reviewed this model/provider |
| Activation state | Active / Inactive / Under Review |
| Retirement status | Scheduled retirement date, if any, and its replacement |

**No model registry entry exists yet** — this is the schema a future implementation phase populates once a provider evaluation is approved.

## 4. Fallback and Quotas (Cross-Reference)

Provider-outage behavior, timeouts, and quota enforcement are detailed in [ai-observability-cost-quotas-and-operations.md, Sections 2 and 4](ai-observability-cost-quotas-and-operations.md#2-cost-and-quota-management) — not duplicated here.

## 5. Open Questions

See [ai-open-decisions.md](ai-open-decisions.md) — notably provider-evaluation process design and whether local-model hosting is pursued for any Restricted/Highly Restricted-tier use case.
