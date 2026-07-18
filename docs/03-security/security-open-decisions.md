# PMMS Security, Privacy, Audit, Compliance, and Data Governance — Open Decisions

**Status:** Draft Complete — Pending Security, Privacy, Legal, Audit, Data Governance, and Engineering Validation
**Related:** [security-architecture.md](security-architecture.md) · [../02-data/data-open-decisions.md](../02-data/data-open-decisions.md) · [../01-architecture/runtime-open-decisions.md](../01-architecture/runtime-open-decisions.md) · [../01-architecture/access-open-decisions.md](../01-architecture/access-open-decisions.md)

This document tracks every unresolved Phase 0.6 decision using Decision ID prefix `SD-` (Security Decision), distinct from Phase 0.1's `OD-`, Phase 0.2's `DD-`, Phase 0.3's `AD-`, Phase 0.4's `RD-`, and Phase 0.5's `PD-` series. Each entry follows the established format: Question / Areas Affected / Why It Matters / Options / Recommended Direction / Evidence Required / Decision Owner / Target Phase / Status.

---

### SD-01 — MFA Enforcement Scope

- **Question:** Is MFA mandatory for all users, or only for privileged/high-integrity roles?
- **Areas affected:** [identity-authentication-and-session-security.md](identity-authentication-and-session-security.md), [authorization-and-privileged-access-assurance.md](authorization-and-privileged-access-assurance.md)
- **Why it matters:** Mandatory MFA reduces credential-theft risk but adds friction for low-privilege, high-volume roles (e.g., delegation encoders using shared devices).
- **Options:** (a) Mandatory for all; (b) Mandatory for privileged/high-integrity roles only; (c) Risk-based (device/location anomaly triggers).
- **Recommended direction:** Mandatory for privileged/high-integrity roles at minimum, with (b) as the launch baseline and (c) as a future enhancement.
- **Evidence required:** Security-owner risk assessment, usability testing with representative committee staff.
- **Decision owner:** Security owner
- **Target phase:** 0.7+ (implementation)
- **Status:** Open — mirrors [../01-architecture/access-open-decisions.md, AD-21](../01-architecture/access-open-decisions.md#ad-21--authentication-mechanism-selection-mfassorecovery)

### SD-02 — Session Timeout Values

- **Question:** What are the specific idle-timeout and absolute-timeout durations?
- **Areas affected:** [identity-authentication-and-session-security.md, Section 3](identity-authentication-and-session-security.md#3-session-security)
- **Why it matters:** Too short disrupts legitimate long-running workflows (e.g., a multi-hour scoring session); too long increases exposure from an unattended shared device.
- **Options:** Framework default (`SESSION_LIFETIME=120` minutes) as baseline; role-differentiated timeouts; kiosk/shared-device-specific shorter timeouts.
- **Recommended direction:** Framework default as launch baseline, with shorter timeouts for kiosk/shared-device contexts specifically.
- **Evidence required:** Operational input from committee staff on realistic session-length needs.
- **Decision owner:** Security owner + Application owner
- **Target phase:** 0.7+
- **Status:** Open

### SD-03 — Break-Glass Access Necessity

- **Question:** Is break-glass (emergency) access implemented at all?
- **Areas affected:** [authorization-and-privileged-access-assurance.md, Section 6](authorization-and-privileged-access-assurance.md#6-break-glass-access-governance)
- **Why it matters:** No recommended direction exists — this is the single most genuinely open access-governance question carried since Phase 0.3.
- **Options:** (a) Implement with strict governance; (b) Do not implement, rely on ordinary privileged-access escalation.
- **Recommended direction:** None — mirrors [../01-architecture/access-open-decisions.md, AD-10](../01-architecture/access-open-decisions.md#ad-10--break-glassemergency-access-necessity-and-policy-owner) exactly, genuinely unresolved.
- **Evidence required:** DepEd Leadership and Security owner joint decision on operational necessity.
- **Decision owner:** DepEd Leadership + Security owner
- **Target phase:** 0.7+
- **Status:** Open — no recommended direction, mirrors AD-10

### SD-04 — Support Impersonation Necessity

- **Question:** Is support impersonation implemented at all?
- **Areas affected:** [authorization-and-privileged-access-assurance.md, Section 5](authorization-and-privileged-access-assurance.md#5-support-impersonation-governance)
- **Why it matters:** Mirrors [../01-architecture/access-open-decisions.md, AD-09](../01-architecture/access-open-decisions.md#ad-09--support-impersonation-necessity-and-approval-authority) exactly.
- **Options:** (a) Implement with strict governance (SOD-11-bound); (b) Do not implement.
- **Recommended direction:** If implemented, bound absolutely by SOD-11; necessity itself remains open.
- **Evidence required:** Support-operations volume/pattern data once the platform is live.
- **Decision owner:** Security owner
- **Target phase:** 0.7+
- **Status:** Open — mirrors AD-09

### SD-05 — Malware-Scanning Service Selection

- **Question:** Which malware-scanning service/vendor is integrated?
- **Areas affected:** [file-object-storage-and-malware-security.md, Section 3](file-object-storage-and-malware-security.md#3-malware-scanning-architecture)
- **Why it matters:** Every file upload depends on this capability being available before objects leave quarantine.
- **Options:** Open-source (e.g., ClamAV-class) vs. commercial/cloud scanning service.
- **Recommended direction:** Vendor-neutral architecture already defined; specific selection deferred to implementation phase based on cost/deployment-topology fit.
- **Evidence required:** Deployment-topology decision (cloud/on-prem/hybrid, per [../00-product/phase-0.1-product-foundation.md, Section 17](../00-product/phase-0.1-product-foundation.md#17-deployment-model)).
- **Decision owner:** Infrastructure owner + Security owner
- **Target phase:** 0.7+
- **Status:** Open

### SD-06 — Field-Level Encryption Scope and Algorithm

- **Question:** Which specific fields receive field-level encryption, and with what algorithm?
- **Areas affected:** [cryptography-key-and-secret-management.md, Section 1](cryptography-key-and-secret-management.md#1-cryptographic-architecture)
- **Why it matters:** Directly mirrors the still-open Phase 0.5 question.
- **Options:** Framework-native encryption (Laravel's built-in encrypted casts) vs. a dedicated field-encryption library; scope limited to Highly Restricted fields vs. broader.
- **Recommended direction:** None finalized — mirrors [../02-data/data-open-decisions.md, PD-06/PD-07](../02-data/data-open-decisions.md#pd-06--field-level-encryption-candidate-list) exactly.
- **Evidence required:** Security-architect review once Phase 0.6 is validated.
- **Decision owner:** Security owner
- **Target phase:** 0.7+
- **Status:** Open — mirrors PD-06/PD-07

### SD-07 — Audit-Integrity Mechanism (Hash-Chaining or Signing)

- **Question:** Is hash-chaining or cryptographic signing adopted for audit-event tamper-evidence?
- **Areas affected:** [audit-and-security-event-architecture.md, Section 3](audit-and-security-event-architecture.md#3-audit-integrity)
- **Why it matters:** Determines whether "tamper-evident" becomes a verifiable, implemented guarantee or remains a structural-discipline-only posture.
- **Options:** (a) No additional mechanism, rely on append-only discipline + restricted access; (b) Hash-chaining; (c) Cryptographic signing.
- **Recommended direction:** Start with (a); evaluate (b)/(c) if a specific integrity concern or compliance requirement (once verified via [policy-source-registry.md](policy-source-registry.md)) justifies the added complexity.
- **Evidence required:** Verified policy requirement, or a specific identified threat justifying the investment.
- **Decision owner:** Security owner + Audit owner
- **Target phase:** 0.7+
- **Status:** Open

### SD-08 — Separate Audit Storage

- **Question:** Is audit-event storage physically separated from operational data storage?
- **Areas affected:** [audit-and-security-event-architecture.md, Section 3](audit-and-security-event-architecture.md#3-audit-integrity)
- **Why it matters:** Separation increases tamper-resistance and blast-radius containment but adds operational/query complexity.
- **Options:** Same database, dedicated tables vs. separate database instance.
- **Recommended direction:** Same database initially (simpler, still restricted-access); revisit if scale or a specific risk justifies separation.
- **Evidence required:** Operational volume data, per [../02-data/indexing-performance-and-capacity.md, Section 2](../02-data/indexing-performance-and-capacity.md#2-capacity-and-growth-categories) (`AuditEvent` flagged Very High volume).
- **Decision owner:** Infrastructure owner + Audit owner
- **Target phase:** 0.7+
- **Status:** Open

### SD-09 — Classification-Tier Formal Validation

- **Question:** Who formally validates the five-tier classification model and every field's assigned tier?
- **Areas affected:** [privacy-by-design-architecture.md](privacy-by-design-architecture.md), [medical-eligibility-finance-and-sensitive-data-controls.md](medical-eligibility-finance-and-sensitive-data-controls.md)
- **Why it matters:** Directly mirrors the still-open Phase 0.5 question — every access/encryption/logging/export rule in this package depends on it.
- **Options:** None — validation is required regardless of approach.
- **Recommended direction:** Formal review by Privacy owner + Security owner before Phase 0.7 implementation begins.
- **Evidence required:** Completed field-by-field classification review.
- **Decision owner:** Privacy owner + Security owner
- **Target phase:** 0.7 (should precede physical schema work)
- **Status:** Open — mirrors [../02-data/data-open-decisions.md, PD-08](../02-data/data-open-decisions.md#pd-08--formal-classification-tier-validation), high priority

### SD-10 — Guardian-Relationship Verification Mechanism

- **Question:** What specific mechanism verifies a claimed guardian relationship?
- **Areas affected:** [minor-athlete-and-guardian-data-governance.md, Section 2](minor-athlete-and-guardian-data-governance.md#2-guardian-data)
- **Why it matters:** Directly gates access to minor-athlete data on a guardian's behalf.
- **Options:** Delegation-representative-attested relationship (school-based) vs. a formal document-based verification vs. a hybrid.
- **Recommended direction:** Delegation-based attestation as the practical launch baseline, given PMMS's school-based delegation model — formal document verification for a future direct-guardian-access feature, if adopted.
- **Evidence required:** Confirmation of whether guardians ever get direct portal access (per [../00-product/open-decisions.md](../00-product/open-decisions.md)).
- **Decision owner:** Privacy owner + Domain owner (Participant Registry)
- **Target phase:** 0.7+
- **Status:** Open

### SD-11 — Photo/Media Publication Consent Model

- **Question:** Does photo/media publication of minors use an opt-out or affirmative-consent model?
- **Areas affected:** [minor-athlete-and-guardian-data-governance.md, Section 3](minor-athlete-and-guardian-data-governance.md#3-photo-and-media-publication-governance)
- **Why it matters:** Materially affects the registration workflow and the platform's public-media publication process.
- **Options:** (a) Affirmative consent required per athlete/guardian; (b) Opt-out available, default publication allowed for official meet coverage.
- **Recommended direction:** None — requires legal/policy input given the minor-data sensitivity.
- **Evidence required:** Legal/policy guidance, ideally citing [policy-source-registry.md, POL-02](policy-source-registry.md#registry) once verified.
- **Decision owner:** Privacy owner + DepEd Leadership
- **Target phase:** 0.7+
- **Status:** Open

### SD-12 — Policy-Source Verification

- **Question:** Which of the 13 placeholder entries in [policy-source-registry.md](policy-source-registry.md) correspond to real, citable DepEd/NPC/government policy sources?
- **Areas affected:** Every document in this package citing a policy source
- **Why it matters:** This is collectively the largest single compliance-readiness gap in Phase 0.6 — nearly every "requires legal/policy validation" note throughout this package resolves once the relevant registry entry is verified.
- **Options:** N/A — requires active sourcing, not a design choice.
- **Recommended direction:** Assign a named research/liaison responsibility to systematically pursue verification of POL-01 through POL-13, prioritizing POL-03 (records management), POL-04 (eligibility), and POL-05 (medical).
- **Evidence required:** Actual policy documents from DepEd/NPC/government sources.
- **Decision owner:** Audit owner + Privacy owner, with DepEd Leadership facilitation
- **Target phase:** 0.7+ (ongoing)
- **Status:** Open, high priority — blocks converting numerous `Recommended control` statements into verified compliance statements

### SD-13 — Risk-Scoring Methodology

- **Question:** What qualitative or quantitative methodology scores likelihood/impact in [security-risk-register.md](security-risk-register.md)?
- **Areas affected:** [security-risk-register.md](security-risk-register.md), [compliance-control-framework.md](compliance-control-framework.md)
- **Why it matters:** Without a consistent methodology, risk prioritization remains qualitative/ad hoc.
- **Options:** A simple qualitative matrix (Low/Medium/High/Critical) vs. a quantitative scoring model.
- **Recommended direction:** A simple qualitative matrix as the practical starting point, given the platform's current pre-implementation stage.
- **Evidence required:** Security-owner facilitated risk-assessment workshop.
- **Decision owner:** Security owner
- **Target phase:** 0.7
- **Status:** Open

### SD-14 — Incident Severity Matrix

- **Question:** What specific severity levels and criteria classify a declared incident?
- **Areas affected:** [incident-response-and-breach-readiness.md, Section 2](incident-response-and-breach-readiness.md#2-incident-categories-and-severity)
- **Why it matters:** Determines escalation speed and notification-decision urgency.
- **Options:** A 3-tier (Low/Medium/High) vs. 4-tier (adding Critical) severity scale.
- **Recommended direction:** 4-tier, aligning with common industry practice, finalized alongside SD-13's risk methodology for consistency.
- **Evidence required:** Security-owner + Incident-commander joint definition.
- **Decision owner:** Security owner
- **Target phase:** 0.7
- **Status:** Open

### SD-15 — Penetration Testing Scope, Vendor, and Timing

- **Question:** When and by whom is the first penetration test/security assessment performed?
- **Areas affected:** [security-testing-and-assurance.md, "Penetration Testing Readiness"](security-testing-and-assurance.md#penetration-testing-readiness)
- **Why it matters:** A meaningful penetration test requires a testable implementation to exist — timing depends on the implementation roadmap beyond Phase 0.6.
- **Options:** Pre-launch only vs. pre-launch plus periodic post-launch.
- **Recommended direction:** Both — a pre-launch assessment as a release gate, periodic post-launch assessments thereafter.
- **Evidence required:** Implementation roadmap/timeline once Phase 0.7+ begins.
- **Decision owner:** Security owner + DepEd Leadership (budget approval)
- **Target phase:** Pre-launch (post-0.7)
- **Status:** Open

### SD-16 — Static-Analysis Security Scanner Selection

- **Question:** Which security-focused static-analysis tool supplements the existing Larastan/Pint code-quality tooling?
- **Areas affected:** [secure-development-lifecycle.md, Section 3](secure-development-lifecycle.md#3-vulnerability-management)
- **Why it matters:** Larastan/Pint address code quality/types, not security-specific patterns (e.g., injection, hardcoded secrets).
- **Options:** PHP-specific SAST tools vs. general-purpose multi-language scanners.
- **Recommended direction:** Deferred to CI/CD establishment; a specific tool selection is a low-risk, easily-changed implementation decision.
- **Evidence required:** CI/CD platform decision (a prerequisite).
- **Decision owner:** Application owner
- **Target phase:** 0.7+ (with CI/CD introduction)
- **Status:** Open

### SD-17 — Certificate Pinning Commitment

- **Question:** Does the Flutter mobile app implement TLS certificate pinning?
- **Areas affected:** [mobile-device-and-offline-security.md, Section 1](mobile-device-and-offline-security.md#1-flutter-security)
- **Why it matters:** Pinning increases man-in-the-middle resistance on untrusted venue networks but adds operational complexity for certificate rotation.
- **Options:** (a) Implement pinning; (b) Rely on OS-standard TLS trust chain only.
- **Recommended direction:** Evaluate during Flutter implementation, weighing venue-network risk (a named constraint) against certificate-rotation operational burden.
- **Evidence required:** Mobile-security-tooling capability assessment.
- **Decision owner:** Application owner + Security owner
- **Target phase:** 0.7+
- **Status:** Open

### SD-18 — Rooted/Jailbroken Device Response Policy

- **Question:** Does a detected rooted/jailbroken device get blocked, warned, or elevated-audit-flagged?
- **Areas affected:** [mobile-device-and-offline-security.md, Section 1](mobile-device-and-offline-security.md#1-flutter-security)
- **Why it matters:** Blocking may exclude legitimate users on modified-but-otherwise-fine devices in resource-constrained settings (a named PMMS constraint); permitting increases risk exposure.
- **Options:** (a) Hard block; (b) Warn and allow; (c) Allow with elevated audit flag.
- **Recommended direction:** (c) as a balance, given PMMS's device-availability constraints — reconsider if evidence of actual abuse emerges.
- **Evidence required:** None yet — a policy call informed by the device-availability constraint.
- **Decision owner:** Security owner
- **Target phase:** 0.7+
- **Status:** Open

### SD-19 — Offline Event-Signing Adoption

- **Question:** Are offline-captured records cryptographically signed at the point of capture?
- **Areas affected:** [mobile-device-and-offline-security.md, Section 3](mobile-device-and-offline-security.md#3-offline-security)
- **Why it matters:** Would strengthen tamper-evidence for offline-originated high-integrity data (scores, access scans) at the cost of implementation complexity and mobile-side key management.
- **Options:** (a) Adopt; (b) Rely on server-side re-validation alone (current baseline).
- **Recommended direction:** (b) as the launch baseline; revisit if a specific offline-tampering incident or risk-assessment finding justifies (a).
- **Evidence required:** Pilot-meet operational experience.
- **Decision owner:** Security owner
- **Target phase:** 0.7+ (deferred pending evidence)
- **Status:** Open

### SD-20 — AI Use-Case Approval for Initial Implementation

- **Question:** Which specific AI-assisted use cases (per [ai-security-privacy-and-governance.md](ai-security-privacy-and-governance.md)) are approved for the first implementation phase?
- **Areas affected:** [ai-security-privacy-and-governance.md](ai-security-privacy-and-governance.md)
- **Why it matters:** Mirrors [../00-product/open-decisions.md, OD-29](../00-product/open-decisions.md#od-29--ai-service-restrictions) — the underlying policy remains unresolved.
- **Options:** None yet defined — requires product/policy scoping.
- **Recommended direction:** Start with the lowest-risk candidates (e.g., drafting communications, flagging incomplete submissions) once OD-29 resolves.
- **Evidence required:** OD-29 resolution.
- **Decision owner:** Security owner + Privacy owner + Product owner
- **Target phase:** 0.7+, blocked on OD-29
- **Status:** Open — blocked

### SD-21 — Vendor Security-Questionnaire Template Adoption

- **Question:** Is a standardized vendor security-questionnaire template adopted for [vendor-and-third-party-risk.md, Section 1](vendor-and-third-party-risk.md#1-vendor-assessment-areas)?
- **Areas affected:** [vendor-and-third-party-risk.md](vendor-and-third-party-risk.md)
- **Why it matters:** A consistent template speeds assessment and improves comparability across vendors.
- **Options:** Adopt an existing industry-standard questionnaire (e.g., a CAIQ-style template) vs. a PMMS-custom-built questionnaire based on Section 1's assessment areas.
- **Recommended direction:** Build a lightweight PMMS-custom questionnaire from Section 1's areas initially; adopt an industry-standard template if vendor volume grows.
- **Evidence required:** None yet — a practical implementation-phase task.
- **Decision owner:** Vendor manager
- **Target phase:** First vendor-approval request
- **Status:** Open

### SD-22 — Deployment Topology (Cross-Reference)

- **Question:** Cloud, on-premises, or hybrid deployment?
- **Areas affected:** [infrastructure-runtime-and-network-security.md, Section 5](infrastructure-runtime-and-network-security.md#5-network-and-runtime-security)
- **Why it matters:** Materially affects network-security configuration, backup topology, and several vendor-selection decisions (SD-05, SD-21).
- **Options:** Per [../00-product/phase-0.1-product-foundation.md, Section 17](../00-product/phase-0.1-product-foundation.md#17-deployment-model) — unchanged since Phase 0.1.
- **Recommended direction:** Unresolved since Phase 0.1; not newly decided here.
- **Evidence required:** DepEd infrastructure/budget decision.
- **Decision owner:** DepEd Leadership + Infrastructure owner
- **Target phase:** Pre-0.7 (blocking infrastructure design)
- **Status:** Open — carried unchanged from Phase 0.1

### SD-23 — Retention Periods (Cross-Reference)

- **Question:** What are the actual numeric retention periods for every category in [retention-disposal-and-legal-hold-governance.md](retention-disposal-and-legal-hold-governance.md)?
- **Areas affected:** Every retention-referencing document across Phase 0.5 and 0.6
- **Why it matters:** The single largest blocking dependency across both phases.
- **Options:** N/A — requires DepEd records-management authority input.
- **Recommended direction:** None — mirrors [../02-data/data-open-decisions.md, PD-04](../02-data/data-open-decisions.md#pd-04--retention-periods-8-categories) exactly.
- **Evidence required:** DepEd records-management policy (tracked as [policy-source-registry.md, POL-03](policy-source-registry.md#registry)).
- **Decision owner:** Records owner + DepEd Leadership
- **Target phase:** 0.7+, blocking
- **Status:** Open — blocking, mirrors PD-04

### SD-24 — RPO/RTO Numeric Targets (Cross-Reference)

- **Question:** What are the numeric Recovery Point/Time Objectives?
- **Areas affected:** [infrastructure-runtime-and-network-security.md](infrastructure-runtime-and-network-security.md), [../02-data/backup-restore-and-data-recovery.md](../02-data/backup-restore-and-data-recovery.md)
- **Why it matters:** Mirrors [../01-architecture/runtime-open-decisions.md, RD-18](../01-architecture/runtime-open-decisions.md#rd-18--rporto-targets) and [../02-data/data-open-decisions.md, PD-23](../02-data/data-open-decisions.md#pd-23--rporto-numeric-targets) exactly.
- **Options:** N/A — requires DepEd institutional-record requirements and infrastructure-budget input.
- **Recommended direction:** None — genuinely open, carried across three phases now.
- **Evidence required:** DepEd institutional requirements, infrastructure capability assessment.
- **Decision owner:** Infrastructure owner + DepEd Leadership
- **Target phase:** Pre-launch
- **Status:** Open — mirrors RD-18/PD-23

### SD-25 — Access-Review and Production-Access Review Cadence

- **Question:** What specific interval governs each review type in [access-review-production-and-support-controls.md](access-review-production-and-support-controls.md) and [../01-architecture/access-review-and-revocation.md](../01-architecture/access-review-and-revocation.md)?
- **Areas affected:** [access-review-production-and-support-controls.md](access-review-production-and-support-controls.md)
- **Why it matters:** Mirrors the still-unresolved Phase 0.3 review-interval question.
- **Options:** Per-meet-cycle for meet-scoped roles; quarterly/semi-annual for platform-level roles.
- **Recommended direction:** Per-meet-cycle minimum for sensitive/meet-scoped roles (aligning naturally with PMMS's operating rhythm); a fixed calendar cadence for platform-level roles, exact interval TBD.
- **Evidence required:** Operational rhythm data once the platform has run through at least one full meet cycle.
- **Decision owner:** Security owner
- **Target phase:** 0.7+
- **Status:** Open — mirrors unresolved Phase 0.3 review-interval questions

---

## Summary of Blocking / High-Priority Security Decisions

| Decision | Why It Blocks |
|---|---|
| **SD-09** | Classification-tier validation underlies nearly every access/encryption/logging/export control in this package |
| **SD-12** | Policy-source verification is the prerequisite for converting the majority of `Recommended control` statements into anything closer to a compliance-supportable claim |
| **SD-23** | Retention periods block finalizing disposal automation and several audit/backup-retention decisions |
| **SD-24** | RPO/RTO targets block finalizing backup frequency and disaster-recovery provisioning |
| **SD-20** | AI use-case approval is blocked directly on the still-unresolved Phase 0.1 OD-29 |
| **SD-03 / SD-04** | Break-glass and impersonation necessity remain genuinely undecided, carried unchanged from Phase 0.3 (AD-10/AD-09) |
