# ADR-0006: Security, Privacy, Audit, Compliance, and Data Governance Architecture

## Status

Accepted (as a Phase 0.6 security/privacy/governance-architecture decision; pending formal security, privacy, legal, audit, data governance, and engineering sign-off — see [../current-phase.md](../current-phase.md))

## Context

ADR-0002 through ADR-0005 established PMMS's bounded contexts, authorization model, runtime architecture, and data/persistence architecture. None of them specified how the platform actually protects what they defined: what happens when a credential is stolen, whether an audit trail can be trusted, what a minor athlete's data is allowed to be used for, how a security incident is contained, or how PMMS can eventually demonstrate compliance readiness to DepEd without claiming a compliance status it has not earned.

Left unspecified, this gap risks the same failure mode every prior phase's ADR was built to prevent, now expressed at the security/governance level: without an explicit, centrally-defined security architecture, an implementation team would invent authentication conventions, audit-logging practices, and privacy handling independently per feature — producing a platform that "works" in the narrow sense of functioning, while silently under-protecting exactly the data (minor-athlete identities, medical records, eligibility evidence, official results, government funds) whose protection is the entire basis for DepEd's institutional trust in PMMS.

A second, distinct risk this ADR addresses: the temptation, common in institutional software projects, to assert compliance with a legal or regulatory framework prematurely — because documenting a control feels like satisfying it. PMMS's working rules explicitly reject this: no control candidate in this package is asserted as compliant with anything until a named authority verifies the underlying policy source.

## Decision

PMMS will use a **security and privacy architecture that (1) preserves every authorization, runtime, and data rule established in ADR-0002 through ADR-0005 without modification, (2) adds assurance, audit, and governance structure around them, (3) applies enhanced, explicit protection to minor-athlete, guardian, medical, eligibility, and financial data, (4) binds AI-assisted features to an absolute, non-negotiable action boundary, and (5) never claims compliance with any law, regulation, or standard without a verified policy source.**

Specifically:

1. **The Phase 0.3 authorization formula is preserved exactly** — `Permission + Scope + Assignment + Resource State + Data Classification + Separation of Duties + Device Trust + Time Validity + Explicit Restrictions`. Phase 0.6 adds testable assurance (policy coverage, query filtering, export/file/broadcast/queue authorization checkpoints, privilege-escalation detection) around it, never redefines it.
2. **Privileged access, break-glass, and support impersonation are governed, not assumed.** Twelve privileged categories require named accounts, mandatory MFA, least privilege, time limitation, and approval. Break-glass necessity remains genuinely undecided (mirrors AD-10, restated as SD-03). Support impersonation, if ever implemented, can **never** perform an approval/certification/publication action while active (SOD-11, absolute).
3. **Every consequential action produces an append-only audit event**, across 27 categories. Audit events are never updated or deleted through ordinary operation. Tamper-evidence beyond append-only discipline (hash-chaining, signing) is evaluated, not committed — **immutability is not claimed as an implemented guarantee.**
4. **Minor-athlete, guardian, medical, eligibility, and finance data each receive dedicated, enhanced governance** beyond the general five-tier classification model — most notably, only a minimal ACL-derived clearance-status flag ever crosses from Medical Operations into Eligibility and Clearance; full medical detail never does.
5. **AI-assisted features operate within an absolute action boundary**: never autonomously approving eligibility, certifying results, changing scores, resolving protests, awarding medals, issuing medical decisions, granting/changing access, revoking credentials, publishing protected data, deleting records, executing production repair, or exfiltrating data. Every AI-proposed change is acted on only by a human with the actual authority, through the ordinary Command/Application-layer path.
6. **No cryptographic algorithm, key-management product, secret-management platform, or malware-scanning vendor is selected.** Every cryptographic/scanning requirement in this package is a vendor-neutral, algorithm-agile requirement a future implementation phase satisfies.
7. **No compliance claim is made anywhere in this package.** Every framework reference (Data Privacy Act, NPC guidance, DepEd orders, OWASP, ISO, NIST, CIS) is labeled `Candidate reference requiring validation`; every policy source is tracked in a registry as an unverified placeholder until a named stakeholder confirms it.
8. **No vendor or external integration is approved.** Any future vendor is assessed through an 18-area framework before approval — none is pre-approved by being named as a candidate category.

**Explicitly not decided by this ADR:** specific cryptographic algorithms, specific key-management/secret-management products, specific malware-scanning vendor, break-glass/impersonation implementation necessity, MFA-enforcement scope, session-timeout values, numeric retention periods, numeric RPO/RTO targets, and which policy-source-registry placeholders correspond to real, verified DepEd/NPC/government documents.

## Rationale

