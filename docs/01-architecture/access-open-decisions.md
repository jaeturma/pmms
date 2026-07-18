# PMMS Access Open Decisions

**Status:** Draft Complete — Pending Security, Domain, and Stakeholder Validation
**Related:** [phase-0.3-access-and-assignment-architecture.md](phase-0.3-access-and-assignment-architecture.md) · [domain-open-decisions.md](domain-open-decisions.md) (Phase 0.2) · [../00-product/open-decisions.md](../00-product/open-decisions.md) (Phase 0.1)

This document records unresolved **identity, role, permission, scope, and assignment** questions identified during Phase 0.3. Each carries a decision ID (`AD-XX`), distinct from Phase 0.2's `DD-XX` domain-modeling decisions and Phase 0.1's `OD-XX` product decisions, though several cross-reference those directly. **No decision below is final.**

---

### AD-01 — Identity Proofing Mechanism Per Assurance Level
- **Question:** What specific mechanism (document upload, in-person verification, DepEd employee ID validation) establishes each identity assurance level (Low/Medium/High) defined in [identity-model.md, Section 8](identity-model.md#8-identity-proofing-concept)?
- **Areas affected:** Identity and Access (BC-02), all role-granting workflows.
- **Why it matters:** High-integrity roles (Eligibility Approver, Result Certifier) should require higher assurance than routine self-service accounts; without a defined mechanism, this requirement cannot be operationalized.
- **Options:** (a) Manual verification by Secretariat/committee onboarding; (b) Document-based self-verification; (c) Tiered — (a) for high-integrity roles, (b) for routine accounts.
- **Recommended direction:** (c) — proportionate to role sensitivity.
- **Evidence required:** DepEd policy on acceptable identity-verification methods for volunteer/temporary staff.
- **Decision owner:** To be identified (Security Administrator, DepEd Leadership)
- **Target phase:** Phase 0.4 / security-policy phase
- **Status:** Open

### AD-02 — Guardian-Relationship Verification Mechanism
- **Question:** If a Parent/Guardian User role (ROLE-52) is implemented, how is the guardian-to-athlete relationship verified before granting access?
- **Areas affected:** Identity and Access, Participant Registry (BC-07).
- **Why it matters:** An unverified relationship claim could expose a minor's data to an unauthorized party — directly touches the platform's highest-sensitivity population.
- **Options:** School/delegation-confirmed relationship; document-based proof; no guardian accounts at all (relay through school/coach instead).
- **Recommended direction:** None — fully dependent on [Phase 0.1 OD-16](../00-product/open-decisions.md#od-16--parent-or-guardian-access).
- **Evidence required:** Resolution of OD-16.
- **Decision owner:** To be identified
- **Target phase:** Future scope, contingent on OD-16
- **Status:** Open — blocked on OD-16

### AD-03 — Athlete Self-Service / Athlete Portal Timing
- **Question:** When, if ever, does PMMS introduce direct athlete login (as opposed to access mediated entirely through Coach/Delegation Head accounts)?
- **Areas affected:** Identity and Access, Participant Registry.
- **Why it matters:** Directly affects minor-data handling and consent requirements (see [identity-model.md, Section 9](identity-model.md#9-minor-athlete-considerations)).
- **Options:** No athlete self-service at launch (recommended); athlete self-service for older/self-consenting participants only, introduced later.
- **Recommended direction:** No athlete self-service at launch; revisit as future scope with its own age-appropriate consent design.
- **Evidence required:** None blocking for the "not at launch" default.
- **Decision owner:** To be identified
- **Target phase:** Future scope
- **Status:** Open — recommended direction stated

### AD-04 — Cross-Organization Identity Handling
- **Question:** If PMMS later onboards organizations beyond DepEd, how are identities that might legitimately span two organizations handled?
- **Areas affected:** Identity and Access, Participant Registry, Organization Directory.
- **Why it matters:** Directly extends [domain-open-decisions.md, DD-21](domain-open-decisions.md#dd-21--tenant-boundaries) into the identity layer.
- **Options:** Not designed for in the initial model (recommended); federated identity in a future phase.
- **Recommended direction:** Not designed for now — single-organization assumption holds per [Phase 0.1 OD-02](../00-product/open-decisions.md#od-02--single-organization-versus-multi-organization).
- **Evidence required:** None blocking.
- **Decision owner:** To be identified
- **Target phase:** Deferred
- **Status:** Open — recommended direction stated

### AD-05 — Role Consolidation Validation
- **Question:** Are the consolidation decisions in [role-catalog.md](role-catalog.md) (Technical Official absorbing Referee/Judge/Umpire/Scorer/Timer; Coach absorbing Assistant Coach/Team Manager/Coach Portal User; School Coordinator likely merging into Delegation Head) correct once real per-sport and per-school operating practice is confirmed?
- **Areas affected:** Role Catalog, Permission Catalog.
- **Why it matters:** Under-consolidation creates unnecessary role sprawl (working rule anti-pattern); over-consolidation could hide a genuinely distinct permission need (e.g., a Referee's authority may need to differ from a Timer's in ways assignment metadata alone cannot express).
- **Options:** Keep current consolidation; split specific roles once real sport-officiating structures are documented (per [Phase 0.1 OD-10](../00-product/open-decisions.md#od-10--sports-rule-source)).
- **Recommended direction:** Keep consolidated for Phase 0.3; revisit per-sport during Phase 0.4/implementation once OD-10 is resolved.
- **Evidence required:** Confirmed per-sport officiating structure.
- **Decision owner:** To be identified (Sports Specialists, Technical Delegates)
- **Target phase:** Phase 0.4
- **Status:** Open — recommended direction stated

### AD-06 — Maximum Concurrent High-Integrity Assignments Per User
- **Question:** Should PMMS enforce a cap on how many high-integrity assignments (Eligibility Approver, Result Certifier, Tally Certifier, Accreditation Officer) a single user may hold concurrently?
- **Areas affected:** Assignment Model, Separation-of-Duties Matrix.
- **Why it matters:** Excessive concentration of high-integrity authority in one individual increases both error risk and the impact of a single compromised account, even without a direct SoD conflict.
- **Options:** No cap (rely on SoD matrix alone); a soft cap with review-triggered exception; a hard cap.
- **Recommended direction:** None — requires input on realistic staffing patterns for smaller meets, where a hard cap could be operationally infeasible.
- **Evidence required:** Staffing data from a pilot meet.
- **Decision owner:** To be identified (Meet Organizing Committee)
- **Target phase:** Post-pilot review
- **Status:** Open

### AD-07 — Acting Assignment Resumption
- **Question:** When an Acting Assignment's period ends, does the original assignment auto-resume, or does it require explicit resumption action?
- **Areas affected:** Assignment Model.
- **Why it matters:** Auto-resume risks reactivating authority for someone who may have become unavailable again; explicit resumption risks an operational gap if no one remembers to act.
- **Options:** Auto-resume with notification; explicit resumption required.
- **Recommended direction:** Explicit resumption required for high-integrity roles; auto-resume acceptable for routine roles.
- **Evidence required:** None blocking.
- **Decision owner:** To be identified (software architect)
- **Target phase:** Phase 0.4
- **Status:** Open — recommended direction stated

### AD-08 — Assignment Evidentiary Requirements Per Type
- **Question:** Which assignment types require a formal source document (appointment memo, committee resolution) before activation, and who verifies it?
- **Areas affected:** Assignment Model.
- **Why it matters:** Ties system authority to real institutional authority — an assignment without a verifiable basis is a governance gap.
- **Options:** Uniform requirement for all assignments; risk-tiered requirement (only high-integrity/governance roles require formal documentation).
- **Recommended direction:** Risk-tiered — Meet Director, Eligibility Approver, Result Certifier, Tally Certifier, Accreditation Officer require documented basis; routine operational roles (e.g., Access Control Operator) do not.
- **Evidence required:** DepEd governance/appointment norms.
- **Decision owner:** To be identified (DepEd Leadership)
- **Target phase:** Phase 0.4
- **Status:** Open — recommended direction stated

### AD-09 — Support Impersonation Necessity and Approval Authority
- **Question:** Is impersonation/support-access capability needed at all for the initial release, and if so, who approves each session?
- **Areas affected:** ROLE-04 (Support Administrator), SOD-11.
- **Why it matters:** Impersonation is a high-risk capability that should not be assumed necessary by default (per [phase-0.3, Section 32](phase-0.3-access-and-assignment-architecture.md#32-impersonation-and-support-access) — "disabled by default").
- **Options:** No impersonation capability at launch (support handled via direct account review/reset instead); impersonation with mandatory Security Administrator approval per session.
- **Recommended direction:** No impersonation capability at launch; introduce only if a demonstrated support need emerges, with mandatory approval and the SOD-11 restrictions from day one if it is introduced.
- **Evidence required:** Real support-ticket patterns from a pilot meet.
- **Decision owner:** To be identified (Security Administrator, DepEd Leadership)
- **Target phase:** Post-pilot review
- **Status:** Open — recommended direction stated

### AD-10 — Break-Glass/Emergency Access Necessity and Policy Owner
- **Question:** Is a formal break-glass emergency-access mechanism required, and who owns approval of its policy?
- **Areas affected:** [phase-0.3, Section 31](phase-0.3-access-and-assignment-architecture.md#31-emergency-and-break-glass-access), Assignment Model (Emergency Assignment type).
- **Why it matters:** Per working rule, break-glass access should not be assumed automatically required — it is a real operational need (medical/security emergencies) but also a real risk surface if under-controlled.
- **Options:** No formal break-glass mechanism (rely on rapid normal-assignment creation instead); a defined, narrowly-scoped break-glass mechanism restricted to Medical/Security emergencies only.
- **Recommended direction:** None — requires explicit security and stakeholder validation before either direction is finalized; this is one of the few Phase 0.3 topics where no default is recommended given the risk on both sides of getting it wrong.
- **Evidence required:** Security policy review, real emergency-response time requirements from DepEd/venue operations.
- **Decision owner:** To be identified (Security Administrator, DepEd Leadership, Medical Team lead)
- **Target phase:** Phase 0.4 — should precede any implementation of Emergency Assignments
- **Status:** Open — **no direction recommended, high priority**

### AD-11 — District-Level Scope Necessity
- **Question:** Does PMMS need District as a distinct scope level, or does it collapse into Division scope?
- **Areas affected:** Scope Model.
- **Why it matters:** Directly depends on [Phase 0.1 OD-04](../00-product/open-decisions.md#od-04--delegation-hierarchy).
- **Options:** Keep District as a distinct scope; collapse into Division.
- **Recommended direction:** None — mirrors OD-04's unresolved status.
- **Evidence required:** Resolution of OD-04.
- **Decision owner:** To be identified
- **Target phase:** Phase 0.4
- **Status:** Open — blocked on Phase 0.1 OD-04

### AD-12 — Competition Area vs. Venue Scope Boundary
- **Question:** For venues with multiple simultaneous competition areas (e.g., a stadium with multiple courts), does scope need to distinguish Competition Area from Venue in the initial model, or is Venue-level granularity sufficient at launch?
- **Areas affected:** Scope Model, Technical Officials assignment.
- **Why it matters:** Affects how narrowly a Technical Official's or Access Control Operator's assignment can be scoped.
- **Options:** Venue-level granularity only at launch (simpler); full Competition Area granularity from the start.
- **Recommended direction:** Venue-level at launch, Competition Area granularity introduced if/when a specific sport/venue configuration demonstrates the need.
- **Evidence required:** Confirmed venue configurations for the pilot meet.
- **Decision owner:** To be identified (software architect)
- **Target phase:** Phase 0.4
- **Status:** Open — recommended direction stated

### AD-13 — Shift Scope as First-Class Concept
- **Question:** Should "Shift" be a standalone, reusable scope entity (with its own definition, shared across assignments) or remain an attribute embedded within individual Assignments?
- **Areas affected:** Scope Model, Assignment Model.
- **Why it matters:** Affects reusability (e.g., defining "July 15 AM shift" once vs. per-assignment) without being a business-critical distinction either way.
- **Options:** First-class Shift entity; embedded attribute only.
- **Recommended direction:** Embedded attribute for Phase 0.3/0.4 — lower complexity, revisit if shift-scheduling complexity grows (e.g., if shift-swap workflows become a real operational need).
- **Evidence required:** None blocking.
- **Decision owner:** To be identified (software architect)
- **Target phase:** Phase 0.4
- **Status:** Open — recommended direction stated

### AD-14 — Device Credential Rotation Cadence
- **Question:** How frequently must device and service-identity credentials be rotated?
- **Areas affected:** Device and Service Identity Model.
- **Why it matters:** Longer rotation periods increase exposure window if a credential is compromised without detection; too-frequent rotation creates operational burden at a live meet.
- **Options:** Fixed cadence (e.g., per meet cycle); risk-based cadence (shorter for Access Validation/Scoring devices, longer for low-risk kiosks).
- **Recommended direction:** Risk-based cadence, aligned with the risk tiers already established in [permission-catalog.md](permission-catalog.md).
- **Evidence required:** Security-policy input.
- **Decision owner:** To be identified (Security Administrator)
- **Target phase:** Phase 0.4
- **Status:** Open — recommended direction stated

### AD-15 — Dedicated Device-Management Committee Role
- **Question:** Given the scale of scanner/encoder devices expected at a multi-venue meet, does device management warrant a role distinct from the general ICT Coordinator (ROLE-44)?
- **Areas affected:** Role Catalog, Device and Service Identity Model.
- **Why it matters:** A single ICT Coordinator managing dozens of devices across multiple venues may be an operational bottleneck.
- **Options:** Keep device management under ROLE-44; introduce a dedicated Device Custodian role per venue.
- **Recommended direction:** None — depends on real device counts and venue distribution from a pilot meet.
- **Evidence required:** Pilot-meet device inventory.
- **Decision owner:** To be identified (ICT Committee)
- **Target phase:** Post-pilot review
- **Status:** Open

### AD-16 — Public API Client Onboarding Process
- **Question:** What is the onboarding/approval process for a future external API client, once any integration becomes concrete?
- **Areas affected:** Device and Service Identity Model, deferred integrations per [Phase 0.1 product-scope.md, Section 8](../00-product/product-scope.md#8-deferred-integrations).
- **Why it matters:** Not urgent — no integrations are planned for the initial release — but should not be designed reactively once a real integration request arrives.
- **Options:** Defer entirely until a concrete integration is proposed (recommended); pre-design a generic onboarding process now.
- **Recommended direction:** Defer — premature to design without a concrete integration target.
- **Evidence required:** A specific, approved integration proposal.
- **Decision owner:** To be identified
- **Target phase:** Future scope
- **Status:** Open — recommended direction stated

### AD-17 — Offline Snapshot Validity Durations
- **Question:** What is the maximum validity period for an offline authorization snapshot, per context (Access Validation, Scoring, Medical, etc.)?
- **Areas affected:** Offline Authorization Model.
- **Why it matters:** Directly bounds the revocation-lag risk window (RSK-08); too-long a duration increases risk, too-short undermines the offline-resilience goal from [Phase 0.1](../00-product/phase-0.1-product-foundation.md#8-product-principles).
- **Options:** Uniform duration across all offline contexts; risk-tiered durations (shorter for Access Validation, given its highest-volume/highest-risk profile).
- **Recommended direction:** Risk-tiered, with Access Validation and Scoring warranting the shortest validity windows given their Critical offline-priority classification.
- **Evidence required:** Real venue connectivity data from a pilot meet.
- **Decision owner:** To be identified (ICT Committee, Security Administrator)
- **Target phase:** Post-pilot review
- **Status:** Open — recommended direction stated

### AD-18 — Offline Reauthentication Mechanism for Shared Devices
- **Question:** What mechanism allows an operator to reauthenticate on a shared device while that device is offline (per [offline-authorization-model.md, Section 5](offline-authorization-model.md#5-user-reauthentication))?
- **Areas affected:** Offline Authorization Model, Identity and Access.
- **Why it matters:** A shared scanner used across shifts needs some locally-verifiable operator check that doesn't require connectivity, without falling back to "the device is de facto the operator" (a named anti-pattern).
- **Options:** Locally-verifiable PIN; passkey with local verification; no shared-device support at all (one device per operator).
- **Recommended direction:** None — a technical security-design question appropriately deferred to Phase 0.4/implementation, informed by whatever device hardware is actually procured.
- **Evidence required:** Confirmed device hardware capabilities.
- **Decision owner:** To be identified (ICT Committee, Security Administrator)
- **Target phase:** Phase 0.4
- **Status:** Open

### AD-19 — Extended-Outage Policy
- **Question:** Is a formal policy needed for venues expected to experience multi-day connectivity loss, beyond the standard offline model?
- **Areas affected:** Offline Authorization Model, [domain-open-decisions.md, DD-19](domain-open-decisions.md#dd-19--offline-finality-rules).
- **Why it matters:** The standard offline model assumes bounded, intermittent disconnection; a genuinely multi-day outage at a remote venue could exceed every snapshot validity window this document assumes.
- **Options:** No special policy — rely on the standard model even for extended outages (accepting degraded operation); a defined extended-outage fallback procedure (e.g., paper-based backup process).
- **Recommended direction:** A defined paper/manual fallback procedure for extended outages, consistent with [Phase 0.1's printable-document constraint](../00-product/assumptions-constraints-risks.md#2-constraints) — but the specific procedure is not designed here.
- **Evidence required:** Real venue connectivity data from a pilot meet.
- **Decision owner:** To be identified (ICT Committee, Meet Organizing Committee)
- **Target phase:** Post-pilot review
- **Status:** Open — recommended direction stated

### AD-20 — Access Review Intervals and Dormant-Account Threshold
- **Question:** What specific cadence applies to each review type in [access-review-and-revocation.md](access-review-and-revocation.md), and what inactivity period defines a "dormant" account?
- **Areas affected:** Access Review and Revocation.
- **Why it matters:** Without defined intervals, "periodic review" is unenforceable as a real operational practice.
- **Options:** Fixed intervals set now (risk: arbitrary, unvalidated); intervals set after a pilot meet establishes realistic operational rhythms.
- **Recommended direction:** Set after a pilot meet — avoids inventing numbers with no operational basis, consistent with the approach taken for KPI targets in [Phase 0.1 success-framework.md](../00-product/success-framework.md#11-baseline-requirements).
- **Evidence required:** Pilot-meet operational data.
- **Decision owner:** To be identified (Security Administrator)
- **Target phase:** Post-pilot review
- **Status:** Open — recommended direction stated

### AD-21 — Authentication Mechanism Selection (MFA/SSO/Recovery)
- **Question:** Beyond the architectural readiness requirements in [phase-0.3-access-and-assignment-architecture.md, Section 36](phase-0.3-access-and-assignment-architecture.md#36-authentication-architecture-boundaries), which specific mechanisms (password policy, MFA method, SSO provider if any, account-recovery flow) will PMMS use?
- **Areas affected:** Identity and Access (BC-02).
- **Why it matters:** The repository already contains a starter-kit implementation with password + passkey + 2FA scaffolding (per [.ai/project-context.md](../../.ai/project-context.md)) — this decision should confirm whether that scaffolding satisfies Phase 0.3's MFA-readiness requirement or needs extension, not select a mechanism from scratch.
- **Options:** Adopt the existing Fortify-based scaffolding as-is; extend it for role-risk-based MFA enforcement (e.g., mandatory MFA for high-integrity roles specifically).
- **Recommended direction:** Adopt existing scaffolding as the foundation; layer risk-based MFA enforcement for high-integrity roles (Eligibility Approver, Result Certifier, Tally Certifier, Accreditation Officer, Platform/Security Administrators) as a Phase 0.4 implementation detail.
- **Evidence required:** Confirmation that the existing Fortify scaffolding's 2FA/passkey support meets DepEd security policy requirements.
- **Decision owner:** To be identified (Security Administrator)
- **Target phase:** Phase 0.4
- **Status:** Open — recommended direction stated

### AD-22 — Permission and Role Naming Governance
- **Question:** Once implementation begins, who has authority to approve new permissions/roles beyond this catalog, and how are additions kept consistent with the naming standard in [permission-catalog.md](permission-catalog.md) and [role-catalog.md](role-catalog.md)?
- **Areas affected:** Role Catalog, Permission Catalog, all future phases.
- **Why it matters:** Without governance, permission/role sprawl (a named anti-pattern throughout this documentation) will re-emerge during implementation regardless of how disciplined the Phase 0.3 catalog is.
- **Options:** Software architect sign-off required for any new permission/role; a lighter-weight peer-review process.
- **Recommended direction:** Software architect (or designated technical lead) sign-off required for any new Critical/High-risk permission or any new role; lighter review for Low-risk additions.
- **Evidence required:** None blocking.
- **Decision owner:** To be identified (software architect)
- **Target phase:** Phase 0.4
- **Status:** Open — recommended direction stated

---

## Summary of High-Priority Open Decisions

The following are flagged as highest priority because they affect the fundamental shape of the authorization model, not just a parameter within an agreed shape:

- **AD-10** — Break-glass/emergency access necessity (no direction recommended — genuine open question)
- **AD-09** — Support impersonation necessity
- **AD-06** — Maximum concurrent high-integrity assignments
- Plus the six Phase 0.1 decisions this Phase 0.3 documentation depends on directly: [OD-07](../00-product/open-decisions.md#od-07--eligibility-authority), [OD-08](../00-product/open-decisions.md#od-08--official-result-approval-chain), [OD-09](../00-product/open-decisions.md#od-09--protest-and-appeal-authority), [OD-12](../00-product/open-decisions.md#od-12--medal-tally-rules), [OD-15](../00-product/open-decisions.md#od-15--medical-data-handling), [OD-29](../00-product/open-decisions.md#od-29--ai-service-restrictions).

These should be prioritized in stakeholder/security consultation before or during Phase 0.4.
