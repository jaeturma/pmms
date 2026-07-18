# PMMS Incident Response and Breach Readiness

**Status:** Draft Complete — Pending Security, Privacy, Legal, Audit, Data Governance, and Engineering Validation
**Related:** [security-risk-register.md](security-risk-register.md) · [audit-and-security-event-architecture.md, Section 8](audit-and-security-event-architecture.md#8-security-events) · [security-architecture.md, Section 3](security-architecture.md#3-security-governance-model)

This document defines the security-incident and privacy-incident/breach-readiness lifecycle. **No incident-management tooling, escalation software, or legal notification deadline is created or asserted here.**

---

## 1. Security Incident Response Lifecycle

```text
Detect → Triage → Contain → Preserve Evidence → Investigate → Eradicate → Recover → Notify → Review → Improve
```

| Stage | Activity |
|---|---|
| Detect | A security event (Section 8, [audit-and-security-event-architecture.md](audit-and-security-event-architecture.md)) or external report triggers detection |
| Triage | The Incident commander (or first responder) assesses scope and assigns a severity placeholder |
| Contain | Immediate action to limit further impact — credential revocation, device isolation, service disablement (Section 4 below) |
| Preserve evidence | Logs, audit events, and affected-system state are preserved before remediation risks losing them |
| Investigate | Root cause and full scope of impact are determined |
| Eradicate | The underlying cause (compromised credential, vulnerability, malicious file) is removed |
| Recover | Affected systems/data are restored to normal operation, per [../02-data/backup-restore-and-data-recovery.md](../02-data/backup-restore-and-data-recovery.md) where data restoration is needed |
| Notify | Per Section 3's decision process — internal and, where determined necessary, external notification |
| Review | A post-incident review identifies what happened and why |
| Improve | Control improvements from the review are tracked to completion, feeding [security-risk-register.md](security-risk-register.md) and [compliance-control-framework.md](compliance-control-framework.md) |

## 2. Incident Categories and Severity

| Category | Examples |
|---|---|
| Credential compromise | Account takeover, stolen device credential |
| Data exposure | Unauthorized access to or disclosure of Restricted/Highly Restricted data |
| Data integrity | Unauthorized alteration of high-integrity data |
| Malware | Detected malicious file or compromised endpoint |
| Availability | Denial-of-service, ransomware, extended outage |
| Insider misuse | Authorized access used outside legitimate purpose |
| Third-party/vendor | A vendor-side incident affecting PMMS data |
| AI-specific | Prompt injection, AI-policy violation, AI-service compromise |
| Physical/device | Lost/stolen device, physical venue-security incident affecting PMMS systems |

**Severity is not numerically fixed in this document** — every incident's severity is a placeholder assessed at triage time against a future-defined severity matrix (an open decision).

## 3. Roles and Escalation

| Role | Responsibility |
|---|---|
| Incident commander | Leads the incident through the full lifecycle; the single point of coordination |
| Security owner | Technical decision authority for containment/eradication actions |
| Privacy owner | Assesses privacy/data-protection impact, leads Section 5 (breach readiness) activity |
| Data owner (affected domain) | Provides domain-specific context and impact assessment |
| Communications lead | Coordinates any internal or external communication (candidate role, may coincide with Media and Communications, BC-28) |
| DepEd Leadership | Escalation point for incidents with institutional/reputational significance |

Escalation path: first responder → Incident commander → Security/Privacy owners → DepEd Leadership (for significant incidents), with every escalation step itself audit-relevant.

## 4. Containment Actions (Candidate Toolkit)

- Credential revocation (per [../01-architecture/access-review-and-revocation.md](../01-architecture/access-review-and-revocation.md)).
- Device isolation/revocation (per [mobile-device-and-offline-security.md, Section 2](mobile-device-and-offline-security.md#2-device-and-scanner-security)).
- Service disablement (per [infrastructure-runtime-and-network-security.md, Section 4](infrastructure-runtime-and-network-security.md#4-service-account-security)).
- Session invalidation (per [identity-authentication-and-session-security.md, Section 3](identity-authentication-and-session-security.md#3-session-security)).
- AI-feature disablement (per [ai-security-privacy-and-governance.md, Section 2](ai-security-privacy-and-governance.md#2-ai-privacy-and-governance)).
- Network isolation of an affected component.
- Temporary suspension of a specific workflow (e.g., pausing public registration during an active incident affecting it).

## 5. Privacy Incident and Breach Readiness

Extends the general lifecycle above for incidents involving personal data:

| Activity | Direction |
|---|---|
| Privacy-event detection | Detected through the same Section 1 lifecycle, with Privacy owner involvement from Triage onward for any personal-data-involving incident |
| Data categories affected | Identified from the personal-data inventory in [privacy-by-design-architecture.md, Section 3](privacy-by-design-architecture.md#3-personal-data-inventory) |
| Number of persons affected placeholder | Estimated during investigation — no specific reporting-threshold number is asserted as a legal trigger here |
| Minor-data involvement | Flagged with elevated priority immediately upon detection, per [minor-athlete-and-guardian-data-governance.md, Section 1](minor-athlete-and-guardian-data-governance.md#1-minor-athlete-privacy) |
| Medical-data involvement | Flagged with elevated priority immediately upon detection, per [medical-eligibility-finance-and-sensitive-data-controls.md](medical-eligibility-finance-and-sensitive-data-controls.md) |
| Evidence | Per Section 1's "Preserve evidence" stage |
| Containment | Per Section 4 |
| Access revocation | Per Section 4 |
| Data-sharing review | Any active data-sharing arrangement touching the affected data is reviewed for continued appropriateness during the incident |
| Legal or privacy-owner review | The Privacy owner (with legal input where available) assesses whether the incident meets any external notification threshold — **no specific legal notification deadline is prescribed here**, per working rule ("Do not prescribe legal notification deadlines without verified sources") |
| Notification-decision support | This documentation supports the decision process; it does not make the notification decision or assert a specific timeline |
| Communication approval | Any external communication about the incident is approved through the Communications lead and DepEd Leadership, never sent ad hoc |
| Recovery | Per Section 1 |
| Post-incident actions | Feed into [security-risk-register.md](security-risk-register.md) and [compliance-control-framework.md](compliance-control-framework.md) as control improvements |

## 6. Breach Notification Readiness (Not a Legal Determination)

PMMS's architecture is designed so that, **if** a breach-notification obligation is later confirmed to apply (a determination for Data Privacy and Legal Stakeholders, not this documentation), the platform can support it:

- The personal-data inventory (Section, [privacy-by-design-architecture.md](privacy-by-design-architecture.md)) identifies what data categories may be affected.
- The audit architecture (Section, [audit-and-security-event-architecture.md](audit-and-security-event-architecture.md)) supports determining scope and timeline.
- The classification model supports assessing severity/sensitivity of exposed data.
- This document's lifecycle supports timely internal escalation, a prerequisite for meeting any external deadline that may eventually apply.

**No specific notification deadline, authority, or threshold is asserted** — this section exists to establish readiness, not to make a compliance claim.

## 7. Post-Incident Review

Every declared incident receives a post-incident review producing: a timeline, root cause, what worked, what didn't, and specific control improvements — tracked to completion in [security-risk-register.md](security-risk-register.md) and, where relevant, a new or updated entry in [compliance-control-framework.md, Section 2](compliance-control-framework.md#2-control-catalog).

## 8. Open Questions

See [security-open-decisions.md](security-open-decisions.md) — notably the severity-matrix definition, whether a formal incident-response tabletop exercise is scheduled before Phase 0.7, and confirmation of any DepEd-specific incident-reporting obligation (tracked as [policy-source-registry.md, POL-11](policy-source-registry.md#registry)).
