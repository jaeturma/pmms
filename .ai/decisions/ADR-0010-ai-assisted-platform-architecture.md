# ADR-0010: AI-Assisted Platform Architecture

## Status

Accepted (as a Phase 0.10 AI-architecture decision; pending formal AI, security, privacy, domain, sports, quality, and engineering sign-off — see [../current-phase.md](../current-phase.md))

## Context

ADR-0002 through ADR-0009 established PMMS's bounded contexts, authorization model, runtime architecture, data/persistence architecture, security/privacy/audit/governance architecture, quality-engineering architecture, DevOps/operations architecture, and design-system/UX/accessibility architecture — each one restating, without exception, that an AI-assisted feature never autonomously approves eligibility, certifies results, changes scores, resolves protests, awards medals, issues medical decisions, grants access, changes permissions, revokes credentials, publishes protected data, deletes records, or exfiltrates data. None of them, however, specified *how* AI is actually integrated: what gateway pattern every AI request passes through, how a candidate AI capability is risk-classified before consideration, what retrieval architecture grounds an AI answer in a verified source, or what identity and audit model governs an AI service distinct from the human user it assists.

Left unspecified, this gap risks the same failure mode every prior phase's centralization work was built to prevent, now expressed at the AI layer specifically: thirteen plausible AI capabilities (eligibility document review, duplicate detection, schedule conflict detection, tournament scheduling recommendations, narrative summaries, incident classification, medal/performance analytics, result anomaly detection, a helpdesk assistant, a committee knowledge assistant, natural-language report generation, policy/rulebook search, and venue/schedule risk prediction), if each were integrated ad hoc, would each invent its own authorization shortcut, its own data-access assumption, and its own confidence-presentation convention — producing exactly the fragmented, ungoverned surface the absolute AI-boundary rule in every prior ADR exists to prevent from ever mattering in practice.

## Decision

PMMS will govern every future AI capability through a **single AI Gateway pattern, a four-tier risk classification, an absolute human-in-the-loop authority model, and a dual-identity (requesting-user + AI-service-identity) access model — with none of the thirteen candidate AI capabilities evaluated in this phase approved for implementation.**

Specifically:

1. **Every AI capability routes through one AI Gateway.** Authentication, authorization, scope validation, request classification, data minimization, provider/prompt/model selection, rate limiting, quota enforcement, response validation, audit, and fallback are enforced centrally — never as a scattered per-feature integration each capability implements independently.
2. **AI capabilities are risk-classified before any consideration of implementation.** Tier 0 (deterministic-only, no AI — score calculation, permission evaluation, result certification, medal-tally calculation, credential validity, access-control enforcement), Tier 1 (Low-Risk Assistance), Tier 2 (Moderate-Risk Recommendation), Tier 3 (High-Risk Decision Support), plus an absolute, no-tier Prohibited Autonomous Actions category outside the entire system.
3. **AI outputs never directly update authoritative state.** The human-in-the-loop lifecycle (`User Request → Authorization Check → Data Minimization → AI Processing → Grounded Output → Confidence and Limitation Display → Human Review → Accept, Reject, Edit, or Escalate → Authorized Application Action → Audit`) governs every AI-assisted feature without exception.
4. **AI data access is the intersection, never the union, of the requesting user's authorization and a separate, restricted AI service identity.** The AI service identity has no human role, no DBA access, no unrestricted object-storage access, and is environment-separated, rotated, revocable, and independently audited.
5. **Retrieval is grounded and citation-based.** PMMS never treats an unverified document or generated content as official policy — the 14-stage RAG lifecycle (`Source Approval → Ingestion → Classification → Parsing → Chunking → Metadata → Indexing → Retrieval Authorization → Query → Reranking → Context Assembly → Generation → Citation → Human Review`) requires every knowledge source to be verified before use.
6. **Sensitive data carries enhanced, never-default AI-access rules.** Minor-athlete data receives enhanced protection; medical data is excluded from general AI use by default; authentication data is never sent to any AI service, under any circumstance — no approval process can override this last rule.
7. **Anomaly is not accusation, and predictive risk is never a sole basis for exclusion or discipline.** Anomaly detection creates a review alert only, never alters data, and never labels a person as fraudulent or dishonest; a risk prediction informs contingency planning only, restated absolutely per working rules 32–34.
8. **No AI capability bypasses the 18-item release-gate list, regardless of its risk tier.** Approved use case, risk tier, data-owner/security/privacy/prompt review, knowledge-source verification, evaluation dataset, quality threshold, failure-mode/authorization/prompt-injection/hallucination testing, cost review, observability, feature flag, rollback plan, user guidance, and human-review workflow are all required before any capability reaches production. AI feature-flag disablement is the primary, fastest AI-specific incident-response mechanism — faster than an application-code deployment rollback.
9. **None of the thirteen candidate AI capabilities (UC-01–UC-13) is approved for implementation by this phase.** Each is documented with its own potential-assistance list, absolute prohibitions, and authority table — but every one remains a candidate pending its own separate future approval and full release-gate pass.

