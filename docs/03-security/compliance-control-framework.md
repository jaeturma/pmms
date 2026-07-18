# PMMS Compliance-Readiness and Control Framework

**Status:** Draft Complete — Pending Security, Privacy, Legal, Audit, Data Governance, and Engineering Validation
**Related:** [policy-source-registry.md](policy-source-registry.md) · [security-architecture.md, Section 6](security-architecture.md#6-compliance-language-discipline) · [security-risk-register.md](security-risk-register.md)

This document creates a control-mapping structure without asserting compliance. **PMMS is not stated to be compliant with any law, regulation, or standard anywhere in this document** — every framework reference is a `Candidate reference requiring validation`, per working rules 10–14.

---

## 1. Framework Candidates

None of the frameworks below is verified as an official, binding requirement for PMMS in this repository. Each is listed as a **candidate reference requiring validation** — useful for structuring the control catalog, not as an asserted compliance target.

| Framework | Relevance to PMMS | Status |
|---|---|---|
| Philippine Data Privacy Act (RA 10173) and its implementing rules | Personal-data-handling obligations for a Philippine government-adjacent platform | Candidate reference requiring validation — no verified official source document is present in this repository |
| National Privacy Commission (NPC) guidance | Interpretive guidance under the Data Privacy Act | Candidate reference requiring validation |
| Relevant DepEd orders and memoranda | Institution-specific policy (records management, child protection, ICT governance) | Candidate reference requiring validation — see [policy-source-registry.md](policy-source-registry.md) placeholders |
| Records-management requirements | Government records-retention obligations | Candidate reference requiring validation |
| Government cybersecurity guidance | Philippine government cybersecurity baseline expectations | Candidate reference requiring validation |
| DICT-related guidance | Department of Information and Communications Technology standards | Candidate reference requiring validation |
| OWASP Application Security Verification Standard (ASVS) | Web-application security control baseline | Candidate reference requiring validation — a widely-used industry standard, not confirmed as a PMMS-binding requirement |
| OWASP API Security Top 10 | API-specific risk baseline | Candidate reference requiring validation |
| OWASP Mobile Application Security guidance | Flutter-app security baseline | Candidate reference requiring validation |
| CIS Controls | General cybersecurity control baseline | Candidate reference requiring validation |
| ISO/IEC 27001 control concepts | Information-security-management-system control structure | Candidate reference requiring validation |
| ISO/IEC 27701 privacy concepts | Privacy-information-management extension to ISO 27001 | Candidate reference requiring validation |
| NIST Cybersecurity Framework | US-origin cybersecurity risk-management framework | Candidate reference requiring validation |

**No framework above is treated as binding.** Where this documentation's controls happen to align with a framework's structure (e.g., OWASP-style application-security categories in [application-api-and-client-security.md](application-api-and-client-security.md)), that alignment is coincidental to good practice, not a claim of certification or formal adoption.

## 2. Control Catalog

The catalog below prioritizes high-risk and foundational controls — it is **not exhaustive**, per the working instructions ("do not attempt to define every possible control"). Each entry uses Control ID prefix `CTL-`.

| Control ID | Domain | Control Objective | Control Statement | Risk Addressed | Components | Owner | Evidence | Test Method | Frequency | Policy Source | Phase | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| CTL-01 | Authentication | Prevent account takeover | Every account requires a unique credential; MFA is available and enforceable for privileged/high-integrity roles | Credential theft, brute force | Fortify, User Accounts | Security owner | Auth logs, config review | Manual + automated test | Continuous | TBD | 0.7+ | Candidate |
| CTL-02 | Authorization | Enforce least privilege | Every action checks Permission + Scope + Assignment + Resource State + SoD before proceeding | Privilege escalation, unauthorized access | Authorization decision model | Security owner | Policy test coverage | Automated test | Continuous | [../01-architecture/authorization-decision-model.md](../01-architecture/authorization-decision-model.md) | 0.7+ | Candidate |
| CTL-03 | Separation of duties | Prevent self-approval of high-integrity actions | The 11 SoD conflicts are structurally blocked or audit-detectable | Fraud, integrity failure | Scoring, Eligibility, Results, Tally, Finance | Data owners | SoD test coverage | Automated + manual review | Continuous | [../01-architecture/separation-of-duties-matrix.md](../01-architecture/separation-of-duties-matrix.md) | 0.7+ | Candidate |
| CTL-04 | Audit | Ensure accountability for consequential actions | Every consequential action produces an append-only audit event with actor, action, target, time, reason | Repudiation, undetected misuse | Audit and Compliance (BC-32) | Audit owner | Audit-event completeness test | Automated test | Continuous | [audit-and-security-event-architecture.md](audit-and-security-event-architecture.md) | 0.7+ | Candidate |
| CTL-05 | Data protection | Protect Highly Restricted data at rest | Field/object-level encryption candidates applied to the highest-sensitivity data categories | Data breach impact | MySQL, MinIO | Security owner | Encryption configuration review | Manual review | Per release | [cryptography-key-and-secret-management.md](cryptography-key-and-secret-management.md) | 0.7+ | Candidate |
| CTL-06 | Data protection | Protect data in transit | TLS enforced for all network traffic beyond Local environment | Interception | All runtime components | Infrastructure owner | TLS configuration scan | Automated scan | Continuous | [infrastructure-runtime-and-network-security.md](infrastructure-runtime-and-network-security.md) | 0.7+ | Candidate |
| CTL-07 | Secret management | Prevent secret disclosure | No secret is committed to source control or embedded in a client application | Credential compromise | Source control, CI/CD, mobile/web clients | Infrastructure owner | Secret-scan results | Automated scan | Continuous (CI) | [cryptography-key-and-secret-management.md](cryptography-key-and-secret-management.md) | 0.7+ | Candidate |
| CTL-08 | File security | Prevent malware distribution | Every uploaded file is quarantined and scanned before consumer access | Malware, ransomware | Document and Records (BC-30), MinIO | Security owner | Scan-coverage test | Automated test | Continuous | [file-object-storage-and-malware-security.md](file-object-storage-and-malware-security.md) | 0.7+ | Candidate |
| CTL-09 | Privacy | Limit personal-data exposure in public output | Public projections are filtered at build time to Public-tier data only | Privacy violation, minor-data exposure | Public Information (BC-29) | Privacy owner | Projection-filter test | Automated test | Continuous | [privacy-by-design-architecture.md](privacy-by-design-architecture.md) | 0.7+ | Candidate |
| CTL-10 | Privacy | Protect minor-athlete data | Enhanced controls (Section, [minor-athlete-and-guardian-data-governance.md](minor-athlete-and-guardian-data-governance.md)) apply to every minor-athlete record | Child-data exposure | Participant Registry (BC-07) | Privacy owner | Field-level exposure review | Manual review | Per release | [minor-athlete-and-guardian-data-governance.md](minor-athlete-and-guardian-data-governance.md) | 0.7+ | Candidate |
| CTL-11 | Offline security | Prevent unauthorized offline finalization | Eligibility approval, result certification, protest resolution, medal tally certification, and other named actions never finalize offline | Data-integrity bypass | Flutter clients, Offline sync | Security owner | Offline-abuse test scenarios | Automated + manual test | Continuous | [mobile-device-and-offline-security.md](mobile-device-and-offline-security.md) | 0.7+ | Candidate |
| CTL-12 | Device security | Prevent compromised-device abuse | Device credentials are revocable, purpose-restricted, and independent of operator identity | Device compromise, credential misuse | Devices, scanners | Infrastructure owner | Device-revocation test | Manual + automated test | Continuous | [mobile-device-and-offline-security.md](mobile-device-and-offline-security.md) | 0.7+ | Candidate |
| CTL-13 | Privileged access | Prevent standing excessive privilege | Privileged categories require named accounts, MFA, time limitation, and approval | Insider threat, credential misuse | Platform/Security administration | Security owner | Privileged-access review log | Manual review | Periodic (TBD) | [authorization-and-privileged-access-assurance.md](authorization-and-privileged-access-assurance.md) | 0.7+ | Candidate |
| CTL-14 | Incident response | Ensure timely incident containment | A documented lifecycle (Detect → Triage → Contain → ... → Improve) governs every declared incident | Extended breach impact | Platform-wide | Incident commander | Incident-response exercise records | Tabletop exercise | Periodic (TBD) | [incident-response-and-breach-readiness.md](incident-response-and-breach-readiness.md) | 0.7+ | Candidate |
| CTL-15 | Vulnerability management | Reduce exploitable dependency risk | Dependency and code scanning run continuously; findings are triaged by severity | Supply-chain compromise, known-vulnerability exploitation | Composer, npm, Flutter dependencies | Application owner | Scan results, remediation tracking | Automated scan | Continuous (CI) | [secure-development-lifecycle.md](secure-development-lifecycle.md) | 0.7+ | Candidate |
| CTL-16 | AI governance | Prevent autonomous high-integrity AI action | AI-assisted features never autonomously perform the Section 3 prohibited actions | AI misuse, unauthorized automated decision | AI services | Security + Privacy owners | AI-action audit review | Manual review + automated test | Continuous | [ai-security-privacy-and-governance.md](ai-security-privacy-and-governance.md) | 0.7+ | Candidate |
| CTL-17 | Backup and recovery | Ensure recoverability of high-integrity data | The "Highest" backup-priority tier is backed up and periodically restore-tested | Data loss, ransomware | MySQL, MinIO | Infrastructure owner | Restore-test results | Restore drill | Periodic (TBD) | [../02-data/backup-restore-and-data-recovery.md](../02-data/backup-restore-and-data-recovery.md) | 0.7+ | Candidate |
| CTL-18 | Vendor risk | Prevent unassessed third-party data exposure | No vendor receives PMMS data without a completed risk assessment | Third-party breach | External services | Vendor manager | Vendor assessment records | Manual review | Per vendor onboarding | [vendor-and-third-party-risk.md](vendor-and-third-party-risk.md) | 0.7+ | Candidate |

**Control status legend:** `Candidate` = documented here, not yet implemented or tested. No control in this catalog is asserted as implemented, tested, or effective — Phase 0.6 is documentation, not implementation, per the phase's own working rules.

## 3. Control Prioritization

The 18 controls above are prioritized by their direct traceability to PMMS's highest-stakes risks: high-integrity data tampering (CTL-02, CTL-03, CTL-04), minor/sensitive-data exposure (CTL-09, CTL-10), and platform-availability/recoverability (CTL-17). Full risk traceability: [security-risk-register.md](security-risk-register.md).

## 4. Open Questions

See [security-open-decisions.md](security-open-decisions.md) — notably which compliance framework(s), if any, DepEd formally requires PMMS to target, and the control-testing cadence for each catalog entry above.
