# PMMS Security Architecture

**Status:** Draft Complete — Pending Security, Privacy, Legal, Audit, Data Governance, and Engineering Validation
**Related:** [../01-architecture/phase-0.3-access-and-assignment-architecture.md](../01-architecture/phase-0.3-access-and-assignment-architecture.md) · [../01-architecture/runtime-security-architecture.md](../01-architecture/runtime-security-architecture.md) · [threat-model.md](threat-model.md) · [trust-boundaries-and-attack-surface.md](trust-boundaries-and-attack-surface.md)

This document defines PMMS's security objectives, principles, governance model, and control-domain model — the foundation every other Phase 0.6 document builds on. **No security package, middleware, policy class, or configuration is created here.** Every control described is a **control candidate** requiring engineering implementation and, where noted, legal/policy validation — nothing here is asserted as an implemented or compliant control.

---

## 1. Security Objectives

| Objective | Meaning for PMMS |
|---|---|
| Confidentiality | Protected data (eligibility, medical, guardian, finance, audit, credentials) is visible only to authorized identities under the Phase 0.3 authorization model |
| Integrity | Official scores, results, protests, medal tally, eligibility decisions, and accreditation records cannot be silently altered — restated from [../02-data/high-integrity-data-model.md](../02-data/high-integrity-data-model.md) |
| Availability | Scoring and access-validation capability survives connectivity loss and traffic spikes (public result announcements) without degrading — restated from [../01-architecture/resilience-performance-and-scaling.md](../01-architecture/resilience-performance-and-scaling.md) |
| Authenticity | Every action is attributable to a genuinely authenticated identity (human, device, or service) — never an ambiguous or shared credential |
| Accountability | Every consequential action has a named actor, a timestamp, and a discoverable audit trail |
| Traceability | A certified result, medal award, or eligibility decision can be traced back through every version and approval that produced it |
| Non-repudiation readiness | Actors cannot plausibly deny performing an action the audit trail attributes to them — a design goal, not a cryptographic guarantee unless a specific signing mechanism is later approved |
| Privacy | Personal data — especially minor-athlete, guardian, medical, and eligibility data — is collected, used, and disclosed only as necessary and authorized |
| Resilience | The platform degrades gracefully under partial failure (venue connectivity loss, a compromised device, a failed dependency) rather than failing unsafely |
| Recoverability | Backups, audit trails, and versioned records allow the platform to recover from data loss or a security incident without losing institutional trust |
| Least privilege | Every identity — human, device, or service — holds the minimum access its role genuinely requires, never a convenience-driven broader grant |
| Separation of duties | High-integrity actions structurally prevent the same individual from both initiating and approving the same transaction, per [../01-architecture/separation-of-duties-matrix.md](../01-architecture/separation-of-duties-matrix.md) |
| Safe public disclosure | Public projections expose only what Phase 0.5's classification model marks Public, never a byproduct of an incomplete privacy filter |
| Secure offline operation | Offline-capable devices operate within a bounded trust window and never finalize a high-integrity action disconnected |
| Controlled AI assistance | Every AI-assisted feature operates within the intersection of its own scope and the requesting user's authority, never autonomously deciding a consequential outcome |
| Commercial product trustworthiness | Security and privacy are engineered as a first-class product capability DepEd can rely on across meet cycles, not a retrofit |

## 2. Security Principles

1. **Deny by default** — every access decision starts from no access; a grant must be explicit and traceable.
2. **Least privilege** — every identity holds the minimum scope, role, and permission set its function requires.
3. **Zero implicit trust** — no request is trusted merely because it originates from an internal network, a known device, or a prior successful authentication; every request is evaluated on its own merits.
4. **Explicit scope** — sensitive access always names the organization/meet/committee/domain scope it applies to, never a blanket grant.
5. **Strong separation of duties** — structurally enforced wherever feasible for the 11 SOD-matrix conflicts, audit-detectable at minimum where structural enforcement is not yet feasible.
6. **Defense in depth** — no single control is trusted as sufficient; authorization is checked at the controller, the application/domain layer, the query-filter layer, and the file/broadcast/queue boundary.
7. **Secure by default** — a newly introduced feature, role, or integration starts with no access and no data exposure until deliberately configured otherwise.
8. **Privacy by design** — data minimization, purpose limitation, and classification-aware handling are built into the data model (Phase 0.5), not layered on afterward.
9. **Data minimization** — collect, replicate, log, and export only what a specific workflow requires.
10. **Purpose limitation** — data collected for one purpose (e.g., eligibility review) is not repurposed for another (e.g., marketing, unrelated analytics) without a new, explicit basis.
11. **Fail safely** — a failure in an authorization check, encryption operation, or validation step denies the action; it never silently falls back to an unprotected path.
12. **Complete mediation** — every access to a protected resource is checked every time, never cached as a permanent grant for a session's lifetime.
13. **Server-side authority** — the frontend (React/Inertia, Flutter) is never the security boundary; every sensitive check is enforced server-side regardless of what the UI shows or hides.
14. **No security through obscurity** — security depends on genuine controls (authentication, authorization, encryption), never on an attacker's presumed unfamiliarity with the system.
15. **Tamper-evident history** — audit and high-integrity records are structured so unauthorized alteration is detectable, restated from [../02-data/audit-and-security-data-architecture.md](../02-data/audit-and-security-data-architecture.md).
16. **Secure recovery** — backup, restore, and disaster-recovery processes themselves respect classification and least-privilege, never becoming a bypass path around normal controls.
17. **Minimal external data sharing** — no external service, integration, or AI provider receives more data than its specific approved purpose requires.
18. **Human accountability for consequential decisions** — eligibility approval, result certification, protest resolution, medal certification, and medical/security decisions are always human-owned actions with a named, authenticated actor.
19. **AI advisory-only operation** — AI-assisted features draft, detect, summarize, or recommend; they never autonomously finalize a high-integrity outcome (Section 58 of the main document; full detail in [ai-security-privacy-and-governance.md](ai-security-privacy-and-governance.md)).
20. **Continuous verification and review** — access, controls, and risk are periodically reviewed rather than assumed correct once configured (see [access-review-production-and-support-controls.md](access-review-production-and-support-controls.md)).

