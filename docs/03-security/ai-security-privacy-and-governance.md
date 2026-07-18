# PMMS AI Security, Privacy, and Governance

**Status:** Draft Complete — Pending Security, Privacy, Legal, Audit, Data Governance, and Engineering Validation
**Related:** [../01-architecture/phase-0.3-access-and-assignment-architecture.md, Section 29](../01-architecture/phase-0.3-access-and-assignment-architecture.md#29-ai-authorization-boundary) · [../01-architecture/device-and-service-identity-model.md, Section 7](../01-architecture/device-and-service-identity-model.md#7-ai-service-identity-cross-reference) · [../00-product/phase-0.1-product-foundation.md, Section 19](../00-product/phase-0.1-product-foundation.md#19-ai-assisted-product-direction)

This document defines AI-specific security threats, privacy/governance controls, and the absolute action boundaries every AI-assisted feature in PMMS must respect. **No AI service, model, or vendor is selected here.**

---

## 1. AI Security Threats

| Threat | PMMS-Specific Framing |
|---|---|
| Prompt injection | A user or an uploaded document deliberately crafts input to make an AI feature deviate from its intended task (e.g., an eligibility-evidence document containing hidden instructions to approve the case) |
| Indirect prompt injection | Malicious instructions embedded in content the AI processes on someone else's behalf (a document, an imported record) rather than the direct user's own prompt |
| Sensitive-data leakage | An AI feature's output inadvertently reveals data the requesting user was not authorized to see, or data from another context entirely |
| Over-permission | An AI service's execution identity is granted broader data access than its specific feature requires |
| Hallucinated rules | An AI feature presents an invented sports/eligibility/scoring rule as if authoritative — directly prohibited, per working rule 11 applied to AI output specifically |
| Data poisoning | Deliberately corrupted input data intended to bias a future AI-assisted recommendation |
| Unauthorized action | An AI feature attempting to perform (or being tricked into triggering) an action beyond its approved, human-authority-bound scope |
| Model or vendor compromise | A compromise of the underlying AI service provider itself |
| Insecure tool use | An AI feature with tool-calling capability (e.g., querying the database, calling an internal API) executing an unintended or unauthorized tool call |
| Excessive retention | An AI vendor retaining PMMS data (including for model training) beyond what the approved use case and agreement permit |
| Cross-tenant leakage | If PMMS ever operates multi-organization, an AI feature inadvertently blending data across organizational boundaries |
| Untraceable recommendations | An AI-generated suggestion that cannot be traced back to its inputs, model version, and the human decision that acted on it |

## 2. AI Privacy and Governance

| Control | Direction |
|---|---|
| Approved AI use cases | AI assistance is scoped to specifically approved use cases (e.g., drafting communications, flagging incomplete eligibility submissions, summarizing evidence for a human reviewer) — never a blanket "AI can do anything" grant |
| Prohibited use cases | Section 3 below — absolute, non-negotiable |
| Human review | Every AI output feeding a consequential decision is reviewed by the accountable human before that decision takes effect |
| Minimum necessary data | An AI feature receives only the data its specific request requires, per [privacy-by-design-architecture.md, Section 2](privacy-by-design-architecture.md#2-privacy-by-design-controls) — never a standing broad dataset |
| Redaction | Sensitive fields not needed for the specific AI task are redacted/excluded from the data sent to the AI service |
| Source citation | Where an AI feature references a rule or policy, it cites its source rather than presenting an unsourced assertion, mirroring the platform-wide "no invented rules" discipline |
| Model and prompt version | Recorded for every consequential AI-assisted action, per [audit-and-security-event-architecture.md, Section 7](audit-and-security-event-architecture.md#7-ai-assistance-auditing) |
| Service identity | Every AI feature executes under its own Service Identity, per [../01-architecture/device-and-service-identity-model.md, Section 7](../01-architecture/device-and-service-identity-model.md#7-ai-service-identity-cross-reference) |
| User authorization inheritance | An AI feature's effective access is the *intersection* of its own scope and the requesting user's authority, never a union — restated as the platform's central AI-authorization rule |
| Audit | Every AI-assisted action is audited per [audit-and-security-event-architecture.md, Section 7](audit-and-security-event-architecture.md#7-ai-assistance-auditing) |
| Data retention | Data sent to an AI service is retained only as long as the specific interaction requires, per the eventual vendor agreement — no indefinite retention by the AI provider is accepted without explicit approval |
| Vendor agreement readiness | Any AI vendor is a candidate for [vendor-and-third-party-risk.md](vendor-and-third-party-risk.md) assessment before approval |
| Region or data-residency review | Where an AI vendor processes data outside an acceptable region, this is a specific, named risk requiring review, per [security-open-decisions.md](security-open-decisions.md) |
| Disablement | Every AI feature can be disabled platform-wide or per-context without requiring a broader system change |
| Fallback | Every AI-assisted workflow has a non-AI fallback path — no workflow becomes unusable if the AI feature is disabled or unavailable |
| Incident response | An AI-specific security or privacy concern follows [incident-response-and-breach-readiness.md](incident-response-and-breach-readiness.md) |
| Bias and fairness review readiness | A candidate future review area for any AI feature influencing an outcome affecting people (even in an advisory capacity) — not yet a defined process |
| Explainability | An AI-generated suggestion is presented with enough context for the human reviewer to evaluate it, not as an unexplained black-box output |
| Contestability | A human affected by an AI-assisted recommendation (e.g., a flagged eligibility submission) has a path to have the underlying human decision reviewed — the AI's involvement does not shield the decision from ordinary review/appeal processes |

## 3. AI Action Boundaries (Absolute Prohibitions)

An AI-assisted feature must **never**, under any circumstance, autonomously:

- Approve or reject eligibility.
- Certify results.
- Change scores.
- Resolve protests.
- Award medals.
- Issue medical decisions.
- Grant access.
- Change permissions.
- Revoke credentials without an approved human-initiated workflow.
- Publish protected data.
- Delete records.
- Execute production repair.
- Exfiltrate data to an unapproved external service.

**Approved AI suggestions must pass through ordinary authorized use cases** — an AI-generated draft, flag, or recommendation is acted upon only by a human with the actual authority to perform that action, through the same Command/Application-layer path, authorization check, and audit trail as if the human had initiated it without AI involvement. This restates and does not weaken [../01-architecture/phase-0.3-access-and-assignment-architecture.md, Section 29](../01-architecture/phase-0.3-access-and-assignment-architecture.md#29-ai-authorization-boundary) and [../01-architecture/laravel-architecture.md](../01-architecture/laravel-architecture.md)'s AI-integration rule.

## 4. Relationship to Prior Phases

This document adds security/governance assurance around a boundary already established — it introduces no new AI capability and narrows nothing already approved. The full underlying policy remains blocked on [../00-product/open-decisions.md, OD-29](../00-product/open-decisions.md#od-29--ai-service-restrictions) (AI-Service Restrictions) and [../01-architecture/domain-open-decisions.md, DD-26](../01-architecture/domain-open-decisions.md#dd-26--ai-service-data-access-boundaries-domain-specific-framing) (AI data-access boundaries).

## 5. Open Questions

See [security-open-decisions.md](security-open-decisions.md) — notably which specific AI use cases are approved for initial implementation, AI-vendor selection and data-residency review, and whether a bias/fairness review process is formally adopted.