- **Preserves every prior ADR's guarantees at the exact layer where they are most easily undermined.** An authorization model is only real if privileged access is actually governed; a versioned, append-only data model is only trustworthy if the audit trail attesting to it is itself tamper-resistant; a classification model is only protective if minor/medical/eligibility data actually receives differentiated handling. This ADR is where each of those translations happens.
- **Prevents the platform from inventing 34 independent, inconsistent security conventions.** Centralizing authentication, audit, privacy, and AI-boundary rules now is far cheaper than discovering their absence during a real security incident or a real DepEd compliance inquiry.
- **Protects PMMS's most consequential data categories with proportionate, not uniform, controls.** A single undifferentiated "protect sensitive data" policy would under-protect medical/eligibility/minor data and over-restrict lower-stakes Confidential data; this ADR's domain-specific governance documents (medical, eligibility, finance, minor/guardian) exist specifically to avoid that flattening.
- **Avoids the specific institutional risk of a false compliance claim.** An unsupported "PMMS is Data-Privacy-Act-compliant" statement is worse than an honest "not yet validated" — it creates liability exactly where the platform is trying to build trust. The compliance-language discipline in this ADR is a direct, deliberate response to that risk.
- **Establishes an absolute AI boundary before any AI feature is built**, so no future implementation decision can quietly erode it under delivery pressure — the boundary is a security decision, not a product-scope negotiation.

## Approved Security and Privacy Architecture Direction

> Preserve Phase 0.3's authorization model, Phase 0.4's runtime boundaries, and Phase 0.5's classification/retention/lifecycle rules unchanged; layer a compliance-honest, governance-explicit, and AI-boundary-absolute security and privacy architecture on top; treat every numeric, legal, or policy-dependent value as an open decision or registry placeholder rather than an invented default.

## Authorization Assurance Rule (Carried Forward from ADR-0003, Extended)

Every authorization enforcement point (controller, application/domain layer, query filter, file/broadcast/queue boundary) independently re-checks the full formula — no enforcement point trusts an upstream check as sufficient in isolation. Every one of the 11 separation-of-duties conflicts (SOD-01 through SOD-11) is either structurally prevented or, at minimum, audit-detectable.

## Audit Integrity Rule (New in This Phase)

Every consequential action produces an append-only audit event with actor, action, target, before/after-state references, reason, and time. No role, including a platform super administrator, has an ordinary audit-deletion capability. A correction to an incorrect audit event is a new event, never an edit to the original.

## Sensitive-Data Governance Rule (New in This Phase)

Medical data crosses into Eligibility only as a minimal clearance-status flag, never as full detail. Eligibility evidence never appears in a public projection. Minor-athlete guardian access requires a verified, never inferred, relationship. Financial approval always requires a different individual than the one who recorded the transaction (SOD-06).

## AI Boundary Rule (Carried Forward from ADR-0003/0004, Made Absolute)

