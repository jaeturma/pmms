# PMMS Policy, Rulebook, and Source Validation Gap Register

**Status:** Draft Complete — Pending Formal Architecture Review and Stakeholder Sign-Off
**Related:** [../03-security/policy-source-registry.md](../03-security/policy-source-registry.md)

This register consolidates every unverified policy, privacy, child-protection, records-management, cybersecurity, sports-rule, and procedural source referenced across Phase 0.1–0.12, using ID prefix `PSG-` (Policy/Source Gap). **No source is invented here** — every entry restates an existing placeholder from [../03-security/policy-source-registry.md](../03-security/policy-source-registry.md) or a phase-specific open decision, consolidated for this review's blocking-analysis purpose.

---

| Source-Gap ID | Category | Required Source | Intended Authority | Affected Capability | Why Required | Current Status | Responsible Reviewer | Blocking Level | Target Phase |
|---|---|---|---|---|---|---|---|---|---|
| PSG-01 | Data privacy | DepEd data-privacy policy (restates [POL-01](../03-security/policy-source-registry.md)) | DepEd | Every personal-data-handling control | Governs the baseline for all personal-data processing | Not located/verified | To be identified | High | Pre-Phase 1 data-handling implementation |
| PSG-02 | Child protection | DepEd child-protection policy ([POL-02](../03-security/policy-source-registry.md)) | DepEd | Minor-athlete data governance, guardian consent | Governs minor-athlete safeguarding requirements | Not located/verified | To be identified | Critical | Pre-Phase 1 (minor-athlete data is core to PMMS) |
| PSG-03 | Records management | DepEd records-management order ([POL-03](../03-security/policy-source-registry.md)) | DepEd | Retention periods (8 categories, [PD-04](../02-data/data-open-decisions.md), blocking) | The single most-blocking policy gap across Phase 0.5 and 0.6 per that registry's own notes | Not located/verified | To be identified | Critical — Blocking | Pre-Phase 1 |
| PSG-04 | Sports/eligibility | DepEd eligibility and sports-competition policy ([POL-04](../03-security/policy-source-registry.md)) | DepEd / sports governing bodies | Eligibility workflow (OD-07), sports rule source (OD-10), medal tally (OD-12) | Blocks the architecture's three highest-priority high-integrity open decisions simultaneously | Not located/verified | To be identified | Critical — Blocking | Pre-module implementation (Eligibility, Scoring, Medal Tally) |
| PSG-05 | Medical | DepEd medical/health-services policy ([POL-05](../03-security/policy-source-registry.md)) | DepEd | Medical Operations (BC-21) core policy, OD-15 | Blocks BC-21's core policy per the registry's own note | Not located/verified | To be identified | Critical — Blocking | Pre-medical-module implementation |
| PSG-06 | Finance | DepEd financial/procurement policy ([POL-06](../03-security/policy-source-registry.md)) | DepEd / COA-adjacent | Finance Operations (BC-26), WF-24 | Government financial accountability rules are commonly stringent — not assumed | Not located/verified | To be identified | High | Pre-finance-module implementation |
| PSG-07 | Data privacy (national) | Philippine Data Privacy Act, RA 10173 ([POL-07](../03-security/policy-source-registry.md)) | National Privacy Commission | Every privacy control | National legal framework — candidate reference only, no compliance claimed | Publicly available; not verified as incorporated by name | To be identified | High | Pre-production, requires legal review |
| PSG-08 | Data privacy guidance | NPC implementing guidance ([POL-08](../03-security/policy-source-registry.md)) | National Privacy Commission | Same as PSG-07 | Interpretive guidance for DPA application | Not located/verified | To be identified | Moderate | Pre-production |
| PSG-09 | Cybersecurity | Government cybersecurity guidance ([POL-09](../03-security/policy-source-registry.md)) | DICT | Infrastructure/network security baseline | Not yet confirmed applicable to a DepEd-adjacent platform | Not located/verified | To be identified | Moderate | Pre-production |
| PSG-10 | ICT governance | DepEd ICT governance policy ([POL-10](../03-security/policy-source-registry.md)) | DepEd | Deployment topology, vendor decisions (DV-01 and related) | Relevant to hosting/procurement standard compliance | Not located/verified | To be identified | High | Pre-deployment-topology decision |
| PSG-11 | Incident reporting | DepEd incident-reporting policy ([POL-11](../03-security/policy-source-registry.md)) | DepEd | Security/privacy incident response | No confirmed internal escalation requirement identified | Not located/verified | To be identified | Moderate | Pre-production |
| PSG-12 | Public communication | DepEd public-communication policy ([POL-12](../03-security/policy-source-registry.md)) | DepEd | Public portal, media/communications workflow | Relevant to public-disclosure decisions | Not located/verified | To be identified | Moderate | Pre-public-portal implementation |
| PSG-13 | Accessibility | Accessibility policy/standard ([POL-13](../03-security/policy-source-registry.md)) | DepEd / national standard | WCAG conformance target (DX-01) | Referenced as a product principle since Phase 0.1, no standard cited | Not located/verified | To be identified | High | Pre-Phase 1 (accessibility is a Phase 0.1 non-negotiable principle) |
| PSG-14 | Sports rulebooks (per sport) | Official sport-specific rulebooks | Individual sports governing bodies | Every sport-specific competition format, scoring, bracket rule | No sport can be implemented without its verified rulebook, restated absolutely across every phase | Not located/verified for any sport | To be identified (Sports-domain representative) | Critical — Blocking, per-sport | Pre-sport-specific implementation, per sport |
| PSG-15 | Protest/appeal procedure | Official protest and appeal procedure/deadlines | DepEd / meet governance authority | Protest and Appeal workflow (WF-14), SOD-03 | Blocks filing-period and authority determination absolutely (working rule 56 of Phase 0.11) | Not located/verified | To be identified | Critical — Blocking | Pre-protest-module implementation |
| PSG-16 | Medal tally rules | Official medal-tally computation and tie-breaking rules | DepEd / sports governing bodies | Medal Tally workflow (WF-15), SOD-04 | No medal-tally rule is ever invented, restated absolutely | Not located/verified | To be identified | Critical — Blocking | Pre-medal-tally-module implementation |
| PSG-17 | Committee procedures | Official committee operating procedures (12 committees) | DepEd meet-governance authority | Committee-specific workflows (Section, [../08-workflows/committee-logistics-medical-finance-and-ict-workflows.md](../08-workflows/committee-logistics-medical-finance-and-ict-workflows.md)) | Committee workflows currently reflect architecturally reasonable structure, not verified operating procedure | Not located/verified | To be identified | Moderate | Pre-committee-module implementation |