## 3. Security Governance Model

### Candidate Governance Roles (Not Named Individuals)

| Governance Role | Responsibility |
|---|---|
| Product owner | Overall product direction and priority, per [../00-product/README.md](../00-product/README.md) |
| System owner | Overall platform operational accountability |
| Security owner | Security architecture, control effectiveness, incident command escalation point |
| Privacy / data-protection owner | Privacy-by-design compliance readiness, data-subject-rights readiness, minor-data protection |
| Data owner | Per bounded context, per [../02-data/persistence-ownership-map.md](../02-data/persistence-ownership-map.md) — authoritative decision-maker for their owned data |
| Data steward | Day-to-day data-quality and classification correctness within an owned domain |
| Application owner | Laravel/React/Flutter application security and secure-development-lifecycle adherence |
| Infrastructure owner | Database, Redis, MinIO, Reverb, queue, and network security configuration (future DevOps phase) |
| Audit owner | Audit-architecture integrity, audit-event completeness, audit-access governance |
| Incident commander | Leads a declared security or privacy incident through the lifecycle in [incident-response-and-breach-readiness.md](incident-response-and-breach-readiness.md) |
| Committee business owner | Business-process accountability for a specific committee's data and workflows |
| Vendor manager | Third-party/vendor risk assessment and ongoing oversight |
| Risk owner | Accepts, mitigates, transfers, or escalates a specific risk-register entry |
| Control owner | Accountable for a specific control candidate's implementation and evidence |

No names are assigned in this documentation — every role above is "to be identified" per [../00-product/stakeholder-register.md](../00-product/stakeholder-register.md).

### Decision Rights

| Decision | Rights Holder (role, not named) |
|---|---|
| Security architecture | Security owner, with software architect concurrence |
| Access approval | Resource/domain owner, following the assignment model in [../01-architecture/assignment-model.md](../01-architecture/assignment-model.md) |
| Sensitive data access | Data owner + Privacy owner concurrence for Restricted/Highly Restricted tiers |
| Emergency access | Security owner, per the (currently unresolved) break-glass governance in Section 13 |
| Incident declaration | Incident commander, or any staff member escalating to the Security owner |
| External data sharing | Privacy owner + Data owner + (where applicable) DepEd Leadership |
| Retention | Data owner, bound by [../02-data/retention-archival-and-disposal.md](../02-data/retention-archival-and-disposal.md) and eventual DepEd records-management authority |
| Data disposal | Data owner, with Audit owner visibility |
| Vendor approval | Vendor manager + Security owner + Privacy owner |
| AI-service approval | Security owner + Privacy owner, per [ai-security-privacy-and-governance.md](ai-security-privacy-and-governance.md) |
| Production access | Infrastructure owner + Security owner |
| Risk acceptance | Risk owner, with Security owner visibility for any risk above a low severity placeholder |
| Compliance evidence approval | Audit owner + Security owner |

## 4. Control-Domain Model

