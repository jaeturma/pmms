# PMMS Architecture Consistency and Contradiction Analysis

**Status:** Draft Complete — Pending Formal Architecture Review and Stakeholder Sign-Off
**Related:** [contradiction-and-decision-resolution-register.md](contradiction-and-decision-resolution-register.md)

This document searches explicitly for contradictions across the 12-phase architecture. Each pair below was checked against the actual documented rule in both directions; **no material contradiction was found in the architecture's own stated rules** — the recurring pattern instead is *terminology that could be misread as conflicting if a reader assumes conventional meaning*, which this document resolves by restating the actual, consistent rule.

---

## 1. Terminology Conflicts

### Tenant Versus Organization

Not a contradiction — a defined distinction. "Tenant" (Phase 0.12) is the isolation boundary; "Organization" (Phase 0.2, BC-03) is a node in the domain hierarchy a tenant may contain. Restated explicitly in [../09-enterprise/multi-tenant-product-and-organization-model.md, Section 1](../09-enterprise/multi-tenant-product-and-organization-model.md#1-terminology-do-not-conflate): "not every organization row is automatically a tenant." **No contradiction.**

### Role Versus Assignment

Not a contradiction — the platform's most repeated distinction. A role names a category of authority; an assignment is the time-bound, scoped grant activating it. Restated identically across Phase 0.3 (identity-model.md, assignment-model.md), Phase 0.4, Phase 0.8's tenant-onboarding readiness, and Phase 0.9's UX layer. **No contradiction.**

### Approval Versus Certification Versus Validation

Not a contradiction — three distinct action types with separate permissions, restated explicitly in [../08-workflows/human-tasks-approvals-reviews-and-certifications.md, Section 2](../08-workflows/human-tasks-approvals-reviews-and-certifications.md#2-approval-review-and-certification-are-distinct). Validation confirms technical correctness (e.g., a score entry is internally consistent); Approval is a binding decision (e.g., accepting an eligibility case); Certification is an elevated attestation for an official record (e.g., an Official Result). **No contradiction**, though this three-way distinction is easy to conflate in casual conversation — flagged as a training/onboarding risk, not an architecture defect, in [architecture-gap-register.md](architecture-gap-register.md).

### Score Versus Result

Not a contradiction. Scoring (BC-15) captures raw performance data; Official Results (BC-16) is the certified, authoritative output derived from validated scores. Restated absolutely in [../01-architecture/data-ownership-map.md](../01-architecture/data-ownership-map.md) Rule: "Official Results owns certified results (not Scoring)." **No contradiction.**

### Certified Versus Published

Not a contradiction — two sequential, separately-authorized states. A record can be Certified but not yet Published (held pending release); it cannot be Published without first being Certified. Restated in [../08-workflows/result-protest-medal-and-publication-workflows.md, Section 4](../08-workflows/result-protest-medal-and-publication-workflows.md#4-publication-workflow-cross-cutting): "publication never certifies as a side effect." **No contradiction.**

### Delete Versus Revoke Versus Correct Versus Supersede

Not a contradiction — four distinct, non-overlapping high-integrity mechanisms replacing destructive deletion, restated identically since Phase 0.5's [temporal-history-and-versioning-model.md](../02-data/temporal-history-and-versioning-model.md): Cancellation, Revocation, Reversal, Supersession, Inactivation, Archival, Correction version — each with a specific applicability (e.g., a credential is Revoked, a result is Corrected/Superseded, nothing high-integrity is ever hard-Deleted). **No contradiction**, though the seven-term vocabulary is dense enough to warrant a glossary cross-reference for implementers — tracked as a documentation-usability item, not a defect.

### Domain Event Versus Audit Event Versus Notification Event

Not a contradiction — three of six explicitly distinct event types, restated unchanged from Phase 0.4 through Phase 0.11: "a single domain event (`ResultCertified`) may simultaneously trigger an integration event, a real-time broadcast, an audit event, and a notification event — but the domain event itself is none of these; it is the source fact all four derive from." **No contradiction.**

## 2. Ownership Conflicts

### Platform Administrator Versus Tenant-Data Access

Not a contradiction — an explicit, absolute boundary. Restated identically in Phase 0.6 (support/impersonation governance) and Phase 0.12 ([tenant-identity-authorization-and-administration.md, Section 4](../09-enterprise/tenant-identity-authorization-and-administration.md#4-platform-administration)): "Platform administrators must not automatically receive unrestricted access to protected tenant data." **No contradiction** — this is a working rule (working rule 38, Phase 0.12) consistently enforced, not a conflict between two documents.

### Shared Data Versus Tenant-Owned Data

Not a contradiction — a defined five-way classification (Platform-owned, Tenant-owned, Organization-owned, Meet-owned, Shared reference, Derived projection) established in [../09-enterprise/multi-tenant-product-and-organization-model.md, Section 4](../09-enterprise/multi-tenant-product-and-organization-model.md#4-data-ownership-classification), layered on top of — never replacing — Phase 0.2's bounded-context data-ownership model. **No contradiction.**

## 3. Authority Conflicts

No authority conflict was found between any two roles, committees, or automated actors across the architecture — every high-integrity action traces to exactly one authorized human role or an explicitly bounded, human-approved automation entry (per [../08-workflows/responsible-automation-and-authority-boundaries.md](../08-workflows/responsible-automation-and-authority-boundaries.md)), never two competing authorities for the same action. Where authority itself is genuinely undecided (e.g., who holds final eligibility approval authority, OD-07), this is correctly tracked as an **unresolved decision**, not a contradiction — see [contradiction-and-decision-resolution-register.md](contradiction-and-decision-resolution-register.md).

## 4. Runtime Conflicts

### Redis Versus MySQL

Not a contradiction — restated absolutely and identically in every phase from 0.4 through 0.12: MySQL is sole authoritative store; Redis is transient (cache/queue/session/lock/rate-limit only). **No contradiction**, and no instance was found anywhere in the 12-phase corpus where a document implies Redis holds authoritative state.

### MinIO Versus Database Metadata

Not a contradiction — restated absolutely and identically: MinIO stores object content only; MySQL metadata is always authoritative for ownership/access/lifecycle. **No contradiction.**

### Queue Versus Workflow State

Not a contradiction — restated absolutely in [../08-workflows/business-process-and-state-machine-architecture.md, Section 3](../08-workflows/business-process-and-state-machine-architecture.md#3-long-running-workflow-architecture): "long-running process state is never kept only in Redis or a queue." A queue transports work; MySQL holds the durable workflow-instance state. **No contradiction.**

## 5. Source-of-Truth Conflicts

None found. Every data concept in [../01-architecture/data-ownership-map.md](../01-architecture/data-ownership-map.md) has exactly one authoritative bounded context, restated unchanged through every subsequent phase (Phase 0.9's public-projection rules, Phase 0.10's AI advisory-only boundary, Phase 0.11's event-sourcing-from-authoritative-state rule, Phase 0.12's read-replica exclusion for immediate-consistency actions).

## 6. State Conflicts

### Offline Acceptance Versus Server Authority

Not a contradiction — restated absolutely and identically across Phase 0.3 (offline authorization model), Phase 0.4, Phase 0.8 (meet-day operations), Phase 0.11, and Phase 0.12: an offline action is always Provisional pending server validation; the eight-item never-final-offline list (eligibility approval, result certification, protest resolution, medal tally certification, meet closure, privilege grants, permanent credential revocation, high-risk overrides) is restated verbatim, unchanged, in every phase that touches offline behavior. **No contradiction.**

## 7. AI Conflicts

### AI Recommendation Versus Deterministic Validation

Not a contradiction — restated absolutely as Phase 0.10's central architectural principle, carried unchanged into every subsequent phase that touches AI (0.11's automation boundaries, 0.12's tenant-aware AI access): deterministic rules are preferred for authoritative validation; AI may draft, classify, summarize, detect, compare, and recommend, never decide. **No contradiction.**

## 8. Tenant Conflicts

### Pilot Scope Versus Enterprise Scope

Not a contradiction, but a deliberate, explicitly-managed tension. PMMS's confirmed current state (single-organization, single-server, Enterprise Maturity Stage 1) coexists with an extensive Phase 0.12 enterprise-readiness architecture that is explicitly never claimed as active. The risk is not a documentation contradiction but a **process risk**: that Phase 0.14 or later implementers misread the volume of enterprise-readiness documentation as an implied requirement to build multi-tenancy, SSO, or DR infrastructure before the core platform exists. This is addressed directly in [remediation-roadmap-and-priority-sequencing.md](remediation-roadmap-and-priority-sequencing.md) and the Priority 5 classification of nearly all Phase 0.12 capabilities.

## 9. Documentation Inconsistencies Found (Distinct From Architecture Contradictions)

Two genuine, minor internal inconsistencies were found during this review — neither is a contradiction in the *architecture's rules*, both are inconsistencies in the *documentation's own self-description*:

| Finding | Evidence | Severity |
|---|---|---|
| Role-category count mismatch | [../01-architecture/phase-0.3-access-and-assignment-architecture.md, Section 6](../01-architecture/phase-0.3-access-and-assignment-architecture.md#6-role-model) states "53 roles across 12 categories," but the same sentence's prose names 13 category labels, and [../01-architecture/role-catalog.md](../01-architecture/role-catalog.md)'s actual file structure has 9 H2 section headings. The role count (53) itself is internally consistent and confirmed correct by direct tally. | Low — cosmetic, does not affect the role catalog's substance |
| Permission-count approximation | [../01-architecture/permission-catalog.md](../01-architecture/permission-catalog.md) is described as "~115 permissions"; direct row-count yields 104 explicit catalog rows. The source's own "~" qualifier already signals approximation. | Low — the catalog content itself is not in question, only the summary figure's precision |

Both are tracked as documentation debt in [technical-debt-and-documentation-debt-register.md](technical-debt-and-documentation-debt-register.md), not as architecture gaps.

## 10. Proposed Resolution

No contradiction identified in this document requires an ADR or a changed architectural rule — every "conflict" investigated resolved to a defined, consistent distinction already present in the documentation. The two documentation inconsistencies (Section 9) require only a numbers/heading correction in their source files, tracked as low-severity documentation debt.

## 11. Open Questions

Whether a periodic (e.g., annual, or triggered-by-material-change) re-run of this contradiction analysis is adopted as ongoing governance — see [architecture-gap-register.md](architecture-gap-register.md).
