# PMMS Architecture Risk Register (Consolidated)

**Status:** Draft Complete — Pending Formal Architecture Review and Stakeholder Sign-Off
**Related:** [../00-product/assumptions-constraints-risks.md](../00-product/assumptions-constraints-risks.md) (Phase 0.1, `RSK-` prefix) · [../03-security/security-risk-register.md](../03-security/security-risk-register.md) (Phase 0.6) · [../09-enterprise/enterprise-risk-register.md](../09-enterprise/enterprise-risk-register.md) (Phase 0.12, `ERK-` prefix)

This register consolidates the highest-priority risks from every prior phase's risk content plus risks newly identified during this Phase 0.13 review, using ID prefix `ARR-` (Architecture Risk Register) to avoid colliding with `RSK-` or `ERK-`. Each entry preserves a reference to its original source where one exists.

---

### ARR-01 — Self-Approval Undermines Eligibility Integrity

- **Category:** Authorization / Domain
- **Scenario:** A reviewer approves their own eligibility case submission.
- **Cause:** SOD-01 enforcement mechanism not yet designed; blocked on OD-07.
- **Affected capability:** Eligibility and Clearance (BC-09).
- **Impact:** Compromises the eligibility-integrity chain — restated verbatim from [../00-product/assumptions-constraints-risks.md, RSK-01](../00-product/assumptions-constraints-risks.md).
- **Likelihood placeholder:** Medium (pending implementation evidence).
- **Existing controls:** SOD-01 documented, structural-enforcement intent stated.
- **Missing controls:** OD-07 resolution; actual structural enforcement (not yet implemented).
- **Recommended treatment:** Resolve OD-07 before Eligibility module implementation; enforce SOD-01 structurally, not by policy alone.
- **Owner:** To be identified (Eligibility Authority per OD-07).
- **Review trigger:** OD-07 resolution.
- **Residual-risk placeholder:** Pending.
- **Status:** Open, tracked in [GAP-02](architecture-gap-register.md)/PSG-04.

### ARR-02 — Entering Official Validates Own Score

- **Category:** Authorization / Domain
- **Scenario:** The same individual who enters a score also validates it.
- **Cause:** SOD-02 not yet structurally enforced (implementation-phase concern).
- **Affected capability:** Scoring (BC-15).
- **Impact:** Defeats the purpose of score validation — restated verbatim from [RSK-03](../00-product/assumptions-constraints-risks.md).
- **Likelihood placeholder:** Medium.
- **Existing controls:** SOD-02 documented; scoring workflow (WF-10/WF-11) separates entry and validation as distinct transitions.
- **Missing controls:** Structural (database/application) enforcement, not yet implemented.
- **Recommended treatment:** Enforce at the assignment level during Scoring module implementation.
- **Owner:** To be identified (Technical Delegate).
- **Review trigger:** Scoring module implementation start.
- **Residual-risk placeholder:** Pending.
- **Status:** Open.

### ARR-03 — Offline Revocation-List Staleness Enables False-Accept

