# PMMS Open Decisions

**Status:** Draft for Architecture and Stakeholder Validation
**Related:** [phase-0.1-product-foundation.md](phase-0.1-product-foundation.md) · [product-scope.md](product-scope.md) · [operating-model.md](operating-model.md) · [assumptions-constraints-risks.md](assumptions-constraints-risks.md)

This document records unresolved decisions identified during Phase 0.1. Each item includes the question, why it matters, options, a recommended direction where a reasonable one exists, the decision owner (to be identified), the evidence needed to close it, the target phase for resolution, and its current status. **No decision in this document is final.**

---

### OD-01 — Initial Deployment Scope
- **Question:** Does the initial PMMS release target a single pilot provincial meet, or must it support multiple provincial meets from launch?
- **Why it matters:** Determines MVP scope (see [product-scope.md, Section 3](product-scope.md#3-minimum-viable-provincial-meet-scope)) and infrastructure sizing.
- **Options:** (a) Single pilot meet; (b) Multiple concurrent provincial meets at launch.
- **Recommended direction:** Single pilot meet first, with architecture designed for multi-meet extension — reduces initial risk while preserving the long-term direction.
- **Decision owner:** To be identified (DepEd Leadership / Meet Organizing Committee)
- **Required evidence:** Confirmed pilot meet calendar and organizational readiness.
- **Target phase:** Phase 0.2
- **Status:** Open

### OD-02 — Single-Organization versus Multi-Organization
- **Question:** Is PMMS built solely for DepEd, or must it support multiple organizations (tenant isolation) from the start?
- **Why it matters:** Affects data model, tenancy architecture, and security boundaries.
- **Options:** (a) Single-organization (DepEd only) at launch with future multi-tenant readiness; (b) Multi-tenant from launch.
- **Recommended direction:** Single-organization at launch, tenant-isolation-ready architecture (consistent with [phase-0.1-product-foundation.md, Section 18](phase-0.1-product-foundation.md#18-commercial-quality-product-direction)).
- **Decision owner:** To be identified (DepEd Leadership)
- **Required evidence:** Confirmation of whether non-DepEd organizations are a near-term target.
- **Target phase:** Phase 0.2 / Architecture phase
- **Status:** Open

### OD-03 — Single-Meet versus Multi-Meet Launch
- **Question:** Must the platform support more than one active meet concurrently at launch?
- **Why it matters:** Directly affects Section 1 of [operating-model.md](operating-model.md) and initial architecture complexity.
- **Options:** (a) Single active meet at launch; (b) Multiple concurrent meets at launch.
- **Recommended direction:** Single active meet at launch, multi-meet-ready data model.
- **Decision owner:** To be identified
- **Required evidence:** DepEd's provincial meet calendar and concurrency needs.
- **Target phase:** Phase 0.2
- **Status:** Open

### OD-04 — Delegation Hierarchy
- **Question:** What grouping unit (school, municipality, district, cluster, legislative area) defines a delegation, and can a school belong to more than one delegation?
- **Why it matters:** Foundational to registration, entry, and reporting structures.
- **Options:** (a) School-based delegations; (b) Municipality/district-based delegations composed of multiple schools; (c) Hybrid.
- **Recommended direction:** None — requires DepEd/organizer input; do not assume.
- **Decision owner:** To be identified (Schools Division Office / Meet Organizing Committee)
- **Required evidence:** Current provincial meet delegation structure documentation.
- **Target phase:** Phase 0.2
- **Status:** Open

### OD-05 — Athlete Identity Source
- **Question:** What is the authoritative source of athlete identity (name, birthdate, school affiliation) — self/coach-submitted, or a DepEd system of record?
- **Why it matters:** Affects duplicate-detection design and eligibility validation trust level.
- **Options:** (a) Coach/school-submitted with manual validation; (b) Integration with a DepEd learner information system (deferred integration per [product-scope.md, Section 8](product-scope.md#8-deferred-integrations)).
- **Recommended direction:** Coach/school-submitted with manual validation for MVP; integration considered in future scope.
- **Decision owner:** To be identified
- **Required evidence:** Confirmation of learner information system availability/access.
- **Target phase:** Phase 0.2
- **Status:** Open

### OD-06 — School Data Source
- **Question:** Where does the authoritative list of participating schools come from?
- **Why it matters:** Determines whether school master data is manually maintained in PMMS or sourced externally.
- **Options:** (a) Manually maintained within PMMS; (b) Imported/synced from a DepEd school registry.
- **Recommended direction:** Manually maintained for MVP, with import capability considered for future scope.
- **Decision owner:** To be identified
- **Required evidence:** Availability of a DepEd school registry data source.
- **Target phase:** Phase 0.2
- **Status:** Open

### OD-07 — Eligibility Authority
- **Question:** Which role or office holds final authority to approve or reject athlete eligibility?
- **Why it matters:** Central to the result-validation chain and to Product Principle "official human validation for official outcomes."
- **Options:** To be defined based on actual DepEd governance (e.g., Secretariat, Schools Division Office, Technical Delegate).
- **Recommended direction:** None — must be sourced from DepEd policy, not invented.
- **Decision owner:** To be identified (DepEd Leadership)
- **Required evidence:** Documented DepEd eligibility policy and approval chain.
- **Target phase:** Phase 0.2
- **Status:** Open — **high priority**, blocks eligibility workflow design

### OD-08 — Official Result Approval Chain
- **Question:** Which roles are authorized to enter versus validate official results, and does this vary by sport?
- **Why it matters:** Central to [operating-model.md, Section 9](operating-model.md#9-result-validation-chain) and separation-of-duties enforcement.
- **Options:** To be defined per sport, based on actual officiating structure.
- **Recommended direction:** None — must be sourced from sport-specific officiating practice.
- **Decision owner:** To be identified (Technical Delegates / Tournament Managers)
- **Required evidence:** Per-sport officiating and result-certification process documentation.
- **Target phase:** Phase 0.2
- **Status:** Open — **high priority**

### OD-09 — Protest and Appeal Authority
- **Question:** Who has authority to receive, adjudicate, and finalize protests and appeals, and within what time window?
- **Why it matters:** Affects the "Protest or Appeal Handling" lifecycle stage and result-finality rules.
- **Options:** To be defined based on DepEd/sport governance.
- **Recommended direction:** None — must be sourced from official policy.
- **Decision owner:** To be identified (Technical Delegates)
- **Required evidence:** Documented protest/appeal policy per sport.
- **Target phase:** Phase 0.2
- **Status:** Open

### OD-10 — Sports Rule Source
- **Question:** What is the authoritative source for each sport's competition rules, formats, and scoring systems?
- **Why it matters:** PMMS must not invent sports rules (Rule 8 of the working rules); configuration must be source-backed.
- **Options:** National sports association rules, DepEd-specific meet rules, or a hybrid confirmed per sport.
- **Recommended direction:** None — requires sports-specialist input per sport.
- **Decision owner:** To be identified (Sports Specialists / Technical Delegates)
- **Required evidence:** Confirmed rulebooks per sport included in the initial meet program.
- **Target phase:** Phase 0.2
- **Status:** Open — **high priority**, blocks sport/event configuration design

### OD-11 — Competition Format Configuration
- **Question:** What range of competition formats (elimination, round-robin, time-based, judged, etc.) must PMMS support at launch?
- **Why it matters:** Determines scheduling/bracket engine scope.
- **Options:** Narrow initial format set vs. broad format support at launch.
- **Recommended direction:** Start with the formats used by sports in the initial pilot meet; expand incrementally.
- **Decision owner:** To be identified
- **Required evidence:** Confirmed sports/events list for the pilot meet.
- **Target phase:** Phase 0.2
- **Status:** Open

### OD-12 — Medal Tally Rules
- **Question:** What are the official rules for computing medal tally and ranking (e.g., gold-first ranking, tie-breaking rules)?
- **Why it matters:** Directly affects public trust in a high-integrity domain (medal tally).
- **Options:** To be defined based on DepEd's official medal tally policy.
- **Recommended direction:** None — must be sourced from official policy, not invented.
- **Decision owner:** To be identified (DepEd Leadership / Meet Organizing Committee)
- **Required evidence:** Documented official medal tally computation rules.
- **Target phase:** Phase 0.2
- **Status:** Open — **high priority**

### OD-13 — Team Point Rules
- **Question:** Are team/overall championship points awarded, and if so, under what rules?
- **Why it matters:** May be a distinct computation from medal tally and requires its own source-backed rule set.
- **Options:** No team points; team points per a defined formula.
- **Recommended direction:** None — requires confirmation of whether this applies to provincial meets.
- **Decision owner:** To be identified
- **Required evidence:** Documented team point policy, if one exists.
- **Target phase:** Phase 0.2
- **Status:** Open

### OD-14 — Accreditation Coverage
- **Question:** Which participant categories require formal accreditation (athletes, coaches, officials, committee members, media, others), and is QR-based validation required at launch?
- **Why it matters:** Affects MVP scope (see [product-scope.md, Section 3](product-scope.md#3-minimum-viable-provincial-meet-scope)).
- **Options:** Full QR-based accreditation at launch vs. basic identity credentialing at launch with QR deferred.
- **Recommended direction:** Basic credentialing at MVP, QR-based validation in subsequent release, per the proposed release sequencing.
- **Decision owner:** To be identified (Accreditation Officers / Security Committee)
- **Required evidence:** Venue security requirements and available scanning hardware.
- **Target phase:** Phase 0.2
- **Status:** Open

### OD-15 — Medical-Data Handling
- **Question:** What medical data is collected, who can access it, and what consent/legal basis governs its collection given most athletes are minors?
- **Why it matters:** High-integrity, high-sensitivity domain; direct data-privacy and legal exposure.
- **Options:** To be defined with legal/data-privacy stakeholder input.
- **Recommended direction:** None — requires a formal privacy/legal review before design proceeds.
- **Decision owner:** To be identified (Data Privacy and Legal Stakeholders)
- **Required evidence:** Applicable data privacy law analysis and DepEd consent policy for minors.
- **Target phase:** Phase 0.2 — **should precede detailed domain design for medical data**
- **Status:** Open — **high priority**

### OD-16 — Parent or Guardian Access
- **Question:** Do parents/guardians require direct system access, or is information relayed through schools/delegations?
- **Why it matters:** Affects user group scope and public/restricted data boundaries.
- **Options:** (a) No direct access — public portal only; (b) Scoped parent/guardian access to their child's information.
- **Recommended direction:** Public portal only at MVP; direct access considered in future scope pending consent-framework resolution (see OD-15).
- **Decision owner:** To be identified
- **Required evidence:** DepEd policy on parent/guardian data access.
- **Target phase:** Phase 0.2 / future scope
- **Status:** Open

### OD-17 — Public Athlete-Profile Limits
- **Question:** What athlete information, if any, may appear on the public portal (name, school, event, results) versus what must remain restricted?
- **Why it matters:** Balances public transparency against minor-data protection.
- **Options:** Minimal public data (name, school, event, result only) vs. broader public profile data.
- **Recommended direction:** Minimal public data by default, consistent with data minimization; broader exposure only with explicit policy approval.
- **Decision owner:** To be identified (Data Privacy and Legal Stakeholders)
- **Required evidence:** Data privacy policy determination.
- **Target phase:** Phase 0.2
- **Status:** Open

### OD-18 — Offline Architecture Expectations
- **Question:** Which specific field workflows must function fully offline at launch, and what is the acceptable synchronization delay?
- **Why it matters:** Major architecture driver; affects mobile app design and infrastructure.
- **Options:** Full offline-first for all field workflows vs. limited offline support for a defined critical subset at launch.
- **Recommended direction:** Limited offline support for critical field workflows (score entry, accreditation scanning) at launch, expanding per the proposed release sequencing.
- **Decision owner:** To be identified (ICT Committee)
- **Required evidence:** Venue connectivity assessment.
- **Target phase:** Architecture phase
- **Status:** Open

### OD-19 — Mobile Application Scope
- **Question:** Which user groups require a dedicated mobile application at launch, and is a public-facing mobile app required, or is the public portal (mobile web) sufficient?
- **Why it matters:** Affects Flutter application scope and delivery timeline.
- **Options:** Field-staff mobile app only vs. field-staff app plus public-facing app.
- **Recommended direction:** Field-staff mobile app first (organizers, officials, scanners); public portal via responsive mobile web initially.
- **Decision owner:** To be identified
- **Required evidence:** Confirmed field operations requirements from the pilot meet.
- **Target phase:** Phase 0.2 / Architecture phase
- **Status:** Open

### OD-20 — Hosting Ownership
- **Question:** Will PMMS be hosted by DepEd (on-premise/government cloud), by a vendor, or in a commercial cloud?
- **Why it matters:** Affects deployment model (see [phase-0.1-product-foundation.md, Section 17](phase-0.1-product-foundation.md#17-deployment-model)) and data sovereignty considerations.
- **Options:** DepEd-owned hosting; vendor-managed hosting; commercial cloud.
- **Recommended direction:** None — requires DepEd ICT policy and budget input.
- **Decision owner:** To be identified (DepEd Leadership / ICT Committee)
- **Required evidence:** DepEd hosting policy and budget constraints.
- **Target phase:** Architecture / DevOps phase
- **Status:** Open

### OD-21 — Product Branding
- **Question:** Will PMMS retain its current working name/acronym, or will it be rebranded for broader or commercial use?
- **Why it matters:** Affects public-facing identity and, if commercialized, trademark/branding considerations.
- **Options:** Retain "Provincial Meet Management System / PMMS"; rebrand later.
- **Recommended direction:** Retain current identity through Phase 0.1 and near-term phases; revisit if/when commercialization beyond DepEd becomes concrete.
- **Decision owner:** To be identified (DepEd Leadership)
- **Required evidence:** None required to defer this decision.
- **Target phase:** Deferred — revisit if commercialization proceeds
- **Status:** Open — low urgency

### OD-22 — Licensing Model
- **Question:** Is PMMS an internal DepEd system, or is a licensing/subscription model anticipated for other organizations?
- **Why it matters:** Affects the commercial-quality product direction and tenancy architecture priority.
- **Options:** Internal-only; licensed/SaaS for other organizations in the future.
- **Recommended direction:** Internal DepEd system as the confirmed near-term direction; SaaS-readiness maintained as an architectural principle without a committed licensing model.
- **Decision owner:** To be identified (DepEd Leadership)
- **Required evidence:** DepEd's intent regarding external organizational use.
- **Target phase:** Deferred
- **Status:** Open — low urgency

### OD-23 — Support Model
- **Question:** Who provides ongoing production support after go-live (DepEd ICT, vendor, hybrid), and what are the service-level expectations?
- **Why it matters:** Affects operational readiness and long-term platform sustainability.
- **Options:** DepEd-run support; vendor-run support; hybrid.
- **Recommended direction:** None — requires DepEd ICT capacity assessment.
- **Decision owner:** To be identified
- **Required evidence:** DepEd ICT staffing and support capability assessment.
- **Target phase:** Later implementation/DevOps phase
- **Status:** Open

### OD-24 — Data-Retention Requirements
- **Question:** How long must meet records (registration, results, medical, financial) be retained, and under what legal/records-management basis?
- **Why it matters:** Affects archiving design and data-privacy compliance.
- **Options:** To be defined per DepEd records-management policy and applicable law.
- **Recommended direction:** None — must be sourced from DepEd records-management policy.
- **Decision owner:** To be identified (Data Privacy and Legal Stakeholders / DepEd Leadership)
- **Required evidence:** Documented DepEd records-retention policy.
- **Target phase:** Phase 0.2
- **Status:** Open

### OD-25 — Integration Requirements
- **Question:** Which external/adjacent systems (learner information systems, HR systems, payment gateways, SMS gateways) must PMMS integrate with, and when?
- **Why it matters:** Affects API scope and architecture (see [product-scope.md, Section 8](product-scope.md#8-deferred-integrations)).
- **Options:** No integrations at launch vs. specific confirmed integrations at launch.
- **Recommended direction:** No integrations at launch; revisit per confirmed DepEd system availability.
- **Decision owner:** To be identified
- **Required evidence:** Inventory of DepEd systems available for integration and their access policies.
- **Target phase:** Phase 0.2 / Architecture phase
- **Status:** Open

### OD-26 — Official Document Formats
- **Question:** What official document formats (result sheets, certificates, rosters) are required, and do they need to match existing DepEd templates?
- **Why it matters:** Affects reporting/document-generation scope.
- **Options:** New PMMS-defined formats vs. replicating existing DepEd/organizer templates.
- **Recommended direction:** Replicate existing templates where they exist, to ease adoption.
- **Decision owner:** To be identified (Secretariat)
- **Required evidence:** Existing document templates from prior manually run meets.
- **Target phase:** Phase 0.2
- **Status:** Open

### OD-27 — Digital Signature Requirements
- **Question:** Do official results, certificates, or reports require digital or physical signatures for legal/institutional validity?
- **Why it matters:** Affects publication workflow and document-generation design.
- **Options:** Digital signature/certification workflow vs. continued reliance on physical signatures alongside digital records.
- **Recommended direction:** None — requires DepEd institutional policy confirmation.
- **Decision owner:** To be identified (DepEd Leadership)
- **Required evidence:** DepEd policy on digital signature validity for official records.
- **Target phase:** Phase 0.2
- **Status:** Open

### OD-28 — QR Validation Rules
- **Question:** What specific rules govern QR-based accreditation validation (expiry, venue-scoping, revocation)?
- **Why it matters:** Affects accreditation and venue security design; relates to RSK-08 in [assumptions-constraints-risks.md](assumptions-constraints-risks.md).
- **Options:** To be defined based on venue security requirements.
- **Recommended direction:** None at this phase — deferred alongside OD-14 (Accreditation Coverage).
- **Decision owner:** To be identified (Security Committee / Accreditation Officers)
- **Required evidence:** Venue security and accreditation process requirements.
- **Target phase:** Subsequent release (per [product-scope.md, Section 16](product-scope.md#16-release-sequencing-directional-not-committed))
- **Status:** Open

### OD-29 — AI-Service Restrictions
- **Question:** Which AI services/providers (if any) are approved for use with PMMS data, and what data categories must never be sent to an external AI service?
- **Why it matters:** Directly affects the AI-Assisted Product Direction (Section 19 of the foundation document) and data-privacy compliance.
- **Options:** No external AI services permitted; approved-provider list with data-category restrictions.
- **Recommended direction:** No sensitive data (medical, full eligibility documentation, personally identifying minor data) sent to any AI service absent an explicit, approved DepEd policy; AI features restricted to advisory, non-sensitive use cases until such a policy exists.
- **Decision owner:** To be identified (DepEd Leadership / Data Privacy and Legal Stakeholders)
- **Required evidence:** Formal DepEd AI-use policy.
- **Target phase:** Phase 0.2 — **should precede any AI feature design**
- **Status:** Open — **high priority**

---

## Summary of High-Priority Open Decisions

The following decisions are flagged as high priority because they block downstream domain design (Phase 0.2) in high-integrity areas:

- OD-07 — Eligibility Authority
- OD-08 — Official Result Approval Chain
- OD-10 — Sports Rule Source
- OD-12 — Medal Tally Rules
- OD-15 — Medical-Data Handling
- OD-29 — AI-Service Restrictions

These should be prioritized in stakeholder consultation before or during Phase 0.2.
