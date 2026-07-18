# PMMS Architecture Gap Register

**Status:** Draft Complete — Pending Formal Architecture Review and Stakeholder Sign-Off
**Related:** All 12 area-review documents in this directory; [architecture-risk-register.md](architecture-risk-register.md)

This register consolidates every material gap identified across the 12 area-review documents, using ID prefix `GAP-`. Each entry follows the required structure: Gap ID / Architecture area / Finding type / Description / Evidence / Severity / Impact / Affected capabilities / Recommended resolution / Required decision / Required stakeholder / Target phase / Implementation blocker / Pilot blocker / Production blocker / Status.

---

### GAP-01 — No Physical Database Schema Exists

- **Architecture area:** Data/Database
- **Finding type:** Missing requirement / Under-specified architecture
- **Description:** No phase has ever produced a physical table definition, column list, index, or migration — every phase from 0.6 onward correctly operates at the logical level only, but the physical-schema step itself has no owning phase.
- **Evidence:** [architecture-completeness-assessment.md, Section 3](architecture-completeness-assessment.md#3-the-physical-database-schema-gap-critical-finding); Phase 0.5 Section 78 and Phase 0.7 Section 43 self-acknowledgment.
- **Severity:** Critical
- **Impact:** Phase 0.14 cannot decompose data-layer work packages into estimable units without a physical schema.
- **Affected capabilities:** Every module implementation.
- **Recommended resolution:** Scope physical schema design as the first Phase 0.14 work package, not a further documentation phase.
- **Required decision:** Whether schema design precedes or is embedded within Phase 0.14.
- **Required stakeholder:** Lead architect, Data owner.
- **Target phase:** Phase 0.14 (first work package).
- **Implementation blocker:** Yes. **Pilot blocker:** Yes. **Production blocker:** Yes.
- **Status:** Open — Critical, Priority 0.

### GAP-02 — Zero Verified Policy or Sports-Rule Sources

- **Architecture area:** Security/Domain
- **Finding type:** Missing policy source / Missing sports-rule source
- **Description:** All 13 policy-source placeholders (POL-01–13) and every sport's rulebook remain unverified.
- **Evidence:** [policy-rulebook-and-source-validation-gap-register.md](policy-rulebook-and-source-validation-gap-register.md).
- **Severity:** Critical
- **Impact:** Blocks eligibility, protest, medal-tally, medical, retention, and every sport-specific module.
- **Affected capabilities:** Eligibility, Protest and Appeals, Medal Tally, Medical Operations, retention governance, all sport-specific competition logic.
- **Recommended resolution:** DepEd/sports-governing-body engagement to locate and verify sources, prioritized per PSG-03/04/05/14/15/16.
- **Required decision:** None architectural — this is an external validation dependency.
- **Required stakeholder:** DepEd Leadership, Sports-domain representative, Legal/Privacy reviewer.
- **Target phase:** Ongoing, pre-module implementation per affected module.
- **Implementation blocker:** Yes (per affected module). **Pilot blocker:** Yes. **Production blocker:** Yes.
- **Status:** Open — Critical, Priority 1.

### GAP-03 — No Pilot Has Occurred; Zero Stakeholder-Validated or Operationally-Validated Evidence Exists

- **Architecture area:** Cross-cutting
- **Finding type:** Missing requirement
- **Description:** Every capability across all 12 phases is at the Documented or Cross-Validated evidence level only.
- **Evidence:** This review's own evidence-level assessment across all 12 area reviews.
- **Severity:** Critical
- **Impact:** No numeric target (capacity, SLO, RPO/RTO, quota) can be set responsibly; no proto-persona, workflow, or AI capability can be trusted as solving the real problem.
- **Affected capabilities:** All.
- **Recommended resolution:** Prioritize the earliest possible controlled pilot after Phase 1 foundation implementation.
- **Required decision:** [QD-13](../04-quality/quality-open-decisions.md) (pilot meet selection and timing).
- **Required stakeholder:** Product owner, Project sponsor.
- **Target phase:** Post-Phase-1-foundation.
- **Implementation blocker:** No. **Pilot blocker:** N/A (this is the pilot itself). **Production blocker:** Yes.
- **Status:** Open — Critical, Priority 3.

### GAP-04 — Deployment Topology and Cloud Provider Unresolved (DV-01)

- **Architecture area:** DevOps
- **Finding type:** Missing decision
- **Description:** Blocks five further DevOps decisions directly (DV-09, DV-10, DV-13, DV-16, DV-21) and two Phase 0.12 decisions (ED-26 CDN, ED-34 DR topology).
- **Evidence:** [devops-observability-operations-and-recovery-review.md, Section 14](devops-observability-operations-and-recovery-review.md#14-recommendation).
- **Severity:** Critical
- **Impact:** No infrastructure decision can be finalized.
- **Affected capabilities:** Deployment, monitoring, DR, CDN.
- **Recommended resolution:** Executive decision on cloud-versus-on-premise and hosting ownership ([OD-20](../00-product/open-decisions.md)), informed by budget and DepEd IT governance (PSG-10).
- **Required decision:** DV-01.
- **Required stakeholder:** DepEd Leadership, Infrastructure owner.
- **Target phase:** Pre-Phase-1-deployment planning.
- **Implementation blocker:** No (does not block application-code work). **Pilot blocker:** Yes. **Production blocker:** Yes.
- **Status:** Open — Critical, Priority 1.

### GAP-05 — RPO/RTO Numeric Targets Unresolved Across Six Phases

- **Architecture area:** Data/DevOps/Enterprise
- **Finding type:** Missing decision
- **Description:** The longest-carried open decision in the entire architecture (RD-18 → PD-23 → SD-24 → DV-17 → ED-33).
- **Evidence:** [multitenancy-scalability-and-enterprise-readiness-review.md, Section 8](multitenancy-scalability-and-enterprise-readiness-review.md#8-disaster-recovery).
- **Severity:** High
- **Impact:** No backup frequency, DR topology, or contractual service level can be finalized.
- **Affected capabilities:** Backup, DR, business continuity.
- **Recommended resolution:** Set placeholder-to-real values only after Stage 2 pilot operational data exists.
- **Required decision:** ED-33 (and its full ancestor chain).
- **Required stakeholder:** Infrastructure owner, Security owner, DepEd Leadership.
- **Target phase:** Post-pilot.
- **Implementation blocker:** No. **Pilot blocker:** No (readiness is sufficient for pilot scale). **Production blocker:** Yes.
- **Status:** Open — High, Priority 4.

### GAP-06 — Outbox-Versus-`after_commit` Dispatch Unresolved

- **Architecture area:** Application/Workflow
- **Finding type:** Missing decision
- **Description:** Unresolved across three phases (RD-01 → PD-21 → WD-08), affecting every critical cross-context event's delivery-reliability guarantee.
- **Evidence:** [workflow-event-notification-and-automation-review.md, Section 13](workflow-event-notification-and-automation-review.md#13-risks).
- **Severity:** High
- **Impact:** `ResultCertified` → Medal Tally and `AccreditationRevoked` → offline sync reliability is unconfirmed.
- **Affected capabilities:** Official Results, Medal Tally, Accreditation.
- **Recommended resolution:** A short, targeted technical spike early in Phase 0.14 comparing `after_commit` dispatch against a formal outbox table under realistic load.
- **Required decision:** WD-08.
- **Required stakeholder:** Lead architect, Engineering lead.
- **Target phase:** Phase 0.14, before Official Results/Medal Tally implementation.
- **Implementation blocker:** Yes (for the affected modules specifically). **Pilot blocker:** Yes. **Production blocker:** Yes.
- **Status:** Open — High, Priority 2.

### GAP-07 — Retention Periods Unresolved (PD-04, Blocking)

- **Architecture area:** Data/Security
- **Finding type:** Missing decision
- **Description:** Eight data categories' retention periods remain unset, blocked on PSG-03 (records-management policy source).
- **Evidence:** [data-database-history-and-persistence-review.md, Section 12](data-database-history-and-persistence-review.md#12-retention).
- **Severity:** Critical
- **Impact:** No archival, disposal, or export-expiry rule can be finalized.
- **Affected capabilities:** All data categories.
- **Recommended resolution:** See PSG-03.
- **Required decision:** PD-04.
- **Required stakeholder:** Data owner, Privacy reviewer, DepEd Leadership.
- **Target phase:** Pre-Phase-1 data-layer implementation.
- **Implementation blocker:** Yes. **Pilot blocker:** Yes. **Production blocker:** Yes.
- **Status:** Open — Critical, Priority 1.

### GAP-08 — All 27 UX Proto-Personas Unvalidated

- **Architecture area:** UX
- **Finding type:** Missing requirement (validation evidence)
- **Description:** Every user-group persona in Phase 0.9 explicitly requires pilot validation, none has occurred.
- **Evidence:** [design-ux-accessibility-and-cross-platform-review.md, Section 11](design-ux-accessibility-and-cross-platform-review.md#11-user-research-gaps).
- **Severity:** High
- **Impact:** UX design decisions rest on unvalidated assumptions about user needs.
- **Affected capabilities:** All user-facing surfaces.
- **Recommended resolution:** Validate personas against real committee staff/officials during the first pilot.
- **Required decision:** None architectural.
- **Required stakeholder:** UX lead, Product owner.
- **Target phase:** During first pilot.
- **Implementation blocker:** No. **Pilot blocker:** No. **Production blocker:** Yes (for a Formally Accepted UX claim).
- **Status:** Open — High, Priority 3.

### GAP-09 — WCAG Conformance Target and Branding Palette Unset (DX-01, DX-02)

- **Architecture area:** UX
- **Finding type:** Missing decision
- **Description:** No accessibility conformance level or institutional branding palette is approved.
- **Evidence:** [design-ux-accessibility-and-cross-platform-review.md, Section 12](design-ux-accessibility-and-cross-platform-review.md#12-branding).
- **Severity:** High (DX-01) / Moderate (DX-02)
- **Impact:** Blocks every accessibility-related design-quality gate and every brand-carrying visual token.
- **Affected capabilities:** All UI components.
- **Recommended resolution:** DX-01 requires stakeholder decision pre-Phase-1; DX-02 may follow shortly after, non-blocking for functional implementation.
- **Required decision:** DX-01, DX-02.
- **Required stakeholder:** DepEd Leadership, UX lead, Accessibility specialist.
- **Target phase:** Pre-Phase-1 (DX-01), early Phase 1 (DX-02).
- **Implementation blocker:** Partial (DX-01 blocks accessibility-gate enforcement). **Pilot blocker:** Yes (DX-01). **Production blocker:** Yes.
- **Status:** Open — High/Moderate, Priority 1 (DX-01) / Priority 2 (DX-02).

### GAP-10 — No AI Provider Selected, Zero Evaluation Dataset Exists

- **Architecture area:** AI
- **Finding type:** Missing decision / Missing requirement
- **Description:** AX-02 (provider evaluation process) and AX-03 (quality-threshold methodology) remain open; no capability can be piloted without both.
- **Evidence:** [ai-governance-and-decision-support-review.md, Section 10](ai-governance-and-decision-support-review.md#10-evaluation).
- **Severity:** High
- **Impact:** No AI capability can move beyond Documented evidence level.
- **Affected capabilities:** All 13 candidate AI use cases.
- **Recommended resolution:** Defer entirely until Phase 1 foundation and first pilot are complete — AI is explicitly Priority 5.
- **Required decision:** AX-01, AX-02, AX-03.
- **Required stakeholder:** AI governance owner.
- **Target phase:** Post-pilot.
- **Implementation blocker:** No (AI is not required for Phase 1). **Pilot blocker:** No. **Production blocker:** N/A (deferred).
- **Status:** Open — High, Priority 5 (deferred by design).

### GAP-11 — Break-Glass and Support-Impersonation Necessity Undecided (AD-09, AD-10)

- **Architecture area:** Identity/Security
- **Finding type:** Missing decision
- **Description:** Neither capability is assumed as a default; necessity itself remains genuinely open.
- **Evidence:** [identity-access-scope-and-assignment-review.md, Section 9](identity-access-scope-and-assignment-review.md#9-privileged-access).
- **Severity:** Moderate
- **Impact:** Support-operations design cannot finalize its escalation model without this decision.
- **Affected capabilities:** Support operations, incident response.
- **Recommended resolution:** Decide based on Phase 1 support-model needs, not preemptively.
- **Required decision:** AD-09, AD-10.
- **Required stakeholder:** Security owner, Support/Operations lead.
- **Target phase:** Pre-production support-model finalization.
- **Implementation blocker:** No. **Pilot blocker:** No. **Production blocker:** Yes.
- **Status:** Open — Moderate, Priority 4.

### GAP-12 — Participant Identity Modeling Unresolved (DD-01)

- **Architecture area:** Domain
- **Finding type:** Missing decision
- **Description:** Whether Participant Registry is unified or split by role (Athlete/Coach/Official) remains open, affecting five bounded contexts simultaneously.
- **Evidence:** [domain-bounded-context-and-ownership-review.md, Section 10](domain-bounded-context-and-ownership-review.md#10-aggregate-risks).
- **Severity:** High
- **Impact:** The single highest-leverage unresolved domain-modeling decision for Phase 1 scoping.
- **Affected capabilities:** Participant Registry, Athlete Registration, Technical Officials, Accreditation, Medical Operations.
- **Recommended resolution:** Resolve before physical schema design (GAP-01) begins, since it directly shapes the core identity table structure.
- **Required decision:** DD-01.
- **Required stakeholder:** Lead architect, Domain reviewer.
- **Target phase:** Immediately before Phase 0.14's schema work package.
- **Implementation blocker:** Yes. **Pilot blocker:** Yes. **Production blocker:** Yes.
- **Status:** Open — High, Priority 0 (must precede GAP-01).

### GAP-13 — Reviewer and Document-Owner Roles Unassigned Across All 12 Phases

- **Architecture area:** Cross-cutting / Governance
- **Finding type:** Missing owner
- **Description:** Every phase's document-owner and reviewer-role fields remain "To be identified," consistently.
- **Evidence:** [architecture-completeness-assessment.md, Section 5](architecture-completeness-assessment.md#5-missing-ownership).
- **Severity:** High
- **Impact:** Formal architecture sign-off (per [phase-0-final-architecture-signoff.md](phase-0-final-architecture-signoff.md)) cannot occur without named reviewers.
- **Affected capabilities:** Sign-off process itself.
- **Recommended resolution:** DepEd Leadership names the candidate reviewer roles from [architecture-review-methodology-and-evidence-model.md, Section 6](architecture-review-methodology-and-evidence-model.md#6-reviewer-roles-candidate-not-assigned).
- **Required decision:** None architectural — an organizational staffing decision.
- **Required stakeholder:** DepEd Leadership, Project sponsor.
- **Target phase:** Immediately, pre-formal-signoff.
- **Implementation blocker:** No. **Pilot blocker:** No. **Production blocker:** Yes (blocks Formally Accepted evidence level entirely).
- **Status:** Open — High, Priority 0.

### GAP-14 — WF-24/WF-25 Not Back-Ported to the Phase 0.2 Workflow Catalog

- **Architecture area:** Workflow/Documentation
- **Finding type:** Documentation gap
- **Description:** Finance and ICT workflows introduced in Phase 0.11 continue the numbering but were never added to the original Phase 0.2 catalog.
- **Evidence:** [workflow-event-notification-and-automation-review.md, Section 12](workflow-event-notification-and-automation-review.md#12-overly-generic-or-under-specified-workflows).
- **Severity:** Low
- **Impact:** Minor catalog-consistency issue, no functional impact.
- **Affected capabilities:** None functionally; documentation navigability only.
- **Recommended resolution:** A small documentation update to `workflow-and-command-catalog.md` adding WF-24/WF-25.
- **Required decision:** WD-29.
- **Required stakeholder:** Domain reviewer.
- **Target phase:** Next Phase 0.2 revision opportunity.
- **Implementation blocker:** No. **Pilot blocker:** No. **Production blocker:** No.
- **Status:** Open — Low, Priority 5.

### GAP-15 — Documentation Inconsistencies: Role-Category Count and Permission-Count Approximation

- **Architecture area:** Identity/Documentation
- **Finding type:** Documentation gap
- **Description:** Restated from [architecture-consistency-and-contradiction-analysis.md, Section 9](architecture-consistency-and-contradiction-analysis.md#9-documentation-inconsistencies-found-distinct-from-architecture-contradictions).
- **Evidence:** Same.
- **Severity:** Low
- **Impact:** Cosmetic only — the underlying role/permission catalogs are substantively correct.
- **Affected capabilities:** None.
- **Recommended resolution:** Correct the summary figures in `phase-0.3-access-and-assignment-architecture.md` and `permission-catalog.md`.
- **Required decision:** None.
- **Required stakeholder:** Domain reviewer.
- **Target phase:** Opportunistic, any future Phase 0.3 documentation touch.
- **Implementation blocker:** No. **Pilot blocker:** No. **Production blocker:** No.
- **Status:** Open — Low, Priority 5.

### GAP-16 — No CI/CD Pipeline Exists

- **Architecture area:** DevOps
- **Finding type:** Missing requirement (expected for Phase 0)
- **Description:** No `.github/workflows` directory exists; CI platform (DV-02) unselected.
- **Evidence:** Repository inspection confirmed during this Phase 0.13 review.
- **Severity:** Low (expected state, not a defect)
- **Impact:** Quality gates defined in Phase 0.7 cannot be enforced automatically until CI exists.
- **Affected capabilities:** All, once implementation begins.
- **Recommended resolution:** CI pipeline is a Phase 1 foundation work package, not a Phase 0 deliverable.
- **Required decision:** DV-02.
- **Required stakeholder:** DevOps/Operations lead.
- **Target phase:** Phase 0.14, foundation work.
- **Implementation blocker:** No (does not block writing code, blocks automated gate enforcement). **Pilot blocker:** Yes. **Production blocker:** Yes.
- **Status:** Open — Low severity as a Phase 0 finding, Priority 1 as a Phase 1 foundation item.

## Summary Table

| Gap | Severity | Priority |
|---|---|---|
| GAP-01 (physical schema) | Critical | P0 |
| GAP-12 (participant identity, DD-01) | High | P0 |
| GAP-13 (reviewer roles unassigned) | High | P0 |
| GAP-02 (policy/sports-rule sources) | Critical | P1 |
| GAP-04 (deployment topology) | Critical | P1 |
| GAP-07 (retention periods) | Critical | P1 |
| GAP-09 (DX-01 WCAG target) | High | P1 |
| GAP-06 (outbox pattern) | High | P2 |
| GAP-09 (DX-02 branding) | Moderate | P2 |
| GAP-08 (proto-personas) | High | P3 |
| GAP-03 (no pilot evidence) | Critical | P3 |
| GAP-11 (break-glass/impersonation) | Moderate | P4 |
| GAP-05 (RPO/RTO) | High | P4 |
| GAP-10 (AI provider/evaluation) | High | P5 (deferred by design) |
| GAP-14 (WF catalog back-port) | Low | P5 |
| GAP-15 (documentation inconsistencies) | Low | P5 |
