# PMMS Incident Classification and Operational Assistance (UC-06)

**Status:** Draft Complete — Pending AI, Security, Privacy, Domain, Sports, Quality, and Engineering Validation
**Related:** [../06-design/committee-logistics-medical-finance-and-support-experience.md](../06-design/committee-logistics-medical-finance-and-support-experience.md) · [../05-devops/runbooks-playbooks-and-standard-operating-procedures.md](../05-devops/runbooks-playbooks-and-standard-operating-procedures.md) · [ai-use-case-and-risk-classification.md](ai-use-case-and-risk-classification.md)

Tier 2 (Moderate-Risk Recommendation). **AI must not replace emergency judgment, medical judgment, security authority, or incident-command decisions** — restated absolutely as this document's central constraint.

---

## 1. Potential Assistance

Classify medical, security, venue, transport, ICT, accreditation, or operational incidents · suggest severity category · suggest responsible committee · suggest escalation path · identify missing incident details · suggest related runbooks (per [../05-devops/runbooks-playbooks-and-standard-operating-procedures.md](../05-devops/runbooks-playbooks-and-standard-operating-procedures.md)).

## 2. Candidate Incident Categories

Medical · security · ICT · venue · transport · billeting · food · accreditation · crowd · equipment · schedule · data or system.

## 3. AI Output

Suggested category · suggested severity · suggested owner · missing information (what the report is missing that a complete incident record needs) · related runbook · uncertainty.

**Every suggestion is exactly that — a suggestion.** The actual incident owner, severity determination, and response remain a human decision, made through the ordinary incident-management workflow already established in [../05-devops/incident-problem-change-and-release-management.md, Section 1](../05-devops/incident-problem-change-and-release-management.md#1-incident-management).

## 4. Medical and Security Incident Boundary

For medical incidents specifically, AI classification never substitutes for or delays clinical judgment — restated absolutely from working rule's medical-decision boundary and [../03-security/medical-eligibility-finance-and-sensitive-data-controls.md, "Medical Data Governance"](../03-security/medical-eligibility-finance-and-sensitive-data-controls.md#medical-data-governance). For security incidents, AI classification never substitutes for the Security Coordinator's actual investigative authority, restated from [../03-security/incident-response-and-breach-readiness.md](../03-security/incident-response-and-breach-readiness.md).

## 5. Authority Table

| Element | Value |
|---|---|
| Requesting user | Security/Medical/ICT/Venue committee staff, per the incident's category |
| Scope | The specific incident being logged |
| Data classification | Restricted–Highly Restricted depending on category (medical/security incidents are Highly Restricted) |
| Permitted input | The incident report's own content as entered by the reporting user |
| Prohibited input | Unrelated incidents, other individuals' medical/security records not part of this specific incident |
| Allowed output | Category/severity/owner suggestion, missing-information prompts |
| Prohibited action | Any final classification, severity assignment, or incident closure — all remain human decisions |
| Required reviewer | The responsible committee's incident owner |
| Audit level | Full |
| Feature-flag state | Off by default, pending pilot approval |

## 6. Open Questions

See [ai-open-decisions.md](ai-open-decisions.md) — notably whether medical-incident classification assistance is excluded entirely from the initial pilot given its elevated sensitivity, pending [Phase 0.1 OD-15](../00-product/open-decisions.md#od-15--medical-data-handling).
