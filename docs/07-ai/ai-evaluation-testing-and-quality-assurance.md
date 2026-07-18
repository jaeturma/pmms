# PMMS AI Evaluation, Testing, and Quality Assurance

**Status:** Draft Complete — Pending AI, Security, Privacy, Domain, Sports, Quality, and Engineering Validation
**Related:** [../04-quality/phase-0.7-quality-engineering-testing-validation-assurance.md](../04-quality/phase-0.7-quality-engineering-testing-validation-assurance.md) · [../04-quality/test-data-fixture-and-scenario-strategy.md](../04-quality/test-data-fixture-and-scenario-strategy.md)

This document defines AI evaluation dimensions, golden datasets, and release-gate requirements, extending Phase 0.7's risk-based quality architecture into AI-specific evaluation. **No evaluation script or dataset is created here.**

---

## 1. Evaluation Dimensions

Correctness · groundedness (is the output actually supported by retrieved evidence?) · citation accuracy · completeness · hallucination rate · false-positive rate · false-negative rate · classification quality · recommendation usefulness · privacy leakage · authorization leakage · bias · consistency · latency · cost · human acceptance · override rate.

Every one of these is a **quality threshold to be defined** — restated per this phase's own working instruction (Section, [ai-incident-response-change-and-release-governance.md, "AI Release Gates"](ai-incident-response-change-and-release-governance.md#3-ai-release-gates)) — no specific numeric threshold is invented in this document.

## 2. Evaluation Datasets

**Use synthetic, de-identified, or formally approved datasets** — restated absolutely, directly extending [../04-quality/test-data-fixture-and-scenario-strategy.md, Section 2](../04-quality/test-data-fixture-and-scenario-strategy.md#2-synthetic-data-requirements)'s "no real production data by default" rule to AI evaluation specifically. No real minor-athlete, medical, eligibility, financial, or authentication data is ever used in an evaluation dataset.

### Evaluation Categories

Eligibility documents · duplicate identities · schedule conflicts · incident classifications · result anomalies · medal analytics · policy questions · helpdesk questions · risk predictions · narratives — one category per the 13 use cases in [ai-use-case-and-risk-classification.md](ai-use-case-and-risk-classification.md).

**Golden datasets cite approved expected results** — restated absolutely; a golden dataset's "correct answer" for a policy-search question cites the actual verified source (per [policy-rulebook-and-source-governance.md](policy-rulebook-and-source-governance.md)), never an assumed or invented correct answer.

## 3. Retrieval, Hallucination, and Prompt-Injection Tests

| Test Type | Purpose |
|---|---|
| Retrieval tests | Confirm the RAG pipeline (per [retrieval-knowledge-and-semantic-search-architecture.md](retrieval-knowledge-and-semantic-search-architecture.md)) retrieves the correct, authorized source for a given query |
| Hallucination tests | Confirm a model doesn't fabricate a citation, rule, or fact absent from retrieved evidence — the direct evaluation counterpart to working rule 14's "no invented rules" prohibition |
| Privacy tests | Confirm no AI output exposes data beyond the requesting user's authorization, per [ai-security-privacy-and-data-minimization.md](ai-security-privacy-and-data-minimization.md) |
| Authorization tests | Confirm AI retrieval correctly enforces scope/classification boundaries, per [ai-identity-authorization-scope-and-audit.md, Section 3](ai-identity-authorization-scope-and-audit.md#3-ai-data-access) |
| Prompt-injection tests | Deliberately crafted adversarial inputs (in user prompts and in retrieved documents) confirming the Safety and Policy Filter (per [ai-platform-and-service-architecture.md](ai-platform-and-service-architecture.md)) holds |
| Bias readiness | A candidate future evaluation category (systematic unfairness across delegation, school, sex, or other protected attributes) — evaluated, not yet a defined process, restated per working rule 55's spirit |
| Regression | Every prompt/model/knowledge-source change re-runs the full golden-dataset suite before replacing the active version, per [prompt-context-and-structured-output-architecture.md, Section 2](prompt-context-and-structured-output-architecture.md#2-prompt-registry), Rule 8 |
| Human evaluation | A sample of AI outputs reviewed by a domain expert (not merely automated scoring) before and periodically after release |

## 4. Model, Prompt, and Retrieval Evaluation

**Model evaluation** confirms a specific model/version meets the accuracy/safety bar for its approved use cases before entering the Model Registry (per [ai-gateway-provider-and-model-abstraction.md, Section 3](ai-gateway-provider-and-model-abstraction.md#3-model-registry)) as "Active." **Prompt evaluation** confirms a specific prompt version performs correctly against its golden dataset before replacing the active prompt version. **Retrieval evaluation** confirms the knowledge-retrieval pipeline surfaces the correct, authorized, current source for representative queries.

## 5. AI Release Gates (Cross-Reference)

Full release-gate requirements (approved use case, risk tier, data-owner approval, security/privacy review, prompt review, knowledge-source verification, evaluation dataset, quality threshold, failure-mode testing, authorization testing, prompt-injection testing, hallucination testing, cost review, observability, feature flag, rollback/disablement, user guidance, human-review workflow) are defined in [ai-incident-response-change-and-release-governance.md, "AI Release Gates"](ai-incident-response-change-and-release-governance.md#3-ai-release-gates) — not duplicated here.

## 6. Open Questions

See [ai-open-decisions.md](ai-open-decisions.md) — notably the specific numeric quality thresholds per evaluation dimension (deliberately left undefined pending real evaluation-methodology selection) and bias-evaluation process design.