| Domain | Scope | Primary Document |
|---|---|---|
| Governance | Roles, decision rights, policy sources | This document, [data-governance-operating-model.md](data-governance-operating-model.md), [policy-source-registry.md](policy-source-registry.md) |
| Asset management | Identifying what needs protecting | [threat-model.md](threat-model.md) (protected assets), [../02-data/persistence-ownership-map.md](../02-data/persistence-ownership-map.md) |
| Identity and access | Human/device/service identity, authorization | [identity-authentication-and-session-security.md](identity-authentication-and-session-security.md), [authorization-and-privileged-access-assurance.md](authorization-and-privileged-access-assurance.md) |
| Authentication | Login, MFA, sessions, tokens | [identity-authentication-and-session-security.md](identity-authentication-and-session-security.md) |
| Authorization | Permission/scope/assignment enforcement | [authorization-and-privileged-access-assurance.md](authorization-and-privileged-access-assurance.md) |
| Privileged access | Administrative, support, break-glass | [authorization-and-privileged-access-assurance.md](authorization-and-privileged-access-assurance.md), [access-review-production-and-support-controls.md](access-review-production-and-support-controls.md) |
| Cryptography | Encryption, hashing, keys, secrets | [cryptography-key-and-secret-management.md](cryptography-key-and-secret-management.md) |
| Application security | Input validation, injection, business-logic abuse | [application-api-and-client-security.md](application-api-and-client-security.md) |
| API security | Authentication, rate limiting, object/function-level authorization | [application-api-and-client-security.md](application-api-and-client-security.md) |
| Mobile and device security | Flutter, scanners, offline devices | [mobile-device-and-offline-security.md](mobile-device-and-offline-security.md) |
| Infrastructure security | Network, runtime, MySQL/Redis/MinIO/Reverb | [infrastructure-runtime-and-network-security.md](infrastructure-runtime-and-network-security.md) |
| Data security | File upload, malware, storage | [file-object-storage-and-malware-security.md](file-object-storage-and-malware-security.md) |
| Privacy | Privacy-by-design, minor/guardian/medical/eligibility/finance data | [privacy-by-design-architecture.md](privacy-by-design-architecture.md) and its family of documents |
| Audit and monitoring | Audit architecture, security events, logging | [audit-and-security-event-architecture.md](audit-and-security-event-architecture.md), [security-metrics-monitoring-and-reporting.md](security-metrics-monitoring-and-reporting.md) |
| Incident response | Security and privacy incident lifecycle | [incident-response-and-breach-readiness.md](incident-response-and-breach-readiness.md) |
| Business continuity | Backup/DR security | [infrastructure-runtime-and-network-security.md](infrastructure-runtime-and-network-security.md), restating [../02-data/backup-restore-and-data-recovery.md](../02-data/backup-restore-and-data-recovery.md) |
| Vendor risk | Third-party assessment | [vendor-and-third-party-risk.md](vendor-and-third-party-risk.md) |
| Secure development | SDLC, coding standards, testing | [secure-development-lifecycle.md](secure-development-lifecycle.md), [security-testing-and-assurance.md](security-testing-and-assurance.md) |
| Vulnerability management | Dependency and code scanning | [secure-development-lifecycle.md](secure-development-lifecycle.md) |
| Compliance evidence | Control mapping, policy sources | [compliance-control-framework.md](compliance-control-framework.md), [policy-source-registry.md](policy-source-registry.md) |
| Data governance | Ownership, quality, lifecycle | [data-governance-operating-model.md](data-governance-operating-model.md) |

## 5. Relationship to Prior Phases

This document does not redefine anything already decided:

- **Phase 0.3 authorization architecture is preserved unchanged.** The Authority formula (`Permission + Scope + Assignment + Resource State + Data Classification + Separation of Duties + Device Trust + Time Validity + Explicit Restrictions`) remains the governing model; Phase 0.6 adds assurance, monitoring, and privileged-access governance around it.
- **Phase 0.4 runtime boundaries are preserved unchanged.** Redis-never-a-system-of-record, MinIO-as-infrastructure, server-side authorization enforcement, and the six event types all carry forward.
- **Phase 0.5 source-of-truth, classification, retention, and lifecycle rules are preserved unchanged.** The five-tier classification model, append-only high-integrity history, and placeholder-only retention periods are the foundation this phase's privacy and audit architecture builds on.

## 6. Compliance Language Discipline

Per working rules 10–14, every statement in this Phase 0.6 package uses one of these framings, never an unsupported claim of legal compliance:

- **Legal requirement** — a specific, cited law or regulation, only where the repository contains verified official source text.
- **Policy requirement** — a specific DepEd or institutional policy, only where the repository contains verified official source text.
- **Contractual requirement** — a specific agreement obligation, only where such an agreement is provided.
- **Recommended control** — a security/privacy practice this documentation proposes, not yet validated by an authority.
- **Architectural safeguard** — a structural design choice (e.g., append-only audit) that supports a broader objective without itself being a compliance claim.
- **Open decision** — an explicitly unresolved question requiring a named authority's input, tracked in [security-open-decisions.md](security-open-decisions.md).

No document in this package states that PMMS "is compliant with" the Data Privacy Act, DepEd policy, NPC rules, DICT standards, ISO standards, or any other law or standard. Where a framework is referenced, it is labeled `Candidate reference requiring validation` per [compliance-control-framework.md, Section 1](compliance-control-framework.md#1-framework-candidates).

## 7. Open Questions

See [security-open-decisions.md](security-open-decisions.md) for every unresolved governance, threat-model, and control question this document depends on.
