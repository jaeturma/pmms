# PMMS AI-Assisted Platform Architecture — Open Decisions

**Status:** Draft Complete — Pending AI, Security, Privacy, Domain, Sports, Quality, and Engineering Validation
**Related:** [ai-vision-principles-and-governance.md](ai-vision-principles-and-governance.md) · [../06-design/design-open-decisions.md](../06-design/design-open-decisions.md) · [../05-devops/devops-open-decisions.md](../05-devops/devops-open-decisions.md)

This document tracks every unresolved Phase 0.10 decision using Decision ID prefix `AX-` (AI eXperience), distinct from Phase 0.1's `OD-`, Phase 0.2's `DD-`, Phase 0.3's `AD-`, Phase 0.4's `RD-`, Phase 0.5's `PD-`, Phase 0.6's `SD-`, Phase 0.7's `QD-`, Phase 0.8's `DV-`, and Phase 0.9's `DX-` series. Each entry follows the established format: Question / Areas Affected / Why It Matters / Options / Recommended Direction / Evidence Required / Decision Owner / Target Phase / Status.

---

### AX-01 — First Pilot Use-Case Selection

- **Question:** Which of the 13 use cases (UC-01–UC-13) is implemented and piloted first?
- **Areas affected:** All capability-specific documents
- **Why it matters:** Sequencing affects which risk tier and evaluation burden the platform encounters first.
- **Options:** A Tier 1 capability (UC-09/UC-10/UC-12, lower risk, faster to evaluate) vs. a higher-value Tier 2/3 capability first.
- **Recommended direction:** A Tier 1 knowledge-assistance capability (Helpdesk or Policy/Rulebook Search), given its lower risk profile and shared RAG-infrastructure dependency with other capabilities.
- **Evidence required:** AI governance owner + Product owner prioritization decision.
- **Decision owner:** AI governance owner + Product owner
- **Target phase:** 0.11+
- **Status:** Open

### AX-02 — AI Provider Evaluation Process Design

- **Question:** What specific process/questionnaire evaluates a candidate AI provider before approval?
- **Areas affected:** [ai-provider-vendor-and-third-party-risk.md](ai-provider-vendor-and-third-party-risk.md)
- **Why it matters:** No provider is currently approved; a defined process is needed before the first approval can occur.
- **Options:** A custom AI-specific supplement to the existing vendor-assessment framework vs. adopting an industry-standard AI-vendor questionnaire.
- **Recommended direction:** A custom supplement built from Section 1's assessment areas, mirroring [../03-security/design-open-decisions... vendor-questionnaire direction](../05-devops/devops-open-decisions.md#dv-21--container-registry-selection)'s "build lightweight first" pattern.
- **Evidence required:** None yet — a practical implementation-phase task.
- **Decision owner:** Vendor manager + AI governance owner
- **Target phase:** Pre-first-provider-approval
- **Status:** Open

### AX-03 — Quality Threshold Values Per Evaluation Dimension

