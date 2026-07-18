# PMMS Workflow Classification and Risk Model

**Status:** Draft Complete — Pending Domain, Workflow, Security, Quality, Operations, and Engineering Validation
**Related:** [workflow-vision-principles-and-governance.md](workflow-vision-principles-and-governance.md) · [../01-architecture/high-integrity-domain-rules.md](../01-architecture/high-integrity-domain-rules.md)

---

## 1. Workflow Categories

| Category | Definition | Examples |
|---|---|---|
| Transactional Workflow | A short, strongly consistent operation within one context | Submit registration, record score, approve eligibility, revoke credential |
| Human Approval Workflow | Requires one or more assigned human actions | Eligibility review, result certification, finance approval, protest resolution |
| Long-Running Business Process | May span hours, days, or the full meet lifecycle | Athlete registration lifecycle, meet preparation, accreditation issuance, protest resolution, meet closure |
| Asynchronous Processing Workflow | Performs non-immediate work | Notifications, document processing, report generation, projection updates, bulk imports |
| Scheduled Workflow | Triggered by time or a calendar condition | Reminders, assignment expiry, publication activation, archive jobs, daily summaries |
| Real-Time Update Workflow | Delivers transient updates | Live scoreboards, tournament status, operational alerts |
| Responsible Automation Workflow | Performs a pre-authorized deterministic action within explicit limits | Expire a credential at its validity end, dispatch a reminder, rebuild a projection, escalate an overdue task |

A single business process may combine categories — e.g., Athlete Registration (WF-03) is a Long-Running Business Process containing Transactional steps, a Human Approval sub-workflow (Eligibility Validation, WF-04), and Scheduled reminder steps.

## 2. Workflow Risk Classification

### Low Risk

Informational reminders · non-sensitive notifications · cache refresh · public projection rebuild.

### Moderate Risk

Assignment reminders · deadline escalation · committee handoffs · import processing · document classification.

### High Risk

Eligibility state transitions · score correction · result certification · protest hold · accreditation revocation · finance approval · sensitive exports.

**High-risk workflows require stronger authorization, audit, idempotency, history, testing, manual recovery, and observability** than Low- or Moderate-risk workflows — restated as the section's governing rule. High-risk workflow classification tracks directly onto the 13 high-integrity domains in [../01-architecture/high-integrity-domain-rules.md](../01-architecture/high-integrity-domain-rules.md): Participant Identity, Athlete Registration, Eligibility, Competition Entries, Tournament Progression, Scoring, Official Results, Protests and Appeals, Medal Tally, Accreditation, Medical Information, Financial Records, Audit History.

## 3. Risk-to-Control Mapping

| Risk Tier | Authorization | Audit | Idempotency | History | Testing | Recovery | Observability |
|---|---|---|---|---|---|---|---|
| Low | Standard | Standard | Recommended | Not required | Standard | Automatic retry | Aggregate metrics |
| Moderate | Standard, scope-checked | Standard | Required | Recommended | Elevated (negative-path) | Automatic retry + manual review path | Per-workflow metrics |
| High | Full authorization formula (per [../01-architecture/authorization-decision-model.md](../01-architecture/authorization-decision-model.md)), synchronous where required | Full, Critical/Elevated-audit-level | Required, verified | Full append-only version chain | Full Critical-tier depth (per [../04-quality/risk-based-testing-model.md](../04-quality/risk-based-testing-model.md)) | Manual intervention required, never blind auto-retry | Individual-instance visibility, alerting |

## 4. Workflow Risk Is Not the Same as AI Risk

Phase 0.10's four-tier AI risk classification (Tier 0–3, per [../07-ai/ai-use-case-and-risk-classification.md, Section 2](../07-ai/ai-use-case-and-risk-classification.md#2-risk-tiers)) and this document's three-tier workflow risk classification are related but distinct dimensions — a Low-risk workflow (e.g., a cache-refresh automation) may still touch a Tier-3 AI capability's output for display purposes without itself becoming AI-classified, and a High-risk workflow (e.g., eligibility approval) may use a Tier-3 AI capability for advisory assistance while the workflow's own human-approval transition remains entirely outside AI's authority.

## 5. Domain Workflow Boundaries

Every workflow's boundary is exactly its owning bounded context's boundary, per [../01-architecture/context-map.md](../01-architecture/context-map.md) — a workflow never silently expands to make a decision belonging to another context's authoritative data. Cross-context workflow steps use the orchestration patterns in [orchestration-choreography-and-process-manager-architecture.md](orchestration-choreography-and-process-manager-architecture.md), never a direct cross-context write.

## 6. Open Questions

See [workflow-open-decisions.md](workflow-open-decisions.md) — notably WD-01 (numeric cycle-time/SLA targets per risk tier, deliberately left undefined pending real operational data).
