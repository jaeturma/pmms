# PMMS Policy Source Registry

**Status:** Draft Complete — Pending Security, Privacy, Legal, Audit, Data Governance, and Engineering Validation
**Related:** [compliance-control-framework.md](compliance-control-framework.md) · [security-open-decisions.md](security-open-decisions.md)

This registry tracks official policy sources PMMS's security, privacy, and compliance controls should eventually cite. **No policy entry below is invented — every entry is a placeholder pending verification**, per working rule 16 ("If policy documents are not available, create a policy-source registry with placeholders rather than inventing rules").

---

## Registry

| Policy ID | Title | Issuing Authority | Reference Number | Date | Scope | Status | Official Source Location | Verified By | Relevant Controls | Notes |
|---|---|---|---|---|---|---|---|---|---|---|
| POL-01 | (Placeholder) DepEd data-privacy policy | DepEd | TBD | TBD | Personal-data handling across DepEd systems | Not yet located/verified | TBD | Not yet verified | CTL-09, CTL-10 | Referenced conceptually throughout this package; no specific DepEd issuance has been confirmed as of this phase |
| POL-02 | (Placeholder) DepEd child-protection policy | DepEd | TBD | TBD | Minor-athlete data and safeguarding | Not yet located/verified | TBD | Not yet verified | CTL-10, [minor-athlete-and-guardian-data-governance.md](minor-athlete-and-guardian-data-governance.md) | Directly relevant to Section "Minor-Athlete Privacy"; not yet cited with a verified source |
| POL-03 | (Placeholder) DepEd records-management order | DepEd | TBD | TBD | Records retention and disposal | Not yet located/verified | TBD | Not yet verified | [retention-disposal-and-legal-hold-governance.md](retention-disposal-and-legal-hold-governance.md), [../02-data/data-open-decisions.md, PD-04](../02-data/data-open-decisions.md#pd-04--retention-periods-8-categories) | The single most-blocking policy gap across both Phase 0.5 and Phase 0.6 |
| POL-04 | (Placeholder) DepEd eligibility and sports-competition policy | DepEd / sports governing bodies | TBD | TBD | Athlete eligibility criteria | Not yet located/verified | TBD | Not yet verified | [medical-eligibility-finance-and-sensitive-data-controls.md](medical-eligibility-finance-and-sensitive-data-controls.md), [../00-product/open-decisions.md, OD-07/OD-10](../00-product/open-decisions.md#od-07--eligibility-authority) | Blocking multiple Critical high-integrity decisions across all prior phases |
| POL-05 | (Placeholder) DepEd medical/health-services policy | DepEd | TBD | TBD | Medical-data handling, emergency care authority | Not yet located/verified | TBD | Not yet verified | [medical-eligibility-finance-and-sensitive-data-controls.md](medical-eligibility-finance-and-sensitive-data-controls.md), [../00-product/open-decisions.md, OD-15](../00-product/open-decisions.md#od-15--medical-data-handling) | Blocking BC-21's core policy |
| POL-06 | (Placeholder) DepEd financial/procurement policy | DepEd / COA-adjacent | TBD | TBD | Meet finance handling, procurement | Not yet located/verified | TBD | Not yet verified | [medical-eligibility-finance-and-sensitive-data-controls.md](medical-eligibility-finance-and-sensitive-data-controls.md) | Government financial accountability rules are commonly stringent — not assumed here |
| POL-07 | Philippine Data Privacy Act (RA 10173) | Congress of the Philippines / National Privacy Commission | RA 10173 | 2012 | National personal-data-protection law | Referenced as a candidate framework only | Publicly available; not verified as reviewed/incorporated by name in this repository | Not yet verified against this repository's specific controls | [compliance-control-framework.md, Section 1](compliance-control-framework.md#1-framework-candidates) | Listed as `Candidate reference requiring validation` — this documentation does not claim compliance |
| POL-08 | (Placeholder) National Privacy Commission implementing guidance | National Privacy Commission | TBD | TBD | DPA interpretive guidance | Not yet located/verified | TBD | Not yet verified | [compliance-control-framework.md](compliance-control-framework.md) | Specific circulars/advisories not yet identified for PMMS's context |
| POL-09 | (Placeholder) Government cybersecurity guidance (DICT-related) | DICT | TBD | TBD | Government-system cybersecurity baseline | Not yet located/verified | TBD | Not yet verified | [compliance-control-framework.md](compliance-control-framework.md) | Not yet confirmed applicable to a DepEd-adjacent platform specifically |
| POL-10 | (Placeholder) DepEd ICT governance policy | DepEd | TBD | TBD | ICT system approval, hosting, procurement standards | Not yet located/verified | TBD | Not yet verified | [infrastructure-runtime-and-network-security.md](infrastructure-runtime-and-network-security.md), [vendor-and-third-party-risk.md](vendor-and-third-party-risk.md) | Relevant to future deployment-topology and vendor decisions |
| POL-11 | (Placeholder) DepEd incident-reporting policy | DepEd | TBD | TBD | Security/privacy incident reporting obligations | Not yet located/verified | TBD | Not yet verified | [incident-response-and-breach-readiness.md](incident-response-and-breach-readiness.md) | No confirmed internal DepEd escalation/reporting requirement identified yet |
| POL-12 | (Placeholder) DepEd public-communication policy | DepEd | TBD | TBD | Public disclosure and media-communication standards | Not yet located/verified | TBD | Not yet verified | [data-sharing-export-and-public-disclosure-controls.md](data-sharing-export-and-public-disclosure-controls.md) | Relevant to public-portal and media-sharing decisions |
| POL-13 | (Placeholder) Accessibility policy or standard | DepEd / national accessibility standard | TBD | TBD | Accessibility requirements for public-facing systems | Not yet located/verified | TBD | Not yet verified | [../00-product/phase-0.1-product-foundation.md, Section 8](../00-product/phase-0.1-product-foundation.md#8-product-principles) (accessibility principle) | Referenced as a product principle since Phase 0.1; no specific standard cited yet |

## Category Index

| Category | Policy IDs |
|---|---|
| DepEd | POL-01, POL-02, POL-03, POL-04, POL-05, POL-06, POL-10, POL-11, POL-12, POL-13 |
| Data privacy | POL-01, POL-07, POL-08 |
| Cybersecurity | POL-09, POL-10 |
| Records management | POL-03 |
| Medical | POL-05 |
| Finance | POL-06 |
| Sports and eligibility | POL-04 |
| Procurement and vendor management | POL-06, POL-10 |
| Incident response | POL-11 |
| Public communication | POL-12 |
| Child protection | POL-02 |
| Accessibility | POL-13 |

## Rules

1. **No entry is invented.** Every row above marked `Not yet located/verified` remains exactly that until a named stakeholder locates and confirms the official source document.
2. **A policy is only cited as authoritative once verified.** No document in this Phase 0.6 package cites any entry above as a settled legal/policy basis for a specific numeric value (retention period, MFA requirement, etc.) — every such value remains an open decision until its policy source is verified here.
3. **This registry is a living document.** As DepEd, NPC, or other authorities confirm applicable policies, this table is updated with real reference numbers, dates, and verification — converting placeholders into verified entries, never the reverse.
4. **Verification is a named responsibility**, not an assumed completion — the `Verified By` column remains "Not yet verified" until a specific stakeholder role formally confirms the source.

## Open Questions

See [security-open-decisions.md](security-open-decisions.md) — every unverified entry above is, collectively, the largest single open-decision cluster in this phase.
