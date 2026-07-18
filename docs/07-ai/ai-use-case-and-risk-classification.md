# PMMS AI Use-Case and Risk Classification

**Status:** Draft Complete — Pending AI, Security, Privacy, Domain, Sports, Quality, and Engineering Validation
**Related:** [ai-vision-principles-and-governance.md](ai-vision-principles-and-governance.md) · [human-in-the-loop-and-authority-model.md](human-in-the-loop-and-authority-model.md)

This document classifies the 13 candidate AI capabilities named in this phase's prompt and defines the four-tier risk model. **No use case is automatically approved by appearing here** — restated per the phase's own working instruction; every entry below is evaluated, not endorsed.

---

## 1. Use-Case Classification

| Use-Case ID | Name | User | AI Type | Risk Tier | Data Classification | Human Reviewer | Evaluation Requirement | Audit Level | Release Phase | Status |
|---|---|---|---|---|---|---|---|---|---|---|
| UC-01 | Athlete Eligibility Document Review Assistance | Eligibility reviewer | Document assistance | Tier 3 | Restricted | Eligibility reviewer/approver | Full (Section, [ai-evaluation-testing-and-quality-assurance.md](ai-evaluation-testing-and-quality-assurance.md)) | Full | Not yet scheduled | Candidate |
| UC-02 | Duplicate Athlete Detection | Secretariat / Participant Registry steward | Detection | Tier 2 | Confidential–Restricted | Identity-resolution reviewer | Full | Full | Not yet scheduled | Candidate |
| UC-03 | Schedule Conflict Detection | Tournament manager | Detection (deterministic-preferred) | Tier 2 | Internal | Tournament manager | Full | Standard | Not yet scheduled | Candidate |
| UC-04 | Tournament Scheduling Recommendations | Tournament manager | Recommendation | Tier 2 | Internal | Tournament manager | Full | Standard | Not yet scheduled | Candidate |
| UC-05 | Automatic Narrative Event Summaries | Media / Public Information | Generative | Tier 1–2 (public vs. internal) | Internal–Public | Media reviewer | Full | Standard | Not yet scheduled | Candidate |
| UC-06 | Incident Classification | Security / Medical / ICT / Venue staff | Detection and classification | Tier 2 | Restricted–Highly Restricted | Incident owner (per category) | Full | Full | Not yet scheduled | Candidate |
| UC-07 | Medal and Performance Analytics | Tally team / Executive | Analytics | Tier 1–2 | Internal–Public | Tally certifier | Full | Standard | Not yet scheduled | Candidate |
| UC-08 | Result Anomaly Detection | Result validator | Detection | Tier 3 | Internal | Result validator/certifier | Full | Full | Not yet scheduled | Candidate |
| UC-09 | Helpdesk Assistant | Any authenticated user | Knowledge assistance | Tier 1 | Internal (role-aware) | Support Tier 1/2 | Full | Standard | Not yet scheduled | Candidate |
| UC-10 | Committee Knowledge Assistant | Committee staff | Knowledge assistance | Tier 1 | Internal | Committee lead | Full | Standard | Not yet scheduled | Candidate |
| UC-11 | Natural-Language Report Generation | Authorized reporting users | Generative + retrieval | Tier 1–2 | Varies by report | Report requester's own authority | Full | Standard | Not yet scheduled | Candidate |
| UC-12 | Policy and Rulebook Search | Any authorized user | Knowledge assistance / RAG | Tier 1 | Public–Internal | Policy owner | Full | Standard | Not yet scheduled | Candidate |
| UC-13 | Venue and Schedule Risk Prediction | Meet administrator / ICT | Predictive analytics | Tier 3 | Internal | Meet administrator | Full | Full | Not yet scheduled | Candidate |

Every use case's detailed architecture is developed in its own capability-specific document (Section, [README.md](README.md), "Document Index") — this table is the index, not the full specification.

## 2. Risk Tiers

### Tier 0 — No AI

Deterministic logic only. **Examples:** official score calculation · permission evaluation · result-certification rules · medal-tally calculations based on approved formulas · credential validity · access-control enforcement. No AI capability in this package operates at Tier 0 — restated absolutely, these functions remain entirely outside AI's reach.

### Tier 1 — Low-Risk Assistance

**Examples:** help-article retrieval · draft summary generation · search assistance · terminology explanation. Lower evaluation/review burden, still fully auditable and labeled.

### Tier 2 — Moderate-Risk Recommendation

**Examples:** duplicate suggestions · schedule conflicts · incident classification · missing-document suggestions. Requires structured output, explicit evidence, and a defined human reviewer.

### Tier 3 — High-Risk Decision Support

**Examples:** eligibility review assistance · result anomalies · venue risk prediction · performance interpretations. **Requires stronger review, evidence, access restrictions, evaluation, and audit** — restated absolutely as the tier's governing requirement.

### Prohibited Autonomous Actions (No Tier — Always Human-Only)

Eligibility approval or rejection · score modification · result certification · protest resolution · medal awarding · medical decisions · permission changes · financial approval · credential revocation · disciplinary action. Restated absolutely from [ai-vision-principles-and-governance.md, Section 4](ai-vision-principles-and-governance.md#4-prohibited-actions-absolute) — no risk tier authorizes any of these; they are categorically outside AI's reach regardless of evidence quality or confidence level.

## 3. Tier Assignment Rationale

A use case's tier reflects the *cost of being wrong*, not merely the sophistication of the AI technique involved — restated from [../04-quality/risk-based-testing-model.md](../04-quality/risk-based-testing-model.md)'s risk-based philosophy, applied to AI specifically. UC-01 (eligibility) and UC-08 (result anomalies) are Tier 3 because an undetected false negative directly touches the platform's highest-integrity domains, even though the AI itself never finalizes either decision.

## 4. Open Questions

See [ai-open-decisions.md](ai-open-decisions.md) — notably which use case is approved for the first pilot implementation, and whether UC-05/UC-07/UC-11's public-versus-internal split warrants being tracked as two separate use-case entries rather than one dual-tier entry.