- **Category:** Security / Offline
- **Scenario:** A revoked credential is still accepted by an offline scanner that has not yet synced the revocation.
- **Cause:** Sync-priority ordering is architected (revocations first) but not yet implemented or tested.
- **Affected capability:** Access Validation (BC-20), Accreditation (BC-19).
- **Impact:** Named as the primary offline-security risk since Phase 0.2 — restated as RSK-08.
- **Likelihood placeholder:** Medium-High during any real offline-heavy meet.
- **Existing controls:** Revocation-priority sync ordering (architected, per [../01-architecture/offline-sync-runtime-architecture.md, Section 5](../01-architecture/offline-sync-runtime-architecture.md#5-sync-priority-ordering)).
- **Missing controls:** Implementation and load-tested verification of actual sync latency under realistic reconnect conditions.
- **Recommended treatment:** Prioritize this scenario specifically in Phase 0.14's offline/access-validation test plan.
- **Owner:** To be identified (Security Coordinator, ICT Coordinator).
- **Review trigger:** First pilot with real offline scanning.
- **Residual-risk placeholder:** Pending.
- **Status:** Open.

### ARR-04 — Physical Schema Design Introduces Unreviewed High-Integrity Modeling Errors

- **Category:** Data / Implementation
- **Scenario:** Physical schema is designed under Phase 1 time pressure without the same rigor as the logical architecture, introducing a soft-delete column or missing version chain on a high-integrity table.
- **Cause:** No physical schema exists yet (GAP-01); no dedicated review gate for schema-design fidelity to the logical model exists.
- **Affected capability:** Every high-integrity domain.
- **Impact:** Could silently reintroduce the destructive-overwrite pattern the entire architecture exists to prevent.
- **Likelihood placeholder:** Medium, given the novelty and time-pressure of first physical implementation.
- **Existing controls:** The logical high-integrity data model (Phase 0.5) is thorough and specific.
- **Missing controls:** A formal schema-review gate checking physical implementation against logical requirements.
- **Recommended treatment:** Require an explicit schema-design review (against [architecture-fitness-functions-and-validation-gates.md](architecture-fitness-functions-and-validation-gates.md)) before any high-integrity migration is merged.
- **Owner:** To be identified (Lead architect).
- **Review trigger:** Every high-integrity table's first migration.
- **Residual-risk placeholder:** Pending.
- **Status:** Open, new finding.

### ARR-05 — Documentation Volume Misread as Implementation Scope

- **Category:** Process / Scope
- **Scenario:** Phase 0.14 or later implementers treat the sheer volume of Phase 0.10–0.12 readiness documentation as an implied requirement to build AI, full multi-tenancy, or DR infrastructure early.
- **Cause:** 87 supporting documents across Phases 0.10–0.12 alone, all correctly marked "readiness," but volume itself creates a scope-creep temptation.
- **Affected capability:** Phase 1 planning and sequencing.
- **Impact:** A manageable Phase 1 foundation becomes unmanageable if enterprise/AI/DR scope is pulled forward without evidence.
- **Likelihood placeholder:** Medium — this is a known failure mode in large documentation-first efforts.
- **Existing controls:** Every readiness document explicitly labeled "candidate," "not committed," "readiness only."
- **Missing controls:** An explicit, prioritized sequencing document making deferral decisions unambiguous.
- **Recommended treatment:** [remediation-roadmap-and-priority-sequencing.md](remediation-roadmap-and-priority-sequencing.md) exists specifically to mitigate this risk.
- **Owner:** To be identified (Product owner, Lead architect).
- **Review trigger:** Phase 0.14 kickoff.
- **Residual-risk placeholder:** Low, given the mitigation already in place.
- **Status:** Open, new finding — actively mitigated by this review's own deliverables.

### ARR-06 — Tenant-Isolation Gap Exposes Cross-Tenant Data (Restated From ERK-01)

- **Category:** Security / Enterprise
- **Scenario:** A missing tenant filter in a query exposes one tenant's data to another.
- **Cause:** No implementation exists yet; defense-in-depth isolation is architected but untested.
- **Affected capability:** Any future multi-tenant deployment.
- **Impact:** Critical — restated unchanged from [../09-enterprise/enterprise-risk-register.md, ERK-01](../09-enterprise/enterprise-risk-register.md).
- **Likelihood placeholder:** Not applicable until multi-tenancy is activated (Stage 4+).
- **Existing controls:** Defense-in-depth isolation architecture.
- **Missing controls:** Implementation, tenant-isolation testing.
- **Recommended treatment:** Restated unchanged — mandatory tenant-isolation test suite before any Stage 4 activation.
- **Owner:** To be identified.
- **Review trigger:** Multi-tenancy activation decision.
- **Residual-risk placeholder:** Deferred, not currently active.
- **Status:** Deferred (Priority 5) — correctly not a near-term risk given current Stage 1 scope.

### ARR-07 — Public Traffic Starves Critical Scoring/Accreditation Workloads

- **Category:** Availability / Scalability
- **Scenario:** A result-announcement traffic spike degrades score-entry or credential-validation capacity.
- **Cause:** Workload isolation architected, not implemented or load-tested — restated unchanged from [../09-enterprise/enterprise-risk-register.md, ERK-03](../09-enterprise/enterprise-risk-register.md).
- **Affected capability:** Scoring, Access Validation, public portal.
- **Impact:** Critical — this is the platform's single most-repeated scaling rule across every phase.
- **Likelihood placeholder:** Medium-High at any well-attended meet with public digital engagement.
- **Existing controls:** Workload isolation architecture (Phase 0.4, 0.8, 0.12).
- **Missing controls:** Implementation and load testing.
- **Recommended treatment:** Prioritize workload-isolation implementation before any meet with meaningful public digital traffic.
- **Owner:** To be identified (Lead architect, Infrastructure owner).
- **Review trigger:** First pilot with real public traffic.
- **Residual-risk placeholder:** Pending.
- **Status:** Open.

### ARR-08 — Untested Disaster-Recovery Capability Fails When Actually Needed

- **Category:** Resilience / DevOps
- **Scenario:** A real disaster occurs before any restore or DR exercise has ever been performed.
- **Cause:** Restated unchanged from [../09-enterprise/enterprise-risk-register.md, ERK-04/ERK-10](../09-enterprise/enterprise-risk-register.md) — no restore has ever been executed (confirmed by this review, [devops-observability-operations-and-recovery-review.md, Section 9](devops-observability-operations-and-recovery-review.md#9-restore)).
- **Affected capability:** All data.
- **Impact:** Critical.
- **Likelihood placeholder:** Low probability, catastrophic impact if it occurs before mitigation.
- **Existing controls:** Comprehensive documented backup/restore architecture.
- **Missing controls:** Any actual restore-drill evidence.
- **Recommended treatment:** Schedule the first restore drill as early as Stage 2 (Controlled Pilot), not deferred to production.
- **Owner:** To be identified (Infrastructure owner).
- **Review trigger:** Stage 2 pilot readiness.
- **Residual-risk placeholder:** Pending.
- **Status:** Open — Priority 3.

### ARR-09 — Unresolved High-Priority Decisions Compound Into a Single Implementation-Start Bottleneck

- **Category:** Process / Governance
- **Scenario:** GAP-01 (schema), GAP-02 (policy sources), GAP-04 (deployment topology), GAP-07 (retention), and GAP-12 (participant identity) are all Priority 0/1 and interdependent — resolving them sequentially rather than in parallel could materially delay Phase 1 start.
- **Cause:** Twelve phases of architecture naturally accumulated a long open-decision tail; no phase was tasked with actively resolving decisions, only recording them.
- **Affected capability:** Phase 1 start date.
- **Impact:** High — a purely sequential resolution path could delay implementation start by months.
- **Likelihood placeholder:** Medium.
- **Existing controls:** This review's own priority-sequencing (Section 40, main document; [remediation-roadmap-and-priority-sequencing.md](remediation-roadmap-and-priority-sequencing.md)).
- **Missing controls:** An active decision-resolution sprint or working session bringing multiple stakeholders together in parallel, rather than resolving decisions one at a time as they happen to surface.
- **Recommended treatment:** Convene a single, focused Phase 0→1 transition working session addressing all Priority 0/1 items together, per [remediation-roadmap-and-priority-sequencing.md](remediation-roadmap-and-priority-sequencing.md).
- **Owner:** To be identified (Project sponsor).
- **Review trigger:** Immediately, before Phase 0.14 begins.
- **Residual-risk placeholder:** Pending.
- **Status:** Open, new finding.

### ARR-10 — Minor-Athlete Data Exposure Through an Unreviewed Implementation Shortcut

- **Category:** Privacy
- **Scenario:** A developer under Phase 1 time pressure takes a shortcut that exposes minor-athlete data beyond its approved classification (e.g., logging full participant records for debugging).
- **Cause:** No implementation exists yet to test against; the architecture's privacy controls are thorough but unenforced.
- **Affected capability:** Every context touching participant/athlete data.
- **Impact:** Critical — this is PMMS's most protectively-designed data category across all 12 phases.
- **Likelihood placeholder:** Medium during early, unreviewed implementation.
- **Existing controls:** Extensive documented minor-athlete governance (Phase 0.6, restated through every later phase).
- **Missing controls:** Implementation-time enforcement (e.g., automated PII-in-logs scanning), not yet built.
- **Recommended treatment:** Include a dedicated minor-athlete-data-exposure fitness function (per [architecture-fitness-functions-and-validation-gates.md](architecture-fitness-functions-and-validation-gates.md)) in Phase 1's CI pipeline from the start.
- **Owner:** To be identified (Privacy reviewer).
- **Review trigger:** First implementation of any participant-data-touching module.
- **Residual-risk placeholder:** Pending.
- **Status:** Open, new finding.

## Summary of Highest-Priority Architecture Risks

| Risk | Why It Is Highest Priority |
|---|---|
| **ARR-09** | Names the compounding-bottleneck risk this entire review's remediation roadmap exists to prevent |
| **ARR-04** | Directly threatens the architecture's most mature guarantee (high-integrity data model) at the exact moment physical implementation begins |
| **ARR-07** | The platform's single most-repeated scaling rule, still entirely unimplemented and untested |
| **ARR-08** | An untested DR capability is architecturally equivalent to no DR capability at all |