## Summary of Blocking Policy/Source Gaps

| Gap | Why It Blocks |
|---|---|
| **PSG-03** | Retention periods (PD-04) cannot be finalized for any data category without this source — the single most-blocking gap identified in the original Phase 0.6 registry, confirmed still unresolved at Phase 0.13 |
| **PSG-04** | Simultaneously blocks OD-07 (eligibility authority), OD-10 (sports rule source), and OD-12 (medal tally rules) — the architecture's three highest-priority high-integrity open decisions |
| **PSG-05** | Blocks BC-21 Medical Operations' core policy and OD-15 (medical-data handling), itself blocking SOD-09 and Phase 0.10's AX-12 |
| **PSG-14** | No sport can begin implementation without its own verified rulebook — this is a per-sport, recurring blocker, not a one-time resolution |
| **PSG-15** | Blocks SOD-03 (result certification/protest resolution) and the entire Protest and Appeal workflow (WF-14) |
| **PSG-16** | Blocks SOD-04 (medal tally) and the Medal Tally workflow (WF-15) |

## Notes

This register does not create new open decisions — every entry above already exists as a placeholder in [../03-security/policy-source-registry.md](../03-security/policy-source-registry.md) or a phase-specific open-decision register (OD-07/09/10/12/15, PD-04, DX-01). This document exists solely to consolidate them for Phase 1 sequencing purposes, per [remediation-roadmap-and-priority-sequencing.md](remediation-roadmap-and-priority-sequencing.md).