An AI-assisted feature's effective access is the intersection of its own scope and the requesting user's authority, never a union. It never autonomously performs any of the 13 prohibited actions named in [../../docs/03-security/ai-security-privacy-and-governance.md, Section 3](../../docs/03-security/ai-security-privacy-and-governance.md#3-ai-action-boundaries-absolute-prohibitions).

## Compliance-Language Rule (New in This Phase)

No PMMS document states or implies compliance with any law, regulation, or standard without a verified policy source recorded in [../../docs/03-security/policy-source-registry.md](../../docs/03-security/policy-source-registry.md). Every framework reference is a candidate pending validation; every control is documented as `Candidate` status until actually implemented and tested.

## Consequences

**Positive:**
- A future implementation phase inherits a complete security-control vocabulary, audit-event catalog, and privacy-governance structure, and can build authentication/authorization/audit code against known, consistent requirements rather than inventing conventions per feature.
- The platform's highest-stakes data categories (minor-athlete, medical, eligibility, financial, official-result, audit) have explicit, differentiated protection requirements defined before any code exists to under-protect them.
- The compliance-language discipline protects DepEd and the platform itself from the institutional risk of an unsupported compliance claim, while still building toward genuine compliance readiness as policy sources are verified.

**Negative / trade-offs:**
- The dual (structural-or-audit-detectable) enforcement requirement for SoD conflicts, and the requirement that every consequential action produce an audit event, add real implementation overhead — accepted because the alternative (a policy-only, undetectable violation) directly undermines the high-integrity guarantees every prior phase was built to establish.
- Deferring cryptographic-algorithm, key-management, and malware-scanner selection preserves flexibility but means Phase 0.7 cannot finalize certain infrastructure decisions until a security-architect review occurs — an accepted sequencing cost.
- A large number of decisions remain open (25 items in [../../docs/03-security/security-open-decisions.md](../../docs/03-security/security-open-decisions.md)), with policy-source verification (SD-12) — 13 placeholder entries, none yet verified — the single largest gap, meaning much of this package's compliance-readiness language will remain at "candidate" status until dedicated policy-sourcing work occurs.

## Alternatives Considered

1. **Defer all security/privacy architecture until implementation begins, relying on ordinary Laravel/Fortify defaults plus ad hoc feature-level security decisions.** Rejected — the most direct path to the exact per-feature security-convention inconsistency this ADR exists to prevent, and the path most likely to under-protect minor/medical/eligibility data specifically.
2. **Assert compliance with a specific framework (e.g., the Data Privacy Act or ISO 27001) now, to give DepEd stakeholders immediate confidence.** Rejected — directly violates the project's working rules and creates false institutional assurance; the compliance-readiness framework instead builds toward a genuine, evidence-based claim over time.
3. **Select specific cryptographic algorithms, a malware-scanning vendor, and a secret-management platform now, to give engineering concrete targets.** Rejected for the highest-sensitivity categories — premature selection without a dedicated security-architect review and a confirmed deployment topology risks a costly redo; the vendor-neutral, algorithm-agile approach preserves optionality at low cost given no implementation exists yet to be blocked by the deferral.
4. **Implement break-glass and support-impersonation capabilities by default, governed loosely.** Rejected — per working rule 29, neither is a default capability; both require explicit governance approval before any implementation, and their underlying necessity (AD-09/AD-10) remains genuinely unresolved.
5. **Treat all sensitive data uniformly under a single "Restricted" handling policy**, rather than differentiating medical/eligibility/finance/minor-guardian governance. Rejected — a flattened policy would either under-protect the highest-stakes categories (medical, minor data) or over-restrict lower-stakes Confidential data; domain-specific governance documents avoid both failure modes.

## Validation Requirements

This decision is provisional pending:

- Formal review by a designated security owner, privacy/data-protection owner, audit owner, data governance lead, software architect, DepEd Leadership, and Data Privacy and Legal Stakeholders, per [../../docs/03-security/README.md, "Ownership and Review Expectations"](../../docs/03-security/README.md#ownership-and-review-expectations).
- Resolution of the highest-priority Phase 0.6 open decisions, per [../../docs/03-security/security-open-decisions.md, "Summary of Blocking / High-Priority Security Decisions"](../../docs/03-security/security-open-decisions.md#summary-of-blocking--high-priority-security-decisions) — notably SD-09 (classification-tier validation) and SD-12 (policy-source verification).
- Continued resolution of the Phase 0.1 policy decisions this ADR's sensitive-data governance rules depend on (eligibility authority, result approval chain, protest authority, medal tally rules, medical-data handling, AI-service restrictions).

## Related Documents

- [../../docs/03-security/phase-0.6-security-privacy-audit-compliance-governance.md](../../docs/03-security/phase-0.6-security-privacy-audit-compliance-governance.md)
- [../../docs/03-security/security-architecture.md](../../docs/03-security/security-architecture.md)
- [../../docs/03-security/authorization-and-privileged-access-assurance.md](../../docs/03-security/authorization-and-privileged-access-assurance.md)
- [../../docs/03-security/audit-and-security-event-architecture.md](../../docs/03-security/audit-and-security-event-architecture.md)
- [../../docs/03-security/ai-security-privacy-and-governance.md](../../docs/03-security/ai-security-privacy-and-governance.md)
- [../../docs/03-security/compliance-control-framework.md](../../docs/03-security/compliance-control-framework.md)
- [../../docs/03-security/policy-source-registry.md](../../docs/03-security/policy-source-registry.md)
- [../../docs/03-security/security-open-decisions.md](../../docs/03-security/security-open-decisions.md)
- [../architecture.md](../architecture.md)
- [../security-rules.md](../security-rules.md)
- [../privacy-rules.md](../privacy-rules.md)
- [../audit-rules.md](../audit-rules.md)
- [../compliance-rules.md](../compliance-rules.md)
- [../data-governance-rules.md](../data-governance-rules.md)
- [../secure-development-rules.md](../secure-development-rules.md)
- [../current-phase.md](../current-phase.md)
- [ADR-0001-product-foundation.md](ADR-0001-product-foundation.md)
- [ADR-0002-domain-and-bounded-context-architecture.md](ADR-0002-domain-and-bounded-context-architecture.md)
- [ADR-0003-role-permission-scope-and-assignment-architecture.md](ADR-0003-role-permission-scope-and-assignment-architecture.md)
- [ADR-0004-application-integration-and-runtime-architecture.md](ADR-0004-application-integration-and-runtime-architecture.md)
- [ADR-0005-data-database-and-information-lifecycle-architecture.md](ADR-0005-data-database-and-information-lifecycle-architecture.md)