**Explicitly not decided by this ADR:** which AI capability is piloted first, which AI provider or model is ever approved, whether a vector database is ever adopted, the numeric quality-threshold values gating any release, and every other item tracked in [../../docs/07-ai/ai-open-decisions.md](../../docs/07-ai/ai-open-decisions.md).

## Rationale

- **Preserves every prior ADR's AI-boundary guarantee by finally specifying its enforcement mechanism.** ADR-0004, ADR-0006, and ADR-0009 each restated that AI never exceeds the requesting user's authority and never autonomously decides a high-integrity outcome — this ADR is where that guarantee becomes a specific, enforceable architecture (the AI Gateway, the dual-identity model, the release-gate list) rather than a repeated principle with no mechanism behind it.
- **Prevents thirteen ad hoc AI integrations from each inventing their own authorization shortcut.** A single Gateway, a shared risk-classification system, and a shared release-gate list mean every future AI capability inherits the same guardrails rather than reproving them independently — and inconsistently — each time.
- **Protects PMMS's highest-sensitivity data at the exact point an AI integration could accidentally over-expose it.** The intersection-not-union access model and the sensitive-document handling matrix exist because an AI service identity that merely inherited a human role's full access would be a strictly worse security posture than the human role itself, given how easily broad context windows can leak information the interface would otherwise carefully mask.
- **Avoids both premature AI commitment and premature vagueness.** No provider, model, prompt, embedding, or vector database is selected — but the gateway pattern, risk classification, human-in-the-loop model, and thirteen capabilities' detailed requirements every future AI decision must respect are fully specified, so the next phase to actually build an AI capability begins from a governed foundation rather than a blank slate.
- **Matches PMMS's actual risk profile, not a generic "AI-first" template.** A platform managing minor-athlete data, medical records, official competition results, and government accountability cannot treat AI assistance as a low-stakes convenience feature — every capability in this package is evaluated against that reality specifically, not against what a general-purpose AI product might default to.

## Approved AI Architecture Direction

> Route every future AI capability through a single AI Gateway enforcing authorization, data minimization, and audit; classify every candidate capability by risk tier before considering it for implementation; ground every knowledge-assistance answer in a verified, cited source; treat AI data access as the intersection — never the union — of the requesting user's authority and a separately restricted AI service identity; and require every capability to pass a full, tier-independent release-gate list before reaching production — approving none of the thirteen candidate capabilities evaluated in this phase.

## AI-Boundary Rule (Restated and Given Full Mechanism, Extending ADR-0004/0006/0009)

No AI-assisted feature ever approves, certifies, publishes, revokes, resolves, merges, deletes, or alters a high-integrity record — restated absolutely and without exception. What is new in this phase is the specific mechanism enforcing it: the AI Gateway's Context and Authorization Resolver, the intersection-not-union data-access model, and the human-in-the-loop lifecycle's mandatory `Human Review → Accept, Reject, Edit, or Escalate` stage before any authorized application action occurs.

