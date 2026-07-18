# PMMS Prompt, Context, and Structured Output Architecture

**Status:** Draft Complete — Pending AI, Security, Privacy, Domain, Sports, Quality, and Engineering Validation
**Related:** [ai-platform-and-service-architecture.md](ai-platform-and-service-architecture.md) · [ai-explainability-confidence-and-user-review.md](ai-explainability-confidence-and-user-review.md)

This document defines prompt layering, the prompt registry, structured-output requirements, and tool-use/agentic-workflow restrictions. **No prompt file intended for production execution is created here** — restated absolutely per working rule 7.

---

## 1. Prompt Layers

System instruction · capability instruction · domain rule · user context · authorization context · retrieved evidence · user question · required output schema · safety constraints.

Every layer is assembled by the AI Gateway (per [ai-platform-and-service-architecture.md, Section 1](ai-platform-and-service-architecture.md#1-conceptual-components)), never hand-composed per request — this layering discipline is what makes prompt versioning (Section 2) and evaluation (per [ai-evaluation-testing-and-quality-assurance.md](ai-evaluation-testing-and-quality-assurance.md)) meaningful across many requests sharing the same structure.

## 2. Prompt Registry

Every prompt template is: versioned (a specific, addressable version, never a silently-mutating live string) · reviewed (per [ai-vision-principles-and-governance.md, Section 3](ai-vision-principles-and-governance.md#3-governance-roles), Prompt reviewer) · associated with a specific capability and risk tier · associated with the model(s) it's approved for use with.

### Rules

1. **Prompts are versioned** — restated absolutely.
2. **Prompts are reviewed** — no prompt reaches production without passing the same review discipline as a code change.
3. **Prompts do not contain secrets** — restated absolutely, extending [../03-security/cryptography-key-and-secret-management.md, Section 3](../03-security/cryptography-key-and-secret-management.md#3-secret-management)'s secret-handling discipline.
4. **Prompts minimize personal data** — restated from [ai-security-privacy-and-data-minimization.md](ai-security-privacy-and-data-minimization.md).
5. **Prompts identify official-source requirements** — a prompt for a policy-search capability explicitly instructs the model to cite only verified sources, per [policy-rulebook-and-source-governance.md](policy-rulebook-and-source-governance.md).
6. **Prompts require uncertainty disclosure** — every prompt instructs the model to express confidence/limitations, never to present a guess as certain.
7. **Prompts prohibit autonomous high-integrity decisions** — every prompt for a capability touching a high-integrity domain explicitly instructs the model that its output is a suggestion requiring human review, restated absolutely from working rule 16.
8. **Prompt changes require evaluation** — a modified prompt is re-evaluated against the golden dataset (per [ai-evaluation-testing-and-quality-assurance.md, Section 2](ai-evaluation-testing-and-quality-assurance.md#2-evaluation-datasets)) before replacing the active version.

## 3. System Prompts and User Prompts

**System prompts** carry the capability instruction, domain rules, safety constraints, and output-schema requirements — authored and reviewed by PMMS, never influenced by end-user input. **User prompts** carry the requesting user's actual question/request — treated as untrusted input requiring the same validation discipline as any other user-submitted content, per [../03-security/application-api-and-client-security.md, Section 1](../03-security/application-api-and-client-security.md#1-application-security).

### Prompt Injection

User content (and retrieved document content, per Section 4) is never permitted to override system-level safety constraints — restated per this phase's own governing statement (Section, [ai-security-privacy-and-data-minimization.md, "Prompt Injection"](ai-security-privacy-and-data-minimization.md#prompt-injection)). The Safety and Policy Filter (per [ai-platform-and-service-architecture.md, Section 1](ai-platform-and-service-architecture.md#1-conceptual-components)) screens for injection indicators both before and after model invocation.

## 4. Retrieved Context

Content retrieved via RAG (per [retrieval-knowledge-and-semantic-search-architecture.md](retrieval-knowledge-and-semantic-search-architecture.md)) is **untrusted content**, even though it comes from an approved knowledge source — a retrieved document's content is data for the model to reason about, never an instruction the model should follow. This distinction is enforced at the prompt-layering level (Section 1): retrieved evidence occupies its own layer, structurally separated from system instructions.

## 5. Structured Outputs

Preferred for: duplicate candidates · missing-document findings · schedule conflicts · incident classifications · anomaly alerts · risk indicators · report outlines · source citations.

### Every Output Includes

Result type · summary · evidence · source references · confidence or uncertainty · limitations · recommended human action · model version · prompt version · generated time.

The Structured Output Validator (per [ai-platform-and-service-architecture.md, Section 1](ai-platform-and-service-architecture.md#1-conceptual-components)) confirms every response matches its required schema before it reaches a human reviewer — a malformed or incomplete structured output is treated as a failure requiring fallback (per [ai-observability-cost-quotas-and-operations.md, Section 4](ai-observability-cost-quotas-and-operations.md#4-failure-and-fallback)), never silently passed through.

## 6. Tool Use and Agents

### AI Tools May

Search approved knowledge · query authorized read models · retrieve source documents · generate drafts · create review suggestions · request report generation.

### AI Tools Must Not Directly

Write authoritative records · approve cases · certify results · alter scores · resolve protests · modify permissions · revoke credentials · execute unrestricted exports · run database commands · access arbitrary files.

**Every tool call requires the same server-side authorization check as any other application action** — restated from working rule (extending [ai-security-rules.md](../../.ai/ai-security-rules.md)'s "tool calls require server-side authorization" principle); a tool is never a backdoor around the ordinary Command/Application-layer authorization path.

## 7. Agentic Workflow Restrictions

**Avoid autonomous multi-agent workflows during initial implementation** — restated absolutely per this phase's own working instruction. A single AI request produces a single, human-reviewed output; PMMS does not deploy AI agents that chain multiple tool calls or sub-decisions without a human checkpoint between them, at least not in this phase's approved architecture.

## 8. Open Questions

See [ai-open-decisions.md](ai-open-decisions.md) — notably whether a structured-output schema library/format is standardized across all capabilities, and the specific prompt-review approval workflow.