- **Question:** What specific numeric thresholds (accuracy, hallucination rate, false-positive rate, etc.) gate an AI capability's release?
- **Areas affected:** [ai-evaluation-testing-and-quality-assurance.md](ai-evaluation-testing-and-quality-assurance.md), [ai-incident-response-change-and-release-governance.md, "AI Release Gates"](ai-incident-response-change-and-release-governance.md#3-ai-release-gates)
- **Why it matters:** Deliberately left undefined in this phase per the phase's own working instruction — no threshold is invented without real evaluation-methodology data.
- **Options:** None yet — requires an evaluation-methodology selection first.
- **Recommended direction:** Establish thresholds empirically once the first golden dataset (AX-01's chosen use case) produces real baseline measurements.
- **Evidence required:** First evaluation-dataset run.
- **Decision owner:** Evaluation lead
- **Target phase:** Pre-first-capability-release
- **Status:** Open

### AX-04 — Vector Database Adoption

- **Question:** Is a dedicated vector database adopted for semantic search, or does keyword/full-text search remain sufficient?
- **Areas affected:** [retrieval-knowledge-and-semantic-search-architecture.md, Section 5](retrieval-knowledge-and-semantic-search-architecture.md#5-semantic-search-versus-keyword-search-versus-hybrid-retrieval)
- **Why it matters:** A significant infrastructure and cost commitment — no embeddings or vector database is created in this phase, per working rule 10.
- **Options:** Keyword/full-text search only (MySQL-backed, per Phase 0.5's staged-search direction) vs. dedicated vector database.
- **Recommended direction:** Keyword search first; vector database evaluated only once demonstrated necessary, mirroring [../02-data/public-reporting-and-projection-data.md, Section 3](../02-data/public-reporting-and-projection-data.md#3-search-indexes)'s identical staged-adoption principle.
- **Evidence required:** Retrieval-quality evaluation showing keyword search's specific limitations.
- **Decision owner:** AI governance owner + Infrastructure owner
- **Target phase:** Post-pilot
- **Status:** Open

### AX-05 — OCR Provider Evaluation Timing

- **Question:** When is OCR-assisted document extraction (for UC-01, eligibility documents) evaluated and adopted?
- **Areas affected:** [athlete-eligibility-document-review-assistance.md, Section 4](athlete-eligibility-document-review-assistance.md#4-input-restrictions)
- **Why it matters:** UC-01's usefulness for scanned/image-based documents depends on it.
- **Options:** Defer until UC-01 is actually approved for pilot vs. evaluate proactively.
- **Recommended direction:** Defer until UC-01's own pilot approval, avoiding premature investment in a capability that may not be an early priority (per AX-01).
- **Evidence required:** UC-01 pilot-approval decision.
- **Decision owner:** AI governance owner
- **Target phase:** Contingent on UC-01 approval
- **Status:** Open

### AX-06 — Knowledge-Source Revalidation Cadence

- **Question:** How often is each knowledge source (DepEd issuances, sports rulebooks, committee manuals) formally revalidated?
- **Areas affected:** [policy-rulebook-and-source-governance.md, Section 3](policy-rulebook-and-source-governance.md#3-knowledge-freshness)
- **Why it matters:** Without a defined cadence, knowledge sources risk silently becoming stale.
- **Options:** A fixed calendar cadence (e.g., quarterly) vs. an event-driven revalidation (triggered by a known policy update).
- **Recommended direction:** Event-driven revalidation as the primary trigger, with an annual calendar-based fallback review for sources with no known update.
- **Evidence required:** None — a governance-process decision.
- **Decision owner:** Knowledge-source owner (per category)
- **Target phase:** Pre-RAG-implementation
- **Status:** Open

### AX-07 — Bias Evaluation Process Design

- **Question:** What specific process evaluates AI capabilities (especially UC-02 duplicate detection) for systematic bias?
- **Areas affected:** [ai-evaluation-testing-and-quality-assurance.md, Section 3](ai-evaluation-testing-and-quality-assurance.md#3-retrieval-hallucination-and-prompt-injection-tests), [duplicate-athlete-detection-architecture.md, Section 5](duplicate-athlete-detection-architecture.md#5-bias-and-naming-variation-risks)
- **Why it matters:** Named as a readiness concern (working rule 55) but not yet a defined process.
- **Options:** Not evaluated in this phase.
- **Recommended direction:** None yet — requires bias-evaluation methodology expertise beyond this documentation phase's scope.
- **Evidence required:** Specialist input (a bias/fairness evaluation practitioner).
- **Decision owner:** Evaluation lead + AI governance owner
- **Target phase:** Pre-UC-02-release
- **Status:** Open

### AX-08 — Review-Fatigue Monitoring Mechanism

- **Question:** What specific mechanism detects reviewer rubber-stamping of AI suggestions?
- **Areas affected:** [human-in-the-loop-and-authority-model.md, Section 7](human-in-the-loop-and-authority-model.md#7-review-fatigue)
- **Why it matters:** A named operational risk to the human-in-the-loop guarantee's actual effectiveness, not just its architecture.
- **Options:** Override-rate monitoring · periodic sampling audits · both.
- **Recommended direction:** Both — override-rate monitoring as a continuous signal, periodic sampling audits as a deeper quality check.
- **Evidence required:** None — an operational-process design decision.
- **Decision owner:** Quality owner + AI governance owner
- **Target phase:** Post-first-capability-release
- **Status:** Open

### AX-09 — Structured-Output Schema Standardization

- **Question:** Is a single structured-output schema format (e.g., a shared JSON schema library) standardized across all 13 use cases?
- **Areas affected:** [prompt-context-and-structured-output-architecture.md, Section 5](prompt-context-and-structured-output-architecture.md#5-structured-outputs)
- **Why it matters:** Consistency reduces implementation and evaluation complexity across capabilities.
- **Options:** Fully standardized shared schema vs. per-capability schemas sharing only the common required fields.
- **Recommended direction:** A shared base schema (result type, summary, evidence, citations, confidence, limitations, recommended action, model/prompt version, timestamp) with capability-specific extension fields.
- **Evidence required:** None — a low-risk, reversible implementation convention.
- **Decision owner:** AI governance owner
- **Target phase:** Pre-first-capability-implementation
- **Status:** Open

### AX-10 — Prompt-Review Approval Workflow

- **Question:** What specific workflow reviews and approves a new or modified prompt before it enters the Prompt Registry?
- **Areas affected:** [prompt-context-and-structured-output-architecture.md, Section 2](prompt-context-and-structured-output-architecture.md#2-prompt-registry)
- **Why it matters:** Determines how quickly a prompt fix can ship versus how much review rigor is applied.
- **Options:** Mirrors the existing pull-request review workflow (per [../05-devops/source-control-branching-and-release-workflow.md, Section 3](../05-devops/source-control-branching-and-release-workflow.md#3-pull-request-workflow)) vs. a dedicated AI-specific review board.
- **Recommended direction:** Mirror the existing pull-request workflow, adding the Prompt reviewer role as a required approver — avoiding a parallel governance process where the existing one suffices.
- **Evidence required:** None — a process-design decision.
- **Decision owner:** AI governance owner
- **Target phase:** Pre-first-capability-implementation
- **Status:** Open

### AX-11 — Matching-Algorithm Evaluation for Philippine Naming Conventions

- **Question:** How is UC-02's name-matching algorithm specifically evaluated for accuracy across Philippine naming conventions (multi-part surnames, common nicknames, regional variation)?
- **Areas affected:** [duplicate-athlete-detection-architecture.md, Section 5](duplicate-athlete-detection-architecture.md#5-bias-and-naming-variation-risks)
- **Why it matters:** A generic, unvalidated matching algorithm risks systematically under- or over-matching for this specific population.
- **Options:** Not evaluated in this phase.
- **Recommended direction:** A dedicated evaluation dataset built from realistic (synthetic) Philippine name variations, per [ai-evaluation-testing-and-quality-assurance.md, Section 2](ai-evaluation-testing-and-quality-assurance.md#2-evaluation-datasets).
- **Evidence required:** Domain input on realistic naming-variation patterns.
- **Decision owner:** Evaluation lead
- **Target phase:** Pre-UC-02-release
- **Status:** Open

### AX-12 — Medical-Incident-Classification Exclusion (Cross-Reference)

- **Question:** Is UC-06's medical-incident classification excluded from the initial AI pilot entirely?
- **Areas affected:** [incident-classification-and-operational-assistance.md, Section 6](incident-classification-and-operational-assistance.md#6-open-questions)
- **Why it matters:** Blocked directly on [Phase 0.1 OD-15](../00-product/open-decisions.md#od-15--medical-data-handling).
- **Options:** Exclude entirely from initial pilot vs. include with elevated restriction.
- **Recommended direction:** Exclude from initial pilot; medical-incident classification assistance is evaluated only after OD-15 resolves.
- **Evidence required:** OD-15 resolution.
- **Decision owner:** AI governance owner + Privacy owner
- **Target phase:** Post-OD-15
- **Status:** Open — blocked, mirrors OD-15

### AX-13 — Weather-Data Integration (Cross-Reference)

- **Question:** Is external weather-data integration ever approved for UC-04/UC-13?
- **Areas affected:** [schedule-conflict-and-tournament-recommendation-architecture.md](schedule-conflict-and-tournament-recommendation-architecture.md), [venue-and-schedule-risk-prediction.md](venue-and-schedule-risk-prediction.md)
- **Why it matters:** Currently entirely out of scope — no weather-data source is approved or assumed anywhere in this package.
- **Options:** No integration (current state) vs. a future approved external-data integration.
- **Recommended direction:** No integration until a specific external-data vendor is separately evaluated and approved, per [../03-security/vendor-and-third-party-risk.md](../03-security/vendor-and-third-party-risk.md) — an entirely distinct decision from AI-provider approval.
- **Evidence required:** A specific vendor proposal.
- **Decision owner:** Vendor manager
- **Target phase:** Not scheduled
- **Status:** Open

### AX-14 — Local-Model Hosting for Sensitive Use Cases

- **Question:** Is a self-hosted/local model deployment pursued for UC-01 (eligibility) or other Restricted-tier use cases specifically?
- **Areas affected:** [ai-gateway-provider-and-model-abstraction.md, Section 2](ai-gateway-provider-and-model-abstraction.md#2-provider-and-model-abstraction), [ai-security-privacy-and-data-minimization.md, Section 7](ai-security-privacy-and-data-minimization.md#7-data-residency-readiness)
- **Why it matters:** A candidate mitigation for data-residency and external-sharing concerns on the highest-sensitivity use cases.
- **Options:** Hosted-provider-only · local-model hosting for sensitive tiers specifically · local-model hosting platform-wide.
- **Recommended direction:** Hosted-provider-only initially, given implementation complexity; local-model hosting evaluated specifically if AX-02's provider evaluation reveals unacceptable data-handling terms for Restricted-tier data.
- **Evidence required:** AX-02's provider evaluation outcome.
- **Decision owner:** AI governance owner + Infrastructure owner
- **Target phase:** Contingent on AX-02
- **Status:** Open

### AX-15 — Cross-Tenant AI Isolation (Cross-Reference)

- **Question:** Does AI request context, retrieval, and cost tracking ever need genuine cross-organization isolation?
- **Areas affected:** [ai-security-privacy-and-data-minimization.md, Section 9](ai-security-privacy-and-data-minimization.md#9-cross-tenant-isolation)
- **Why it matters:** Mirrors [Phase 0.1 OD-02](../00-product/open-decisions.md#od-02--single-organization-versus-multi-organization) exactly — entirely outside this phase's authority to resolve.
- **Options:** Per OD-02.
- **Recommended direction:** None — carried unchanged, readiness-only for now.
- **Evidence required:** Product-direction decision, outside AI architecture's scope.
- **Decision owner:** Product owner + DepEd Leadership
- **Target phase:** Unresolved since Phase 0.1
- **Status:** Open — mirrors OD-02

### AX-16 — Public AI Capability Scope

- **Question:** Is any AI capability ever exposed to unauthenticated public users, and if so, which?
- **Areas affected:** [mobile-offline-and-public-ai-boundaries.md, Section 3](mobile-offline-and-public-ai-boundaries.md#3-public-ai)
- **Why it matters:** The public surface carries the strictest data-exposure constraints of any PMMS context.
- **Options:** No public AI capability at launch · a narrowly-scoped public policy-search capability (UC-12, public sources only).
- **Recommended direction:** No public AI capability at launch; UC-12's public-source-only variant evaluated only after the authenticated version has a proven track record.
- **Evidence required:** Authenticated UC-12 pilot results.
- **Decision owner:** AI governance owner + Security owner
- **Target phase:** Post-pilot
- **Status:** Open

### AX-17 — Mobile AI Rollout Sequencing (Cross-Reference)

- **Question:** When are AI capabilities extended to the Flutter mobile app, given `mobile/` does not yet exist?
- **Areas affected:** [mobile-offline-and-public-ai-boundaries.md, Section 2](mobile-offline-and-public-ai-boundaries.md#2-mobile-ai-boundaries)
- **Why it matters:** Directly dependent on [../06-design/design-open-decisions.md, DX-18](../06-design/design-open-decisions.md#dx-18--mobile-scaffolding-timing-cross-reference).
- **Options:** None yet — dependent on DX-18.
- **Recommended direction:** None — mobile AI rollout follows `mobile/` scaffolding, never precedes it.
- **Evidence required:** DX-18 resolution.
- **Decision owner:** Technical lead
- **Target phase:** Contingent on DX-18
- **Status:** Open — mirrors DX-18

### AX-18 — AI Governance Board Establishment Timing

- **Question:** When, if ever, is a formal recurring AI governance board established beyond the current decision-rights table?
- **Areas affected:** [ai-vision-principles-and-governance.md, Section 9](ai-vision-principles-and-governance.md#9-ai-governance-board-readiness)
- **Why it matters:** A heavier governance structure than the platform currently needs, given zero AI capabilities are in production.
- **Options:** Establish now · establish once the first capability reaches pilot · establish once multiple capabilities are live.
- **Recommended direction:** Once multiple capabilities are live and require ongoing coordinated oversight — premature before then.
- **Evidence required:** AI capability count and operational complexity growth.
- **Decision owner:** DepEd Leadership + AI governance owner
- **Target phase:** Post-multiple-capability-launch
- **Status:** Open

### AX-19 — Per-Capability Cost Budget Values

- **Question:** What specific cost ceiling applies to each use case's AI budget?
- **Areas affected:** [ai-observability-cost-quotas-and-operations.md, Section 2](ai-observability-cost-quotas-and-operations.md#2-cost-and-quota-management)
- **Why it matters:** No numeric budget is invented in this phase — cost governance requires real usage data first.
- **Options:** None yet.
- **Recommended direction:** Establish empirically from the first pilot capability's actual measured usage, then extrapolate.
- **Evidence required:** First capability's pilot usage data.
- **Decision owner:** AI governance owner + Infrastructure owner (per [../05-devops/cost-resource-and-capacity-governance.md](../05-devops/cost-resource-and-capacity-governance.md))
- **Target phase:** Post-first-pilot
- **Status:** Open

### AX-20 — AI-Specific Incident Severity Matrix

- **Question:** Does AI use a dedicated incident-severity matrix, or the existing general severity model?
- **Areas affected:** [ai-incident-response-change-and-release-governance.md, Section 6](ai-incident-response-change-and-release-governance.md#6-open-questions)
- **Why it matters:** An AI-specific incident (e.g., a hallucinated policy citation) may not map cleanly to existing severity categories.
- **Options:** Reuse the existing general severity model (per [../05-devops/incident-problem-change-and-release-management.md, Section 1](../05-devops/incident-problem-change-and-release-management.md#1-incident-management)) vs. a dedicated AI severity matrix.
- **Recommended direction:** Reuse the existing model initially, given no AI incident has yet occurred to demonstrate a specific gap.
- **Evidence required:** First AI incident (if any) revealing a categorization gap.
- **Decision owner:** Security owner + AI governance owner
- **Target phase:** Reactive, as-needed
- **Status:** Open

### AX-21 — Model Registry Population Process

- **Question:** Who populates and maintains the Model Registry (per [ai-gateway-provider-and-model-abstraction.md, Section 3](ai-gateway-provider-and-model-abstraction.md#3-model-registry)) as models are evaluated and approved?
- **Areas affected:** [ai-gateway-provider-and-model-abstraction.md](ai-gateway-provider-and-model-abstraction.md)
- **Why it matters:** An empty or stale registry undermines every downstream capability's model-selection logic.
- **Options:** A manual, governance-board-maintained registry vs. an automated registry synced from provider APIs.
- **Recommended direction:** Manual initially, given the small number of models expected at launch; automation evaluated once model count grows.
- **Evidence required:** None — a low-risk, reversible operational decision.
- **Decision owner:** AI governance owner
- **Target phase:** Pre-first-provider-approval
- **Status:** Open

---

## Summary of Blocking / High-Priority AI Decisions

| Decision | Why It Blocks |
|---|---|
| **AX-12** | Medical-incident-classification assistance is blocked directly on the still-unresolved Phase 0.1 OD-15 |
| **AX-15** | Cross-tenant AI isolation mirrors the still-unresolved Phase 0.1 OD-02, entirely outside AI architecture's own authority |
| **AX-17** | Mobile AI rollout is blocked on Phase 0.9's DX-18 (`mobile/` scaffolding timing) |
| **AX-03** | Quality-threshold values block finalizing every capability's release-gate enforcement, deliberately deferred pending real evaluation data |