## Anomaly-and-Risk Neutrality Rule (New in This Phase)

Anomaly detection (UC-08) and risk prediction (UC-13) outputs use deliberately neutral language and create review alerts only — never a factual accusation, never a fraud/dishonesty label, and never the sole basis for exclusion or disciplinary action, restated absolutely per working rules 32–34.

## Sensitive-Data AI-Access Rule (New in This Phase, Extending ADR-0006)

Authentication data is never sent to any AI service under any circumstance; medical data is excluded from general AI use by default; minor-athlete, guardian, eligibility, finance, and security-audit data each carry their own AI-access rule in [../../docs/07-ai/ai-security-privacy-and-data-minimization.md, Section 5](../../docs/07-ai/ai-security-privacy-and-data-minimization.md#5-sensitive-document-handling) — none of them defaults to whatever access a human role happens to have.

## Consequences

**Positive:**
- Any future phase that implements an AI capability inherits a complete gateway pattern, risk-classification system, identity model, retrieval architecture, and release-gate list, and can begin implementation against known, consistent expectations rather than inventing AI governance per capability.
- The platform's highest-sensitivity data categories (minor-athlete, medical, eligibility, financial, authentication) have explicit, differentiated AI-access rules defined before any AI integration could accidentally under-protect them.
- Thirteen plausible AI capabilities are documented with enough specificity (workflow, inputs, outputs, absolute prohibitions, authority table) that a future approval decision can be made capability-by-capability, evidence-based, rather than as a single undifferentiated "turn on AI" decision.

**Negative / trade-offs:**
- No AI capability is usable at the end of this phase — deliberately, but this means the administrative burden and information-discovery benefits this package's own vision statement describes remain entirely unrealized until a future phase completes at least one capability's full release-gate pass.
- A significant number of decisions remain open (21 items in [../../docs/07-ai/ai-open-decisions.md](../../docs/07-ai/ai-open-decisions.md)), including which capability is piloted first and what provider-evaluation process even exists — an accepted sequencing cost, consistent with never finalizing without evidence.
- The intersection-not-union access model and the sensitive-document handling matrix add real design complexity beyond a naive "AI inherits the user's permissions" approach — accepted because the alternative risks exactly the kind of AI-mediated data exposure this ADR exists to prevent.

## Alternatives Considered

1. **Approve and implement the first AI capability directly within this phase.** Rejected — directly violates this phase's own working rules; no provider, model, or production prompt may be selected or deployed here.
2. **Allow each of the thirteen candidate capabilities to define its own ad hoc AI integration.** Rejected — would repeat exactly the fragmentation risk the AI Gateway pattern exists to prevent, with no consistent authorization, audit, or evaluation discipline across capabilities.
3. **Grant the AI service identity the same access as the human role it assists.** Rejected — directly violates the intersection-not-union access model; an AI service identity is always more restricted than any human role it serves, given the elevated risk of broad context windows leaking information an interface would otherwise carefully mask.
4. **Invent placeholder numeric quality thresholds now to give evaluation work a concrete target.** Rejected — a fabricated threshold is worse than an explicitly undefined one; it would misrepresent unvalidated confidence as an evaluated standard.
5. **Treat anomaly-detection findings as evidence of wrongdoing pending human review.** Rejected — directly violates working rules 32–33; anomaly detection produces a review alert only, never a factual accusation.

## Validation Requirements

This decision is provisional pending:

- Formal review by a designated AI governance owner, Security owner, Privacy owner, Domain reviewers, Sports reviewers, Quality owner, Evaluation lead, and DepEd Leadership, per [../../docs/07-ai/README.md, "Ownership and Review Expectations"](../../docs/07-ai/README.md#ownership-and-review-expectations).
- Resolution of the highest-priority Phase 0.10 open decisions, per [../../docs/07-ai/ai-open-decisions.md, "Summary of Blocking / High-Priority AI Decisions"](../../docs/07-ai/ai-open-decisions.md#summary-of-blocking--high-priority-ai-decisions) — notably AX-02 (AI provider evaluation process) and AX-01 (first pilot use-case selection).
- Continued resolution of the Phase 0.1 policy decisions several capability-specific documents depend on (OD-15 medical-data handling, OD-02 single-versus-multi-organization, OD-29 AI-service restrictions).
- A completed vendor-risk assessment, including the training-use restriction, before any AI provider is approved, per [../../docs/07-ai/ai-provider-vendor-and-third-party-risk.md](../../docs/07-ai/ai-provider-vendor-and-third-party-risk.md).

## Related Documents

- [../../docs/07-ai/phase-0.10-ai-assisted-platform-architecture.md](../../docs/07-ai/phase-0.10-ai-assisted-platform-architecture.md)
- [../../docs/07-ai/ai-vision-principles-and-governance.md](../../docs/07-ai/ai-vision-principles-and-governance.md)
- [../../docs/07-ai/ai-use-case-and-risk-classification.md](../../docs/07-ai/ai-use-case-and-risk-classification.md)
- [../../docs/07-ai/human-in-the-loop-and-authority-model.md](../../docs/07-ai/human-in-the-loop-and-authority-model.md)
- [../../docs/07-ai/ai-gateway-provider-and-model-abstraction.md](../../docs/07-ai/ai-gateway-provider-and-model-abstraction.md)
- [../../docs/07-ai/ai-identity-authorization-scope-and-audit.md](../../docs/07-ai/ai-identity-authorization-scope-and-audit.md)
- [../../docs/07-ai/ai-security-privacy-and-data-minimization.md](../../docs/07-ai/ai-security-privacy-and-data-minimization.md)
- [../../docs/07-ai/retrieval-knowledge-and-semantic-search-architecture.md](../../docs/07-ai/retrieval-knowledge-and-semantic-search-architecture.md)
- [../../docs/07-ai/ai-incident-response-change-and-release-governance.md](../../docs/07-ai/ai-incident-response-change-and-release-governance.md)
- [../../docs/07-ai/ai-open-decisions.md](../../docs/07-ai/ai-open-decisions.md)
- [../architecture.md](../architecture.md)
- [../ai-rules.md](../ai-rules.md)
- [../ai-security-rules.md](../ai-security-rules.md)
- [../ai-prompt-rules.md](../ai-prompt-rules.md)
- [../ai-retrieval-rules.md](../ai-retrieval-rules.md)
- [../ai-evaluation-rules.md](../ai-evaluation-rules.md)
- [../ai-ux-rules.md](../ai-ux-rules.md)
- [../current-phase.md](../current-phase.md)
- [ADR-0001-product-foundation.md](ADR-0001-product-foundation.md)
- [ADR-0002-domain-and-bounded-context-architecture.md](ADR-0002-domain-and-bounded-context-architecture.md)
- [ADR-0003-role-permission-scope-and-assignment-architecture.md](ADR-0003-role-permission-scope-and-assignment-architecture.md)
- [ADR-0004-application-integration-and-runtime-architecture.md](ADR-0004-application-integration-and-runtime-architecture.md)
- [ADR-0005-data-database-and-information-lifecycle-architecture.md](ADR-0005-data-database-and-information-lifecycle-architecture.md)
- [ADR-0006-security-privacy-audit-compliance-and-data-governance.md](ADR-0006-security-privacy-audit-compliance-and-data-governance.md)
- [ADR-0007-quality-engineering-testing-validation-and-assurance.md](ADR-0007-quality-engineering-testing-validation-and-assurance.md)
- [ADR-0008-devops-environment-cicd-deployment-and-operations.md](ADR-0008-devops-environment-cicd-deployment-and-operations.md)
- [ADR-0009-design-system-ux-accessibility-and-cross-platform-experience.md](ADR-0009-design-system-ux-accessibility-and-cross-platform-experience.md)
